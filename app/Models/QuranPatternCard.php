<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranPatternCard extends Model
{
    protected $fillable = [
        'verse_id',
        'focus_token',
        'gloss',
        'arabic_term',
        'ring_note',
        'linguistic_note',
        'topics',
        'math',
    ];

    protected $casts = [
        'topics' => 'array',
        'math' => 'array',
    ];
}
