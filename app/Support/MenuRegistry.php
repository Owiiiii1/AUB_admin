<?php

namespace App\Support;

class MenuRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function items(): array
    {
        return config('aub-menu.items', []);
    }

    /**
     * @return list<string>
     */
    public static function menuKeys(): array
    {
        return array_keys(static::items());
    }

    public static function routePatternsForMenuKey(string $menuKey): array
    {
        $item = static::items()[$menuKey] ?? [];

        return $item['route_patterns'] ?? [];
    }

    public static function isAdminOnlyMenuKey(string $menuKey): bool
    {
        return (bool) (static::items()[$menuKey]['admin_only'] ?? false);
    }

    /**
     * @return list<string>
     */
    public static function alwaysAllowedRoutePatterns(): array
    {
        return config('aub-menu.always_allowed_route_patterns', []);
    }
}
