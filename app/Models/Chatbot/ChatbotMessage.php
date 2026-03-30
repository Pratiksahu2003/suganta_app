<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_messages';

    protected $fillable = [
        'conversation_id',
        'chatbot_user_id',
        'direction',
        'message_type',
        'content',
        'raw_payload',
        'matched_by',
        'matched_faq_id',
        'matched_intent_id',
        'meta_message_id',
        'delivery_status',
        'response_time_ms',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    /* ── Relationships ────────────────────── */

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }

    public function chatbotUser(): BelongsTo
    {
        return $this->belongsTo(ChatbotUser::class, 'chatbot_user_id');
    }

    public function matchedFaq(): BelongsTo
    {
        return $this->belongsTo(ChatbotFaq::class, 'matched_faq_id');
    }

    public function matchedIntent(): BelongsTo
    {
        return $this->belongsTo(ChatbotIntent::class, 'matched_intent_id');
    }

    /* ── Scopes ───────────────────────────── */

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeByMatchType($query, string $type)
    {
        return $query->where('matched_by', $type);
    }

    /* ── Helpers ──────────────────────────── */

    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    public function markDelivered(): void
    {
        $this->update(['delivery_status' => 'delivered']);
    }

    public function markFailed(): void
    {
        $this->update(['delivery_status' => 'failed']);
    }

    public function markSent(string $metaMessageId = null): void
    {
        $update = ['delivery_status' => 'sent'];
        if ($metaMessageId) {
            $update['meta_message_id'] = $metaMessageId;
        }
        $this->update($update);
    }
}
