<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\AdministratorLockoutGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * @var list<string>
     */
    private const LOG_FIELDS = ['name', 'email', 'role_id', 'can_delete', 'can_write', 'account_type', 'is_active'];

    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'can_delete' => ['sometimes', 'boolean'],
            'can_write' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'account_type' => User::TYPE_STAFF,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'role_id' => $validated['role_id'],
            'can_delete' => (bool) ($validated['can_delete'] ?? false),
            'can_write' => (bool) ($validated['can_write'] ?? false),
        ]);

        $this->activityLogger->logForModel(
            $request,
            'created',
            $user,
            'user',
            null,
            null,
            $user->only(self::LOG_FIELDS),
        );

        return Redirect::route('settings.index', ['tab' => 'users']);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge([
            'role_id' => $request->filled('role_id') ? $request->input('role_id') : null,
        ]);

        $roleRule = match ($user->account_type) {
            User::TYPE_STAFF => ['required', 'exists:roles,id'],
            User::TYPE_TEACHER => ['nullable', 'exists:roles,id'],
            default => ['prohibited'],
        };

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role_id' => $roleRule,
            'can_delete' => ['sometimes', 'boolean'],
            'can_write' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (in_array($user->account_type, [User::TYPE_STUDENT, User::TYPE_PARENT], true)) {
            $validated['role_id'] = null;
            $validated['can_delete'] = false;
            $validated['can_write'] = false;
        }

        if ($user->account_type === User::TYPE_STAFF || ($user->account_type === User::TYPE_TEACHER && ! empty($validated['role_id']))) {
            AdministratorLockoutGuard::ensureCanChangeUserRole($user, (int) $validated['role_id']);
        }

        $before = $user->only(self::LOG_FIELDS);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'] ?? null,
            'can_delete' => (bool) ($validated['can_delete'] ?? false),
            'can_write' => (bool) ($validated['can_write'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? $user->is_active),
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        $after = $user->only(self::LOG_FIELDS);
        if (! empty($validated['password'])) {
            $after['password'] = '***';
            $before['password'] = '***';
        }

        $this->activityLogger->logForModel(
            $request,
            'updated',
            $user,
            'user',
            null,
            $before,
            $after,
        );

        return Redirect::route('settings.index', ['tab' => 'users']);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return Redirect::route('settings.index', ['tab' => 'users'])->withErrors([
                'user_delete' => 'You cannot delete your own account from this screen.',
            ]);
        }

        AdministratorLockoutGuard::ensureCanDeleteUser($user);

        $before = $user->only(self::LOG_FIELDS);
        $userId = $user->id;
        $label = $user->name;

        $user->delete();

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'user',
            $userId,
            $label,
            null,
            $before,
        );

        return Redirect::route('settings.index', ['tab' => 'users']);
    }
}
