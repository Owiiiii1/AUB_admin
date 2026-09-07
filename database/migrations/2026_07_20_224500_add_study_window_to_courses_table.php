<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->time('study_starts_at')->nullable()->after('sort_order');
            $table->time('study_ends_at')->nullable()->after('study_starts_at');
        });

        $courses = DB::table('courses')
            ->leftJoin('course_groups', 'course_groups.course_id', '=', 'courses.id')
            ->leftJoin('course_group_lesson', 'course_group_lesson.course_group_id', '=', 'course_groups.id')
            ->groupBy('courses.id', 'courses.name', 'courses.sort_order')
            ->orderByDesc(DB::raw('COALESCE(SUM(course_group_lesson.hours), 0)'))
            ->orderBy('courses.sort_order')
            ->orderBy('courses.id')
            ->get([
                'courses.id',
                DB::raw('COALESCE(SUM(course_group_lesson.hours), 0) as weekly_hours'),
            ]);

        $morningLoad = 0.0;
        $afternoonLoad = 0.0;

        foreach ($courses as $course) {
            $hours = (float) $course->weekly_hours;
            $useMorning = $morningLoad <= $afternoonLoad;

            DB::table('courses')->where('id', $course->id)->update([
                'study_starts_at' => $useMorning ? '08:00:00' : '13:00:00',
                'study_ends_at' => $useMorning ? '13:00:00' : '18:00:00',
                'updated_at' => now(),
            ]);

            if ($useMorning) {
                $morningLoad += $hours;
            } else {
                $afternoonLoad += $hours;
            }
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn(['study_starts_at', 'study_ends_at']);
        });
    }
};
