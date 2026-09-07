<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $item = config('aub-menu.items.teachers');
        if (! is_array($item)) {
            return;
        }

        $studentsSort = DB::table('role_menu_items')
            ->where('menu_key', 'students')
            ->value('sort_order');

        $sortOrder = $studentsSort !== null ? ((int) $studentsSort + 1) : 2;

        DB::table('role_menu_items')
            ->where('sort_order', '>=', $sortOrder)
            ->increment('sort_order');

        $adminRoleIds = DB::table('roles')
            ->where('is_admin', true)
            ->pluck('id');

        foreach ($adminRoleIds as $roleId) {
            $exists = DB::table('role_menu_items')
                ->where('role_id', $roleId)
                ->where('menu_key', 'teachers')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('role_menu_items')->insert([
                'role_id' => $roleId,
                'menu_key' => 'teachers',
                'label' => null,
                'route_name' => $item['route_name'] ?? 'teachers.index',
                'url' => null,
                'icon' => null,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('role_menu_items')
            ->where('menu_key', 'teachers')
            ->delete();
    }
};
