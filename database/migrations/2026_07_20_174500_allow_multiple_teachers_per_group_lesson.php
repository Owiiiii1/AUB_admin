<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_group_lesson', function (Blueprint $table) {
            $table->dropForeign(['course_group_id']);
            $table->dropForeign(['lesson_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropUnique('course_group_lesson_course_group_id_lesson_id_unique');
            $table->index('course_group_id', 'cgl_course_group_id_idx');
            $table->unique(
                ['course_group_id', 'lesson_id', 'teacher_id'],
                'cgl_group_lesson_teacher_unique',
            );
            $table->foreign('course_group_id')->references('id')->on('course_groups')->cascadeOnDelete();
            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('course_group_lesson', function (Blueprint $table) {
            $table->dropForeign(['course_group_id']);
            $table->dropForeign(['lesson_id']);
            $table->dropForeign(['teacher_id']);
            $table->dropUnique('cgl_group_lesson_teacher_unique');
            $table->dropIndex('cgl_course_group_id_idx');
            $table->unique(['course_group_id', 'lesson_id']);
            $table->foreign('course_group_id')->references('id')->on('course_groups')->cascadeOnDelete();
            $table->foreign('lesson_id')->references('id')->on('lessons')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('teachers')->cascadeOnDelete();
        });
    }
};
