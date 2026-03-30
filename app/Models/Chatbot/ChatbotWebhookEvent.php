<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotWebhookEvent extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_webhook_events';

    protected $fillable = [
        'platform',
        'event_type',
        'raw_payload',
        'processing_status',
        'error_message',
        'retry_count',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    /* ── Scopes ───────────────────────────── */

    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    /* ── Helpers ──────────────────────────── */

    public function markProcessed(): void
    {
        $this->update(['processing_status' => 'processed']);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'error_message'     => $error,
        ]);
    }

    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }
}
