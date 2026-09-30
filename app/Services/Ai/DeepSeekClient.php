<?php

declare(strict_types=1);

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeepSeekClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, usage?: array<string, mixed>}
     */
    public function chat(array $messages, ?string $model = null): array
    {
        $apiKey = (string) config('services.deepseek.key');
        $baseUrl = rtrim((string) config('services.deepseek.base_url', 'https://api.deepseek.com'), '/');
        $timeout = (int) config('services.deepseek.timeout', 30);
        $model ??= (string) config('services.deepseek.model', 'deepseek-chat');

        if ($apiKey === '') {
            throw new RuntimeException('DeepSeek API key is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->timeout($timeout)
            ->acceptJson()
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => $messages,
                'temperature' => (float) config('services.deepseek.temperature', 0.7),
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('DeepSeek API request failed with status '.$response->status());
        }

        $content = (string) data_get($response->json(), 'choices.0.message.content', '');

        if (trim($content) === '') {
            throw new RuntimeException('DeepSeek API returned an empty response.');
        }

        return [
            'content' => $content,
            'usage' => data_get($response->json(), 'usage', []),
        ];
    }
}
