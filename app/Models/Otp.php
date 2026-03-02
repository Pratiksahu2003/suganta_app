<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = [
        'identifier',
        'otp',
        'type',
        'expires_at',
        'is_used',
        'used_at',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Check if OTP is valid (not expired and not used)
     */
    public function isValid(): bool
    {
        return !$this->is_used && $this->expires_at->isFuture();
    }

    /**
     * Mark OTP as used
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
        ]);
    }

    /**
     * Scope to get valid OTPs
     */
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get OTPs by identifier and type
     */
    public function scopeForIdentifier($query, string $identifier, string $type = 'login')
    {
        return $query->where('identifier', $identifier)
                    ->where('type', $type);
    }

    /**
     * Clean up expired OTPs (can be called via scheduled task)
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now())
                   ->orWhere(function($query) {
                       $query->where('is_used', true)
                             ->where('used_at', '<', now()->subDays(7));
                   })
                   ->delete();
    }
}
