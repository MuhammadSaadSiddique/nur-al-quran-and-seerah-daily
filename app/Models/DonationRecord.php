<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DonationRecord extends Model
{
    use HasFactory;

    protected $table = 'donation_records';

    protected $fillable = [
        'user_id',
        'donor_name',
        'is_anonymous',
        'donor_email',
        'amount',
        'currency',
        'payment_method',
        'transaction_reference',
        'message',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the public-facing donor name (respects the donor's choice to hide their name).
     */
    public function getPublicDonorNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous Supporter (فاعل خير)';
        }

        if ($this->donor_name) {
            return $this->donor_name;
        }

        if ($this->user) {
            return $this->user->display_name ?: $this->user->name;
        }

        return 'Anonymous Supporter (فاعل خير)';
    }

    /**
     * Get the internal administrative name representation.
     */
    public function getAdministrativeDonorNameAttribute(): string
    {
        $rawName = $this->donor_name ?: ($this->user?->display_name ?: $this->user?->name ?: 'Anonymous');
        if ($this->is_anonymous) {
            return $rawName . ' [Anonymous Requested]';
        }
        return $rawName;
    }
}
