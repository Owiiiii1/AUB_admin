<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_lesson_check_ins', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->change();
            $table->decimal('longitude', 10, 7)->nullable()->change();
            $table->decimal('distance_meters', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_lesson_check_ins', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable(false)->change();
            $table->decimal('longitude', 10, 7)->nullable(false)->change();
            $table->decimal('distance_meters', 8, 2)->nullable(false)->change();
        });
    }
};
