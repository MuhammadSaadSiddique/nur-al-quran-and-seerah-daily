<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppFeedback extends Model
{
    use HasFactory;

    protected $table = 'app_feedbacks';

    protected $fillable = [
        'user_id',
        'rating',
        'category',
        'message',
        'platform',
        'user_name',
        'user_email',
        'device_info',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
