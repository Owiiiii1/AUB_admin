<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_weeks', function (Blueprint $table) {
            $table->time('work_starts_at')->default('08:00:00')->after('status');
            $table->time('work_ends_at')->default('22:30:00')->after('work_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_weeks', function (Blueprint $table) {
            $table->dropColumn(['work_starts_at', 'work_ends_at']);
        });
    }
};
