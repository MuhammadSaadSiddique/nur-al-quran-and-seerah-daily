<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mosque;
use App\Models\MosqueContribution;
use App\Services\MosqueService;
use App\Services\OsmMosqueImporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Moderation and editing for the community mosque directory. Mosques go live
 * as soon as a signed-in worshipper adds them; admins verify trusted entries,
 * deal with reports and correct details.
 */
class MosqueController extends Controller
{
    /** Quick queue tabs shown above the list. */
    public const QUEUES = ['all', 'sect_review', 'reported', 'hidden', 'unverified', 'verified'];

    public const SORTS = [
        'newest' => 'Newest first',
        'oldest' => 'Oldest first',
        'name' => 'Name A–Z',
        'reports' => 'Most reported',
        'regulars' => 'Most regulars',
        'stale' => 'Stalest times first',
        'updated' => 'Recently updated times',
    ];

    public const TIME_FILTERS = [
        'has' => 'Has jama\'ah times',
        'none' => 'No times yet',
        'stale' => 'Times older than ' . MosqueService::STALE_AFTER_DAYS . ' days or missing',
    ];

    public const ADDRESS_FILTERS = [
        'missing' => 'No address given',
        'no_city' => 'No city',
        'has' => 'Has an address',
    ];

    /** Actions that can be applied to many mosques at once. */
    public const BULK_ACTIONS = [
        'verify' => 'Verify',
        'unverify' => 'Remove verification',
        'hide' => 'Hide from app',
        'restore' => 'Restore (and clear reports)',
        'clear_reports' => 'Clear reports',
        'approve_sect' => 'Approve proposed school of thought',
        'reject_sect' => 'Reject proposed school of thought',
        'set_city' => 'Set city to…',
        'set_country' => 'Set country to…',
        'delete' => 'Delete permanently',
    ];

    /** Bulk actions that write a typed value into a column, with its length limit. */
    private const VALUE_ACTIONS = [
        'set_city' => ['column' => 'city', 'max' => 120, 'label' => 'City'],
        'set_country' => ['column' => 'country', 'max' => 80, 'label' => 'Country'],
    ];

    /** Filter value meaning "this field is empty". */
    public const NONE = '__none__';

    /** Upper bound for one bulk action, so a stray "select all" can't touch the whole directory. */
    public const MAX_BULK = 500;

    public function index(Request $request)
    {
        $filters = $this->readFilters($request);
        $sort = array_key_exists($request->input('sort'), self::SORTS) ? $request->input('sort') : null;

        $counts = [
            'all' => Mosque::count(),
            'sect_review' => Mosque::whereNotNull('pending_sect')->count(),
            'reported' => Mosque::where('reports_count', '>', 0)->count(),
            'hidden' => Mosque::where('status', Mosque::STATUS_HIDDEN)->count(),
            'unverified' => Mosque::where('is_verified', false)->where('status', Mosque::STATUS_ACTIVE)->count(),
            'verified' => Mosque::where('is_verified', true)->count(),
        ];

        $queue = in_array($request->input('filter'), self::QUEUES, true) ? $request->input('filter') : 'all';
        // Opening the page cold jumps to whatever needs attention; any explicit filter wins.
        if ($request->query() === []) {
            $queue = match (true) {
                $counts['sect_review'] > 0 => 'sect_review',
                $counts['reported'] > 0 => 'reported',
                default => 'all',
            };
        }

        $query = Mosque::with(['creator:id,name,email', 'timesUpdater:id,name', 'pendingSectBy:id,name'])
            ->withCount([
                'favorites as regulars_count' => fn ($q) => $q->selectRaw('count(distinct user_id)'),
            ]);

        $this->applyQueue($query, $queue);
        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort ?? $this->defaultSort($queue));

        // Pin the resolved queue so page 2 of an auto-opened queue stays on it.
        $mosques = $query->paginate(15)->withQueryString()->appends(['filter' => $queue]);

