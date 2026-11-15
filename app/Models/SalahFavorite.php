<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** The mosque a user prays a given salah at. One row per user per prayer. */
class SalahFavorite extends Model
{
    protected $fillable = [
        'user_id',
        'prayer',
        'mosque_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mosque(): BelongsTo
    {
        return $this->belongsTo(Mosque::class);
    }
}
