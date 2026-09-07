<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_menu_items')
            ->whereIn('menu_key', ['orders', 'calendar'])
            ->delete();

        DB::table('role_menu_items')
            ->where('menu_key', 'customers')
            ->update(['menu_key' => 'students']);
    }

    public function down(): void
    {
        DB::table('role_menu_items')
            ->where('menu_key', 'students')
            ->update(['menu_key' => 'customers']);
    }
};
