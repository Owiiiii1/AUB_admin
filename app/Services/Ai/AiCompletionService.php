<?php

namespace App\Services\Ai;

use App\Models\AiProviderSetting;
use RuntimeException;

class AiCompletionService
{
    public function __construct(
        private readonly AiProviderManager $manager,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(array $messages, int $maxTokens = 8192): string
    {
        $setting = AiProviderSetting::query()
            ->where('is_active', true)
            ->where('is_connected', true)
            ->first();

        if ($setting === null) {
            throw new RuntimeException('No active AI provider is connected.');
        }

        $model = trim((string) $setting->active_model);
        if ($model === '') {
            throw new RuntimeException('Active AI provider has no selected model.');
        }

        $apiKey = $setting->api_key;
        if ($apiKey === null || trim((string) $apiKey) === '') {
            throw new RuntimeException('Active AI provider has no API key.');
        }

        return $this->manager->complete(
            (string) $setting->provider,
            (string) $apiKey,
            $model,
            $messages,
            $maxTokens,
        );
    }
}
