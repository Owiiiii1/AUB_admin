<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });

        Schema::create('lesson_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lesson_id', 'teacher_id']);
        });

        Schema::create('lesson_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lesson_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_course');
        Schema::dropIfExists('lesson_teacher');

        Schema::table('lessons', function (Blueprint $table) {
            $table->unsignedInteger('duration_minutes')->nullable()->after('description');
        });
    }
};
