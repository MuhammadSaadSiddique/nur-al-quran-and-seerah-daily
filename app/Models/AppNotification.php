<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'app_notifications';

    protected $fillable = [
        'title',
        'message',
        'type',
        'action_url',
        'data',
        'sent_by',
        'send_push',
        'send_email',
        'delivered_devices_count',
    ];

    protected $casts = [
        'data' => 'array',
        'send_push' => 'boolean',
        'send_email' => 'boolean',
        'delivered_devices_count' => 'integer',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
