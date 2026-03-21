<?php

namespace App\Models\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageReaction extends Model
{
    protected $connection = 'ai_mysql';

    protected $table = 'chat_message_reactions';

    protected $fillable = [
        'message_id',
        'user_id',
        'reaction',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }
}
