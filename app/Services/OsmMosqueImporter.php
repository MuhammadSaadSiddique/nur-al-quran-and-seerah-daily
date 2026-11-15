<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\MosqueContribution;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Brings mosques from OpenStreetMap into the community directory.
 *
 * This is the same data falah.io's Mosque Finder shows: an Overpass query for
 * amenity=place_of_worship + religion=muslim. OSM data is ODbL-licensed, so
 * every imported row keeps its OSM id and is shown with OSM attribution.
 *
 * Re-running is safe:
 *  - rows already linked to an OSM feature are matched by id, not re-created;
 *  - rows nobody in the community has edited are refreshed from OSM;
 *  - rows the community or an admin edited only get empty fields filled;
 *  - OSM features an admin deleted are skipped (mosque_import_exclusions).
 */
class OsmMosqueImporter
{
    /** Boxes larger than this (degrees) are split into tiles, per Overpass fair use. */
    public const MAX_TILE_DEGREES = 1.0;
    public const TILE_PAUSE_MS = 1000;
    public const IDS_PER_REQUEST = 300;
    public const UNNAMED_NAME = 'Masjid (name not known)';

    /** Legacy rows only know the OSM number; a match must also be this close. */
    private const LEGACY_MATCH_RADIUS_M = 200.0;

    /** OSM denomination=* values we can map with confidence. Anything else stays unspecified. */
    private const SECT_MAP = [
        'sunni' => 'sunni_other',
        'deobandi' => 'deobandi',
        'barelvi' => 'barelvi',
        'ahl-e-hadith' => 'ahl_e_hadith',
        'ahl_e_hadith' => 'ahl_e_hadith',
        'ahle_hadith' => 'ahl_e_hadith',
        'ahl-i-hadith' => 'ahl_e_hadith',
        'shia' => 'shia_other',
        'shiite' => 'shia_other',
        'twelver' => 'shia_ithna_ashari',
        'ithna_ashari' => 'shia_ithna_ashari',
        'jafari' => 'shia_ithna_ashari',
        "ja'fari" => 'shia_ithna_ashari',
        'ismaili' => 'ismaili',
        'nizari' => 'ismaili',
        'bohra' => 'bohra',
        'dawoodi_bohra' => 'bohra',
    ];

    private const MUSALLA_VALUES = ['musalla', 'musallah', 'prayer_room'];

    /** Columns an OSM import fills. Times and community details never come from OSM. */
    private const OSM_COLUMNS = ['name', 'address', 'locality', 'city', 'country', 'latitude', 'longitude', 'place_type', 'sect', 'has_women_area'];

    /** Contribution types that mean a person has changed this mosque's data. */
    private const EDIT_TYPES = [
        MosqueContribution::TYPE_TIMES_UPDATED,
        MosqueContribution::TYPE_DETAILS_UPDATED,
        MosqueContribution::TYPE_SECT_PROPOSED,
    ];

    public function __construct(private MosqueService $mosques)
    {
    }

    // ------------------------------------------------------------------
    // Fetching
    // ------------------------------------------------------------------

    /**
     * Bounding box for a place name via Nominatim.
     *
     * @return array{south: float, west: float, north: float, east: float, label: string}|null
     */
    public function resolveArea(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $response = $this->http(20)->get((string) config('services.osm.nominatim_url'), [
            'q' => $query,
            'format' => 'json',
            'limit' => 5,
        ]);
        $this->ensureOk($response, 'Nominatim');

        $results = $response->json() ?: [];
        $best = collect($results)->first(fn ($r) => in_array($r['type'] ?? '', ['city', 'town', 'village', 'administrative', 'county', 'state', 'country'], true))
            ?? ($results[0] ?? null);
        $bb = $best['boundingbox'] ?? null; // [south, north, west, east]
        if (!is_array($bb) || count($bb) < 4) {
            return null;
        }

        return [
            'south' => (float) $bb[0],
            'north' => (float) $bb[1],
            'west' => (float) $bb[2],
            'east' => (float) $bb[3],
            'label' => (string) ($best['display_name'] ?? $query),
        ];
    }

