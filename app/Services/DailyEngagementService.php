<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDailyEngagement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DailyEngagementService
{
    public function __construct(private ContributionPoints $points)
    {
    }

    /**
     * Idempotently record a daily activity and ensure streak check-in.
     */
    public function record(User $user, string $activity, ?Carbon $date = null): array
    {
        $activity = strtolower(trim($activity));
        if (!in_array($activity, UserDailyEngagement::ACTIVITIES, true)) {
            return $this->todayStatus($user, $date);
        }

        // Always maintain streak even if engagement table is unavailable.
        $streak = $user->checkInStreak();
        $user->refresh();
        $awarded = (int) ($streak['points_awarded'] ?? 0);

        if (!$this->engagementTableReady()) {
            $status = $this->todayStatus($user, $date);
            $status['points_awarded'] = $awarded;

            return $status;
        }

        $day = ($date ?? now())->toDateString();

        try {
            $engagement = UserDailyEngagement::where('user_id', $user->id)
                ->whereDate('engagement_date', $day)
                ->first();

            if (!$engagement) {
                try {
                    $engagement = UserDailyEngagement::create([
                        'user_id' => $user->id,
                        'engagement_date' => $day,
                        'activities' => [],
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                    $engagement = UserDailyEngagement::where('user_id', $user->id)
                        ->whereDate('engagement_date', $day)
                        ->firstOrFail();
                }
            }

            if ($engagement->mark($activity)) {
                $awarded += $this->points->forDailyTask($user, $activity, $day);
            }
        } catch (\Throwable $e) {
            Log::warning('Daily engagement record failed: ' . $e->getMessage());
        }

        $status = $this->todayStatus($user, $date ? Carbon::parse($day) : null);
        $status['points_awarded'] = $awarded;

        return $status;
    }

    /**
     * Build the shared today-status payload for web and mobile.
     */
    public function todayStatus(User $user, ?Carbon $date = null): array
    {
        $day = ($date ?? now())->toDateString();
        $completed = [];

        if ($this->engagementTableReady()) {
            try {
                $engagement = UserDailyEngagement::where('user_id', $user->id)
                    ->whereDate('engagement_date', $day)
                    ->first();
                $completed = $engagement ? $engagement->getActivitiesList() : [];
            } catch (\Throwable $e) {
                Log::warning('Daily engagement status failed: ' . $e->getMessage());
            }
        }

        $definitions = $this->activityDefinitions();
        $activities = [];
        foreach ($definitions as $key => $meta) {
            $activities[$key] = [
                'done' => in_array($key, $completed, true),
                'label' => $meta['label'],
                'href' => $meta['href'],
            ];
        }

        $completedCount = count($completed);
        $total = count(UserDailyEngagement::ACTIVITIES);

        return [
            'date' => $day,
            'activities' => $activities,
            'completed_count' => $completedCount,
            'total' => $total,
            'progress_percent' => $total > 0 ? (int) round(($completedCount / $total) * 100) : 0,
            'streak' => $user->getStreakStatus(),
        ];
    }

    private function engagementTableReady(): bool
    {
        try {
            return Schema::hasTable('user_daily_engagements');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<string, array{label: string, href: string}>
     */
    public function activityDefinitions(): array
    {
        return [
            UserDailyEngagement::ACTIVITY_QUIZ => [
                'label' => 'Complete a quiz',
                'href' => $this->safeRoute('quiz.learning', '/quiz-learning'),
            ],
            UserDailyEngagement::ACTIVITY_CONTRIBUTE => [
                'label' => 'Add a research contribution',
                'href' => $this->safeRoute('research.contributions.create', '/quran-research/contributions/create'),
            ],
            UserDailyEngagement::ACTIVITY_INSIGHT => [
                'label' => 'Read a Seerah or Quran insight',
                'href' => $this->safeRoute('seerah', '/seerah'),
            ],
            UserDailyEngagement::ACTIVITY_DUA => [
                'label' => "Open today's dua",
                'href' => $this->safeRoute('daily.dua', '/daily-dua'),
            ],
            UserDailyEngagement::ACTIVITY_PRAYER => [
                'label' => 'Open the prayer page',
                'href' => $this->safeRoute('prayer', '/prayer'),
            ],
        ];
    }

    private function safeRoute(string $name, string $fallbackPath): string
    {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return url($fallbackPath);
        }
    }
}
