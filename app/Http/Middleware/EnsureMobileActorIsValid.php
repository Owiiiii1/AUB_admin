<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileActorIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $user->refresh();
        }

        if ($user === null || ! $user->canAccessMobileApi()) {
            $token = $user?->currentAccessToken();

            if ($user !== null && ! $user->is_active) {
                $user->tokens()->delete();
            } elseif ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            throw new AuthenticationException('Unauthenticated.');
        }

        $token = $user->currentAccessToken();
        $ability = (string) config('aub.api.token_ability', 'mobile');

        if ($token instanceof PersonalAccessToken && ! $user->tokenCan($ability)) {
            $token->delete();

            throw new AuthenticationException('Unauthenticated.');
        }

        return $next($request);
    }
}
