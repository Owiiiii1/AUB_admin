<?php

namespace App\Services\Ai\Clients;

use App\Services\Ai\Contracts\AiProviderClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicClient implements AiProviderClient
{
    public function provider(): string
    {
        return 'anthropic';
    }

    public function label(): string
    {
        return 'Claude';
    }

    public function listModels(string $apiKey): array
    {
        $response = Http::timeout(15)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->acceptJson()
            ->get('https://api.anthropic.com/v1/models');

        if (! $response->successful()) {
            throw new RuntimeException(
                $response->json('error.message')
                    ?: 'Anthropic request failed with status '.$response->status()
            );
        }

        $models = collect($response->json('data', []))
            ->map(fn (array $item): array => [
                'id' => (string) ($item['id'] ?? ''),
                'name' => (string) ($item['display_name'] ?? $item['id'] ?? ''),
            ])
            ->filter(fn (array $model): bool => $model['id'] !== '')
            ->values()
            ->all();

        if ($models === []) {
            throw new RuntimeException('Anthropic returned no models for this API key.');
        }

        return $models;
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(string $apiKey, string $model, array $messages, int $maxTokens = 8192): string
    {
        $system = '';
        $chatMessages = [];

        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $system .= ($system !== '' ? "\n\n" : '').($message['content'] ?? '');

                continue;
            }

            $chatMessages[] = [
                'role' => ($message['role'] ?? '') === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $chatMessages,
        ];

        if ($system !== '') {
            $payload['system'] = $system;
        }

        $response = Http::timeout(120)
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->acceptJson()
            ->post('https://api.anthropic.com/v1/messages', $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                $response->json('error.message')
                    ?: 'Anthropic completion failed with status '.$response->status()
            );
        }

        $content = collect($response->json('content', []))
            ->filter(fn (array $block): bool => ($block['type'] ?? '') === 'text')
            ->map(fn (array $block): string => (string) ($block['text'] ?? ''))
            ->implode("\n");

        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException('Anthropic returned an empty response.');
        }

        return $content;
    }
}
