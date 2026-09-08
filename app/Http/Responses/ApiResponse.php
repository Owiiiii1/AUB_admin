<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  array<string, list<string>>|null  $fields
     */
    public static function error(string $code, string $message, int $status, ?array $fields = null): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($fields !== null) {
            $error['fields'] = $fields;
        }

        return response()->json([
            'success' => false,
            'error' => $error,
        ], $status);
    }
}
