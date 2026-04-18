<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

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
        ];
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class)->orderBy('created_at', 'desc');
    }

    public function getAccuracyAttribute(): int
    {
        return $this->total_questions > 0
            ? (int) round(($this->total_score / $this->total_questions) * 100)
            : 0;
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
