<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratedQuestion extends Model
{
    protected $fillable = [
        'question_id',
        'type',
        'source_info',
        'difficulty',
        'theme',
        'text',
        'options',
        'correct_answer_index',
        'explanation',
        'reference',
        'times_answered',
        'times_correct',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function getAccuracyPercentAttribute(): int
    {
        return $this->times_answered > 0
            ? (int) round(($this->times_correct / $this->times_answered) * 100)
            : 0;
    }
}
