<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'emoji',
        'description',
        'is_active',
        'status',
        'created_by_user_id',
        'submitter_name',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public const TYPE_DUA = 'dua';
    public const TYPE_QURAN_RESEARCH = 'quran_research';

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Category $category) {
            if (empty($category->type)) {
                $category->type = self::TYPE_DUA;
            }
            if (empty($category->slug)) {
                $baseSlug = Str::slug($category->name);
                $slug = $baseSlug ?: 'category';
                $counter = 1;
                while (static::where('type', $category->type)->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $category->slug = $slug;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function scopeDua($query)
    {
        return $query->where('type', self::TYPE_DUA);
    }

    public function scopeQuranResearch($query)
    {
        return $query->where('type', self::TYPE_QURAN_RESEARCH);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }

    /**
     * Default Quran Research categories.
     */
    public static function defaultQuranResearchCategories(): array
    {
        return [
            'video' => ['name' => 'Video', 'emoji' => '🎬', 'description' => 'Lectures, animations, tafsir videos'],
            'science' => ['name' => 'Science', 'emoji' => '🔬', 'description' => 'Cosmology, biology, geology, embryology'],
            'seerah' => ['name' => 'Seerah', 'emoji' => '🕌', 'description' => 'Chronological life of the Prophet ﷺ'],
            'hadith' => ['name' => 'Hadith', 'emoji' => '📚', 'description' => 'Cross-referencing authenticated traditions'],
            'history' => ['name' => 'History', 'emoji' => '🏛️', 'description' => 'Archaeology, ancient civilizations'],
            'other' => ['name' => 'General', 'emoji' => '💡', 'description' => 'Linguistic or thematic reflection'],
        ];
    }

    /**
     * Default Dua categories.
     */
    public static function defaultDuaCategories(): array
    {
        return [
            ['name' => 'Forgiveness & Mercy', 'emoji' => '🤲', 'description' => 'Supplications seeking Allah\'s pardon, mercy, and forgiveness'],
            ['name' => 'Morning & Evening', 'emoji' => '🌅', 'description' => 'Daily remembrances (adhkar) for morning and evening protection'],
            ['name' => 'Protection & Safety', 'emoji' => '🛡️', 'description' => 'Seeking refuge in Allah from harm, evil eye, and trials'],
            ['name' => 'Guidance & Faith', 'emoji' => '✨', 'description' => 'Prayers for steadfastness of heart, wisdom, and true guidance'],
            ['name' => 'Health & Healing', 'emoji' => '💚', 'description' => 'Prophetic supplications and ruqyah for healing and well-being'],
            ['name' => 'Anxiety & Distress', 'emoji' => '🌧️', 'description' => 'Relief from sorrow, grief, debt, and overwhelming hardship'],
            ['name' => 'Gratitude & Sustenance', 'emoji' => '🌾', 'description' => 'Thankfulness to Allah and seeking lawful provision (rizq)'],
            ['name' => 'Travel & Journey', 'emoji' => '✈️', 'description' => 'Supplications for embarking on a journey and safe return'],
            ['name' => 'Family & Children', 'emoji' => '👨‍👩‍👧‍👦', 'description' => 'Prayers for parents, spouse, righteous offspring, and harmony'],
            ['name' => 'Comprehensive & Success', 'emoji' => '🌟', 'description' => 'All-encompassing prayers for goodness in this world and the Hereafter'],
        ];
    }

    /**
     * Ensure default categories and any existing DB categories from Duas / QuranResearchContributions are synced.
     */
    public static function ensureDefaultsSynced(): void
    {
        try {
            if (!Schema::hasTable('categories')) {
                return;
            }

            // 1. Seed default Quran Research categories if none exist
            if (static::where('type', self::TYPE_QURAN_RESEARCH)->count() === 0) {
                foreach (self::defaultQuranResearchCategories() as $slug => $data) {
                    static::firstOrCreate(
                        ['type' => self::TYPE_QURAN_RESEARCH, 'slug' => $slug],
                        [
                            'name' => $data['name'],
                            'emoji' => $data['emoji'],
                            'description' => $data['description'],
                            'is_active' => true,
                            'status' => 'approved',
                            'submitter_name' => 'System Default',
                        ]
                    );
                }
            }

            // 2. Seed default Dua categories if none exist
            if (static::where('type', self::TYPE_DUA)->count() === 0) {
                foreach (self::defaultDuaCategories() as $data) {
                    static::firstOrCreate(
                        ['type' => self::TYPE_DUA, 'slug' => Str::slug($data['name'])],
                        [
                            'name' => $data['name'],
                            'emoji' => $data['emoji'],
                            'description' => $data['description'],
                            'is_active' => true,
                            'status' => 'approved',
                            'submitter_name' => 'System Default',
                        ]
                    );
                }
            }

            // 3. Sync any custom categories already used in Duas
            if (Schema::hasTable('duas')) {
                $duaCats = Dua::distinct()->whereNotNull('category')->pluck('category');
                foreach ($duaCats as $catName) {
                    $catName = trim((string) $catName);
                    if ($catName !== '') {
                        self::ensureCategory($catName, self::TYPE_DUA);
                    }
                }
            }

            // 4. Sync any custom categories already used in QuranResearchContributions
            if (Schema::hasTable('quran_research_contributions')) {
                $researchCats = QuranResearchContribution::distinct()->whereNotNull('category')->pluck('category');
                foreach ($researchCats as $catSlugOrName) {
                    $catSlugOrName = trim((string) $catSlugOrName);
                    if ($catSlugOrName !== '') {
                        self::ensureCategory($catSlugOrName, self::TYPE_QURAN_RESEARCH);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore if tables are mid-migration
        }
    }

    /**
     * Find or create a category by name or slug.
     */
    public static function ensureCategory(
        string $nameOrSlug,
        string $type = self::TYPE_DUA,
        ?string $emoji = null,
        ?string $description = null,
        ?int $userId = null,
        ?string $submitterName = null
    ): ?self {
        $nameOrSlug = trim($nameOrSlug);
        if ($nameOrSlug === '') {
            return null;
        }

        if (!Schema::hasTable('categories')) {
            return null;
        }

        $slug = Str::slug($nameOrSlug) ?: strtolower($nameOrSlug);

        $existing = static::where('type', $type)
            ->where(function ($q) use ($nameOrSlug, $slug) {
                $q->whereRaw('LOWER(name) = ?', [strtolower($nameOrSlug)])
                  ->orWhere('slug', $slug);
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        // Format a clean display name if a slug like 'linguistic_miracles' was passed
        $displayName = $nameOrSlug;
        if ($type === self::TYPE_QURAN_RESEARCH && $displayName === strtolower($displayName) && strpos($displayName, ' ') === false) {
            $defaults = self::defaultQuranResearchCategories();
            if (isset($defaults[$slug])) {
                $displayName = $defaults[$slug]['name'];
                $emoji = $emoji ?: $defaults[$slug]['emoji'];
                $description = $description ?: $defaults[$slug]['description'];
            } else {
                $displayName = Str::headline(str_replace(['-', '_'], ' ', $displayName));
            }
        }

        // Community-created categories stay pending until an admin approves.
        return static::create([
            'name' => $displayName,
            'slug' => $slug,
            'type' => $type,
            'emoji' => $emoji ?: ($type === self::TYPE_DUA ? '🤲' : '🔬'),
            'description' => $description,
            'is_active' => false,
            'status' => 'pending',
            'created_by_user_id' => $userId,
            'submitter_name' => $submitterName ?: ($userId ? optional(User::find($userId))->name : 'Community Contributor'),
        ]);
    }

    /**
     * Retrieve all active Dua category names sorted alphabetically.
     */
    public static function getDuaCategoryNames(): \Illuminate\Support\Collection
    {
        self::ensureDefaultsSynced();

        $names = collect();
        if (Schema::hasTable('categories')) {
            $names = static::dua()->active()->orderBy('name')->pluck('name');
        }

        if (Schema::hasTable('duas')) {
            $fromDuas = Dua::approved()->distinct()->whereNotNull('category')->pluck('category');
            $names = $names->merge($fromDuas);
        }

        if ($names->isEmpty()) {
            $names = collect(self::defaultDuaCategories())->pluck('name');
        }

        return $names->unique()->sort()->values();
    }

    /**
     * Retrieve all active Quran Research categories for forms and filters.
     */
    public static function getQuranResearchCategories(): \Illuminate\Support\Collection
    {
        self::ensureDefaultsSynced();

        if (Schema::hasTable('categories')) {
            $cats = static::quranResearch()->active()->orderBy('id')->get();
            if ($cats->isNotEmpty()) {
                return $cats;
            }
        }

        $fallback = collect();
        foreach (self::defaultQuranResearchCategories() as $slug => $data) {
            $fallback->push((object) [
                'id' => null,
                'slug' => $slug,
                'name' => $data['name'],
                'emoji' => $data['emoji'],
                'description' => $data['description'],
                'type' => self::TYPE_QURAN_RESEARCH,
            ]);
        }

        return $fallback;
    }
}
