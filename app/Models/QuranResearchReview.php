<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuranResearchReview extends Model
{
    protected $table = 'quran_research_reviews';

    protected $fillable = [
        'contribution_id',
        'user_id',
        'vote',
        'feedback',
    ];

    public function contribution()
    {
        return $this->belongsTo(QuranResearchContribution::class, 'contribution_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
