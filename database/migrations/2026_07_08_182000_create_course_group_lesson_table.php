<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_group_lesson', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('hours');
            $table->timestamps();

            $table->unique(['course_group_id', 'lesson_id']);
            $table->unique('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_group_lesson');
    }
};
