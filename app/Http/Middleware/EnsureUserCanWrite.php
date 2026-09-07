<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canWrite()) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors([
                    'write' => 'You do not have permission to create or modify records.',
                ]);
            }

            abort(403, 'You do not have permission to create or modify records.');
        }

        return $next($request);
    }
}
