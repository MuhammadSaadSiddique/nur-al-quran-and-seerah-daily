<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mosque extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HIDDEN = 'hidden';

    public const SOURCE_COMMUNITY = 'community';
    public const SOURCE_OSM = 'osm';

    /** Required credit wherever OpenStreetMap data is shown (ODbL). */
    public const OSM_ATTRIBUTION = '© OpenStreetMap contributors';
    public const OSM_COPYRIGHT_URL = 'https://www.openstreetmap.org/copyright';

    /** Prayers a mosque can hold a jama'ah for, in daily order. */
    public const PRAYERS = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'jumuah'];

    public const PLACE_TYPES = ['masjid', 'musalla'];

    /** Fixed list, named as communities name themselves. Null means not specified. */
    public const SECTS = [
        'deobandi',
        'barelvi',
        'ahl_e_hadith',
        'sunni_other',
        'shia_ithna_ashari',
        'ismaili',
        'bohra',
        'shia_other',
    ];

    /** Pending-sect value meaning "remove the sect label". */
    public const SECT_NONE = 'none';

    public const ASR_METHODS = ['hanafi', 'standard'];

    /** Facility keys stored as true / false / null (unknown). */
    public const FACILITIES = [
        'wheelchair_entrance',
        'accessible_wudu',
        'prayer_chairs',
        'women_wudu',
        'wudu_area',
        'toilets',
        'car_parking',
        'bike_parking',
        'air_conditioning',
    ];

    public const KHUTBAH_LANGUAGES = ['ar', 'ur', 'en', 'bn', 'pa', 'ps', 'sd', 'fa', 'tr', 'ms', 'id', 'fr', 'so'];

    public const TARAWEEH_RAKATS = [8, 20];

    public const EID_KINDS = ['fitr', 'adha'];

    /** Nearby-list filters the app can ask for. */
    public const FILTERS = ['wheelchair', 'chairs', 'women', 'jumuah', 'parking'];

    protected $fillable = [
        'source',
        'osm_type',
        'osm_id',
        'osm_synced_at',
        'name',
        'address',
        'locality',
        'city',
        'country',
        'latitude',
        'longitude',
        'place_type',
        'sect',
        'pending_sect',
        'pending_sect_by',
        'pending_sect_at',
        'asr_method',
        'fajr_time',
        'dhuhr_time',
        'asr_time',
        'maghrib_time',
        'isha_time',
        'jumuah_time',
        'jumuah_time_2',
        'maghrib_offset_minutes',
        'has_women_area',
        'facilities',
        'khutbah_languages',
        'taraweeh_rakats',
        'taraweeh_khatm',
        'eid_times',
        'status',
        'is_verified',
        'confirmations_count',
        'reports_count',
        'times_updated_at',
        'times_confirmed_at',
        'times_updated_by',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'osm_id' => 'integer',
        'osm_synced_at' => 'datetime',
        'maghrib_offset_minutes' => 'integer',
        'has_women_area' => 'boolean',
        'facilities' => 'array',
        'khutbah_languages' => 'array',
        'taraweeh_rakats' => 'integer',
        'taraweeh_khatm' => 'boolean',
        'eid_times' => 'array',
        'pending_sect_at' => 'datetime',
        'is_verified' => 'boolean',
        'confirmations_count' => 'integer',
        'reports_count' => 'integer',
        'times_updated_at' => 'datetime',
        'times_confirmed_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timesUpdater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'times_updated_by');
    }

    public function pendingSectBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pending_sect_by');
    }

    /** English labels for admin pages; the app has its own copy. */
    public static function sectLabel(?string $sect): string
    {
        return match ($sect) {
            'deobandi' => 'Sunni · Deobandi',
            'barelvi' => 'Sunni · Barelvi (Ahl-e-Sunnat)',
            'ahl_e_hadith' => 'Sunni · Ahl-e-Hadith',
            'sunni_other' => 'Sunni · Other',
            'shia_ithna_ashari' => "Shia · Ithna Ashari (Ja'fari)",
            'ismaili' => 'Shia · Ismaili',
            'bohra' => 'Shia · Bohra',
            'shia_other' => 'Shia · Other',
            self::SECT_NONE, null => 'Not specified',
            default => ucfirst(str_replace('_', ' ', $sect)),
        };
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(MosqueContribution::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(SalahFavorite::class);
    }

    /** @return array<string, string|null> */
    public function jamaahTimes(): array
    {
        $times = [];
        foreach (self::PRAYERS as $prayer) {
            $times[$prayer] = $this->{$prayer . '_time'};
        }

        return $times;
    }

    public function hasAnyTime(): bool
    {
        return $this->maghrib_offset_minutes !== null
            || $this->jumuah_time_2 !== null
            || count(array_filter($this->jamaahTimes())) > 0;
    }

    /** Every facility key, with null where nobody has said yet. */
    public function facilityMap(): array
    {
        $stored = is_array($this->facilities) ? $this->facilities : [];
        $map = [];
        foreach (self::FACILITIES as $key) {
            $map[$key] = array_key_exists($key, $stored) && $stored[$key] !== null ? (bool) $stored[$key] : null;
        }

        return $map;
    }

    public function hasFacility(string $key): bool
    {
        return $this->facilityMap()[$key] ?? false;
    }

    public function hasJumuah(): bool
    {
        return $this->jumuah_time !== null || $this->jumuah_time_2 !== null;
    }

    /** Any OpenStreetMap data in this entry means it must carry OSM attribution. */
    public function usesOsmData(): bool
    {
        return $this->source === self::SOURCE_OSM || $this->osm_id !== null;
    }

    public function osmUrl(): ?string
    {
        if ($this->osm_id === null) {
            return null;
        }

        return $this->osm_type
            ? "https://www.openstreetmap.org/{$this->osm_type}/{$this->osm_id}"
            // Rows from the first importer only know the number; let OSM search resolve it.
            : "https://www.openstreetmap.org/search?query={$this->latitude},{$this->longitude}";
    }

    /**
     * Delete mosques with their favourites and history. Done explicitly
     * because MyISAM tables (the default on some MySQL servers) ignore
     * foreign-key cascades.
     *
     * @param  array<int, int|string>  $ids
     */
    public static function deleteWithRelations(array $ids): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $deleted = 0;
        foreach (array_chunk($ids, 500) as $chunk) {
            SalahFavorite::whereIn('mosque_id', $chunk)->delete();
            MosqueContribution::whereIn('mosque_id', $chunk)->delete();
            $deleted += static::whereIn('id', $chunk)->delete();
        }

        return $deleted;
    }

    /**
     * Remember deleted OpenStreetMap mosques so the next import doesn't bring
     * them back. Call before deleting, single or bulk.
     *
     * @param  iterable<Mosque>  $mosques
     */
    public static function excludeFromImports(iterable $mosques, ?int $userId): void
    {
        $now = now();
        $rows = [];
        foreach ($mosques as $mosque) {
            if ($mosque->osm_id === null) {
                continue;
            }
            $rows[] = [
                'osm_type' => $mosque->osm_type,
                'osm_id' => $mosque->osm_id,
                'mosque_name' => mb_substr((string) $mosque->name, 0, 150),
                'excluded_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            \Illuminate\Support\Facades\DB::table('mosque_import_exclusions')->insert($chunk);
        }
    }
}
