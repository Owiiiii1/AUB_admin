<?php

namespace App\Services\Ai;

use RuntimeException;

class AiJsonParser
{
    /**
     * @return array<string, mixed>
     */
    public static function decodeObject(string $raw, string $errorMessage = 'AI returned invalid JSON.'): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new RuntimeException($errorMessage);
        }

        $candidates = array_values(array_unique(array_filter([
            $raw,
            self::extractMarkdownJson($raw),
            self::extractJsonObject($raw),
        ], static fn (?string $value): bool => is_string($value) && trim($value) !== '')));

        foreach ($candidates as $candidate) {
            $candidate = self::normalizeJsonString($candidate);

            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            $repaired = self::repairTrailingCommas($candidate);
            $decoded = json_decode($repaired, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $preview = mb_substr(preg_replace('/\s+/', ' ', $raw) ?? $raw, 0, 180);

        throw new RuntimeException($errorMessage.' Response preview: '.$preview);
    }

    private static function extractMarkdownJson(string $raw): ?string
    {
        if (! preg_match('/```(?:json)?\s*(.*?)\s*```/s', $raw, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private static function extractJsonObject(string $raw): ?string
    {
        $start = strpos($raw, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($raw);

        for ($index = $start; $index < $length; $index++) {
            $char = $raw[$index];

            if ($inString) {
                if ($escape) {
                    $escape = false;

                    continue;
                }

                if ($char === '\\') {
                    $escape = true;

                    continue;
                }

                if ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;

                continue;
            }

            if ($char === '{') {
                $depth++;
            }

            if ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($raw, $start, $index - $start + 1);
                }
            }
        }

        return null;
    }

    private static function normalizeJsonString(string $json): string
    {
        $json = trim($json);
        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;

        return str_replace(
            ["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"],
            ['"', '"', "'", "'"],
            $json,
        );
    }

    private static function repairTrailingCommas(string $json): string
    {
        return preg_replace('/,\s*([\]}])/', '$1', $json) ?? $json;
    }
}
