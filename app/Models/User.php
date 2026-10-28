<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Attributes set via forceFill on create (never mass-assignable from request fill).
     */
    private const FORCE_FILL_ON_CREATE = [
        'is_admin',
        'is_researcher',
        'quran_access_token',
        'quran_refresh_token',
        'expert_category_id',
        'total_score',
        'total_questions',
        'seerah_read_count',
        'quran_history_read_count',
        'seerah_quiz_count',
        'completed_paras',
        'para_mastery',
        'difficulty_stats',
        'bookmarked_questions',
        'seerah_quiz_best_score',
        'streak_count',
        'longest_streak',
        'last_streak_date',
        'streak_history',
    ];

    /**
     * Create a user, applying non-fillable fields via forceFill.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function create(array $attributes = []): static
    {
        $forced = Arr::only($attributes, self::FORCE_FILL_ON_CREATE);
        $attributes = Arr::except($attributes, self::FORCE_FILL_ON_CREATE);

        /** @var static $user */
        $user = static::query()->create($attributes);

        if ($forced !== []) {
            $user->forceFill($forced)->save();
        }

        return $user;
    }

    protected $fillable = [
        'name',
        'display_name',
        'email',
        'password',
        'quran_user_id',
        // Role flags, tokens, scores, and expert category are NOT fillable.
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'quran_access_token',
        'quran_refresh_token',
    ];

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if (!$user->isDirty('is_admin') && !$user->isDirty('is_researcher')) {
                return;
            }

            // Allow factories, seeders, artisan, and tests to set roles.
            if (app()->runningInConsole() || app()->runningUnitTests()) {
                return;
            }

            $actor = Auth::user();
            if (!$actor || !$actor->is_admin) {
                $user->is_admin = (bool) $user->getOriginal('is_admin');
                $user->is_researcher = (bool) $user->getOriginal('is_researcher');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_streak_date' => 'date',
            'streak_history' => 'array',
            'completed_paras' => 'array',
            'para_mastery' => 'array',
            'difficulty_stats' => 'array',
            'bookmarked_questions' => 'array',
            'seerah_quiz_best_score' => 'array',
            'is_admin' => 'boolean',
            'is_researcher' => 'boolean',
            'expert_category_id' => 'integer',
        ];
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class)->orderBy('created_at', 'desc');
    }

    public function dailyEngagements()
    {
        return $this->hasMany(UserDailyEngagement::class);
    }

    public function expertCategory()
    {
        return $this->belongsTo(ScienceCategory::class, 'expert_category_id');
    }

    public function researcherOnboarding()
    {
        return $this->hasOne(ResearcherOnboarding::class);
    }

    /**
     * True after the user has submitted onboarding and accepted the authenticity pledge.
     * This does not by itself grant researcher access.
     */
    public function hasAcceptedResearcherPledge(): bool
    {
        if ($this->relationLoaded('researcherOnboarding')) {
            return $this->researcherOnboarding !== null && $this->researcherOnboarding->pledged_at !== null;
        }

        return $this->researcherOnboarding()->whereNotNull('pledged_at')->exists();
    }

    public function researchContributions()
    {
        return $this->hasMany(QuranResearchContribution::class);
    }

    public function researchReviews()
    {
        return $this->hasMany(QuranResearchReview::class);
    }

    public function getMaxPossibleScoreAttribute(): int
    {
        if (!$this->difficulty_stats || !isset($this->difficulty_stats['total'])) {
            return $this->total_questions > 0 ? $this->total_questions * 5 : 0;
        }

        $easyTotal = $this->difficulty_stats['total']['Easy'] ?? 0;
        $mediumTotal = $this->difficulty_stats['total']['Medium'] ?? 0;
        $hardTotal = $this->difficulty_stats['total']['Hard'] ?? 0;

        // Insight questions give 1 point each. We calculate them as the remaining questions.
        $insightTotal = max(0, $this->total_questions - ($easyTotal + $mediumTotal + $hardTotal));

        return ($easyTotal * 5) + ($mediumTotal * 10) + ($hardTotal * 15) + $insightTotal;
    }

    public function getAccuracyAttribute(): int
    {
        $maxPossible = $this->max_possible_score;
        if ($maxPossible <= 0) {
            return 0;
        }
        $accuracy = (int) round(($this->total_score / $maxPossible) * 100);
        return min(100, max(0, $accuracy));
    }

    public function getSpiritualLevelAttribute(): string
    {
        if ($this->total_questions > 50)
            return 'Knowledge Seeker';
        if ($this->total_questions > 10)
            return 'Aspirant';
        return 'Novice';
    }

    public function checkInStreak(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $lastDate = null;
        if ($this->last_streak_date instanceof \DateTimeInterface) {
            $lastDate = $this->last_streak_date->format('Y-m-d');
        } elseif (is_string($this->last_streak_date) && !empty($this->last_streak_date)) {
            $lastDate = substr($this->last_streak_date, 0, 10);
        }

        $alreadyCheckedInToday = ($lastDate === $today);

        if ($alreadyCheckedInToday) {
            return [
                'status' => 'already_checked_in',
                'streak_count' => (int) ($this->streak_count ?? 1),
                'longest_streak' => (int) ($this->longest_streak ?? 1),
                'last_streak_date' => $today,
                'is_active_today' => true,
                'message' => 'Streak already maintained for today!'
            ];
        }

        if ($lastDate === $yesterday) {
            $this->streak_count = (int) ($this->streak_count ?? 0) + 1;
        } else {
            $this->streak_count = 1;
        }

        if ($this->streak_count > (int) ($this->longest_streak ?? 0)) {
            $this->longest_streak = $this->streak_count;
        }

        $this->last_streak_date = $today;

        $history = is_array($this->streak_history) ? $this->streak_history : [];
        if (!in_array($today, $history)) {
            $history[] = $today;
            if (count($history) > 60) {
                $history = array_slice($history, -60);
            }
            $this->streak_history = $history;
        }

        try {
            $this->save();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Streak persistence error: ' . $e->getMessage());
        }

        return [
            'status' => 'success',
            'streak_count' => (int) $this->streak_count,
            'longest_streak' => (int) $this->longest_streak,
            'last_streak_date' => $today,
            'is_active_today' => true,
            'message' => 'Daily streak increased! Keep up your devotion.'
        ];
    }

    public function getStreakStatus(): array
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $lastDate = null;
        if ($this->last_streak_date instanceof \DateTimeInterface) {
            $lastDate = $this->last_streak_date->format('Y-m-d');
        } elseif (is_string($this->last_streak_date) && !empty($this->last_streak_date)) {
            $lastDate = substr($this->last_streak_date, 0, 10);
        }

        $isActiveToday = ($lastDate === $today);

        // Effective streak is 0 if last activity was before yesterday and not checked in today
        $effectiveStreak = (int) ($this->streak_count ?? 0);
        if ($lastDate && $lastDate < $yesterday && !$isActiveToday) {
            $effectiveStreak = 0;
        }

        return [
            'current_streak' => $effectiveStreak,
            'longest_streak' => (int) ($this->longest_streak ?? 0),
            'is_active_today' => $isActiveToday,
            'last_streak_date' => $lastDate,
            'streak_history' => is_array($this->streak_history) ? $this->streak_history : []
        ];
    }

    /**
     * Get list of completed Surah numbers (1-114).
     */
    public function getCompletedSurahs(): array
    {
        $mastery = is_array($this->para_mastery) ? $this->para_mastery : [];
        return array_map('intval', $mastery['completed_surahs'] ?? []);
    }

    /**
     * Toggle read status of a Surah (1-114).
     */
    public function toggleCompletedSurah(int $surahNumber): array
    {
        $mastery = is_array($this->para_mastery) ? $this->para_mastery : [];
        $surahs = array_map('intval', $mastery['completed_surahs'] ?? []);
        $isRead = in_array($surahNumber, $surahs);

        if ($isRead) {
            $surahs = array_values(array_diff($surahs, [$surahNumber]));
            $status = 'unmarked';
        } else {
            $surahs[] = $surahNumber;
            sort($surahs);
            $status = 'marked';
        }

        $mastery['completed_surahs'] = $surahs;
        $this->para_mastery = $mastery;
        $this->save();

        if ($status === 'marked') {
            app(\App\Services\DailyEngagementService::class)->record($this, UserDailyEngagement::ACTIVITY_READ);
        }

        return [
            'status' => $status,
            'is_read' => !$isRead,
            'surah_number' => $surahNumber,
            'total_completed' => count($surahs),
        ];
    }

    /**
     * Toggle read/completed status of a Para (1-30).
     */
    public function toggleCompletedPara(int $paraNumber): array
    {
        $completed = array_map('intval', is_array($this->completed_paras) ? $this->completed_paras : []);
        $isDone = in_array($paraNumber, $completed);

        if ($isDone) {
            $completed = array_values(array_diff($completed, [$paraNumber]));
            $status = 'unmarked';
        } else {
            $completed[] = $paraNumber;
            sort($completed);
            $status = 'marked';
        }

        $this->completed_paras = $completed;
        $this->save();

        if ($status === 'marked') {
            app(\App\Services\DailyEngagementService::class)->record($this, UserDailyEngagement::ACTIVITY_READ);
        }

        return [
            'status' => $status,
            'is_completed' => !$isDone,
            'para_number' => $paraNumber,
            'total_completed' => count($completed),
        ];
    }

    /**
     * Mark an insight (Seerah or Quran History) as read.
     * Requires a stable insight_id so counts cannot be farmed with empty IDs.
     */
    public function markInsightRead(string $type, ?string $insightId = null): array
    {
        $insightId = is_string($insightId) ? trim($insightId) : '';
        if ($insightId === '') {
            return [
                'success' => false,
                'already_read' => false,
                'type' => $type,
                'seerah_read_count' => (int) $this->seerah_read_count,
                'quran_history_read_count' => (int) $this->quran_history_read_count,
                'error' => 'insight_id is required.',
            ];
        }

        $mastery = is_array($this->para_mastery) ? $this->para_mastery : [];
        $readInsights = $mastery['read_insights'] ?? [];
        $key = strtoupper($type) . ':' . $insightId;
        $alreadyRead = in_array($key, $readInsights, true);

        if (!$alreadyRead) {
            $readInsights[] = $key;
            $mastery['read_insights'] = $readInsights;
            $this->para_mastery = $mastery;

            if (strtoupper($type) === 'SEERAH') {
                $this->seerah_read_count = (int) ($this->seerah_read_count ?? 0) + 1;
            } elseif (strtoupper($type) === 'QURAN_HISTORY') {
                $this->quran_history_read_count = (int) ($this->quran_history_read_count ?? 0) + 1;
            }

            $this->save();
            app(\App\Services\DailyEngagementService::class)->record($this, UserDailyEngagement::ACTIVITY_INSIGHT);
        }

        return [
            'success' => true,
            'already_read' => $alreadyRead,
            'type' => $type,
            'seerah_read_count' => (int) $this->seerah_read_count,
            'quran_history_read_count' => (int) $this->quran_history_read_count,
        ];
    }

    /**
     * Check if an insight has already been marked as read.
     */
    public function isInsightRead(string $type, ?string $insightId = null): bool
    {
        if (!$insightId) return false;
        $mastery = is_array($this->para_mastery) ? $this->para_mastery : [];
        $readInsights = $mastery['read_insights'] ?? [];
        return in_array(strtoupper($type) . ':' . $insightId, $readInsights);
    }
}
