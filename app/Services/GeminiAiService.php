<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeminiAiService
{
    /**
     * @return array{text:string,usage:array|null,raw:array}
     */
    public function generateReply(string $prompt, array $history = []): array
    {
        $apiKey = config('gemini.api_key');
        $modelId = config('gemini.model_id', 'gemini-2.5-flash-lite');
        $maxOutputTokens = (int) config('gemini.max_output_tokens', 500);

        if (empty($apiKey)) {
            throw new Exception('Gemini API key is not configured.');
        }

        $contents = [];

        foreach ($history as $item) {
            if (! isset($item['role'], $item['content'])) {
                continue;
            }

            $geminiRole = ($item['role'] === 'assistant' || $item['role'] === 'model')
                ? 'model'
                : 'user';

            $contents[] = [
                'role' => $geminiRole,
                'parts' => [
                    ['text' => $item['content']],
                ],
            ];
        }

        // Gemini requires the conversation to start with a 'user' role.
        // If history begins with 'model', drop leading model entries.
        while (! empty($contents) && $contents[0]['role'] === 'model') {
            array_shift($contents);
        }

        // Gemini disallows consecutive messages with the same role.
        // Merge any adjacent same-role entries into one.
        $merged = [];
        foreach ($contents as $entry) {
            if (! empty($merged) && end($merged)['role'] === $entry['role']) {
                $lastIdx = array_key_last($merged);
                $merged[$lastIdx]['parts'][0]['text'] .= "\n" . $entry['parts'][0]['text'];
            } else {
                $merged[] = $entry;
            }
        }
        $contents = array_values($merged);

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt],
            ],
        ];

        // Final safety: merge if the last history entry was also 'user'
        $count = count($contents);
        if ($count >= 2 && $contents[$count - 2]['role'] === 'user') {
            $contents[$count - 2]['parts'][0]['text'] .= "\n" . $contents[$count - 1]['parts'][0]['text'];
            array_pop($contents);
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $modelId
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])->post($url, [
            'contents' => $contents,
            'generationConfig' => [
                // Limit completion length to keep responses around 300–500 tokens.
                'maxOutputTokens' => $maxOutputTokens,
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception('Gemini API error: '.$response->body());
        }

        $data = $response->json();

        if (! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Gemini API response missing text candidate.');
        }

        $text = (string) $data['candidates'][0]['content']['parts'][0]['text'];
        $text = $this->formatResponse($text);

        $usage = $data['usageMetadata'] ?? null;

        return [
            'text' => $text,
            'usage' => is_array($usage) ? $usage : null,
            'raw' => $data,
        ];
    }

    /**
     * Strip markdown / decorative symbols so the response reads as clean
     * plain text suitable for mobile or non-markdown consumers.
     */
    protected function formatResponse(string $text): string
    {
        // Remove bold/italic markers: ***, **, *
        $text = preg_replace('/\*{1,3}/', '', $text);

        // Remove heading markers: ## Heading -> Heading
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);

        // Remove bullet-style leading dashes/plus at line start: - item -> item
        $text = preg_replace('/^[\-\+]\s+/m', '', $text);

        // Remove inline code backticks
        $text = preg_replace('/`{1,3}/', '', $text);

        // Remove horizontal rules (---, ***, ___)
        $text = preg_replace('/^[\-\*_]{3,}\s*$/m', '', $text);

        // Remove image/link markdown: ![alt](url) -> alt , [text](url) -> text
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]*\)/', '$1', $text);
        $text = preg_replace('/\[([^\]]*)\]\([^\)]*\)/', '$1', $text);

        // Remove blockquote markers
        $text = preg_replace('/^>\s?/m', '', $text);

        // Collapse 3+ consecutive newlines into 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}