        $reports = MosqueContribution::with('user:id,name')
            ->whereIn('mosque_id', $mosques->pluck('id'))
            ->where('type', MosqueContribution::TYPE_REPORTED)
            ->latest('id')
            ->get()
            ->groupBy('mosque_id');

        // Name => how many mosques use it, so spelling variants are easy to spot and merge.
        $valueCounts = fn (string $column, int $limit) => Mosque::query()
            ->whereNotNull($column)->where($column, '!=', '')
            ->selectRaw("{$column} as field_value, count(*) as total")
            ->groupBy($column)->orderBy($column)->limit($limit)
            ->pluck('total', 'field_value');
        $cities = $valueCounts('city', 300);
        $countries = $valueCounts('country', 100);
        $noCity = Mosque::where(fn ($q) => $q->whereNull('city')->orWhere('city', ''))->count();
        $noCountry = Mosque::where(fn ($q) => $q->whereNull('country')->orWhere('country', ''))->count();

        $activeFilterCount = count(array_filter($filters, fn ($v) => $v !== '')) + ($sort ? 1 : 0);

        return view('admin.mosques.index', [
            'mosques' => $mosques,
            'reports' => $reports,
            'counts' => $counts,
            'filter' => $queue,
            'search' => $filters['search'],
            'filters' => $filters,
            'sort' => $sort ?? $this->defaultSort($queue),
            'cities' => $cities,
            'countries' => $countries,
            'noCity' => $noCity,
            'noCountry' => $noCountry,
            'none' => self::NONE,
            'activeFilterCount' => $activeFilterCount,
            'sorts' => self::SORTS,
            'timeFilters' => self::TIME_FILTERS,
            'addressFilters' => self::ADDRESS_FILTERS,
            'bulkActions' => self::BULK_ACTIONS,
            'maxBulk' => self::MAX_BULK,
        ]);
    }

    /**
     * One action on the ticked mosques, or on every mosque matching the
     * list's current filters. "All matching" must agree with the count the
     * admin saw, so a list that changed in the meantime is never acted on.
     */
    public function bulk(Request $request, MosqueService $service)
    {
        $data = $request->validate([
            'action' => ['required', 'in:' . implode(',', array_keys(self::BULK_ACTIONS))],
            'ids' => ['array', 'max:' . self::MAX_BULK],
            'ids.*' => ['integer'],
            'select_all_matching' => ['nullable', 'boolean'],
            'expected_count' => ['nullable', 'integer', 'min:0'],
            // Not "city"/"country": those carry the list's current filters.
            'bulk_value' => [
                'nullable',
                'required_if:action,' . implode(',', array_keys(self::VALUE_ACTIONS)),
                'string',
                'max:' . (self::VALUE_ACTIONS[$request->input('action')]['max'] ?? 120),
            ],
        ], [
            'bulk_value.required_if' => 'Type the new value first.',
            'bulk_value.max' => 'That name is too long.',
        ]);

        $query = Mosque::query();
        if ($request->boolean('select_all_matching')) {
            $queue = in_array($request->input('filter'), self::QUEUES, true) ? $request->input('filter') : 'all';
            $this->applyQueue($query, $queue);
            $this->applyFilters($query, $this->readFilters($request));

            $matching = (clone $query)->count();
            if ($matching !== (int) ($data['expected_count'] ?? -1)) {
                return back()->withErrors(['bulk' => "The list changed since you selected it (now {$matching} mosques). Please check it and try again."]);
            }
        } else {
            $ids = array_values(array_unique(array_map('intval', $data['ids'] ?? [])));
            if ($ids === []) {
                return back()->withErrors(['bulk' => 'Tick at least one mosque first.']);
            }
            $query->whereIn('id', $ids);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            return back()->withErrors(['bulk' => 'None of the selected mosques exist any more.']);
        }
        if ($total > self::MAX_BULK) {
            return back()->withErrors(['bulk' => 'That is ' . number_format($total) . ' mosques. Narrow the filters to ' . self::MAX_BULK . ' or fewer.']);
        }

        $action = $data['action'];

        if (isset(self::VALUE_ACTIONS[$action])) {
            $spec = self::VALUE_ACTIONS[$action];
            $value = trim(preg_replace('/\s+/u', ' ', (string) $data['bulk_value']));
            if ($value === '') {
                return back()->withErrors(['bulk' => 'Type the new value first.']);
            }
            $changed = $this->setColumn($query, $spec['column'], $value, $request->user()?->id);
            $noun = \Illuminate\Support\Str::plural('mosque', $changed);
            $message = "{$spec['label']} set to “{$value}” on {$changed} {$noun}.";
            if ($changed < $total) {
                $message .= ' ' . ($total - $changed) . ' already had it.';
            }

            return back()->with('success', $message);
        }

        $affected = match ($action) {
            'verify' => $query->update(['is_verified' => true, 'status' => Mosque::STATUS_ACTIVE, 'updated_at' => now()]),
            'unverify' => $query->update(['is_verified' => false, 'updated_at' => now()]),
            'hide' => $query->update(['status' => Mosque::STATUS_HIDDEN, 'updated_at' => now()]),
            'restore' => $query->update(['status' => Mosque::STATUS_ACTIVE, 'reports_count' => 0, 'updated_at' => now()]),
            'clear_reports' => $query->update(['reports_count' => 0, 'updated_at' => now()]),
            'approve_sect', 'reject_sect' => $query->whereNotNull('pending_sect')->get()
                ->each(fn (Mosque $m) => $service->resolveSectProposal($m, $action === 'approve_sect'))
                ->count(),
            // Favourites and history go with them through the foreign keys.
            'delete' => (function () use ($query, $request) {
                // So the next OpenStreetMap import doesn't bring them back.
                Mosque::excludeFromImports((clone $query)->whereNotNull('osm_id')->get(['id', 'name', 'osm_type', 'osm_id']), $request->user()?->id);

                return Mosque::deleteWithRelations((clone $query)->pluck('id')->all());
            })(),
        };

        $noun = \Illuminate\Support\Str::plural('mosque', $affected);
        $message = match ($action) {
            'verify' => "{$affected} {$noun} verified.",
            'unverify' => "Verification removed from {$affected} {$noun}.",
            'hide' => "{$affected} {$noun} hidden from the app.",
            'restore' => "{$affected} {$noun} restored and reports cleared.",
            'clear_reports' => "Reports cleared on {$affected} {$noun}.",
            'approve_sect' => "School of thought approved for {$affected} {$noun}.",
            'reject_sect' => "Proposed school of thought rejected for {$affected} {$noun}.",
            'delete' => "{$affected} {$noun} deleted along with their favourites and history.",
        };
        if (in_array($action, ['approve_sect', 'reject_sect'], true) && $affected < $total) {
            $message .= ' ' . ($total - $affected) . ' had no pending proposal.';
        }

        return back()->with('success', $message);
    }

    /**
     * Write one value into a column for many mosques, logging each change as
     * an admin edit. The log matters: an OpenStreetMap refresh overwrites
     * fields on mosques nobody edited, so without it a renamed city would
     * quietly revert on the next refresh.
     *
     * @return int mosques actually changed
     */
    private function setColumn(Builder $query, string $column, string $value, ?int $adminId): int
    {
        $rows = (clone $query)
            ->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, '!=', $value))
            ->get(['id', $column]);
        if ($rows->isEmpty()) {
            return 0;
        }

        $now = now();
        DB::transaction(function () use ($rows, $column, $value, $adminId, $now) {
            foreach ($rows->chunk(500) as $chunk) {
                Mosque::whereIn('id', $chunk->pluck('id'))->update([$column => $value, 'updated_at' => $now]);

                MosqueContribution::insert($chunk->map(fn (Mosque $m) => [
                    'mosque_id' => $m->id,
                    'user_id' => $adminId,
                    'type' => MosqueContribution::TYPE_DETAILS_UPDATED,
                    'payload' => json_encode([
                        'by_admin' => true,
                        'bulk' => true,
                        'before' => [$column => $m->{$column}],
                        'after' => [$column => $value],
                    ], JSON_UNESCAPED_UNICODE),
                    'note' => 'Bulk edit in admin panel',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }
        });

        return $rows->count();
    }

    /** @return array<string, string> */
    private function readFilters(Request $request): array
    {
        return [
            'search' => trim((string) $request->input('search', '')),
            'city' => trim((string) $request->input('city', '')),
            'country' => trim((string) $request->input('country', '')),
            'sect' => (string) $request->input('sect', ''),
            'place_type' => (string) $request->input('place_type', ''),
            'asr' => (string) $request->input('asr', ''),
            'times' => (string) $request->input('times', ''),
            'facility' => (string) $request->input('facility', ''),
            'address' => (string) $request->input('address', ''),
            'source' => (string) $request->input('source', ''),
        ];
    }

    public function moderate(Request $request, Mosque $mosque, MosqueService $service)
    {
        $data = $request->validate([
            'action' => 'required|in:verify,unverify,hide,restore,clear_reports,approve_sect,reject_sect',
        ]);

        if (in_array($data['action'], ['approve_sect', 'reject_sect'], true)) {
            $approve = $data['action'] === 'approve_sect';
            $service->resolveSectProposal($mosque, $approve);

            return back()->with('success', $approve ? 'School of thought updated.' : 'Proposed change rejected.');
        }

        [$changes, $message] = match ($data['action']) {
            'verify' => [['is_verified' => true, 'status' => Mosque::STATUS_ACTIVE], 'Mosque marked as verified.'],
            'unverify' => [['is_verified' => false], 'Verification removed.'],
            'hide' => [['status' => Mosque::STATUS_HIDDEN], 'Mosque hidden from the app.'],
            'restore' => [['status' => Mosque::STATUS_ACTIVE, 'reports_count' => 0], 'Mosque restored and visible again.'],
            'clear_reports' => [['reports_count' => 0], 'Reports cleared.'],
        };

        $mosque->update($changes);

        return back()->with('success', $message);
    }

    public function destroy(Request $request, Mosque $mosque)
    {
        $name = $mosque->name;
        Mosque::excludeFromImports([$mosque], $request->user()?->id);
        Mosque::deleteWithRelations([$mosque->id]);

        return redirect()->route('admin.mosques.index')
            ->with('success', "{$name} deleted along with its favourites and history.");
    }

    /** Largest place (in square degrees, roughly a big city) the admin panel imports in one go. */
    public const MAX_PANEL_AREA_SQ_DEG = 1.0;

    /**
     * Preview or run an OpenStreetMap import for a place or a point. Big
     * areas (a whole country) belong to the mosques:import-osm command.
     */
    public function importOsm(Request $request, OsmMosqueImporter $importer)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:place,point'],
            'place' => ['nullable', 'required_if:mode,place', 'string', 'max:120'],
            'latitude' => ['nullable', 'required_if:mode,point', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_if:mode,point', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'between:0.5,25'],
            'include_unnamed' => ['nullable', 'boolean'],
            'apply' => ['nullable', 'boolean'],
        ], [
            'place.required_if' => 'Type a city or area to import.',
            'latitude.required_if' => 'Enter a latitude and longitude.',
            'longitude.required_if' => 'Enter a latitude and longitude.',
        ]);

        @set_time_limit(300);
        $apply = $request->boolean('apply');

        try {
            if ($data['mode'] === 'place') {
                $area = $importer->resolveArea($data['place']);
                if ($area === null) {
                    return back()->withInput()->withErrors(['osm_import' => "Couldn't find \"{$data['place']}\" on OpenStreetMap. Try another spelling."]);
                }
                $size = ($area['north'] - $area['south']) * ($area['east'] - $area['west']);
                if ($size > self::MAX_PANEL_AREA_SQ_DEG) {
                    return back()->withInput()->withErrors(['osm_import' => "{$area['label']} is too large to import from here. Pick a city, or run: php artisan mosques:import-osm --city=\"{$data['place']}\""]);
                }
                $label = $area['label'];
                $elements = $importer->fetchBbox($area['south'], $area['west'], $area['north'], $area['east']);
            } else {
                $radius = (float) ($data['radius_km'] ?? 5);
                $label = sprintf('%s km around %s, %s', rtrim(rtrim(number_format($radius, 1), '0'), '.'), $data['latitude'], $data['longitude']);
                $elements = $importer->fetchAround((float) $data['latitude'], (float) $data['longitude'], $radius);
            }
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['osm_import' => $e->getMessage()]);
        }

        $report = $importer->import($elements, dryRun: !$apply, includeUnnamed: $request->boolean('include_unnamed'));

        return back()->withInput()->with('osm_report', $report + ['area' => $label]);
    }

    public function edit(Request $request, Mosque $mosque)
    {
        $mosque->load(['creator:id,name,email', 'timesUpdater:id,name', 'pendingSectBy:id,name']);

        $history = MosqueContribution::with('user:id,name')
            ->where('mosque_id', $mosque->id)
            ->latest('id')
            ->limit(15)
            ->get();

        return view('admin.mosques.edit', [
            'mosque' => $mosque,
            'history' => $history,
            'returnTo' => $this->safeReturnUrl($request->query('return')),
        ]);
    }

    public function update(Request $request, Mosque $mosque, MosqueService $service)
    {
        // Validation errors redirect back to the form with old input.
        $result = $service->adminUpdate($request->user(), $mosque, $request->except(['_token', '_method', 'return']));

        $message = match (true) {
            !$result['changed'] => 'No changes to save.',
            $result['times_changed'] => "{$mosque->name} updated. Times changed, so confirmations were reset.",
            default => "{$mosque->name} updated.",
        };
        $message .= \App\Services\ContributionPoints::note((int) ($result['points_awarded'] ?? 0));

        $returnTo = $this->safeReturnUrl($request->input('return'));

        return $returnTo
            ? redirect()->to($returnTo)->with('success', $message)
            : redirect()->route('admin.mosques.edit', $mosque)->with('success', $message);
    }

    private function applyQueue(Builder $query, string $queue): void
    {
        match ($queue) {
            'sect_review' => $query->whereNotNull('pending_sect'),
            'reported' => $query->where('reports_count', '>', 0),
            'hidden' => $query->where('status', Mosque::STATUS_HIDDEN),
            'unverified' => $query->where('is_verified', false)->where('status', Mosque::STATUS_ACTIVE),
            'verified' => $query->where('is_verified', true),
            default => null,
        };
    }

    /** @param  array<string, string>  $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $like = '%' . str_replace(['%', '_'], ' ', $filters['search']) . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('locality', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('address', 'like', $like);
            });
        }

        foreach (['city', 'country'] as $column) {
            $wanted = $filters[$column] ?? '';
            if ($wanted === self::NONE) {
                $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, ''));
            } elseif ($wanted !== '') {
                $query->where($column, $wanted);
            }
        }

        match ($filters['source'] ?? '') {
            'osm' => $query->where('source', Mosque::SOURCE_OSM),
            'community' => $query->where('source', Mosque::SOURCE_COMMUNITY)->whereNull('osm_id'),
            'linked' => $query->where('source', Mosque::SOURCE_COMMUNITY)->whereNotNull('osm_id'),
            default => null,
        };

        // "No address given" in the list means every address field is empty.
        $blank = fn (Builder $q, string $column) => $q->where(fn (Builder $w) => $w->whereNull($column)->orWhere($column, ''));
        $addressColumns = ['address', 'locality', 'city', 'country'];
        match ($filters['address'] ?? '') {
            'missing' => $query->where(function (Builder $q) use ($blank, $addressColumns) {
                foreach ($addressColumns as $column) {
                    $blank($q, $column);
                }
            }),
            'no_city' => $blank($query, 'city'),
            'has' => $query->where(function (Builder $q) use ($addressColumns) {
                foreach ($addressColumns as $column) {
                    $q->orWhere(fn (Builder $w) => $w->whereNotNull($column)->where($column, '!=', ''));
                }
            }),
            default => null,
        };

        if ($filters['sect'] === 'unspecified') {
            $query->whereNull('sect');
        } elseif (in_array($filters['sect'], Mosque::SECTS, true)) {
            $query->where('sect', $filters['sect']);
        }

        if (in_array($filters['place_type'], Mosque::PLACE_TYPES, true)) {
            $query->where('place_type', $filters['place_type']);
        }

        if ($filters['asr'] === 'unknown') {
            $query->whereNull('asr_method');
        } elseif (in_array($filters['asr'], Mosque::ASR_METHODS, true)) {
            $query->where('asr_method', $filters['asr']);
        }

        $timeColumns = ['fajr_time', 'dhuhr_time', 'asr_time', 'maghrib_time', 'isha_time', 'jumuah_time', 'jumuah_time_2', 'maghrib_offset_minutes'];
        match ($filters['times']) {
            'has' => $query->where(function (Builder $q) use ($timeColumns) {
                foreach ($timeColumns as $column) {
                    $q->orWhereNotNull($column);
                }
            }),
            'none' => $query->where(function (Builder $q) use ($timeColumns) {
                foreach ($timeColumns as $column) {
                    $q->whereNull($column);
                }
            }),
            'stale' => $query->where(function (Builder $q) {
                $q->whereNull('times_updated_at')
                    ->orWhere('times_updated_at', '<', now()->subDays(MosqueService::STALE_AFTER_DAYS));
            }),
            default => null,
        };

        if ($filters['facility'] === 'women_area') {
            $query->where('has_women_area', true);
        } elseif ($filters['facility'] === 'jumuah') {
            $query->where(fn (Builder $q) => $q->whereNotNull('jumuah_time')->orWhereNotNull('jumuah_time_2'));
        } elseif ($filters['facility'] === 'ramadan') {
            $query->where(fn (Builder $q) => $q->whereNotNull('taraweeh_rakats')->orWhereNotNull('eid_times'));
        } elseif (in_array($filters['facility'], Mosque::FACILITIES, true)) {
            $query->where('facilities->' . $filters['facility'], true);
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->orderBy('id'),
            'name' => $query->orderBy('name'),
            'reports' => $query->orderByDesc('reports_count')->orderByDesc('id'),
            'regulars' => $query->orderByDesc('regulars_count')->orderByDesc('id'),
            // Never-updated first, then the oldest updates.
            'stale' => $query->orderByRaw('times_updated_at is not null')->orderBy('times_updated_at'),
            'updated' => $query->orderByRaw('times_updated_at is null')->orderByDesc('times_updated_at'),
            'sect_review' => $query->orderBy('pending_sect_at'),
            default => $query->orderByDesc('id'),
        };
    }

    private function defaultSort(string $queue): string
    {
        return match ($queue) {
            'sect_review' => 'sect_review',
            'reported' => 'reports',
            default => 'newest',
        };
    }

    /** Only allow returning to the mosque list, never an arbitrary URL. */
    private function safeReturnUrl(mixed $url): ?string
    {
        $base = route('admin.mosques.index');

        return is_string($url) && ($url === $base || str_starts_with($url, $base . '?')) ? $url : null;
    }
}
