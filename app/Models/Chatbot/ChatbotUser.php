<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotUser extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_users';

    protected $fillable = [
        'platform_user_id',
        'platform',
        'name',
        'email',
        'phone',
        'profile_pic_url',
        'locale',
        'metadata',
        'is_blocked',
        'block_reason',
        'blocked_at',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'metadata'      => 'array',
        'is_blocked'    => 'boolean',
        'blocked_at'    => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    /* ── Relationships ────────────────────── */

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class, 'chatbot_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'chatbot_user_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(ChatbotLead::class, 'chatbot_user_id');
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(ChatbotMessageLog::class, 'chatbot_user_id');
    }

    /* ── Scopes ───────────────────────────── */

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function scopeNotBlocked($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /* ── Helpers ──────────────────────────── */

    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    public function block(string $reason = null): void
    {
        $this->update([
            'is_blocked'   => true,
            'block_reason' => $reason,
            'blocked_at'   => now(),
        ]);
    }

    public function unblock(): void
    {
        $this->update([
            'is_blocked'   => false,
            'block_reason' => null,
            'blocked_at'   => null,
        ]);
    }

    public function touchLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
