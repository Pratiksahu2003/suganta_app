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

        $rawText = (string) $data['candidates'][0]['content']['parts'][0]['text'];

        $plainText = $this->stripMarkdown($rawText);
        $sections = $this->parseIntoSections($rawText);

        $usage = $data['usageMetadata'] ?? null;

        return [
            'text' => $plainText,
            'sections' => $sections,
            'usage' => is_array($usage) ? $usage : null,
            'raw' => $data,
        ];
    }

    /**
     * Public access to section parsing for historical messages stored in DB.
     */
    public function buildSections(string $text): array
    {
        return $this->parseIntoSections($text);
    }

    // ------------------------------------------------------------------
    //  Response formatting helpers
    // ------------------------------------------------------------------

    /**
     * Remove all markdown symbols and return clean plain text.
     */
    protected function stripMarkdown(string $text): string
    {
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]*\)/', '$1', $text);
        $text = preg_replace('/\[([^\]]*)\]\([^\)]*\)/', '$1', $text);

        $text = preg_replace('/\*{1,3}/', '', $text);
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);
        $text = preg_replace('/`{1,3}/', '', $text);
        $text = preg_replace('/^[\-\*_]{3,}\s*$/m', '', $text);
        $text = preg_replace('/^>\s?/m', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Parse the raw Gemini markdown into structured sections so the
     * frontend can render each block with proper styling.
     *
     * Returned array of sections, each with:
     *   - type: "heading" | "paragraph" | "list" | "note"
     *   - heading (only for "heading"): the heading text
     *   - body (for "paragraph" / "note"): plain text string
     *   - items (only for "list"): ordered array of list-item strings
     */
    protected function parseIntoSections(string $raw): array
    {
        $raw = preg_replace('/!\[([^\]]*)\]\([^\)]*\)/', '$1', $raw);
        $raw = preg_replace('/\[([^\]]*)\]\([^\)]*\)/', '$1', $raw);
        $raw = preg_replace('/\*{1,3}([^*]+)\*{1,3}/', '$1', $raw);
        $raw = preg_replace('/`{1,3}([^`]*)`{1,3}/', '$1', $raw);

        $lines = preg_split('/\r?\n/', $raw);

        $sections = [];
        $buffer = [];
        $bufferType = null;

        $flushBuffer = function () use (&$sections, &$buffer, &$bufferType) {
            if (empty($buffer)) {
                return;
            }

            if ($bufferType === 'list') {
                $sections[] = [
                    'type' => 'list',
                    'items' => $buffer,
                ];
            } else {
                $text = trim(implode("\n", $buffer));
                if ($text !== '') {
                    $sections[] = [
                        'type' => 'paragraph',
                        'body' => $text,
                    ];
                }
            }

            $buffer = [];
            $bufferType = null;
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || preg_match('/^[\-\*_]{3,}$/', $trimmed)) {
                $flushBuffer();
                continue;
            }

            if (preg_match('/^#{1,6}\s+(.+)$/', $trimmed, $m)) {
                $flushBuffer();
                $sections[] = [
                    'type' => 'heading',
                    'heading' => trim($m[1]),
                ];
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $trimmed, $m)) {
                $flushBuffer();
                $sections[] = [
                    'type' => 'note',
                    'body' => trim($m[1]),
                ];
                continue;
            }

            if (preg_match('/^[\-\+\*]\s+(.+)$/', $trimmed, $m)) {
                if ($bufferType !== 'list') {
                    $flushBuffer();
                    $bufferType = 'list';
                }
                $buffer[] = trim($m[1]);
                continue;
            }

            if (preg_match('/^\d+[\.\)]\s+(.+)$/', $trimmed, $m)) {
                if ($bufferType !== 'list') {
                    $flushBuffer();
                    $bufferType = 'list';
                }
                $buffer[] = trim($m[1]);
                continue;
            }

            if ($bufferType === 'list') {
                $flushBuffer();
            }
            $bufferType = 'paragraph';
            $buffer[] = $trimmed;
        }

        $flushBuffer();

        if (empty($sections)) {
            $clean = $this->stripMarkdown($raw);
            if ($clean !== '') {
                $sections[] = [
                    'type' => 'paragraph',
                    'body' => $clean,
                ];
            }
        }

        return $sections;
    }
}

