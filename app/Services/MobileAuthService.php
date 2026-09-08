<?php

namespace App\Services;

use App\Http\Requests\Api\ApiLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\NewAccessToken;

class MobileAuthService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @return array{user: User, token: NewAccessToken, expires_at: \Illuminate\Support\Carbon}|null
     */
    public function attempt(ApiLoginRequest $request): ?array
    {
        $email = mb_strtolower(trim((string) $request->validated('email')));
        $password = (string) $request->validated('password');
        $deviceName = trim((string) $request->validated('device_name'));

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user === null) {
            $this->logFailure($request, 'unknown_email');

            return null;
        }

        if (! Hash::check($password, $user->password)) {
            $this->logFailure($request, 'bad_password', $user);

            return null;
        }

        if (! $user->is_active) {
            $this->logFailure($request, 'inactive', $user);

            return null;
        }

        if (! $user->isMobileActor()) {
            $this->logFailure($request, 'not_mobile_actor', $user);

            return null;
        }

        if ($user->matchingActorProfile() === null) {
            $this->logFailure($request, 'missing_profile', $user);

            return null;
        }

        $minutes = (int) config('aub.api.token_expiration_minutes', 43200);
        $expiresAt = now()->addMinutes($minutes);
        $ability = (string) config('aub.api.token_ability', 'mobile');

        $token = $user->createToken($deviceName, [$ability], $expiresAt);

        $this->activityLogger->log(
            $request,
            'api_login',
            'user',
            $user->id,
            $user->email,
            properties: ['device_name' => $deviceName],
        );

        return [
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    public function logoutCurrent(Request $request): void
    {
        $user = $request->user();

        if ($user !== null) {
            $this->activityLogger->log(
                $request,
                'api_logout',
                'user',
                $user->id,
                $user->email,
            );
        }

        $token = $user?->currentAccessToken();

        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        }
    }

    public function logoutAll(Request $request): void
    {
        $user = $request->user();

        if ($user === null) {
            return;
        }

        $this->activityLogger->log(
            $request,
            'api_logout_all',
            'user',
            $user->id,
            $user->email,
        );

        $user->tokens()->delete();
    }

    private function logFailure(Request $request, string $reason, ?User $user = null): void
    {
        Log::notice('api.login_failed', [
            'reason' => $reason,
            'user_id' => $user?->id,
            'ip' => $request->ip(),
        ]);
    }
}
