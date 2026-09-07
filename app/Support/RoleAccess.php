<?php

namespace App\Support;

use App\Models\Role;
use App\Models\RoleMenuItem;
use App\Models\User;
use Illuminate\Support\Collection;

class RoleAccess
{
    public static function userCanAccessRoute(?User $user, ?string $routeName): bool
    {
        if ($user === null || $routeName === null) {
            return false;
        }

        if (! $user->hasAssignedRole()) {
            return false;
        }

        if ($user->isAdministrator()) {
            return true;
        }

        foreach (MenuRegistry::alwaysAllowedRoutePatterns() as $pattern) {
            if (static::routeMatchesPattern($routeName, $pattern)) {
                return true;
            }
        }

        foreach (static::allowedRoutePatternsForUser($user) as $pattern) {
            if (static::routeMatchesPattern($routeName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function menuItemsForUser(?User $user): array
    {
        if ($user === null || ! $user->hasAssignedRole()) {
            return [];
        }

        if ($user->isAdministrator()) {
            return static::fullMenuItems();
        }

        $allowedKeys = $user->role
            ->menuItems()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('menu_key')
            ->all();

        return static::fullMenuItems($allowedKeys);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function fullMenuItems(?array $onlyKeys = null): array
    {
        $registry = MenuRegistry::items();
        $items = [];

        foreach ($registry as $menuKey => $config) {
            if ($onlyKeys !== null && ! in_array($menuKey, $onlyKeys, true)) {
                continue;
            }

            $items[] = [
                'menu_key' => $menuKey,
                'route_name' => $config['route_name'],
                'admin_only' => (bool) ($config['admin_only'] ?? false),
                'has_children' => $menuKey === 'statistics',
                'child_route_name' => $menuKey === 'statistics' ? 'statistics.logs' : null,
            ];
        }

        return $items;
    }

    public static function postLoginRedirectUrl(User $user): string
    {
        if (! $user->hasAssignedRole()) {
            return route('login');
        }

        if ($user->isAdministrator()) {
            return route('dashboard');
        }

        $first = static::menuItemsForUser($user)[0] ?? null;

        if ($first !== null && ! empty($first['route_name'])) {
            return route($first['route_name']);
        }

        return route('workplace');
    }

    /**
     * @return list<string>
     */
    public static function allowedRoutePatternsForUser(User $user): array
    {
        if ($user->isAdministrator()) {
            $patterns = [];

            foreach (MenuRegistry::items() as $menuKey => $config) {
                $patterns = array_merge($patterns, $config['route_patterns'] ?? []);
            }

            return array_values(array_unique($patterns));
        }

        $patterns = [];

        $menuKeys = $user->role
            ->menuItems()
            ->where('is_active', true)
            ->pluck('menu_key');

        foreach ($menuKeys as $menuKey) {
            $patterns = array_merge($patterns, MenuRegistry::routePatternsForMenuKey($menuKey));
        }

        return array_values(array_unique($patterns));
    }

    public static function syncRoleMenuItems(Role $role, array $menuKeys): void
    {
        $menuKeys = array_values(array_unique(array_filter($menuKeys)));
        $registry = MenuRegistry::items();
        $sort = 0;

        RoleMenuItem::query()->where('role_id', $role->id)->delete();

        foreach ($menuKeys as $menuKey) {
            if (! isset($registry[$menuKey])) {
                continue;
            }

            RoleMenuItem::query()->create([
                'role_id' => $role->id,
                'menu_key' => $menuKey,
                'route_name' => $registry[$menuKey]['route_name'] ?? null,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function rolesForSelect(): array
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_admin'])
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'is_admin' => $role->is_admin,
            ])
            ->all();
    }

    protected static function routeMatchesPattern(string $routeName, string $pattern): bool
    {
        if ($pattern === $routeName) {
            return true;
        }

        if (str_ends_with($pattern, '.*')) {
            $prefix = substr($pattern, 0, -2);

            return $routeName === $prefix || str_starts_with($routeName, $prefix.'.');
        }

        return false;
    }
}
