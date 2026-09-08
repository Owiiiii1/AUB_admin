<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\LessonsController;
use App\Http\Controllers\Controller;
use App\Models\AcademyBuilding;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\MenuRegistry;
use App\Support\RoleAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AiSettingsController $aiSettingsController,
        private readonly ActivityLogger $activityLogger,
        private readonly LessonsController $lessonsController,
    ) {}

    public function index(Request $request): Response
    {
        $tab = $request->query('tab', 'users');
        if (! in_array($tab, ['users', 'roles', 'app', 'academy', 'ai'], true)) {
            $tab = 'users';
        }

        $academyTab = $request->query('academyTab', 'halls');
        if (! in_array($academyTab, ['general', 'halls', 'lessons'], true)) {
            $academyTab = 'halls';
        }

        $lessonCatalog = $this->lessonsController->catalogForPage(
            $request->query('lessonTab', 'ballet'),
        );

        $users = User::query()
            ->with([
                'role:id,name,is_admin',
                'studentProfile:id,user_id,name,first_name,last_name',
                'parentProfile:id,user_id,first_name,last_name',
                'teacherProfile:id,user_id,name,first_name,last_name',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'account_type', 'is_active', 'role_id', 'can_delete', 'can_write', 'created_at'])
            ->map(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account_type' => $user->account_type,
                'is_active' => (bool) $user->is_active,
                'role_id' => $user->role_id,
                'role_name' => $user->role?->name,
                'can_delete' => $user->canDelete(),
                'can_write' => $user->canWrite(),
                'linked_profile' => $user->linkedProfileSummary(),
                'created_at' => optional($user->created_at)->toIso8601String(),
            ])
            ->all();

        $managedRoles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_admin' => $role->is_admin,
                'is_system' => $role->is_system,
                'is_active' => $role->is_active,
                'users_count' => $role->users_count,
                'menu_keys' => $role->menuItems()->where('is_active', true)->orderBy('sort_order')->pluck('menu_key')->all(),
            ])
            ->all();

        $menuRegistry = [];
        foreach (MenuRegistry::items() as $menuKey => $config) {
            $menuRegistry[] = [
                'menu_key' => $menuKey,
                'route_name' => $config['route_name'],
                'admin_only' => (bool) ($config['admin_only'] ?? false),
            ];
        }

        $academyBuildings = AcademyBuilding::query()
            ->with(['rooms' => function ($query): void {
                $query->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(static fn (AcademyBuilding $building): array => [
                'id' => $building->id,
                'name' => $building->name,
                'slug' => $building->slug,
                'address' => $building->address,
                'sort_order' => $building->sort_order,
                'is_active' => (bool) $building->is_active,
                'rooms' => $building->rooms->map(static fn ($room): array => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'slug' => $room->slug,
                    'capacity' => $room->capacity,
                    'room_type' => $room->room_type,
                    'sort_order' => $room->sort_order,
                    'is_active' => (bool) $room->is_active,
                ])->all(),
            ])
            ->all();

        return Inertia::render('Settings/Index', [
            'tab' => $tab,
            'academyTab' => $academyTab,
            'lessonTab' => $lessonCatalog['lessonTab'],
            'lessons' => $lessonCatalog['lessons'],
            'lessonTeachers' => $lessonCatalog['teachers'],
            'users' => $users,
            'roleOptions' => RoleAccess::rolesForSelect(),
            'managedRoles' => $managedRoles,
            'menuRegistry' => $menuRegistry,
            'academyBuildings' => $academyBuildings,
            'aiProviders' => $this->aiSettingsController->providersForPage(),
        ]);
    }

    public function updateLanguage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'in:it,en,ru,uk'],
        ]);

        $previous = $request->session()->get('locale', config('app.locale', 'it'));
        $request->session()->put('locale', $validated['locale']);

        if ($previous !== $validated['locale']) {
            $this->activityLogger->log(
                $request,
                'updated',
                'settings',
                null,
                'Language',
                null,
                ['changes' => ['locale' => [$previous, $validated['locale']]]],
            );
        }

        return back();
    }
}
