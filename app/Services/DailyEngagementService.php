<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDailyEngagement;
use Illuminate\Support\Carbon;

class DailyEngagementService
{
    /**
     * Idempotently record a daily activity and ensure streak check-in.
     */
    public function record(User $user, string $activity, ?Carbon $date = null): array
    {
        $activity = strtolower(trim($activity));
        if (!in_array($activity, UserDailyEngagement::ACTIVITIES, true)) {
            return $this->todayStatus($user, $date);
        }

        $day = ($date ?? now())->toDateString();

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

        $engagement->mark($activity);

        // Any checklist activity maintains the spiritual streak.
        $user->checkInStreak();
        $user->refresh();

        return $this->todayStatus($user, $date ? Carbon::parse($day) : null);
    }

    /**
     * Build the shared today-status payload for web and mobile.
     */
    public function todayStatus(User $user, ?Carbon $date = null): array
    {
        $day = ($date ?? now())->toDateString();

        $engagement = UserDailyEngagement::where('user_id', $user->id)
            ->whereDate('engagement_date', $day)
            ->first();

        $completed = $engagement ? $engagement->getActivitiesList() : [];

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

    /**
     * @return array<string, array{label: string, href: string}>
     */
    public function activityDefinitions(): array
    {
        return [
            UserDailyEngagement::ACTIVITY_QUIZ => [
                'label' => 'Complete a quiz',
                'href' => $this->safeRoute('home', '/dashboard'),
            ],
            UserDailyEngagement::ACTIVITY_READ => [
                'label' => 'Read a Surah or Para',
                'href' => $this->safeRoute('home', '/dashboard'),
            ],
            UserDailyEngagement::ACTIVITY_INSIGHT => [
                'label' => 'Read a Seerah or Quran insight',
                'href' => $this->safeRoute('seerah', '/seerah'),
            ],
            UserDailyEngagement::ACTIVITY_DUA => [
                'label' => "Open today's dua",
                'href' => $this->safeRoute('daily.dua', '/daily-dua'),
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
