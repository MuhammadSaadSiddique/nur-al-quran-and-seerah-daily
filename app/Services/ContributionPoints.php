<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPointAward;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Points for community actions. Quiz points stay in total_score as before;
 * these awards are added on top and remembered separately so quiz accuracy
 * does not rise just because someone listed a mosque or a verse connection.
 */
class ContributionPoints
{
    public const MOSQUE_ADDED = 'mosque_added';
    public const MOSQUE_UPDATED = 'mosque_updated';
    public const MOSQUE_CONFIRMED = 'mosque_confirmed';
    public const QURAN_VIDEO = 'quran_video';
    public const QURAN_CONNECTION = 'quran_connection';
    public const AFFILIATE_SIGNUP = 'affiliate_signup';
    public const DAILY_TASK = 'daily_task';
    public const DAILY_STREAK = 'daily_streak';
    public const APP_FEEDBACK = 'app_feedback';

    public const MOSQUE_ADDED_POINTS = 20;
    public const MOSQUE_UPDATED_POINTS = 10;
    public const MOSQUE_CONFIRMED_POINTS = 5;
    public const QURAN_VIDEO_POINTS = 15;
    public const QURAN_CONNECTION_POINTS = 10;
    public const AFFILIATE_SIGNUP_POINTS = 25;
    public const DAILY_TASK_POINTS = 5;
    /** Day 1 of a streak. Each extra day adds one point, up to STREAK_MAX_POINTS. */
    public const STREAK_BASE_POINTS = 5;
    public const STREAK_MAX_POINTS = 30;
    public const APP_FEEDBACK_POINTS = 100;

    /** Video and connection awards share this cap so a script cannot farm the leaderboard. */
    public const DAILY_QURAN_LIMIT = 15;

    public const AMOUNTS = [
        self::MOSQUE_ADDED => self::MOSQUE_ADDED_POINTS,
        self::MOSQUE_UPDATED => self::MOSQUE_UPDATED_POINTS,
        self::MOSQUE_CONFIRMED => self::MOSQUE_CONFIRMED_POINTS,
        self::QURAN_VIDEO => self::QURAN_VIDEO_POINTS,
        self::QURAN_CONNECTION => self::QURAN_CONNECTION_POINTS,
        self::AFFILIATE_SIGNUP => self::AFFILIATE_SIGNUP_POINTS,
        self::DAILY_TASK => self::DAILY_TASK_POINTS,
        self::DAILY_STREAK => self::STREAK_BASE_POINTS,
    ];

    public const QURAN_ACTIONS = [
        self::QURAN_VIDEO,
        self::QURAN_CONNECTION,
    ];

    /**
     * Award points once per source. Returns how many points were added (0 when
     * the action was already rewarded, the daily Quran cap is full, or storage
     * is not ready).
     */
    public static function pointsForStreak(int $days): int
    {
        $days = max(1, $days);

        return min(self::STREAK_MAX_POINTS, self::STREAK_BASE_POINTS + ($days - 1));
    }

    public function forDailyTask(User $user, string $activity, string $date): int
    {
        return $this->award($user, self::DAILY_TASK, 'daily_task:' . $user->id . ':' . $date . ':' . $activity);
    }

    public function forDailyStreak(User $user, int $streakDays, string $date): int
    {
        return $this->award(
            $user,
            self::DAILY_STREAK,
            'daily_streak:' . $user->id . ':' . $date,
            self::pointsForStreak($streakDays)
        );
    }

    public function award(User $user, string $action, string $source, ?int $points = null): int
    {
        $points = $points ?? (self::AMOUNTS[$action] ?? 0);
        $source = trim($source);
        if ($points <= 0 || $source === '' || !$user->id || !$this->ready()) {
            return 0;
        }

        try {
            return DB::transaction(function () use ($user, $action, $source, $points) {
                if (UserPointAward::where('source', $source)->exists()) {
                    return 0;
                }

                if (in_array($action, self::QURAN_ACTIONS, true)) {
                    $today = UserPointAward::where('user_id', $user->id)
                        ->whereIn('action', self::QURAN_ACTIONS)
                        ->where('created_at', '>=', now()->startOfDay())
                        ->count();
                    if ($today >= self::DAILY_QURAN_LIMIT) {
                        return 0;
                    }
                }

                UserPointAward::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'points' => $points,
                    'source' => $source,
                ]);

                $user->increment('contribution_points', $points);
                $user->increment('total_score', $points);
                $this->addRewardBalance($user, $points);

                return $points;
            });
        } catch (UniqueConstraintViolationException) {
            return 0;
        } catch (\Throwable $e) {
            Log::warning('Contribution points award failed: ' . $e->getMessage());

            return 0;
        }
    }

    public function forResearch(User $user, string $category, int $contributionId): int
    {
        $action = $category === 'video' ? self::QURAN_VIDEO : self::QURAN_CONNECTION;

        return $this->award($user, $action, 'quran_contribution:' . $contributionId);
    }

    public function forLensAnalysis(User $user, int $analysisId): int
    {
        return $this->award($user, self::QURAN_CONNECTION, 'lens_analysis:' . $analysisId);
    }

    public function forConnectionLink(User $user, string $linkType, int $verseId, string $title): int
    {
        $source = 'quran_link:' . $linkType . ':' . $verseId . ':' . sha1(mb_strtolower(trim($title)));

        return $this->award($user, self::QURAN_CONNECTION, $source);
    }

    /**
     * One signed-in feedback note pays once. These points stay off the quiz
     * score and wait with the rest of the reward balance.
     */
    public function forAppFeedback(User $user): int
    {
        $points = self::APP_FEEDBACK_POINTS;
        $source = 'app_feedback:' . $user->id;
        if ($points <= 0 || !$user->id || !$this->ready() || !$this->rewardReady()) {
            return 0;
        }

        try {
            return DB::transaction(function () use ($user, $source, $points) {
                if (UserPointAward::where('source', $source)->exists()) {
                    return 0;
                }

                UserPointAward::create([
                    'user_id' => $user->id,
                    'action' => self::APP_FEEDBACK,
                    'points' => $points,
                    'source' => $source,
                ]);
                $this->addRewardBalance($user, $points);

                return $points;
            });
        } catch (UniqueConstraintViolationException) {
            return 0;
        } catch (\Throwable $e) {
            Log::warning('Feedback reward failed: ' . $e->getMessage());

            return 0;
        }
    }

    public static function rewardNote(int $points): string
    {
        if ($points <= 0) {
            return '';
        }

        $word = $points === 1 ? 'point' : 'points';

        return 'You earned ' . $points . ' ' . $word . '. They stay on your account for events and courses.';
    }

    public static function note(int $points): string
    {
        if ($points <= 0) {
            return '';
        }

        return ' You earned ' . $points . ' ' . ($points === 1 ? 'point' : 'points') . '.';
    }

    private function addRewardBalance(User $user, int $points): void
    {
        if ($points > 0 && $this->rewardReady()) {
            $user->increment('reward_points', $points);
        }
    }

    private function rewardReady(): bool
    {
        try {
            return Schema::hasColumn('users', 'reward_points');
        } catch (\Throwable) {
            return false;
        }
    }

    private function ready(): bool
    {
        try {
            return Schema::hasTable('user_point_awards')
                && Schema::hasColumn('users', 'contribution_points');
        } catch (\Throwable) {
            return false;
        }
    }
}
