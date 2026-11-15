<?php

namespace App\Models;

use App\Support\Countries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One website visit: where the visitor came from, the page they landed on,
 * their approximate location and how many pages they viewed.
 */
class Visit extends Model
{
    use MassPrunable;

    public const CHANNELS = [
        'direct' => 'Direct',
        'search' => 'Search',
        'social' => 'Social',
        'ai' => 'AI assistants',
        'referral' => 'Referral',
        'email' => 'Email',
        'app' => 'App',
        'campaign' => 'Campaign',
    ];

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_new_visitor' => 'boolean',
            'pageviews' => 'integer',
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        return static::where('started_at', '<', now()->subDays(max(1, (int) config('visitors.retention_days', 365))));
    }

    public function countryName(): ?string
    {
        return Countries::name($this->country_code);
    }

    public function locationLabel(): string
    {
        $parts = array_filter([$this->city, $this->region !== $this->city ? $this->region : null, $this->countryName()]);

        return $parts ? implode(', ', $parts) : 'Unknown';
    }

    public function channelLabel(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst((string) $this->channel);
    }
}
