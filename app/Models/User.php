<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'display_name',
        'email',
        'password',
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
        'quran_user_id',
        'quran_access_token',
        'quran_refresh_token',
        'is_admin',
        'is_researcher',
        'expert_category_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
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

    public function expertCategory()
    {
        return $this->belongsTo(ScienceCategory::class, 'expert_category_id');
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
}
