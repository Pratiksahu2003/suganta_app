<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessageLog extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_message_logs';

    protected $fillable = [
        'chatbot_user_id',
        'conversation_id',
        'platform',
        'event_type',
        'payload',
        'processing_status',
        'error_message',
        'processing_time_ms',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /* ── Relationships ────────────────────── */

    public function chatbotUser(): BelongsTo
    {
        return $this->belongsTo(ChatbotUser::class, 'chatbot_user_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    /* ── Scopes ───────────────────────────── */

    public function scopeErrors($query)
    {
        return $query->where('processing_status', 'failed');
    }

    public function scopeByEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }
}
