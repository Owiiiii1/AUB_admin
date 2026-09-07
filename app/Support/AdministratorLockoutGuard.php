<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdministratorLockoutGuard
{
    public static function activeAdministratorUserCount(?int $excludingUserId = null): int
    {
        $query = User::query()
            ->whereHas('role', fn ($q) => $q->where('is_admin', true)->where('is_active', true));

        if ($excludingUserId !== null) {
            $query->where('id', '!=', $excludingUserId);
        }

        return $query->count();
    }

    public static function activeAdministratorRoleCount(?int $excludingRoleId = null): int
    {
        $query = Role::query()->where('is_admin', true)->where('is_active', true);

        if ($excludingRoleId !== null) {
            $query->where('id', '!=', $excludingRoleId);
        }

        return $query->count();
    }

    public static function ensureCanDeleteRole(Role $role): void
    {
        if ($role->is_system && $role->is_admin) {
            throw ValidationException::withMessages([
                'role' => 'System administrator role cannot be deleted.',
            ]);
        }

        if ($role->is_admin && static::activeAdministratorRoleCount($role->id) === 0) {
            throw ValidationException::withMessages([
                'role' => 'Cannot delete the last active administrator role.',
            ]);
        }

        if ($role->is_admin && $role->users()->exists() && static::activeAdministratorUserCount() <= $role->users()->count()) {
            $others = static::activeAdministratorUserCount();

            if ($others === 0 || ($role->users()->count() >= $others && static::activeAdministratorRoleCount($role->id) === 0)) {
                throw ValidationException::withMessages([
                    'role' => 'Cannot delete role: it would remove all administrator access.',
                ]);
            }
        }
    }

    public static function ensureCanDeactivateRole(Role $role, bool $nextIsActive, bool $nextIsAdmin): void
    {
        if ($role->is_admin && $role->is_active && ! $nextIsActive) {
            if (static::activeAdministratorRoleCount($role->id) === 0) {
                throw ValidationException::withMessages([
                    'is_active' => 'Cannot deactivate the last active administrator role.',
                ]);
            }
        }

        if ($role->is_admin && $nextIsAdmin === false) {
            if (static::activeAdministratorRoleCount($role->id) === 0) {
                throw ValidationException::withMessages([
                    'is_admin' => 'Cannot remove admin access from the only administrator role.',
                ]);
            }
        }
    }

    public static function ensureCanChangeUserRole(User $user, ?int $newRoleId): void
    {
        if (! $user->isAdministrator()) {
            return;
        }

        $newRole = $newRoleId ? Role::query()->find($newRoleId) : null;

        if ($newRole !== null && $newRole->is_admin) {
            return;
        }

        if (static::activeAdministratorUserCount($user->id) === 0) {
            throw ValidationException::withMessages([
                'role_id' => 'Cannot remove administrator access from the only active administrator.',
            ]);
        }
    }

    public static function ensureCanDeleteUser(User $user): void
    {
        if ($user->isAdministrator() && static::activeAdministratorUserCount($user->id) === 0) {
            throw ValidationException::withMessages([
                'user_delete' => 'Cannot delete the only active administrator account.',
            ]);
        }
    }
}
