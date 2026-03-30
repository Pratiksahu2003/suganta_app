<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatbotIntent extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_intents';

    protected $fillable = [
        'name',
        'description',
        'confidence_threshold',
        'is_active',
    ];

    protected $casts = [
        'confidence_threshold' => 'float',
        'is_active'            => 'boolean',
    ];

    /* ── Relationships ────────────────────── */

    public function keywords(): HasMany
    {
        return $this->hasMany(ChatbotIntentKeyword::class, 'intent_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ChatbotIntentResponse::class, 'intent_id');
    }

    /* ── Scopes ───────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* ── Helpers ──────────────────────────── */

    public function getBestResponse(): ?string
    {
        $response = $this->responses()
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->first();

        return $response?->response;
    }
}
