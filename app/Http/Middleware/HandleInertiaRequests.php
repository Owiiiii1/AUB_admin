<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user !== null) {
            $user->loadMissing('role');
        }

        return [
            ...parent::share($request),
            'locale' => Inertia::always(fn () => $request->session()->get('locale', config('app.locale', 'it'))),
            'auth' => Inertia::always(fn () => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'account_type' => $user->account_type,
                    'is_active' => (bool) $user->is_active,
                    'role_id' => $user->role_id,
                    'role_name' => $user->role?->name,
                    'is_administrator' => $user->isAdministrator(),
                    'can_delete' => $user->canDelete(),
                    'can_write' => $user->canWrite(),
                ] : null,
            ]),
            'adminMenu' => Inertia::always(fn () => RoleAccess::menuItemsForUser($request->user())),
            'flash' => Inertia::always(fn () => [
                'success' => $request->session()->get('success'),
            ]),
            'owlAdmin' => Inertia::always(fn () => [
                ...(config('owl-admin.branding', [
                    'brand_name' => config('owl-admin.brand_name', config('owl-admin.name', 'Service Admin')),
                    'logo_path' => config('owl-admin.logo_path', '/images/company-logo.svg'),
                ])),
                'ai' => $this->resolveAiStatus(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveAiStatus(): array
    {
        $fallback = [
            'connected' => false,
            'provider' => null,
            'provider_label' => null,
            'model' => null,
            'status_label' => 'AI: not connected',
        ];

        try {
            if (! class_exists(\App\Models\AiProviderSetting::class)) {
                return $fallback;
            }

            if (! \Illuminate\Support\Facades\Schema::hasTable('ai_provider_settings')) {
                return $fallback;
            }

            $active = \App\Models\AiProviderSetting::query()
                ->where('is_active', true)
                ->where('is_connected', true)
                ->first();

            if ($active === null) {
                return $fallback;
            }

            $providerLabel = $active->label ?: ucfirst((string) $active->provider);

            return [
                'connected' => true,
                'provider' => $active->provider,
                'provider_label' => $providerLabel,
                'model' => $active->active_model,
                'status_label' => sprintf(
                    'AI: connected — %s / %s',
                    $providerLabel,
                    $active->active_model ?? 'unknown'
                ),
            ];
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
