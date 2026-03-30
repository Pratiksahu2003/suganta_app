<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotConversation extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_conversations';

    protected $fillable = [
        'chatbot_user_id',
        'platform',
        'status',
        'assigned_admin_id',
        'subject',
        'message_count',
        'last_message_at',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at'       => 'datetime',
    ];

    /* ── Relationships ────────────────────── */

    public function chatbotUser(): BelongsTo
    {
        return $this->belongsTo(ChatbotUser::class, 'chatbot_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(ChatbotLead::class, 'conversation_id');
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(ChatbotMessageLog::class, 'conversation_id');
    }

    /* ── Scopes ───────────────────────────── */

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['bot', 'human']);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /* ── Helpers ──────────────────────────── */

    public function isBotControlled(): bool
    {
        return $this->status === 'bot';
    }

    public function isHumanControlled(): bool
    {
        return $this->status === 'human';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function takeoverByHuman(int $adminId): void
    {
        $this->update([
            'status'           => 'human',
            'assigned_admin_id' => $adminId,
        ]);
    }

    public function releaseToBot(): void
    {
        $this->update([
            'status'           => 'bot',
            'assigned_admin_id' => null,
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
        $this->update(['last_message_at' => now()]);
    }
}
