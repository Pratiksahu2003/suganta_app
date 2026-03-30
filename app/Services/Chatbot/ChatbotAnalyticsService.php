<?php

namespace App\Services\Chatbot;

use App\Models\Chatbot\ChatbotAnalytics;
use App\Models\Chatbot\ChatbotConversation;
use App\Models\Chatbot\ChatbotMessage;
use App\Models\Chatbot\ChatbotUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ChatbotAnalyticsService
{
    /**
     * Get dashboard summary data.
     */
    public function getDashboard(string $period = '7d', ?string $platform = null): array
    {
        $dateRange = $this->getDateRange($period);

        $query = ChatbotAnalytics::whereBetween('date', [$dateRange['from'], $dateRange['to']]);

        if ($platform) {
            $query->where('platform', $platform);
        } else {
            $query->where('platform', 'all');
        }

        $analytics = $query->orderBy('date')->get();

        // Current period totals
        $totals = [
            'total_messages_received' => $analytics->sum('total_messages_received'),
            'total_messages_sent'     => $analytics->sum('total_messages_sent'),
            'unique_users'            => $analytics->sum('unique_users'),
            'new_users'               => $analytics->sum('new_users'),
            'keyword_matches'         => $analytics->sum('keyword_matches'),
            'faq_matches'             => $analytics->sum('faq_matches'),
            'intent_matches'          => $analytics->sum('intent_matches'),
            'ai_fallbacks'            => $analytics->sum('ai_fallbacks'),
            'no_matches'              => $analytics->sum('no_matches'),
            'leads_captured'          => $analytics->sum('leads_captured'),
            'avg_response_time_ms'    => $analytics->avg('avg_response_time_ms'),
        ];

        // Active conversations
        $activeConversations = ChatbotConversation::active()->count();
        $humanControlled     = ChatbotConversation::byStatus('human')->count();

        // Match distribution
        $totalMatches = $totals['keyword_matches'] + $totals['faq_matches']
            + $totals['intent_matches'] + $totals['ai_fallbacks'] + $totals['no_matches'];

        $matchDistribution = [];
        if ($totalMatches > 0) {
            $matchDistribution = [
                'keyword' => round(($totals['keyword_matches'] / $totalMatches) * 100, 1),
                'faq'     => round(($totals['faq_matches'] / $totalMatches) * 100, 1),
                'intent'  => round(($totals['intent_matches'] / $totalMatches) * 100, 1),
                'ai'      => round(($totals['ai_fallbacks'] / $totalMatches) * 100, 1),
                'none'    => round(($totals['no_matches'] / $totalMatches) * 100, 1),
            ];
        }

        return [
            'period'               => $period,
            'date_range'           => $dateRange,
            'totals'               => $totals,
            'active_conversations' => $activeConversations,
            'human_controlled'     => $humanControlled,
            'match_distribution'   => $matchDistribution,
            'daily_chart'          => $analytics->map(fn ($a) => [
                'date'     => $a->date->format('Y-m-d'),
                'received' => $a->total_messages_received,
                'sent'     => $a->total_messages_sent,
                'users'    => $a->unique_users,
                'leads'    => $a->leads_captured,
            ])->values(),
        ];
    }

    /**
     * Get top FAQs by hit count.
     */
    public function getTopFaqs(int $limit = 10): array
    {
        return DB::connection('ai_mysql')
            ->table('chatbot_faqs')
            ->where('is_active', true)
            ->orderByDesc('hit_count')
            ->limit($limit)
            ->get(['id', 'question', 'category', 'hit_count'])
            ->toArray();
    }

    /**
     * Get top keywords by hit count.
     */
    public function getTopKeywords(int $limit = 10): array
    {
        return DB::connection('ai_mysql')
            ->table('chatbot_keywords')
            ->where('is_active', true)
            ->orderByDesc('hit_count')
            ->limit($limit)
            ->get(['id', 'keyword', 'category', 'hit_count'])
            ->toArray();
    }

    /**
     * Get platform breakdown.
     */
    public function getPlatformBreakdown(string $period = '7d'): array
    {
        $dateRange = $this->getDateRange($period);

        return ChatbotAnalytics::whereBetween('date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('platform', ['instagram', 'messenger'])
            ->select('platform')
            ->selectRaw('SUM(total_messages_received) as messages')
            ->selectRaw('SUM(unique_users) as users')
            ->selectRaw('SUM(leads_captured) as leads')
            ->groupBy('platform')
            ->get()
            ->toArray();
    }

    /**
     * Recalculate unique users for today (called periodically).
     */
    public function recalculateUniqueUsersToday(): void
    {
        $today = now()->toDateString();

        foreach (['instagram', 'messenger', 'all'] as $platform) {
            $query = ChatbotMessage::where('direction', 'incoming')
                ->whereDate('created_at', $today);

            if ($platform !== 'all') {
                $query->whereHas('conversation', fn ($q) => $q->where('platform', $platform));
            }

            $uniqueUsers = $query->distinct('chatbot_user_id')->count('chatbot_user_id');

            $analytics = ChatbotAnalytics::forToday($platform);
            $analytics->update(['unique_users' => $uniqueUsers]);
        }
    }

    /* ── Helpers ──────────────────────────── */

    protected function getDateRange(string $period): array
    {
        $to = Carbon::today();

        $from = match ($period) {
            '1d'  => Carbon::today(),
            '7d'  => Carbon::today()->subDays(6),
            '30d' => Carbon::today()->subDays(29),
            '90d' => Carbon::today()->subDays(89),
            default => Carbon::today()->subDays(6),
        };

        return [
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
        ];
    }
}
