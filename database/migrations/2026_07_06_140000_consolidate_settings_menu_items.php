<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_menu_items')
            ->whereIn('menu_key', ['roles', 'app-settings'])
            ->delete();
    }

    public function down(): void
    {
        // Restored keys depend on config at rollback time; no automatic restore.
    }
};
