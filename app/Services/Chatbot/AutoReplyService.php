<?php

namespace App\Services\Chatbot;

use App\Models\Chatbot\ChatbotFaq;
use App\Models\Chatbot\ChatbotIntent;
use App\Models\Chatbot\ChatbotIntentKeyword;
use App\Models\Chatbot\ChatbotKeyword;
use App\Services\GeminiAiService;
use App\Services\GrokAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoReplyService
{
    protected string $cachePrefix;
    protected int    $cacheTtl;

    public function __construct()
    {
        $this->cachePrefix = config('chatbot.cache_prefix', 'chatbot:');
        $this->cacheTtl    = config('chatbot.cache_ttl', 3600);
    }

    /**
     * Main pipeline: attempt to find the best reply for a user message.
     *
     * Returns: [
     *   'reply'      => string,
     *   'matched_by' => 'keyword'|'faq'|'intent'|'ai_gemini'|'ai_grok'|'fallback',
     *   'faq_id'     => int|null,
     *   'intent_id'  => int|null,
     * ]
     */
    public function getReply(string $messageText, string $platform = 'messenger'): array
    {
        $normalised = $this->normalise($messageText);

        // ── Step 1: Keyword Match ────────────────
        $keywordResult = $this->matchKeyword($normalised);
        if ($keywordResult) {
            return $keywordResult;
        }

        // ── Step 2: FAQ Match ────────────────────
        $faqResult = $this->matchFaq($normalised, $messageText);
        if ($faqResult) {
            return $faqResult;
        }

        // ── Step 3: Intent Detection ─────────────
        $intentResult = $this->detectIntent($normalised);
        if ($intentResult) {
            return $intentResult;
        }

        // ── Step 4: AI Fallback (Disabled by Request) ────
        // Skipped Gemini/Grok entirely.
        // Falls through directly to Static Fallback.

        // ── Step 5: Static Fallback ──────────────
        return [
            'reply'      => config('chatbot.fallback_message'),
            'matched_by' => 'fallback',
            'faq_id'     => null,
            'intent_id'  => null,
        ];
    }

    /* ══════════════════════════════════════════════
     * Step 1: Keyword Matching
     * ══════════════════════════════════════════════ */

    protected function matchKeyword(string $normalised): ?array
    {
        $keywords = $this->getCachedKeywords();

        // Exact match first
        foreach ($keywords as $kw) {
            if ($normalised === Str::lower($kw['keyword'])) {
                ChatbotKeyword::where('id', $kw['id'])->increment('hit_count');
                return [
                    'reply'      => $kw['response'],
                    'matched_by' => 'keyword',
                    'faq_id'     => null,
                    'intent_id'  => null,
                ];
            }
        }

        // Contains match (by priority)
        foreach ($keywords as $kw) {
            if (Str::contains($normalised, Str::lower($kw['keyword']))) {
                ChatbotKeyword::where('id', $kw['id'])->increment('hit_count');
                return [
                    'reply'      => $kw['response'],
                    'matched_by' => 'keyword',
                    'faq_id'     => null,
                    'intent_id'  => null,
                ];
            }
        }

        return null;
    }

    protected function getCachedKeywords(): array
    {
        return Cache::remember(
            $this->cachePrefix . 'keywords:active',
            $this->cacheTtl,
            fn () => ChatbotKeyword::active()
                ->orderByDesc('priority')
                ->get(['id', 'keyword', 'response'])
                ->toArray()
        );
    }

    /* ══════════════════════════════════════════════
     * Step 2: FAQ Matching (fuzzy text similarity)
     * ══════════════════════════════════════════════ */

    protected function matchFaq(string $normalised, string $originalText): ?array
    {
        $faqs = $this->getCachedFaqs();

        $bestMatch = null;
        $bestScore = 0;
        $threshold = 60; // minimum similarity percentage

        foreach ($faqs as $faq) {
            $faqNormalised = $this->normalise($faq['question']);

            // Check exact containment first
            if (Str::contains($faqNormalised, $normalised) || Str::contains($normalised, $faqNormalised)) {
                ChatbotFaq::where('id', $faq['id'])->increment('hit_count');
                return [
                    'reply'      => $faq['answer'],
                    'matched_by' => 'faq',
                    'faq_id'     => $faq['id'],
                    'intent_id'  => null,
                ];
            }

            // Fuzzy similarity
            $similarity = 0;
            similar_text($normalised, $faqNormalised, $similarity);

            // Also try word overlap
            $wordOverlap = $this->wordOverlapScore($normalised, $faqNormalised);

            $combinedScore = max($similarity, $wordOverlap * 100);

            if ($combinedScore > $bestScore && $combinedScore >= $threshold) {
                $bestScore = $combinedScore;
                $bestMatch = $faq;
            }
        }

        if ($bestMatch) {
            ChatbotFaq::where('id', $bestMatch['id'])->increment('hit_count');
            return [
                'reply'      => $bestMatch['answer'],
                'matched_by' => 'faq',
                'faq_id'     => $bestMatch['id'],
                'intent_id'  => null,
            ];
        }

        return null;
    }

    protected function getCachedFaqs(): array
    {
        return Cache::remember(
            $this->cachePrefix . 'faqs:active',
            $this->cacheTtl,
            fn () => ChatbotFaq::active()
                ->orderByDesc('priority')
                ->get(['id', 'question', 'answer'])
                ->toArray()
        );
    }

    /* ══════════════════════════════════════════════
     * Step 3: Intent Detection (weighted scoring)
     * ══════════════════════════════════════════════ */

    protected function detectIntent(string $normalised): ?array
    {
        $intents = $this->getCachedIntentsWithKeywords();

        $words = preg_split('/\s+/', $normalised);
        $bestIntent = null;
        $bestScore  = 0;

        foreach ($intents as $intent) {
            $totalWeight = 0;
            $matchedWeight = 0;

            foreach ($intent['keywords'] as $ik) {
                $totalWeight += $ik['weight'];
                $ikNorm = Str::lower($ik['keyword']);

                // Check if any word matches or if normalised contains the intent keyword
                foreach ($words as $word) {
                    if ($word === $ikNorm || Str::contains($normalised, $ikNorm)) {
                        $matchedWeight += $ik['weight'];
                        break;
                    }
                }
            }

            if ($totalWeight > 0) {
                $score = $matchedWeight / $totalWeight;

                if ($score >= $intent['confidence_threshold'] && $score > $bestScore) {
                    $bestScore  = $score;
                    $bestIntent = $intent;
                }
            }
        }

        if ($bestIntent) {
            // Get the best response for this intent
            $intentModel = ChatbotIntent::find($bestIntent['id']);
            $response = $intentModel?->getBestResponse();

            if ($response) {
                return [
                    'reply'      => $response,
                    'matched_by' => 'intent',
                    'faq_id'     => null,
                    'intent_id'  => $bestIntent['id'],
                ];
            }
        }

        return null;
    }

    protected function getCachedIntentsWithKeywords(): array
    {
        return Cache::remember(
            $this->cachePrefix . 'intents:active',
            $this->cacheTtl,
            fn () => ChatbotIntent::active()
                ->with('keywords:id,intent_id,keyword,weight')
                ->get(['id', 'name', 'confidence_threshold'])
                ->toArray()
        );
    }

    /* ══════════════════════════════════════════════
     * Step 4: AI Fallback (Gemini or Grok)
     * ══════════════════════════════════════════════ */

    protected function getAiReply(string $messageText): ?array
    {
        $provider = config('chatbot.ai_provider', 'gemini');
        $systemPrompt = config('chatbot.ai_system_prompt');

        try {
            if ($provider === 'grok') {
                return $this->getGrokReply($messageText, $systemPrompt);
            }

            return $this->getGeminiReply($messageText, $systemPrompt);

        } catch (\Exception $e) {
            Log::warning('Chatbot: AI fallback failed', [
                'provider' => $provider,
                'error'    => $e->getMessage(),
            ]);

            // Try the other provider as backup
            try {
                if ($provider === 'grok') {
                    return $this->getGeminiReply($messageText, $systemPrompt);
                }
                return $this->getGrokReply($messageText, $systemPrompt);
            } catch (\Exception $e2) {
                Log::error('Chatbot: Both AI providers failed', ['error' => $e2->getMessage()]);
                return null;
            }
        }
    }

    protected function getGeminiReply(string $messageText, string $systemPrompt): ?array
    {
        $service = app(GeminiAiService::class);

        $result = $service->generateReply($messageText, [
            ['role' => 'user', 'content' => $systemPrompt],
            ['role' => 'model', 'content' => 'Understood. I am SuGanta\'s chatbot assistant. I will keep responses concise and helpful.'],
        ]);

        $text = $result['text'] ?? null;

        if (empty($text)) {
            return null;
        }

        return [
            'reply'      => mb_substr($text, 0, 2000),
            'matched_by' => 'ai_gemini',
            'faq_id'     => null,
            'intent_id'  => null,
        ];
    }

    protected function getGrokReply(string $messageText, string $systemPrompt): ?array
    {
        $service = app(GrokAiService::class);

        $result = $service->generateReply($messageText, [
            ['role' => 'user', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => 'Understood. I am SuGanta\'s chatbot assistant. I will keep responses concise and helpful.'],
        ]);

        $text = $result['text'] ?? null;

        if (empty($text)) {
            return null;
        }

        return [
            'reply'      => mb_substr($text, 0, 2000),
            'matched_by' => 'ai_grok',
            'faq_id'     => null,
            'intent_id'  => null,
        ];
    }

    /* ══════════════════════════════════════════════
     * Helpers
     * ══════════════════════════════════════════════ */

    protected function normalise(string $text): string
    {
        $text = Str::lower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);  // remove punctuation
        $text = preg_replace('/\s+/', ' ', $text);               // collapse whitespace
        return $text;
    }

    protected function wordOverlapScore(string $a, string $b): float
    {
        $wordsA = array_unique(preg_split('/\s+/', $a));
        $wordsB = array_unique(preg_split('/\s+/', $b));

        if (empty($wordsA) || empty($wordsB)) {
            return 0;
        }

        $intersection = array_intersect($wordsA, $wordsB);
        $union = array_unique(array_merge($wordsA, $wordsB));

        return count($intersection) / count($union); // Jaccard similarity
    }

    /**
     * Clear all auto-reply caches (call after admin updates).
     */
    public function clearCache(): void
    {
        Cache::forget($this->cachePrefix . 'keywords:active');
        Cache::forget($this->cachePrefix . 'faqs:active');
        Cache::forget($this->cachePrefix . 'intents:active');
    }
}
