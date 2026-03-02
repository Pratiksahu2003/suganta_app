<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blocked_user_id',
        'reason',
        'blocked_at'
    ];

    protected $casts = [
        'blocked_at' => 'datetime'
    ];

    /**
     * Get the user who blocked
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the blocked user
     */
    public function blockedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_user_id');
    }

    /**
     * Check if a user is blocked by another user
     */
    public static function isBlocked($userId, $blockedUserId): bool
    {
        return self::where('user_id', $userId)
                   ->where('blocked_user_id', $blockedUserId)
                   ->exists();
    }

    /**
     * Block a user
     */
    public static function blockUser($userId, $blockedUserId, $reason = null): self
    {
        return self::create([
            'user_id' => $userId,
            'blocked_user_id' => $blockedUserId,
            'reason' => $reason,
            'blocked_at' => now()
        ]);
    }

    /**
     * Unblock a user
     */
    public static function unblockUser($userId, $blockedUserId): bool
    {
        return self::where('user_id', $userId)
                   ->where('blocked_user_id', $blockedUserId)
                   ->delete() > 0;
    }
}
