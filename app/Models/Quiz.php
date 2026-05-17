<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'score',
        'total_questions',
        'difficulty',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMaxScoreAttribute(): int
    {
        if (is_array($this->details) && isset($this->details['questions'])) {
            $maxScore = 0;
            foreach ($this->details['questions'] as $q) {
                $diff = $q['difficulty'] ?? $this->difficulty;
                $points = 5;
                if ($diff === 'Medium') $points = 10;
                if ($diff === 'Hard') $points = 15;
                $maxScore += $points;
            }
            return $maxScore;
        }

        // Fallback using quiz difficulty if details not set
        $diff = $this->difficulty;
        $points = 5;
        if ($diff === 'Medium') $points = 10;
        if ($diff === 'Hard') $points = 15;
        return $this->total_questions * $points;
    }

    public function getPointsGainedAttribute(): int
    {
        if (is_array($this->details) && isset($this->details['questions'])) {
            $pointsScore = 0;
            foreach ($this->details['questions'] as $i => $q) {
                $ua = $this->details['userAnswers'][$i] ?? -1;
                if ($ua == ($q['correctAnswerIndex'] ?? -1)) {
                    $diff = $q['difficulty'] ?? $this->difficulty;
                    $points = 5;
                    if ($diff === 'Medium') $points = 10;
                    if ($diff === 'Hard') $points = 15;
                    $pointsScore += $points;
                }
            }
            return $pointsScore;
        }

        // Fallback: if stored score is already points-based (greater than total questions), return it
        if ($this->score > $this->total_questions) {
            return $this->score;
        }

        // If stored score is correct count, convert it to points based on difficulty
        $diff = $this->difficulty;
        $points = 5;
        if ($diff === 'Medium') $points = 10;
        if ($diff === 'Hard') $points = 15;
        return $this->score * $points;
    }

    public function getCorrectAnswersCountAttribute(): int
    {
        if (is_array($this->details) && isset($this->details['questions'])) {
            $correctCount = 0;
            foreach ($this->details['questions'] as $i => $q) {
                $ua = $this->details['userAnswers'][$i] ?? -1;
                if ($ua == ($q['correctAnswerIndex'] ?? -1)) {
                    $correctCount++;
                }
            }
            return $correctCount;
        }

        // Fallback if details are not set or not structured:
        // If score is less than or equal to total_questions, it is already the correct answers count!
        if ($this->score <= $this->total_questions) {
            return $this->score;
        }

        // Otherwise, if score is points-based, estimate the correct answers count from difficulty
        $diff = $this->difficulty;
        $points = 5;
        if ($diff === 'Medium') $points = 10;
        if ($diff === 'Hard') $points = 15;
        return (int) round($this->score / $points);
    }
}
