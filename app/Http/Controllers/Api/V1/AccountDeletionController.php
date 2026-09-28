<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountDeletionController extends Controller
{
    public function __construct(private readonly AccountDeletionService $deletions) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'confirm' => ['accepted'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $deletion = $this->deletions->submitFromApp($user, $data['password'], $data['message'] ?? null);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $deletion->status,
                'requested_at' => $deletion->requested_at?->toIso8601String(),
            ],
        ]);
    }
}
