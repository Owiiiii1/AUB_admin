<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdatePasswordRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (! Hash::check((string) $validated['current_password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->save();

        $current = $user->currentAccessToken();
        $currentId = $current instanceof PersonalAccessToken ? $current->id : null;
        $user->tokens()
            ->when($currentId !== null, fn ($query) => $query->where('id', '!=', $currentId))
            ->delete();

        $this->activityLogger->log(
            $request,
            'api_password_changed',
            'user',
            $user->id,
            $user->email,
        );

        return ApiResponse::success(['updated' => true]);
    }

    public function devices(Request $request): JsonResponse
    {
        $user = $request->user();
        $current = $user->currentAccessToken();
        $currentId = $current instanceof PersonalAccessToken ? $current->id : null;

        $devices = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->get()
            ->map(static function (PersonalAccessToken $token) use ($currentId): array {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at?->toIso8601String(),
                    'created_at' => $token->created_at?->toIso8601String(),
                    'current' => $currentId !== null && $token->id === $currentId,
                ];
            })
            ->values()
            ->all();

        return ApiResponse::success(['devices' => $devices]);
    }

    public function revokeDevice(Request $request, int $device): JsonResponse
    {
        $user = $request->user();
        $token = $user->tokens()->whereKey($device)->first();

        if (! $token instanceof PersonalAccessToken) {
            return ApiResponse::error('not_found', 'Not found.', 404);
        }

        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken && $token->id === $current->id) {
            throw ValidationException::withMessages([
                'device' => ['The current device cannot be revoked from this list.'],
            ]);
        }

        $token->delete();

        $this->activityLogger->log(
            $request,
            'api_device_revoked',
            'user',
            $user->id,
            $user->email,
            properties: ['device_id' => $device],
        );

        return ApiResponse::success(['id' => $device]);
    }
}
