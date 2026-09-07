<?php

namespace App\Services\Ai\Clients;

use App\Services\Ai\Contracts\AiProviderClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiClient implements AiProviderClient
{
    public function provider(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    public function listModels(string $apiKey): array
    {
        $response = Http::timeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->get('https://api.openai.com/v1/models');

        if (! $response->successful()) {
            throw new RuntimeException(
                $response->json('error.message')
                    ?: 'OpenAI request failed with status '.$response->status()
            );
        }

        $models = collect($response->json('data', []))
            ->map(fn (array $item): array => [
                'id' => (string) ($item['id'] ?? ''),
                'name' => (string) ($item['id'] ?? ''),
            ])
            ->filter(fn (array $model): bool => $model['id'] !== '')
            ->values()
            ->all();

        if ($models === []) {
            throw new RuntimeException('OpenAI returned no models for this API key.');
        }

        return $models;
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(string $apiKey, string $model, array $messages, int $maxTokens = 8192): string
    {
        $response = Http::timeout(120)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                $response->json('error.message')
                    ?: 'OpenAI completion failed with status '.$response->status()
            );
        }

        $content = trim((string) $response->json('choices.0.message.content', ''));

        if ($content === '') {
            throw new RuntimeException('OpenAI returned an empty response.');
        }

        return $content;
    }
}
