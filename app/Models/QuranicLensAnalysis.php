<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranicLensAnalysis extends Model
{
    use SoftDeletes;

    protected $table = 'quranic_lens_analyses';

    protected $fillable = [
        'user_id',
        'chapter_number',
        'verse_number',
        'lens_type',
        'title',
        'content',
        'status',
        'moderated_by',
        'moderated_at',
        'rejection_reason',
        'theme_id',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
