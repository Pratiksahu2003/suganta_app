<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotLead extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_leads';

    protected $fillable = [
        'chatbot_user_id',
        'conversation_id',
        'name',
        'email',
        'phone',
        'source',
        'interest',
        'extra_data',
        'status',
    ];

    protected $casts = [
        'extra_data' => 'array',
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

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
