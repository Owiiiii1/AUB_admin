<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_menu_items')
            ->where('menu_key', 'staff')
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
