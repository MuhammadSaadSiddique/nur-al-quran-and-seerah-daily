<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Scholar extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    /**
     * Confirmation required before a scholar profile is saved.
     */
    public const PLEDGE = 'I confirm that this scholar profile is accurate to the best of my knowledge, and that it will not present unresearched or unauthentic material as this scholar\'s work.';

    protected $fillable = [
        'user_id',
        'added_by',
        'full_name',
        'title',
        'slug',
        'credentials',
        'expertise',
        'background',
        'languages',
        'sources',
        'works',
        'location',
        'pledge_statement',
        'pledged_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'pledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function displayName(): string
    {
        $title = trim((string) $this->title);

        return $title !== '' ? $title.' '.$this->full_name : $this->full_name;
    }

    /**
     * @return array<string, mixed>
     */
    public static function validateSubmission(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:80'],
            'credentials' => ['required', 'string', 'max:2000'],
            'expertise' => ['required', 'string', 'max:255'],
            'background' => ['required', 'string', 'max:3000'],
            'languages' => ['nullable', 'string', 'max:255'],
            'sources' => ['required', 'string', 'max:2000'],
            'works' => ['nullable', 'string', 'max:2000'],
            'location' => ['nullable', 'string', 'max:120'],
            'authenticity_pledge' => ['accepted'],
        ], [
            'authenticity_pledge.accepted' => 'You must confirm this profile will not present unresearched or unauthentic material.',
        ]);
    }

    /**
     * Profile fields taken from a validated onboarding submission.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function attributesFrom(array $data): array
    {
        return [
            'full_name' => $data['full_name'],
            'title' => $data['title'] ?? null,
            'credentials' => $data['credentials'],
            'expertise' => $data['expertise'],
            'background' => $data['background'],
            'languages' => $data['languages'] ?? null,
            'sources' => $data['sources'],
            'works' => $data['works'] ?? null,
            'location' => $data['location'] ?? null,
            'pledge_statement' => self::PLEDGE,
            'pledged_at' => now(),
        ];
    }

    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'scholar';
        }

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
