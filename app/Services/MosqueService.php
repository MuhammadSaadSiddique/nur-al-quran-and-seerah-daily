<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\MosqueContribution;
use App\Models\SalahFavorite;
use App\Models\User;
use App\Models\UserDailyEngagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Community mosque directory: nearest-mosque search, crowdsourced jama'ah
 * times, per-salah favourite mosques, and contribution credit.
 *
 * Shared by the mobile API (public/api/v1/mobile.php) and the admin panel.
 */
class MosqueService
{
    public const DEFAULT_RADIUS_KM = 10.0;
    public const MAX_RADIUS_KM = 50.0;
    public const MAX_RESULTS = 50;

    /** Two entries this close with a similar name are treated as the same mosque. */
    public const DUPLICATE_RADIUS_M = 100.0;
    /** Two entries this close are the same building whatever they are called. */
    public const SAME_SPOT_RADIUS_M = 20.0;

    public const DAILY_ADD_LIMIT = 10;
    public const DAILY_UPDATE_LIMIT = 30;
    public const CONFIRM_COOLDOWN_DAYS = 7;
    public const REPORT_COOLDOWN_DAYS = 30;
    /** Unverified mosques are hidden for admin review after this many distinct reports. */
    public const AUTO_HIDE_REPORTS = 5;
    /** Times older than this are flagged so worshippers know to double-check. */
    public const STALE_AFTER_DAYS = 45;

    public const REPORT_REASONS = ['closed', 'duplicate', 'wrong_location', 'wrong_times', 'wrong_sect', 'inappropriate', 'other'];

    /** Columns holding a jama'ah clock time ("HH:MM"). */
    private const CLOCK_COLUMNS = ['fajr_time', 'dhuhr_time', 'asr_time', 'maghrib_time', 'isha_time', 'jumuah_time', 'jumuah_time_2'];

    private const TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';
    private const EARTH_RADIUS_KM = 6371.0088;

    public function __construct(private DailyEngagementService $engagement)
    {
    }

    /**
     * Active mosques around a point, nearest first. With a search term the
     * radius is ignored so a worshipper can find a mosque near work or family.
     *
     * @param  array<int, string>  $filters  any of Mosque::FILTERS
     * @return array<int, array<string, mixed>>
     */
    public function nearby(
        ?float $lat,
        ?float $lng,
        float $radiusKm = self::DEFAULT_RADIUS_KM,
        int $limit = 30,
        ?string $search = null,
        array $filters = [],
        ?string $sect = null
    ): array {
        $search = $this->cleanSearch($search);
        $filters = array_values(array_intersect(Mosque::FILTERS, array_map(fn ($f) => strtolower(trim((string) $f)), $filters)));
        $sect = in_array($sect, Mosque::SECTS, true) ? $sect : null;
        $hasPoint = $lat !== null && $lng !== null;

        if ($hasPoint) {
            $this->assertCoordinates($lat, $lng);
        } elseif ($search === null) {
            throw ValidationException::withMessages([
                'latitude' => 'Share your location or type a mosque name or area to search.',
            ]);
        }

        $radiusKm = max(0.5, min(self::MAX_RADIUS_KM, $radiusKm));
        $limit = max(1, min(self::MAX_RESULTS, $limit));

        $query = $this->baseQuery();

        if ($search !== null) {
            $like = '%' . $search . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('locality', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhere('city', 'like', $like);
            });
        } else {
            $this->applyBoundingBox($query, $lat, $lng, $radiusKm);
        }

        $results = $query->limit(500)->get()
            ->map(function (Mosque $mosque) use ($lat, $lng, $hasPoint) {
                $distance = $hasPoint ? $this->distanceKm($lat, $lng, $mosque->latitude, $mosque->longitude) : null;

                return ['mosque' => $mosque, 'distance' => $distance];
            })
            ->filter(fn (array $row) => $search !== null || $row['distance'] <= $radiusKm)
            ->filter(fn (array $row) => $this->matchesFilters($row['mosque'], $filters, $sect))
            ->sortBy(fn (array $row) => $row['distance'] ?? strtolower($row['mosque']->name))
            ->take($limit)
            ->values();

