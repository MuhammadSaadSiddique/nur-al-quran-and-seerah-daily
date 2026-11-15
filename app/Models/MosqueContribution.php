<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MosqueContribution extends Model
{
    public const TYPE_ADDED = 'added';
    public const TYPE_TIMES_UPDATED = 'times_updated';
    public const TYPE_CONFIRMED = 'confirmed';
    public const TYPE_REPORTED = 'reported';
    public const TYPE_DETAILS_UPDATED = 'details_updated';
    public const TYPE_SECT_PROPOSED = 'sect_proposed';

    protected $fillable = [
        'mosque_id',
        'user_id',
        'type',
        'payload',
        'note',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
