<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsureMobileActorIsValid;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role.assigned' => \App\Http\Middleware\EnsureUserHasRole::class,
            'role.access' => \App\Http\Middleware\EnsureRouteAllowedForRole::class,
            'administrator' => \App\Http\Middleware\EnsureAdministrator::class,
            'can.delete' => \App\Http\Middleware\EnsureUserCanDelete::class,
            'can.write' => \App\Http\Middleware\EnsureUserCanWrite::class,
            'mobile.actor' => EnsureMobileActorIsValid::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): ?string {
            if ($request->is('api/*')) {
                return null;
            }

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return app(ApiExceptionRenderer::class)->render($e);
        });
    })->create();
