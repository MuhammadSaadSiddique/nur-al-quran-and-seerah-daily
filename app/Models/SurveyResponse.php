<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_key',
        'user_id',
        'answers',
        'nps',
        'wish',
        'name',
        'email',
        'notify',
        'source',
        'duration_seconds',
    ];

    protected $casts = [
        'answers' => 'array',
        'nps' => 'integer',
        'notify' => 'boolean',
        'duration_seconds' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
