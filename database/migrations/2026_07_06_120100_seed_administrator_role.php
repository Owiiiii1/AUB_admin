<?php

use App\Models\Role;
use App\Models\RoleMenuItem;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'Administrator',
            'slug' => 'administrator',
            'description' => 'Full access to the admin panel.',
            'is_admin' => true,
            'is_system' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $registry = config('aub-menu.items', []);
        $sort = 0;

        foreach ($registry as $menuKey => $item) {
            DB::table('role_menu_items')->insert([
                'role_id' => $adminRoleId,
                'menu_key' => $menuKey,
                'label' => null,
                'route_name' => $item['route_name'] ?? null,
                'url' => null,
                'icon' => null,
                'sort_order' => $sort++,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        User::query()
            ->where('email', 'admin@admin.com')
            ->update(['role_id' => $adminRoleId]);

        User::query()
            ->whereNull('role_id')
            ->update(['role_id' => $adminRoleId]);
    }

    public function down(): void
    {
        User::query()->update(['role_id' => null]);
        RoleMenuItem::query()->delete();
        Role::query()->where('slug', 'administrator')->delete();
    }
};
