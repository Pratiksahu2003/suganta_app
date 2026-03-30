<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotKeyword extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_keywords';

    protected $fillable = [
        'keyword',
        'response',
        'category',
        'priority',
        'is_active',
        'hit_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ── Scopes ───────────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* ── Helpers ──────────────────────────── */

    public function incrementHitCount(): void
    {
        $this->increment('hit_count');
    }
}
