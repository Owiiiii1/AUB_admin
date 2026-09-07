<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRouteAllowedForRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if ($user === null || $routeName === null) {
            return $next($request);
        }

        if (RoleAccess::userCanAccessRoute($user, $routeName)) {
            return $next($request);
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('workplace')->withErrors([
                'access' => 'You do not have permission to access this page.',
            ]);
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
