<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GrokAiService
{
    /**
     * Mirror GeminiAiService return structure so controllers
     * can treat both providers the same.
     *
     * @return array{text:string,usage:array|null,raw:array}
     */
    public function generateReply(string $prompt, array $history = []): array
    {
        $apiKey = env('GROK_API_KEY');
        $modelId = env('GROK_MODEL_ID', 'grok-2-latest');

        if (empty($apiKey)) {
            throw new Exception('Grok API key is not configured.');
        }

        $messages = [];

        foreach ($history as $item) {
            if (! isset($item['role'], $item['content'])) {
                continue;
            }

            $role = $item['role'] === 'assistant' ? 'assistant' : 'user';

            $messages[] = [
                'role' => $role,
                'content' => $item['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.x.ai/v1/chat/completions', [
            'model' => $modelId,
            'messages' => $messages,
        ]);

        if (! $response->successful()) {
            throw new Exception('Grok API error: '.$response->body());
        }

        $data = $response->json();

        if (! isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Grok API response missing text content.');
        }

        $rawText = (string) $data['choices'][0]['message']['content'];

        // We only need plain text + sections; reuse Gemini helper
        $gemini = new GeminiAiService();
        $plainText = (new \ReflectionMethod($gemini, 'stripMarkdown'))->invoke($gemini, $rawText);
        $sections = (new \ReflectionMethod($gemini, 'parseIntoSections'))->invoke($gemini, $rawText);

        $usage = null;
        if (isset($data['usage']) && is_array($data['usage'])) {
            $usage = [
                'totalTokenCount' => (int) ($data['usage']['total_tokens'] ?? 0),
                'promptTokenCount' => (int) ($data['usage']['prompt_tokens'] ?? 0),
                'candidatesTokenCount' => (int) ($data['usage']['completion_tokens'] ?? 0),
            ];
        }

        return [
            'text' => $plainText,
            'sections' => $sections,
            'usage' => $usage,
            'raw' => $data,
        ];
    }
}