        return $results->map(fn (array $row) => $this->present($row['mosque'], $row['distance']))->all();
    }

    public function findActive(int $id): ?Mosque
    {
        if ($id <= 0) {
            return null;
        }

        return $this->baseQuery()->whereKey($id)->first();
    }

    /**
     * Fresh data for a set of mosques (used to refresh a guest's saved favourites).
     *
     * @param  array<int, int|string>  $ids
     * @return array<int, array<string, mixed>>
     */
    public function byIds(array $ids, ?float $lat = null, ?float $lng = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $hasPoint = $lat !== null && $lng !== null;

        return $this->baseQuery()
            ->whereIn('id', array_slice($ids, 0, 20))
            ->get()
            ->map(fn (Mosque $m) => $this->present(
                $m,
                $hasPoint ? $this->distanceKm($lat, $lng, $m->latitude, $m->longitude) : null
            ))
            ->values()
            ->all();
    }

    /**
     * Add a mosque. If it already exists nearby, the existing entry is returned
     * instead so the worshipper can update its times rather than duplicate it.
     *
     * @return array{mosque: array<string, mixed>, duplicate: bool}
     */
    public function create(User $user, array $input): array
    {
        $input = $this->normalizeDetailsInput($this->normalizeTimesInput($input));

        $data = Validator::make($input, array_merge([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:500'],
        ], $this->timeRules(), $this->detailRules()), $this->timeMessages())->validate();

        $lat = round((float) $data['latitude'], 7);
        $lng = round((float) $data['longitude'], 7);
        $name = trim(preg_replace('/\s+/u', ' ', $data['name']));

        $existing = $this->findDuplicate($name, $lat, $lng);
        if ($existing) {
            return [
                'mosque' => $this->present($existing, $this->distanceKm($lat, $lng, $existing->latitude, $existing->longitude)),
                'duplicate' => true,
            ];
        }

        $addedToday = MosqueContribution::where('user_id', $user->id)
            ->where('type', MosqueContribution::TYPE_ADDED)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($addedToday >= self::DAILY_ADD_LIMIT) {
            throw ValidationException::withMessages([
                'name' => 'You have added ' . self::DAILY_ADD_LIMIT . ' mosques today. JazakAllah khair. Please continue tomorrow.',
            ]);
        }

        $times = $this->extractTimes($data);

        $mosque = DB::transaction(function () use ($user, $data, $lat, $lng, $name, $times) {
            $hasTimes = $this->timesArePresent($times);

            $mosque = Mosque::create(array_merge([
                'name' => $name,
                'address' => $this->nullableTrim($data['address'] ?? null),
                'locality' => $this->nullableTrim($data['locality'] ?? null),
                'city' => $this->nullableTrim($data['city'] ?? null),
                'country' => $this->nullableTrim($data['country'] ?? null),
                'latitude' => $lat,
                'longitude' => $lng,
                // Whoever adds the mosque sets its sect; later changes go through review.
                'sect' => $data['sect'] ?? null,
                'status' => Mosque::STATUS_ACTIVE,
                'is_verified' => false,
                'created_by' => $user->id,
                'times_updated_at' => $hasTimes ? now() : null,
                'times_updated_by' => $hasTimes ? $user->id : null,
            ], $times, $this->extractDetails($data, null)));

            MosqueContribution::create([
                'mosque_id' => $mosque->id,
                'user_id' => $user->id,
                'type' => MosqueContribution::TYPE_ADDED,
                'payload' => ['times' => $times, 'sect' => $mosque->sect],
                'note' => $this->nullableTrim($data['note'] ?? null),
            ]);

            return $mosque;
        });

        $this->recordContribution($user);

        return [
            'mosque' => $this->present($this->findActive($mosque->id) ?? $mosque, 0.0),
            'duplicate' => false,
        ];
    }

    /**
     * Replace a mosque's jama'ah times. Submitting unchanged times counts as a
     * confirmation instead of a new version.
     *
     * @return array{mosque: array<string, mixed>, changed: bool}
     */
    public function updateTimes(User $user, Mosque $mosque, array $input): array
    {
        $this->assertActive($mosque);
        $input = $this->normalizeTimesInput($input);

        $data = Validator::make($input, array_merge([
            'note' => ['nullable', 'string', 'max:500'],
        ], $this->timeRules()), $this->timeMessages())->validate();

        $times = $this->extractTimes($data);
        if (!$this->timesArePresent($times)) {
            throw ValidationException::withMessages([
                'fajr_time' => 'Add at least one jama\'ah time.',
            ]);
        }

        $before = $this->extractTimes($mosque->only(array_keys($times)));
        if ($before === $times) {
            $this->confirmTimes($user, $mosque);

            return ['mosque' => $this->present($this->findActive($mosque->id) ?? $mosque), 'changed' => false];
        }

        $updatesToday = MosqueContribution::where('user_id', $user->id)
            ->where('type', MosqueContribution::TYPE_TIMES_UPDATED)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($updatesToday >= self::DAILY_UPDATE_LIMIT) {
            throw ValidationException::withMessages([
                'fajr_time' => 'You have reached today\'s limit for time updates. Please continue tomorrow.',
            ]);
        }

        DB::transaction(function () use ($user, $mosque, $times, $before, $data) {
            $mosque->fill($times);
            $mosque->times_updated_at = now();
            $mosque->times_updated_by = $user->id;
            // New times have not been checked by anyone else yet.
            $mosque->confirmations_count = 0;
            $mosque->times_confirmed_at = null;
            $mosque->save();

            MosqueContribution::create([
                'mosque_id' => $mosque->id,
                'user_id' => $user->id,
                'type' => MosqueContribution::TYPE_TIMES_UPDATED,
                'payload' => ['before' => $before, 'after' => $times],
                'note' => $this->nullableTrim($data['note'] ?? null),
            ]);
        });

        $this->recordContribution($user);

        return ['mosque' => $this->present($this->findActive($mosque->id) ?? $mosque), 'changed' => true];
    }

    /**
     * "I prayed here, these times are right." Counted once per worshipper per
     * week and never for the person who just entered the times.
     *
     * @return array{mosque: array<string, mixed>, counted: bool}
     */
    public function confirmTimes(User $user, Mosque $mosque): array
    {
        $this->assertActive($mosque);

        if (!$mosque->hasAnyTime()) {
            throw ValidationException::withMessages([
                'mosque_id' => 'This mosque has no times to confirm yet. Please add them instead.',
            ]);
        }

        $cooldownStart = now()->subDays(self::CONFIRM_COOLDOWN_DAYS);
        $since = $mosque->times_updated_at && $mosque->times_updated_at->greaterThan($cooldownStart)
            ? $mosque->times_updated_at
            : $cooldownStart;

        $alreadyConfirmed = MosqueContribution::where('mosque_id', $mosque->id)
            ->where('user_id', $user->id)
            ->where('type', MosqueContribution::TYPE_CONFIRMED)
            ->where('created_at', '>=', $since)
            ->exists();

        $isOwnFreshUpdate = (int) $mosque->times_updated_by === (int) $user->id
            && $mosque->times_updated_at
            && $mosque->times_updated_at->greaterThan($cooldownStart);

        if ($alreadyConfirmed || $isOwnFreshUpdate) {
            return ['mosque' => $this->present($mosque), 'counted' => false];
        }

        DB::transaction(function () use ($user, $mosque) {
            $mosque->increment('confirmations_count', 1, ['times_confirmed_at' => now()]);

            MosqueContribution::create([
                'mosque_id' => $mosque->id,
                'user_id' => $user->id,
                'type' => MosqueContribution::TYPE_CONFIRMED,
                'payload' => ['times' => $this->extractTimes($mosque->only($this->timeColumns()))],
            ]);
        });

        return ['mosque' => $this->present($this->findActive($mosque->id) ?? $mosque->fresh()), 'counted' => true];
    }

    /**
     * Facilities, place type, Asr timing, khutbah languages and Ramadan / Eid
     * details. Only fields present in the input change. A sect change from
     * anyone but an admin waits in the review queue instead of going live.
     *
     * @return array{mosque: array<string, mixed>, changed: bool, sect_pending: bool}
     */
    public function updateDetails(User $user, Mosque $mosque, array $input): array
    {
        $this->assertActive($mosque);
        $input = $this->normalizeDetailsInput($input);

        $data = Validator::make($input, array_merge([
            'note' => ['nullable', 'string', 'max:500'],
        ], $this->detailRules()), $this->detailMessages())->validate();

        $updatesToday = MosqueContribution::where('user_id', $user->id)
            ->whereIn('type', [MosqueContribution::TYPE_DETAILS_UPDATED, MosqueContribution::TYPE_SECT_PROPOSED])
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        if ($updatesToday >= self::DAILY_UPDATE_LIMIT) {
            throw ValidationException::withMessages([
                'facilities' => 'You have reached today\'s limit for mosque updates. Please continue tomorrow.',
            ]);
        }

        $before = $this->detailSnapshot($mosque);
        $mosque->fill($this->extractDetails($data, $mosque));
        $after = $this->detailSnapshot($mosque);
        $detailsChanged = $before !== $after;

        $proposedSect = array_key_exists('sect', $data) ? ($data['sect'] ?? null) : $mosque->sect;
        $sectDiffers = $proposedSect !== $mosque->sect;
        $applySectNow = $sectDiffers && (bool) $user->is_admin;
        $sectPending = $sectDiffers && !$applySectNow;
        $previousSect = $mosque->sect;

        DB::transaction(function () use ($user, $mosque, $data, $before, $after, $detailsChanged, $proposedSect, $applySectNow, $sectPending, $previousSect) {
            if ($applySectNow) {
                $mosque->sect = $proposedSect;
                $mosque->pending_sect = null;
                $mosque->pending_sect_by = null;
                $mosque->pending_sect_at = null;
            }

            if ($sectPending) {
                $mosque->pending_sect = $proposedSect ?? Mosque::SECT_NONE;
                $mosque->pending_sect_by = $user->id;
                $mosque->pending_sect_at = now();

                MosqueContribution::create([
                    'mosque_id' => $mosque->id,
                    'user_id' => $user->id,
                    'type' => MosqueContribution::TYPE_SECT_PROPOSED,
                    'payload' => ['from' => $previousSect, 'to' => $proposedSect],
                    'note' => $this->nullableTrim($data['note'] ?? null),
                ]);
            }

            if ($detailsChanged || $applySectNow || $sectPending) {
                $mosque->save();
            }

            if ($detailsChanged || $applySectNow) {
                MosqueContribution::create([
                    'mosque_id' => $mosque->id,
                    'user_id' => $user->id,
                    'type' => MosqueContribution::TYPE_DETAILS_UPDATED,
                    'payload' => [
                        'before' => $before + ['sect' => $previousSect],
                        'after' => $after + ['sect' => $mosque->sect],
                    ],
                    'note' => $this->nullableTrim($data['note'] ?? null),
                ]);
            }
        });

        if ($detailsChanged) {
            $this->recordContribution($user);
        }

        return [
            'mosque' => $this->present($this->findActive($mosque->id) ?? $mosque),
            'changed' => $detailsChanged || $applySectNow,
            'sect_pending' => $sectPending,
        ];
    }

    /**
     * Full edit from the admin panel: identity, location, times, details,
     * status and verification in one go. Uses the same validation and
     * normalisation as the app so data stays consistent. Setting a different
     * sect also settles any pending proposal; leaving it as is keeps the
     * proposal for review.
     *
     * @return array{changed: bool, times_changed: bool, changed_fields: array<int, string>}
     */
    public function adminUpdate(User $admin, Mosque $mosque, array $input): array
    {
        $input = $this->normalizeDetailsInput($this->normalizeTimesInput($input));
        if (array_key_exists('is_verified', $input)) {
            $input['is_verified'] = (bool) $this->triState($input['is_verified']);
        }

        $data = Validator::make($input, array_merge([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'locality' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'status' => ['required', Rule::in([Mosque::STATUS_ACTIVE, Mosque::STATUS_HIDDEN])],
            'is_verified' => ['nullable', 'boolean'],
        ], $this->timeRules(), $this->detailRules()), array_merge($this->timeMessages(), $this->detailMessages()))->validate();

        $before = $this->adminSnapshot($mosque);
        $timesBefore = $this->extractTimes($mosque->only($this->timeColumns()));
        $times = $this->extractTimes($data);

        $mosque->fill([
            'name' => trim(preg_replace('/\s+/u', ' ', $data['name'])),
            'address' => $this->nullableTrim($data['address'] ?? null),
            'locality' => $this->nullableTrim($data['locality'] ?? null),
            'city' => $this->nullableTrim($data['city'] ?? null),
            'country' => $this->nullableTrim($data['country'] ?? null),
            'latitude' => round((float) $data['latitude'], 7),
            'longitude' => round((float) $data['longitude'], 7),
            'status' => $data['status'],
            'is_verified' => (bool) ($data['is_verified'] ?? false),
        ]);
        $mosque->fill($times);
        $mosque->fill($this->extractDetails($data, $mosque));

        if (array_key_exists('sect', $data) && ($data['sect'] ?? null) !== $mosque->sect) {
            $mosque->sect = $data['sect'] ?? null;
            $mosque->pending_sect = null;
            $mosque->pending_sect_by = null;
            $mosque->pending_sect_at = null;
        }

        $timesChanged = $timesBefore !== $times;
        if ($timesChanged) {
            $mosque->times_updated_at = now();
            $mosque->times_updated_by = $admin->id;
            $mosque->confirmations_count = 0;
            $mosque->times_confirmed_at = null;
        }

        $after = $this->adminSnapshot($mosque);
        $changedFields = array_keys(array_filter($after, fn ($value, $key) => $before[$key] !== $value, ARRAY_FILTER_USE_BOTH));
        if ($changedFields === []) {
            return ['changed' => false, 'times_changed' => false, 'changed_fields' => []];
        }

        DB::transaction(function () use ($admin, $mosque, $before, $after, $changedFields) {
            $mosque->save();

            // Only what actually changed, so the history stays readable.
            $keys = array_flip($changedFields);
            MosqueContribution::create([
                'mosque_id' => $mosque->id,
                'user_id' => $admin->id,
                'type' => MosqueContribution::TYPE_DETAILS_UPDATED,
                'payload' => [
                    'by_admin' => true,
                    'before' => array_intersect_key($before, $keys),
                    'after' => array_intersect_key($after, $keys),
                ],
                'note' => 'Edited in admin panel',
            ]);
        });

        return ['changed' => true, 'times_changed' => $timesChanged, 'changed_fields' => $changedFields];
    }

    /** Everything the admin form can change, in comparable form. */
    private function adminSnapshot(Mosque $mosque): array
    {
        return array_merge([
            'name' => $mosque->name,
            'address' => $mosque->address,
            'locality' => $mosque->locality,
            'city' => $mosque->city,
            'country' => $mosque->country,
            'latitude' => round((float) $mosque->latitude, 7),
            'longitude' => round((float) $mosque->longitude, 7),
            'sect' => $mosque->sect,
            'status' => $mosque->status,
            'is_verified' => (bool) $mosque->is_verified,
        ], $this->extractTimes($mosque->only($this->timeColumns())), $this->detailSnapshot($mosque));
    }

    /** Admin decision on a proposed sect change. */
    public function resolveSectProposal(Mosque $mosque, bool $approve): void
    {
        if ($mosque->pending_sect === null) {
            return;
        }

        if ($approve) {
            $mosque->sect = $mosque->pending_sect === Mosque::SECT_NONE ? null : $mosque->pending_sect;
        }
        $mosque->pending_sect = null;
        $mosque->pending_sect_by = null;
        $mosque->pending_sect_at = null;
        $mosque->save();
    }

    /** @return array{counted: bool, hidden: bool} */
    public function report(User $user, Mosque $mosque, array $input): array
    {
        $data = Validator::make($input, [
            'reason' => ['required', 'string', 'in:' . implode(',', self::REPORT_REASONS)],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'reason.in' => 'Please choose a reason for the report.',
        ])->validate();

        $recent = MosqueContribution::where('mosque_id', $mosque->id)
            ->where('user_id', $user->id)
            ->where('type', MosqueContribution::TYPE_REPORTED)
            ->where('created_at', '>=', now()->subDays(self::REPORT_COOLDOWN_DAYS))
            ->exists();

        if ($recent) {
            return ['counted' => false, 'hidden' => $mosque->status === Mosque::STATUS_HIDDEN];
        }

        DB::transaction(function () use ($user, $mosque, $data) {
            MosqueContribution::create([
                'mosque_id' => $mosque->id,
                'user_id' => $user->id,
                'type' => MosqueContribution::TYPE_REPORTED,
                'payload' => ['reason' => $data['reason']],
                'note' => $this->nullableTrim($data['note'] ?? null),
            ]);

            $mosque->increment('reports_count');
            $mosque->refresh();

            if (!$mosque->is_verified && $mosque->reports_count >= self::AUTO_HIDE_REPORTS) {
                $mosque->status = Mosque::STATUS_HIDDEN;
                $mosque->save();
            }
        });

        return ['counted' => true, 'hidden' => $mosque->status === Mosque::STATUS_HIDDEN];
    }

    /**
     * The user's mosque for each salah, keyed by prayer. Hidden mosques drop out.
     *
     * @return array<string, array<string, mixed>>
     */
    public function favorites(User $user, ?float $lat = null, ?float $lng = null): array
    {
        $rows = SalahFavorite::where('user_id', $user->id)->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $hasPoint = $lat !== null && $lng !== null;
        $mosques = $this->baseQuery()->whereIn('id', $rows->pluck('mosque_id')->unique())->get()->keyBy('id');

        $result = [];
        foreach (Mosque::PRAYERS as $prayer) {
            $row = $rows->firstWhere('prayer', $prayer);
            $mosque = $row ? $mosques->get($row->mosque_id) : null;
            if ($mosque) {
                $result[$prayer] = $this->present(
                    $mosque,
                    $hasPoint ? $this->distanceKm($lat, $lng, $mosque->latitude, $mosque->longitude) : null
                );
            }
        }

        return $result;
    }

    /**
     * Point one salah at a mosque, or clear it with a null mosque id.
     *
     * @return array<string, array<string, mixed>>
     */
    public function setFavorite(User $user, string $prayer, ?int $mosqueId): array
    {
        $prayer = strtolower(trim($prayer));
        if (!in_array($prayer, Mosque::PRAYERS, true)) {
            throw ValidationException::withMessages(['prayer' => 'Unknown salah.']);
        }

        if (!$mosqueId) {
            SalahFavorite::where('user_id', $user->id)->where('prayer', $prayer)->delete();

            return $this->favorites($user);
        }

        $mosque = $this->findActive($mosqueId);
        if (!$mosque) {
            throw ValidationException::withMessages(['mosque_id' => 'This mosque is no longer listed.']);
        }

        SalahFavorite::updateOrCreate(
            ['user_id' => $user->id, 'prayer' => $prayer],
            ['mosque_id' => $mosque->id]
        );

        return $this->favorites($user);
    }

    /**
     * What a user has given to the directory, and how many worshippers now
     * rely on mosques they added or kept up to date.
     *
     * @return array<string, mixed>
     */
    public function contributionSummary(User $user): array
    {
        $counts = MosqueContribution::where('user_id', $user->id)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $maintainedIds = MosqueContribution::where('user_id', $user->id)
            ->whereIn('type', [MosqueContribution::TYPE_ADDED, MosqueContribution::TYPE_TIMES_UPDATED])
            ->distinct()
            ->pluck('mosque_id');

        $helpedIds = MosqueContribution::where('user_id', $user->id)
            ->whereIn('type', [
                MosqueContribution::TYPE_ADDED,
                MosqueContribution::TYPE_TIMES_UPDATED,
                MosqueContribution::TYPE_DETAILS_UPDATED,
                MosqueContribution::TYPE_CONFIRMED,
            ])
            ->distinct()
            ->pluck('mosque_id');

        $worshippers = $maintainedIds->isEmpty() ? 0 : SalahFavorite::whereIn('mosque_id', $maintainedIds)
            ->where('user_id', '!=', $user->id)
            ->distinct()
            ->count('user_id');

        $recent = MosqueContribution::with('mosque:id,name')
            ->where('user_id', $user->id)
            ->where('type', '!=', MosqueContribution::TYPE_REPORTED)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (MosqueContribution $c) => [
                'type' => $c->type,
                'mosque_id' => $c->mosque_id,
                'mosque_name' => $c->mosque?->name,
                'created_at' => $c->created_at?->toIso8601String(),
            ])
            ->all();

        return [
            'mosques_added' => (int) ($counts[MosqueContribution::TYPE_ADDED] ?? 0),
            'times_updated' => (int) ($counts[MosqueContribution::TYPE_TIMES_UPDATED] ?? 0),
            'confirmations' => (int) ($counts[MosqueContribution::TYPE_CONFIRMED] ?? 0),
            'mosques_helped' => $helpedIds->count(),
            'worshippers_benefiting' => (int) $worshippers,
            'recent' => $recent,
        ];
    }

    /** @return array<string, mixed> */
    public function present(Mosque $mosque, ?float $distanceKm = null): array
    {
        $updatedAt = $mosque->times_updated_at;
        $daysSince = $updatedAt ? (int) floor(abs($updatedAt->diffInDays(now()))) : null;

        return [
            'id' => (int) $mosque->id,
            'name' => $mosque->name,
            'address' => $mosque->address,
            'locality' => $mosque->locality,
            'city' => $mosque->city,
            'country' => $mosque->country,
            'latitude' => (float) $mosque->latitude,
            'longitude' => (float) $mosque->longitude,
            'place_type' => $mosque->place_type ?: 'masjid',
            'sect' => $mosque->sect,
            'sect_change_pending' => $mosque->pending_sect !== null,
            'asr_method' => $mosque->asr_method,
            'times' => $mosque->jamaahTimes(),
            'jumuah_second_time' => $mosque->jumuah_time_2,
            'maghrib_offset_minutes' => $mosque->maghrib_offset_minutes,
            'has_women_area' => $mosque->has_women_area,
            'facilities' => $mosque->facilityMap(),
            'khutbah_languages' => array_values($mosque->khutbah_languages ?? []),
            'taraweeh' => [
                'rakats' => $mosque->taraweeh_rakats,
                'khatm' => $mosque->taraweeh_khatm,
            ],
            'eid_times' => (object) ($mosque->eid_times ?? []),
            'is_verified' => (bool) $mosque->is_verified,
            'confirmations_count' => (int) $mosque->confirmations_count,
            'regulars_count' => (int) ($mosque->regulars_count ?? 0),
            'times_updated_at' => $updatedAt?->toIso8601String(),
            'times_confirmed_at' => $mosque->times_confirmed_at?->toIso8601String(),
            'days_since_update' => $daysSince,
            'is_stale' => $daysSince === null || $daysSince > self::STALE_AFTER_DAYS,
            'distance_km' => $distanceKm === null ? null : round($distanceKm, 2),
            // ODbL: OpenStreetMap-derived entries must be credited where shown.
            'source' => $mosque->source ?: Mosque::SOURCE_COMMUNITY,
            'attribution' => $mosque->usesOsmData() ? Mosque::OSM_ATTRIBUTION : null,
            'osm_url' => $mosque->osmUrl(),
        ];
    }

    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function baseQuery(): Builder
    {
        return Mosque::active()->withCount([
            'favorites as regulars_count' => fn (Builder $q) => $q->select(DB::raw('count(distinct user_id)')),
        ]);
    }

    private function applyBoundingBox(Builder $query, float $lat, float $lng, float $radiusKm): void
    {
        $dLat = $radiusKm / 111.32;
        $dLng = $radiusKm / (111.32 * max(0.01, cos(deg2rad($lat))));

        $query->whereBetween('latitude', [$lat - $dLat, $lat + $dLat]);

        $minLng = $lng - $dLng;
        $maxLng = $lng + $dLng;
        // Near the antimeridian the box wraps; rely on the exact distance filter instead.
        if ($minLng >= -180 && $maxLng <= 180) {
            $query->whereBetween('longitude', [$minLng, $maxLng]);
        }
    }

    private function findDuplicate(string $name, float $lat, float $lng): ?Mosque
    {
        return $this->findLikelySame($name, $lat, $lng);
    }

    /**
     * The listed mosque this one most likely is: anything within 20 m, or a
     * similar name within 100 m. Imports include hidden mosques so a hidden
     * duplicate is not created again.
     */
    public function findLikelySame(string $name, float $lat, float $lng, bool $includeHidden = false): ?Mosque
    {
        $query = $includeHidden ? Mosque::query() : $this->baseQuery();
        $this->applyBoundingBox($query, $lat, $lng, self::DUPLICATE_RADIUS_M / 1000);

        return $query->get()
            ->filter(fn (Mosque $m) => $this->isLikelySame($name, $lat, $lng, (string) $m->name, $m->latitude, $m->longitude))
            ->sortBy(fn (Mosque $m) => $this->distanceKm($lat, $lng, $m->latitude, $m->longitude))
            ->first();
    }

    /** Two entries are one mosque if within 20 m, or within 100 m with a similar name. */
    public function isLikelySame(string $name, float $lat, float $lng, string $otherName, float $otherLat, float $otherLng): bool
    {
        $meters = $this->distanceKm($lat, $lng, $otherLat, $otherLng) * 1000;
        if ($meters > self::DUPLICATE_RADIUS_M) {
            return false;
        }
        if ($meters <= self::SAME_SPOT_RADIUS_M) {
            return true;
        }

        $needle = $this->comparableName($name);
        $other = $this->comparableName($otherName);
        if ($needle === '' || $other === '') {
            return false;
        }
        // Containment only for real words; "a" is inside almost every name.
        $shortest = min(mb_strlen($needle), mb_strlen($other));
        if ($shortest >= 3 && (str_contains($needle, $other) || str_contains($other, $needle))) {
            return true;
        }
        similar_text($needle, $other, $percent);

        return $percent >= 70;
    }

    /** "Jamia Masjid Al-Noor" and "Al Noor Mosque" both become "noor". */
    private function comparableName(string $name): string
    {
        $name = mb_strtolower($name);
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
        $stop = ['masjid', 'masjide', 'masjid-e', 'mosque', 'jamia', 'jama', 'jame', 'jamiah', 'jamea', 'markaz', 'e', 'al', 'el', 'the', 'of', 'and'];
        $words = array_filter(explode(' ', $name), fn ($w) => $w !== '' && !in_array($w, $stop, true));

        return implode(' ', $words);
    }

    /** @return array<string, array<int, string>> */
    private function timeRules(): array
    {
        $rules = [];
        foreach ($this->timeColumns() as $column) {
            if ($column === 'maghrib_offset_minutes') {
                $rules[$column] = ['nullable', 'integer', 'between:0,90'];
            } else {
                $rules[$column] = ['nullable', 'string', 'regex:' . self::TIME_PATTERN];
            }
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function timeMessages(): array
    {
        $messages = ['maghrib_offset_minutes.between' => 'Maghrib gap must be between 0 and 90 minutes.'];
        $labels = [
            'fajr_time' => 'Fajr', 'dhuhr_time' => 'Dhuhr', 'asr_time' => 'Asr', 'maghrib_time' => 'Maghrib',
            'isha_time' => 'Isha', 'jumuah_time' => "Jumu'ah", 'jumuah_time_2' => "Second Jumu'ah",
        ];
        foreach (self::CLOCK_COLUMNS as $column) {
            $messages[$column . '.regex'] = $labels[$column] . ' time must look like 13:30.';
        }

        return $messages;
    }

    /** @return array<int, string> */
    private function timeColumns(): array
    {
        return array_merge(self::CLOCK_COLUMNS, ['maghrib_offset_minutes']);
    }

    /** Accepts "5:05" and "05:05"; blank strings mean "not known". */
    private function normalizeTimesInput(array $input): array
    {
        foreach (self::CLOCK_COLUMNS as $key) {
            if (array_key_exists($key, $input)) {
                $input[$key] = $this->normalizeClock($input[$key]);
            }
        }

        if (array_key_exists('maghrib_offset_minutes', $input)) {
            $offset = trim((string) ($input['maghrib_offset_minutes'] ?? ''));
            $input['maghrib_offset_minutes'] = $offset === '' ? null : $offset;
        }

        return $input;
    }

    /** "5:05" → "05:05", blank → null; anything else is left for validation to reject. */
    private function normalizeClock(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
        }

        return $value;
    }

    /** Yes / no / unknown from form values ("1", "0", "", "yes", true…). */
    private function triState(mixed $value): ?bool
    {
        if (is_bool($value) || $value === null) {
            return $value;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => null,
        };
    }

    /**
     * Mobile forms send flat strings: facilities[key]=1/0/"", languages and Eid
     * times as comma lists. Only keys present in the input are touched.
     */
    private function normalizeDetailsInput(array $input): array
    {
        foreach (['place_type', 'sect', 'asr_method'] as $key) {
            if (array_key_exists($key, $input)) {
                $value = strtolower(trim((string) ($input[$key] ?? '')));
                $input[$key] = ($value === '' || $value === Mosque::SECT_NONE) ? null : $value;
            }
        }

        foreach (['has_women_area', 'taraweeh_khatm'] as $key) {
            if (array_key_exists($key, $input)) {
                $input[$key] = $this->triState($input[$key]);
            }
        }

        foreach (['taraweeh_rakats', 'eid_year'] as $key) {
            if (array_key_exists($key, $input)) {
                $value = trim((string) ($input[$key] ?? ''));
                $input[$key] = $value === '' ? null : $value;
            }
        }

        if (array_key_exists('facilities', $input)) {
            $raw = $input['facilities'];
            if (is_string($raw)) {
                $raw = json_decode($raw, true);
            }
            $clean = [];
            foreach (is_array($raw) ? $raw : [] as $key => $value) {
                if (in_array($key, Mosque::FACILITIES, true)) {
                    $clean[$key] = $this->triState($value);
                }
            }
            $input['facilities'] = $clean;
        }

        if (array_key_exists('khutbah_languages', $input)) {
            $raw = $input['khutbah_languages'];
            $list = array_map(fn ($l) => strtolower(trim((string) $l)), is_array($raw) ? $raw : explode(',', (string) $raw));
            $input['khutbah_languages'] = array_values(array_intersect(Mosque::KHUTBAH_LANGUAGES, $list));
        }

        foreach (Mosque::EID_KINDS as $kind) {
            $key = "eid_{$kind}_times";
            if (array_key_exists($key, $input)) {
                $raw = $input[$key];
                $list = is_array($raw) ? $raw : explode(',', (string) $raw);
                $input[$key] = array_values(array_filter(array_map(fn ($t) => $this->normalizeClock($t), $list)));
            }
        }

        return $input;
    }

    /** @return array<string, mixed> */
    private function detailRules(): array
    {
        $rules = [
            'place_type' => ['nullable', Rule::in(Mosque::PLACE_TYPES)],
            'sect' => ['nullable', Rule::in(Mosque::SECTS)],
            'asr_method' => ['nullable', Rule::in(Mosque::ASR_METHODS)],
            'has_women_area' => ['nullable', 'boolean'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['nullable', 'boolean'],
            'khutbah_languages' => ['nullable', 'array', 'max:4'],
            'khutbah_languages.*' => ['string', Rule::in(Mosque::KHUTBAH_LANGUAGES)],
            'taraweeh_rakats' => ['nullable', 'integer', Rule::in(Mosque::TARAWEEH_RAKATS)],
            'taraweeh_khatm' => ['nullable', 'boolean'],
            'eid_year' => ['nullable', 'integer', 'between:2024,2100'],
        ];
        foreach (Mosque::EID_KINDS as $kind) {
            $rules["eid_{$kind}_times"] = ['nullable', 'array', 'max:4'];
            $rules["eid_{$kind}_times.*"] = ['string', 'regex:' . self::TIME_PATTERN];
        }

        return $rules;
    }

    /** @return array<string, string> */
    private function detailMessages(): array
    {
        return [
            'sect.in' => 'Please choose a school of thought from the list.',
            'khutbah_languages.max' => 'Choose up to 4 khutbah languages.',
            'taraweeh_rakats.in' => 'Taraweeh rakats must be 8 or 20.',
            'eid_fitr_times.*.regex' => 'Eid times must look like 07:30.',
            'eid_adha_times.*.regex' => 'Eid times must look like 07:30.',
            'eid_fitr_times.max' => 'Add up to 4 Eid jama\'ah times.',
            'eid_adha_times.max' => 'Add up to 4 Eid jama\'ah times.',
        ];
    }

    /** Column values for the detail fields present in validated input. */
    private function extractDetails(array $data, ?Mosque $existing): array
    {
        $out = [];

        if (array_key_exists('place_type', $data)) {
            $out['place_type'] = $data['place_type'] ?? 'masjid';
        } elseif (!$existing) {
            $out['place_type'] = 'masjid';
        }

        if (array_key_exists('asr_method', $data)) {
            $out['asr_method'] = $data['asr_method'];
        }

        if (array_key_exists('has_women_area', $data)) {
            $out['has_women_area'] = $data['has_women_area'] === null ? null : (bool) $data['has_women_area'];
        }

        if (array_key_exists('facilities', $data)) {
            $merged = $existing ? $existing->facilityMap() : array_fill_keys(Mosque::FACILITIES, null);
            foreach ($data['facilities'] ?? [] as $key => $value) {
                if (in_array($key, Mosque::FACILITIES, true)) {
                    $merged[$key] = $value === null ? null : (bool) $value;
                }
            }
            $known = array_filter($merged, fn ($v) => $v !== null);
            $out['facilities'] = $known === [] ? null : $known;
        }

        if (array_key_exists('khutbah_languages', $data)) {
            $out['khutbah_languages'] = ($data['khutbah_languages'] ?? []) ?: null;
        }

        if (array_key_exists('taraweeh_rakats', $data)) {
            $out['taraweeh_rakats'] = $data['taraweeh_rakats'] === null ? null : (int) $data['taraweeh_rakats'];
        }

        if (array_key_exists('taraweeh_khatm', $data)) {
            $out['taraweeh_khatm'] = $data['taraweeh_khatm'] === null ? null : (bool) $data['taraweeh_khatm'];
        }

        $touchesEid = array_key_exists('eid_fitr_times', $data) || array_key_exists('eid_adha_times', $data);
        if ($touchesEid) {
            $eid = is_array($existing?->eid_times) ? $existing->eid_times : [];
            ksort($eid);
            $year = (int) ($data['eid_year'] ?? now()->year);
            foreach (Mosque::EID_KINDS as $kind) {
                $key = "eid_{$kind}_times";
                if (!array_key_exists($key, $data)) {
                    continue;
                }
                $times = array_values(array_unique($data[$key] ?? []));
                sort($times);
                if ($times === []) {
                    unset($eid[$kind]);
                } else {
                    $eid[$kind] = ['year' => $year, 'times' => $times];
                }
            }
            ksort($eid);
            $out['eid_times'] = $eid === [] ? null : $eid;
        }

        return $out;
    }

    /** Comparable view of the detail fields, so "no real change" can be detected. */
    private function detailSnapshot(Mosque $mosque): array
    {
        $eid = is_array($mosque->eid_times) ? $mosque->eid_times : [];
        ksort($eid);

        return [
            'place_type' => $mosque->place_type ?: 'masjid',
            'asr_method' => $mosque->asr_method,
            'has_women_area' => $mosque->has_women_area,
            'facilities' => $mosque->facilityMap(),
            'khutbah_languages' => array_values($mosque->khutbah_languages ?? []),
            'taraweeh_rakats' => $mosque->taraweeh_rakats,
            'taraweeh_khatm' => $mosque->taraweeh_khatm,
            'eid_times' => $eid,
        ];
    }

    /** @param  array<int, string>  $filters */
    private function matchesFilters(Mosque $mosque, array $filters, ?string $sect): bool
    {
        if ($sect !== null && $mosque->sect !== $sect) {
            return false;
        }

        foreach ($filters as $filter) {
            $ok = match ($filter) {
                'wheelchair' => $mosque->hasFacility('wheelchair_entrance'),
                'chairs' => $mosque->hasFacility('prayer_chairs'),
                'women' => $mosque->has_women_area === true,
                'jumuah' => $mosque->hasJumuah(),
                'parking' => $mosque->hasFacility('car_parking') || $mosque->hasFacility('bike_parking'),
                default => true,
            };
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function extractTimes(array $data): array
    {
        $times = [];
        foreach (self::CLOCK_COLUMNS as $column) {
            $value = $data[$column] ?? null;
            $times[$column] = ($value === null || $value === '') ? null : (string) $value;
        }
        $offset = $data['maghrib_offset_minutes'] ?? null;
        $times['maghrib_offset_minutes'] = ($offset === null || $offset === '') ? null : (int) $offset;

        // A gap after sunset replaces a fixed Maghrib clock time.
        if ($times['maghrib_offset_minutes'] !== null) {
            $times['maghrib_time'] = null;
        }

        return $times;
    }

    private function timesArePresent(array $times): bool
    {
        return count(array_filter($times, fn ($v) => $v !== null)) > 0;
    }

    private function assertActive(Mosque $mosque): void
    {
        if ($mosque->status !== Mosque::STATUS_ACTIVE) {
            throw ValidationException::withMessages(['mosque_id' => 'This mosque is under review and cannot be changed right now.']);
        }
    }

    private function assertCoordinates(float $lat, float $lng): void
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw ValidationException::withMessages(['latitude' => 'Location coordinates are out of range.']);
        }
    }

    private function cleanSearch(?string $search): ?string
    {
        $search = trim(str_replace(['%', '_'], ' ', (string) $search));

        return mb_strlen($search) >= 2 ? mb_substr($search, 0, 80) : null;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function recordContribution(User $user): void
    {
        try {
            $this->engagement->record($user, UserDailyEngagement::ACTIVITY_CONTRIBUTE);
        } catch (\Throwable $e) {
            Log::warning('Mosque contribution engagement failed: ' . $e->getMessage());
        }
    }
}
