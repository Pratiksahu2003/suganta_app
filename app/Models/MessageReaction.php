<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'user_id',
        'reaction'
    ];

    /**
     * Get the message this reaction belongs to
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who reacted
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Valid reaction types
     */
    public static function getValidReactions(): array
    {
        return ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
    }

    /**
     * Add or update a reaction
     */
    public static function addReaction($messageId, $userId, $reaction): self
    {
        return self::updateOrCreate(
            ['message_id' => $messageId, 'user_id' => $userId],
            ['reaction' => $reaction]
        );
    }

    /**
     * Remove a reaction
     */
    public static function removeReaction($messageId, $userId): bool
    {
        return self::where('message_id', $messageId)
                   ->where('user_id', $userId)
                   ->delete() > 0;
    }
}