    /**
     * Every mosque in a box, split into tiles when the box is large.
     *
     * @param  callable(int $done, int $total): void|null  $progress
     * @return array<int, array<string, mixed>> raw Overpass elements
     */
    public function fetchBbox(float $south, float $west, float $north, float $east, ?callable $progress = null): array
    {
        if ($south >= $north || $west >= $east || $south < -90 || $north > 90 || $west < -180 || $east > 180) {
            throw new RuntimeException('The box must be south,west,north,east with south < north and west < east.');
        }

        $tiles = $this->tiles($south, $west, $north, $east);
        $elements = [];
        foreach ($tiles as $i => [$s, $w, $n, $e]) {
            if ($i > 0) {
                usleep(self::TILE_PAUSE_MS * 1000);
            }
            $filter = sprintf('(%F,%F,%F,%F)', $s, $w, $n, $e);
            array_push($elements, ...$this->overpass($this->mosqueQuery($filter)));
            if ($progress) {
                $progress($i + 1, count($tiles));
            }
        }

        return $elements;
    }

    /** @return array<int, array<string, mixed>> */
    public function fetchAround(float $lat, float $lng, float $radiusKm): array
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new RuntimeException('Latitude or longitude is out of range.');
        }
        $meters = (int) round(max(0.1, min(50.0, $radiusKm)) * 1000);

        return $this->overpass($this->mosqueQuery(sprintf('(around:%d,%F,%F)', $meters, $lat, $lng)));
    }

    /**
     * Current OSM data for mosques already in the directory, for --refresh.
     *
     * @return array{elements: array<int, array<string, mixed>>, requested: int}
     */
    public function fetchLinked(?callable $progress = null): array
    {
        $rows = Mosque::whereNotNull('osm_id')->get(['osm_type', 'osm_id']);
        $byType = ['node' => [], 'way' => [], 'relation' => []];
        foreach ($rows as $row) {
            // Legacy rows don't know their type: ask for the number as a node and a way.
            foreach ($row->osm_type ? [$row->osm_type] : ['node', 'way'] as $type) {
                if (isset($byType[$type])) {
                    $byType[$type][] = $row->osm_id;
                }
            }
        }

        $batches = [];
        foreach ($byType as $type => $ids) {
            foreach (array_chunk(array_values(array_unique($ids)), self::IDS_PER_REQUEST) as $chunk) {
                $batches[] = sprintf('%s(id:%s);', $type, implode(',', $chunk));
            }
        }

        $elements = [];
        foreach ($batches as $i => $statement) {
            if ($i > 0) {
                usleep(self::TILE_PAUSE_MS * 1000);
            }
            array_push($elements, ...$this->overpass("[out:json][timeout:90];({$statement});out center tags;"));
            if ($progress) {
                $progress($i + 1, count($batches));
            }
        }

        return ['elements' => $elements, 'requested' => $rows->count()];
    }

    private function mosqueQuery(string $areaFilter): string
    {
        // Same core selector as falah.io's Mosque Finder, plus relations and
        // mosque buildings mapped without an amenity tag.
        return <<<OVERPASS
[out:json][timeout:90];
(
  nwr["amenity"="place_of_worship"]["religion"="muslim"]{$areaFilter};
  nwr["building"="mosque"]{$areaFilter};
);
out center tags;
OVERPASS;
    }

    /** @return array<int, array<string, mixed>> */
    private function overpass(string $query): array
    {
        $response = $this->http(120)->asForm()->post((string) config('services.osm.overpass_url'), ['data' => $query]);
        $this->ensureOk($response, 'Overpass');

        $json = $response->json();
        if (!is_array($json) || !array_key_exists('elements', $json)) {
            throw new RuntimeException('Overpass returned an unexpected response. It may be busy; try again in a few minutes.');
        }
        if (!empty($json['remark']) && str_contains(strtolower((string) $json['remark']), 'runtime error')) {
            throw new RuntimeException('Overpass could not finish the query (' . $json['remark'] . '). Try a smaller area.');
        }

        return array_values(array_filter($json['elements'], 'is_array'));
    }

    private function http(int $timeout): PendingRequest
    {
        // Verified TLS (with the Windows CA-bundle fallback), an identifying
        // User-Agent as OSM's usage policies ask, and patience when busy.
        return Http::withOptions(QuranHttp::options())
            ->withHeaders(['User-Agent' => (string) config('services.osm.user_agent')])
            ->acceptJson()
            ->connectTimeout(15)
            ->timeout($timeout)
            ->retry(3, fn (int $attempt) => $attempt * 3000, function ($exception) {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && in_array($exception->response->status(), [429, 502, 503, 504], true));
            }, throw: false);
    }

    private function ensureOk(Response $response, string $service): void
    {
        if ($response->successful()) {
            return;
        }
        $hint = in_array($response->status(), [429, 504], true)
            ? ' The public server is busy or rate-limiting; wait a few minutes or use a smaller area.'
            : '';
        throw new RuntimeException("{$service} returned HTTP {$response->status()}.{$hint}");
    }

    /** @return array<int, array{float, float, float, float}> */
    private function tiles(float $south, float $west, float $north, float $east): array
    {
        $rows = max(1, (int) ceil(($north - $south) / self::MAX_TILE_DEGREES));
        $cols = max(1, (int) ceil(($east - $west) / self::MAX_TILE_DEGREES));
        $dLat = ($north - $south) / $rows;
        $dLng = ($east - $west) / $cols;

        $tiles = [];
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $tiles[] = [$south + $r * $dLat, $west + $c * $dLng, $south + ($r + 1) * $dLat, $west + ($c + 1) * $dLng];
            }
        }

        return $tiles;
    }

    // ------------------------------------------------------------------
    // Parsing
    // ------------------------------------------------------------------

    /**
     * One Overpass element as directory fields, or null when it has no usable position.
     *
     * @return array{osm_type: string, osm_id: int, named: bool, attributes: array<string, mixed>, facilities: array<string, bool>}|null
     */
    public function parse(array $element): ?array
    {
        $type = $element['type'] ?? null;
        $id = $element['id'] ?? null;
        if (!in_array($type, ['node', 'way', 'relation'], true) || !is_numeric($id)) {
            return null;
        }

        $lat = $element['lat'] ?? ($element['center']['lat'] ?? null);
        $lng = $element['lon'] ?? ($element['center']['lon'] ?? null);
        if (!is_numeric($lat) || !is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
            return null;
        }

        $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
        $name = $this->clean($this->firstTag($tags, ['name:en', 'name', 'int_name', 'name:ur', 'name:ar']), 150);
        $named = $name !== null && mb_strlen($name) >= 3;

        return [
            'osm_type' => $type,
            'osm_id' => (int) $id,
            'named' => $named,
            'attributes' => [
                'name' => $named ? $name : null,
                'address' => $this->clean(trim(($tags['addr:housenumber'] ?? '') . ' ' . ($tags['addr:street'] ?? '')), 255),
                'locality' => $this->clean($this->firstTag($tags, ['addr:suburb', 'addr:neighbourhood', 'addr:quarter']), 120),
                'city' => $this->clean($this->firstTag($tags, ['addr:city', 'addr:town', 'addr:village']), 120),
                'country' => $this->clean($this->firstTag($tags, ['addr:country']), 80),
                'latitude' => round((float) $lat, 7),
                'longitude' => round((float) $lng, 7),
                'place_type' => $this->placeType($tags),
                'sect' => self::SECT_MAP[strtolower(trim((string) ($tags['denomination'] ?? '')))] ?? null,
                'has_women_area' => $this->yesNo($tags['female'] ?? $tags['women'] ?? null),
            ],
            'facilities' => array_filter([
                // "limited" is not step-free, so it stays unknown.
                'wheelchair_entrance' => $this->yesNo($tags['wheelchair'] ?? null),
                'toilets' => $this->yesNo($tags['toilets'] ?? null),
                'accessible_wudu' => ($tags['toilets:wheelchair'] ?? null) === 'yes' ? true : null,
                'air_conditioning' => $this->yesNo($tags['air_conditioning'] ?? null),
            ], fn ($v) => $v !== null),
        ];
    }

    /** The same tags the Overpass queries select on. */
    public function isMosque(array $tags): bool
    {
        return (($tags['amenity'] ?? null) === 'place_of_worship' && ($tags['religion'] ?? null) === 'muslim')
            || ($tags['building'] ?? null) === 'mosque';
    }

    private function placeType(array $tags): string
    {
        foreach (['place_of_worship', 'building', 'room'] as $key) {
            if (in_array(strtolower((string) ($tags[$key] ?? '')), self::MUSALLA_VALUES, true)) {
                return 'musalla';
            }
        }
        if (($tags['indoor'] ?? null) === 'room') {
            return 'musalla';
        }

        return 'masjid';
    }

    private function yesNo(mixed $value): ?bool
    {
        return match (strtolower(trim((string) $value))) {
            'yes', 'separated' => true,
            'no' => false,
            default => null,
        };
    }

    private function firstTag(array $tags, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($tags[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function clean(?string $value, int $max): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    // ------------------------------------------------------------------
    // Importing
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<string, mixed>>  $elements  raw Overpass elements
     * @param  bool  $onlyExisting  update mosques already linked to OSM, never add (used by --refresh)
     * @return array<string, mixed> counts plus a few sample names
     */
    public function import(
        array $elements,
        bool $dryRun = false,
        bool $verified = false,
        bool $includeUnnamed = false,
        bool $onlyExisting = false
    ): array {
        $report = [
            'fetched' => count($elements),
            'created' => 0,
            'refreshed' => 0,
            'filled' => 0,
            'linked' => 0,
            'unchanged' => 0,
            'skipped_unnamed' => 0,
            'skipped_deleted' => 0,
            'skipped_duplicate' => 0,
            'skipped_invalid' => 0,
            'skipped_not_mosque' => 0,
            'skipped_not_listed' => 0,
            'dry_run' => $dryRun,
            'samples' => [],
        ];

        // A preview walks the same steps without writing. It must not rely on a
        // rolled-back transaction: MyISAM tables ignore transactions entirely.
        $save = !$dryRun;

        // Parse, and collapse the same feature returned by two selectors or
        // tiles, keeping the copy with the most tags.
        $features = [];
        $tagCounts = [];
        foreach ($elements as $element) {
            // Lookups by id (refresh) can return an unrelated feature that shares
            // the number with a mosque of the other type; only mosques count.
            if (!$this->isMosque(is_array($element['tags'] ?? null) ? $element['tags'] : [])) {
                $report['skipped_not_mosque']++;
                continue;
            }
            $feature = $this->parse($element);
            if ($feature === null) {
                $report['skipped_invalid']++;
                continue;
            }
            $key = $feature['osm_type'] . '/' . $feature['osm_id'];
            $tagCount = is_array($element['tags'] ?? null) ? count($element['tags']) : 0;
            if (!isset($features[$key]) || $tagCount > $tagCounts[$key]) {
                $features[$key] = $feature;
                $tagCounts[$key] = $tagCount;
            }
        }

        $osmIds = array_values(array_unique(array_map(fn ($f) => $f['osm_id'], $features)));
        $linked = collect();
        $excluded = collect();
        foreach (array_chunk($osmIds, 1000) as $chunk) {
            $linked = $linked->concat(Mosque::whereIn('osm_id', $chunk)->get());
            $excluded = $excluded->concat(DB::table('mosque_import_exclusions')->whereIn('osm_id', $chunk)->get());
        }
        $linked = $linked->groupBy('osm_id');
        $excluded = $excluded->groupBy('osm_id');
        $edited = $this->editedMosqueIds($linked->flatten(1)->pluck('id')->all());

        $now = now();
        $wouldCreate = [];  // previews: mosques this run would have added so far
        $linkedThisRun = []; // app-added mosques already linked by an earlier feature

        foreach ($features as $feature) {
            $attrs = $feature['attributes'];

            $wasDeleted = ($excluded[$feature['osm_id']] ?? collect())
                ->contains(fn ($x) => $x->osm_type === null || $x->osm_type === $feature['osm_type']);
            if ($wasDeleted) {
                $report['skipped_deleted']++;
                continue;
            }

            $existing = $this->matchLinked($linked[$feature['osm_id']] ?? collect(), $feature);
            if ($existing) {
                $outcome = $this->updateExisting($existing, $feature, in_array($existing->id, $edited, true), $now, $save);
                $report[$outcome]++;
                continue;
            }

            if ($onlyExisting) {
                $report['skipped_not_listed']++;
                continue;
            }

            if (!$feature['named'] && !$includeUnnamed) {
                $report['skipped_unnamed']++;
                continue;
            }
            $name = $attrs['name'] ?? self::UNNAMED_NAME;

            $matchName = $feature['named'] ? $name : '';
            $same = $this->mosques->findLikelySame($matchName, $attrs['latitude'], $attrs['longitude'], includeHidden: true);
            if ($same) {
                if ($same->osm_id === null && !isset($linkedThisRun[$same->id])) {
                    // A worshipper already added it: link it, keep their data, fill gaps.
                    $this->updateExisting($same, $feature, true, $now, $save, link: true);
                    $linkedThisRun[$same->id] = true;
                    $report['linked']++;
                } else {
                    // Another OSM feature (e.g. the building outline) for a mosque we have.
                    $report['skipped_duplicate']++;
                }
                continue;
            }

            if (!$save) {
                $duplicateOfPreview = collect($wouldCreate)->contains(fn (array $p) => $this->mosques->isLikelySame(
                    $matchName, $attrs['latitude'], $attrs['longitude'], $p['name'], $p['latitude'], $p['longitude']
                ));
                if ($duplicateOfPreview) {
                    $report['skipped_duplicate']++;
                    continue;
                }
                $wouldCreate[] = ['name' => $matchName, 'latitude' => $attrs['latitude'], 'longitude' => $attrs['longitude']];
            } else {
                $this->create($feature, $name, $verified, $now);
            }
            $report['created']++;
            if (count($report['samples']) < 10) {
                $report['samples'][] = $name;
            }
        }

        return $report;
    }

    private function matchLinked($candidates, array $feature): ?Mosque
    {
        foreach ($candidates as $mosque) {
            if ($mosque->osm_type === $feature['osm_type']) {
                return $mosque;
            }
        }
        // First-importer rows know only the number: accept it if it is also nearby.
        foreach ($candidates as $mosque) {
            if ($mosque->osm_type === null) {
                $meters = $this->mosques->distanceKm($mosque->latitude, $mosque->longitude, $feature['attributes']['latitude'], $feature['attributes']['longitude']) * 1000;
                if ($meters <= self::LEGACY_MATCH_RADIUS_M) {
                    return $mosque;
                }
            }
        }

        return null;
    }

    /** @return string report key */
    private function updateExisting(Mosque $mosque, array $feature, bool $edited, $now, bool $save, bool $link = false): string
    {
        $attrs = $feature['attributes'];
        $fillOnly = $edited || $mosque->source !== Mosque::SOURCE_OSM;

        if ($link || $mosque->osm_type === null) {
            $mosque->osm_type = $feature['osm_type'];
            $mosque->osm_id = $feature['osm_id'];
        }

        foreach (self::OSM_COLUMNS as $column) {
            $value = $attrs[$column] ?? null;
            if ($value === null) {
                continue;
            }
            $current = $mosque->{$column};
            if ($fillOnly) {
                if ($current === null || $current === '') {
                    $mosque->{$column} = $value;
                }
            } elseif ($column === 'name' && !$feature['named']) {
                continue;
            } else {
                $mosque->{$column} = $value;
            }
        }

        $facilities = $mosque->facilityMap();
        foreach ($feature['facilities'] as $key => $value) {
            if (!$fillOnly || $facilities[$key] === null) {
                $facilities[$key] = $value;
            }
        }
        $known = array_filter($facilities, fn ($v) => $v !== null);
        // Don't count "[]" (first importer) becoming null as a change.
        if ($known !== [] || !empty($mosque->facilities)) {
            $mosque->facilities = $known === [] ? null : $known;
        }

        $changed = $mosque->isDirty(array_merge(self::OSM_COLUMNS, ['facilities']));
        $outcome = $link ? 'linked' : ($changed ? ($fillOnly ? 'filled' : 'refreshed') : 'unchanged');

        if ($save) {
            $mosque->osm_synced_at = $now;
            $mosque->save();
        }

        return $outcome;
    }

    private function create(array $feature, string $name, bool $verified, $now): void
    {
        DB::transaction(function () use ($feature, $name, $verified, $now) {
            $mosque = Mosque::create(array_merge($feature['attributes'], [
                'name' => $name,
                'source' => Mosque::SOURCE_OSM,
                'osm_type' => $feature['osm_type'],
                'osm_id' => $feature['osm_id'],
                'osm_synced_at' => $now,
                'facilities' => $feature['facilities'] ?: null,
                'status' => Mosque::STATUS_ACTIVE,
                'is_verified' => $verified,
                'created_by' => null,
            ]));

            MosqueContribution::create([
                'mosque_id' => $mosque->id,
                'user_id' => null,
                'type' => MosqueContribution::TYPE_ADDED,
                'payload' => ['source' => 'openstreetmap', 'osm_type' => $feature['osm_type'], 'osm_id' => $feature['osm_id']],
                'note' => 'Imported from OpenStreetMap',
            ]);
        });
    }

    /** @return array<int, int> mosque ids someone has edited since they were added */
    private function editedMosqueIds(array $mosqueIds): array
    {
        $edited = [];
        foreach (array_chunk($mosqueIds, 1000) as $chunk) {
            $edited = array_merge($edited, MosqueContribution::whereIn('mosque_id', $chunk)
                ->whereIn('type', self::EDIT_TYPES)
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('mosque_id')
                ->all());
        }

        return array_map('intval', $edited);
    }
}
