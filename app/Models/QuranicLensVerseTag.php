<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranicLensVerseTag extends Model
{
    use SoftDeletes;

    protected $table = 'quranic_lens_verse_tags';

    protected $fillable = [
        'user_id',
        'chapter_number',
        'verse_number',
        'tag_type',
        'tag_value',
        'explanation',
        'status',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'moderated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
