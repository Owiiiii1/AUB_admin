<?php

namespace App\Services\Ai\Contracts;

interface AiProviderClient
{
    public function provider(): string;

    public function label(): string;

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function listModels(string $apiKey): array;

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(string $apiKey, string $model, array $messages, int $maxTokens = 8192): string;
}
