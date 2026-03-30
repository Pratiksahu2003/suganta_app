<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotIntentKeyword extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_intent_keywords';

    protected $fillable = [
        'intent_id',
        'keyword',
        'weight',
    ];

    protected $casts = [
        'weight' => 'float',
    ];

    /* ── Relationships ────────────────────── */

    public function intent(): BelongsTo
    {
        return $this->belongsTo(ChatbotIntent::class, 'intent_id');
    }
}
