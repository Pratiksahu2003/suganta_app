<?php

namespace App\Services;

use App\Services\GeminiAiService;
use App\Services\GrokAiService;

class AiAdviserService
{
    public function __construct(
        protected GeminiAiService $gemini,
        protected GrokAiService $grok,
    ) {
    }

    /**
     * Provider-agnostic entry point for the AI adviser.
     *
     * @return array{text:string,sections:array,usage:array|null,raw:array}
     */
    public function generateReply(string $prompt, array $history = []): array
    {
        $provider = env('AI_ADVISER_PROVIDER', 'gemini'); // 'gemini' or 'grok'

        if ($provider === 'grok') {
            return $this->grok->generateReply($prompt, $history);
        }

        return $this->gemini->generateReply($prompt, $history);
    }

    /**
     * Expose section-building so controller can format
     * historical assistant messages consistently.
     */
    public function buildSections(string $text): array
    {
        return $this->gemini->buildSections($text);
    }
}

