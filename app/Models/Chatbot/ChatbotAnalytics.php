<?php

namespace App\Models\Chatbot;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotAnalytics extends Model
{
    use HasFactory;

    protected $connection = 'ai_mysql';

    protected $table = 'chatbot_analytics';

    protected $fillable = [
        'date',
        'platform',
        'total_messages_received',
        'total_messages_sent',
        'unique_users',
        'new_users',
        'keyword_matches',
        'faq_matches',
        'intent_matches',
        'ai_fallbacks',
        'no_matches',
        'avg_response_time_ms',
        'leads_captured',
    ];

    protected $casts = [
        'date'                 => 'date',
        'avg_response_time_ms' => 'float',
    ];

    /* ── Scopes ───────────────────────────── */

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /* ── Helpers ──────────────────────────── */

    /**
     * Get or create today's analytics record for a platform.
     */
    public static function forToday(string $platform = 'all'): static
    {
        return static::firstOrCreate(
            ['date' => now()->toDateString(), 'platform' => $platform],
            [
                'total_messages_received' => 0,
                'total_messages_sent'     => 0,
                'unique_users'            => 0,
                'new_users'               => 0,
                'keyword_matches'         => 0,
                'faq_matches'             => 0,
                'intent_matches'          => 0,
                'ai_fallbacks'            => 0,
                'no_matches'              => 0,
                'avg_response_time_ms'    => 0,
                'leads_captured'          => 0,
            ]
        );
    }

    /**
     * Increment a specific counter for today.
     */
    public static function incrementToday(string $column, string $platform = 'all', int $amount = 1): void
    {
        $analytics = static::forToday($platform);
        $analytics->increment($column, $amount);

        // Also increment the "all" aggregate if platformspecific
        if ($platform !== 'all') {
            $allAnalytics = static::forToday('all');
            $allAnalytics->increment($column, $amount);
        }
    }
}
