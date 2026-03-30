<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotFaq extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_faqs';

    protected $fillable = [
        'question',
        'answer',
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

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /* ── Helpers ──────────────────────────── */

    public function incrementHitCount(): void
    {
        $this->increment('hit_count');
    }
}
