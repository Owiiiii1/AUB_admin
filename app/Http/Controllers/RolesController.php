<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\ActivityLogger;
use App\Support\AdministratorLockoutGuard;
use App\Support\MenuRegistry;
use App\Support\RoleAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    /**
     * @var list<string>
     */
    private const LOG_FIELDS = ['name', 'slug', 'description', 'is_admin', 'is_active'];

    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['slug'] ?? $validated['name']),
            'description' => $validated['description'] ?? null,
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
            'is_system' => false,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($role->is_admin) {
            RoleAccess::syncRoleMenuItems($role, MenuRegistry::menuKeys());
        } else {
            RoleAccess::syncRoleMenuItems($role, $validated['menu_keys'] ?? []);
        }

        $this->activityLogger->log(
            $request,
            'created',
            'role',
            $role->id,
            $role->name,
            null,
            [
                'attributes' => $role->only(self::LOG_FIELDS),
                'menu_keys' => $role->is_admin ? MenuRegistry::menuKeys() : ($validated['menu_keys'] ?? []),
            ],
        );

        return Redirect::route('settings.index', ['tab' => 'roles']);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $this->validateRole($request, $role);

        $nextIsActive = (bool) ($validated['is_active'] ?? $role->is_active);
        $nextIsAdmin = (bool) ($validated['is_admin'] ?? $role->is_admin);

        AdministratorLockoutGuard::ensureCanDeactivateRole($role, $nextIsActive, $nextIsAdmin);

        $before = $role->only(self::LOG_FIELDS);
        $beforeMenuKeys = $role->menuItems()->where('is_active', true)->orderBy('sort_order')->pluck('menu_key')->all();

        if ($role->is_system) {
            $nextIsAdmin = true;
        }

        $role->fill([
            'name' => $validated['name'],
            'slug' => $role->is_system ? $role->slug : $this->uniqueSlug($validated['slug'] ?? $validated['name'], $role->id),
            'description' => $validated['description'] ?? null,
            'is_admin' => $nextIsAdmin,
            'is_active' => $nextIsActive,
        ]);
        $role->save();

        if ($role->is_admin) {
            RoleAccess::syncRoleMenuItems($role, MenuRegistry::menuKeys());
        } else {
            RoleAccess::syncRoleMenuItems($role, $validated['menu_keys'] ?? []);
        }

        $after = $role->only(self::LOG_FIELDS);
        $afterMenuKeys = $role->is_admin
            ? MenuRegistry::menuKeys()
            : ($validated['menu_keys'] ?? []);

        $changes = [];
        foreach ($after as $key => $value) {
            if (array_key_exists($key, $before) && $before[$key] != $value) {
                $changes[$key] = [$before[$key], $value];
            }
        }
        if ($beforeMenuKeys !== $afterMenuKeys) {
            $changes['menu_keys'] = [$beforeMenuKeys, $afterMenuKeys];
        }

        $this->activityLogger->log(
            $request,
            'updated',
            'role',
            $role->id,
            $role->name,
            null,
            ['changes' => $changes],
        );

        return Redirect::route('settings.index', ['tab' => 'roles']);
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        AdministratorLockoutGuard::ensureCanDeleteRole($role);

        if ($role->users()->exists()) {
            return Redirect::route('settings.index', ['tab' => 'roles'])->withErrors([
                'role' => 'Cannot delete a role that is assigned to users.',
            ]);
        }

        $before = $role->only(self::LOG_FIELDS);
        $roleId = $role->id;
        $label = $role->name;

        $role->delete();

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'role',
            $roleId,
            $label,
            null,
            $before,
        );

        return Redirect::route('settings.index', ['tab' => 'roles']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_admin' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'menu_keys' => ['nullable', 'array'],
            'menu_keys.*' => ['string', Rule::in(MenuRegistry::menuKeys())],
        ]);
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'role';
        $slug = $base;
        $counter = 2;

        while (
            Role::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
