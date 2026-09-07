<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanDelete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canDelete()) {
            if ($request->header('X-Inertia')) {
                return back()->withErrors([
                    'delete' => 'You do not have permission to delete records.',
                ]);
            }

            abort(403, 'You do not have permission to delete records.');
        }

        return $next($request);
    }
}
