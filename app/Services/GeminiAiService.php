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

        if (empty($apiKey)) {
            throw new Exception('Gemini API key is not configured.');
        }

        $contents = [];

        foreach ($history as $item) {
            if (! isset($item['role'], $item['content'])) {
                continue;
            }

            $contents[] = [
                'role' => $item['role'],
                'parts' => [
                    ['text' => $item['content']],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt],
            ],
        ];

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            $modelId
        );

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])->post($url, [
            'contents' => $contents,
        ]);

        if (! $response->successful()) {
            throw new Exception('Gemini API error: '.$response->body());
        }

        $data = $response->json();

        if (! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Gemini API response missing text candidate.');
        }

        $text = (string) $data['candidates'][0]['content']['parts'][0]['text'];

        $usage = $data['usageMetadata'] ?? null;

        return [
            'text' => $text,
            'usage' => is_array($usage) ? $usage : null,
            'raw' => $data,
        ];
    }
}

