<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'verified',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified' => 'boolean',
    ];

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP is valid (not expired and not verified)
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->verified;
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): bool
    {
        $this->verified = true;
        return $this->save();
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Scope to get valid OTPs for a phone number
     */
    public function scopeValidForPhone($query, string $phone)
    {
        return $query->where('phone', $phone)
                    ->where('verified', false)
                    ->where('expires_at', '>', Carbon::now())
                    ->orderBy('created_at', 'desc');
    }
}
