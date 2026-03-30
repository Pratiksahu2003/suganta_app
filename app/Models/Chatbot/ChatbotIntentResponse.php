<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotIntentResponse extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_intent_responses';

    protected $fillable = [
        'intent_id',
        'response',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ── Relationships ────────────────────── */

    public function intent(): BelongsTo
    {
        return $this->belongsTo(ChatbotIntent::class, 'intent_id');
    }

    /* ── Scopes ───────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
