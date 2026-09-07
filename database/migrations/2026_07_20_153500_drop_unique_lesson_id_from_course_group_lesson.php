<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_group_lesson', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropUnique('course_group_lesson_lesson_id_unique');
            $table->index('lesson_id', 'cgl_lesson_id_idx');
            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_group_lesson', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropIndex('cgl_lesson_id_idx');
            $table->unique('lesson_id');
            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
        });
    }
};

