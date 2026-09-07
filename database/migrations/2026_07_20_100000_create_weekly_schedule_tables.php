<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academy_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academy_building_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('room_type')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['academy_building_id', 'slug']);
        });

        Schema::create('schedule_weeks', function (Blueprint $table) {
            $table->id();
            $table->date('week_start_date')->unique();
            $table->date('week_end_date');
            $table->string('title')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('scheduled_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_week_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academy_building_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academy_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->date('lesson_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->string('color')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index(['schedule_week_id', 'lesson_date'], 'sched_lessons_week_date_idx');
            $table->index(['schedule_week_id', 'academy_room_id', 'lesson_date'], 'sched_lessons_room_date_idx');
            $table->index(['schedule_week_id', 'teacher_id', 'lesson_date'], 'sched_lessons_teacher_date_idx');
            $table->index(['schedule_week_id', 'course_group_id', 'lesson_date'], 'sched_lessons_group_date_idx');
        });

        $this->seedBuildingsAndRooms();
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_lessons');
        Schema::dropIfExists('schedule_weeks');
        Schema::dropIfExists('academy_rooms');
        Schema::dropIfExists('academy_buildings');
    }

    private function seedBuildingsAndRooms(): void
    {
        $now = now();

        $buildings = [
            ['name' => 'SEDE CARCANO', 'sort_order' => 1, 'rooms' => [
                'SALA GRANDE', 'SALA TORRETTA', 'SALA A',
            ]],
            ['name' => 'SEDE CABRINI', 'sort_order' => 2, 'rooms' => [
                'SALA1',
            ]],
            ['name' => 'SEDE ACCADEMIA', 'sort_order' => 3, 'rooms' => [
                'SALA VAGANOVA', 'SALA TAGLIONI', 'SALA TCHAIKOVSKY', 'SALA PETIPA', 'SALA NIJINKSY', 'SALA KALCHENKO',
            ]],
            ['name' => 'SEDE DANZA&DANZA', 'sort_order' => 4, 'rooms' => [
                'SALA AUDITORIUM',
            ]],
            ['name' => 'SEDE ARCIMBOLDI', 'sort_order' => 5, 'rooms' => [
                'SALA -2', 'SALA +7',
            ]],
        ];

        foreach ($buildings as $buildingData) {
            $buildingId = DB::table('academy_buildings')->insertGetId([
                'name' => $buildingData['name'],
                'slug' => Str::slug($buildingData['name']),
                'address' => null,
                'sort_order' => $buildingData['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($buildingData['rooms'] as $index => $roomName) {
                DB::table('academy_rooms')->insert([
                    'academy_building_id' => $buildingId,
                    'name' => $roomName,
                    'slug' => Str::slug($roomName),
                    'capacity' => null,
                    'room_type' => null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
