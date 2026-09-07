<?php

namespace App\Services\Ai\Clients;

use App\Services\Ai\Contracts\AiProviderClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient implements AiProviderClient
{
    public function provider(): string
    {
        return 'gemini';
    }

    public function label(): string
    {
        return 'Gemini';
    }

    public function listModels(string $apiKey): array
    {
        $response = Http::timeout(15)
            ->acceptJson()
            ->get('https://generativelanguage.googleapis.com/v1beta/models', [
                'key' => $apiKey,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                $response->json('error.message')
                    ?: 'Gemini request failed with status '.$response->status()
            );
        }

        $models = collect($response->json('models', []))
            ->map(function (array $item): array {
                $rawName = (string) ($item['name'] ?? '');
                $id = str_starts_with($rawName, 'models/')
                    ? substr($rawName, 7)
                    : $rawName;

                return [
                    'id' => $id,
                    'name' => (string) ($item['displayName'] ?? $id),
                ];
            })
            ->filter(fn (array $model): bool => $model['id'] !== '')
            ->values()
            ->all();

        if ($models === []) {
            throw new RuntimeException('Gemini returned no models for this API key.');
        }

        return $models;
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(string $apiKey, string $model, array $messages, int $maxTokens = 8192): string
    {
        $system = '';
        $parts = [];

        foreach ($messages as $message) {
            $role = (string) ($message['role'] ?? 'user');
            $content = (string) ($message['content'] ?? '');

            if ($role === 'system') {
                $system .= ($system !== '' ? "\n\n" : '').$content;

                continue;
            }

            $parts[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $content]],
            ];
        }

        $payload = [
            'contents' => $parts,
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'responseMimeType' => 'application/json',
            ],
        ];

        if ($system !== '') {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $system]],
            ];
        }

        $response = Http::timeout(120)
            ->acceptJson()
            ->withQueryParameters(['key' => $apiKey])
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent',
                $payload,
            );

        if (! $response->successful()) {
            throw new RuntimeException(
                $response->json('error.message')
                    ?: 'Gemini completion failed with status '.$response->status()
            );
        }

        $finishReason = (string) $response->json('candidates.0.finishReason', '');
        if ($finishReason === 'MAX_TOKENS') {
            throw new RuntimeException('Gemini response was truncated. Reduce the schedule size or try again.');
        }

        $content = collect($response->json('candidates.0.content.parts', []))
            ->map(fn (array $part): string => (string) ($part['text'] ?? ''))
            ->implode("\n");

        $content = trim($content);

        if ($content === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return $content;
    }
}
