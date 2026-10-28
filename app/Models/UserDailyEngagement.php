<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyEngagement extends Model
{
    public const ACTIVITY_QUIZ = 'quiz';
    public const ACTIVITY_READ = 'read';
    public const ACTIVITY_INSIGHT = 'insight';
    public const ACTIVITY_DUA = 'dua';

    public const ACTIVITIES = [
        self::ACTIVITY_QUIZ,
        self::ACTIVITY_READ,
        self::ACTIVITY_INSIGHT,
        self::ACTIVITY_DUA,
    ];

    protected $fillable = [
        'user_id',
        'engagement_date',
        'activities',
    ];

    protected function casts(): array
    {
        return [
            'engagement_date' => 'date',
            'activities' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitiesList(): array
    {
        $activities = is_array($this->activities) ? $this->activities : [];

        return array_values(array_unique(array_intersect($activities, self::ACTIVITIES)));
    }

    public function hasActivity(string $activity): bool
    {
        return in_array($activity, $this->getActivitiesList(), true);
    }

    /**
     * Idempotently mark an activity as completed for this day.
     *
     * @return bool True if this call newly added the activity
     */
    public function mark(string $activity): bool
    {
        if (!in_array($activity, self::ACTIVITIES, true)) {
            return false;
        }

        $list = $this->getActivitiesList();
        if (in_array($activity, $list, true)) {
            return false;
        }

        $list[] = $activity;
        $this->activities = $list;
        $this->save();

        return true;
    }

    public function completedCount(): int
    {
        return count($this->getActivitiesList());
    }

    public function progressFraction(): float
    {
        $total = count(self::ACTIVITIES);
        if ($total === 0) {
            return 0.0;
        }

        return $this->completedCount() / $total;
    }

    public function progressPercent(): int
    {
        return (int) round($this->progressFraction() * 100);
    }
}
