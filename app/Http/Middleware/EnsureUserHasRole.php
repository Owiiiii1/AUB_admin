<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            abort(403, 'This account is disabled.');
        }

        if (! $user->canAccessWebAdmin()) {
            abort(403, 'Your account cannot access the web administration panel.');
        }

        return $next($request);
    }
}
