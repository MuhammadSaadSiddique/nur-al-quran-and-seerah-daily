<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearcherOnboarding extends Model
{
    /**
     * Statement a researcher must accept before they can approve content.
     */
    public const PLEDGE = 'I confirm that I will not approve any content that is unresearched or unauthentic. I will approve an analysis, tag, link, or reference only after I have checked it against reliable sources, and I will reject anything I cannot verify.';

    protected $fillable = [
        'user_id',
        'full_name',
        'credentials',
        'expertise',
        'background',
        'languages',
        'sources',
        'requested_expert_category_id',
        'pledge_statement',
        'pledged_at',
    ];

    protected function casts(): array
    {
        return [
            'pledged_at' => 'datetime',
            'requested_expert_category_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function requestedExpertCategory(): BelongsTo
    {
        return $this->belongsTo(ScienceCategory::class, 'requested_expert_category_id');
    }

    /**
     * Record a completed onboarding pledge. Does not grant researcher access.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function acceptFor(User $user, array $overrides = []): self
    {
        $attributes = array_merge([
            'full_name' => $user->name ?: 'Researcher',
            'credentials' => 'Verified test credentials',
            'expertise' => 'Quranic studies',
            'background' => 'Test background for researcher onboarding.',
            'languages' => 'English, Arabic',
            'sources' => 'Quran, Sahih collections, classical tafsir',
            'requested_expert_category_id' => null,
            'pledge_statement' => self::PLEDGE,
            'pledged_at' => now(),
        ], $overrides);

        return static::updateOrCreate(
            ['user_id' => $user->id],
            $attributes
        );
    }
}
