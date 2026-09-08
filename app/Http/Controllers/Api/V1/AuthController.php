<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiLoginRequest;
use App\Http\Responses\ApiResponse;
use App\Services\MobileAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly MobileAuthService $mobileAuth,
    ) {}

    public function login(ApiLoginRequest $request): JsonResponse
    {
        $result = $this->mobileAuth->attempt($request);

        if ($result === null) {
            return ApiResponse::error('invalid_credentials', 'Invalid credentials.', 401);
        }

        return ApiResponse::success([
            'token' => $result['token']->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at']->toIso8601String(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->mobileAuth->logoutCurrent($request);

        return ApiResponse::success([]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->mobileAuth->logoutAll($request);

        return ApiResponse::success([]);
    }
}
