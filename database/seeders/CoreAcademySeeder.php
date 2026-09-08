<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AcademyClass;
use App\Models\AcademyParent;
use App\Models\ClassLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;

class CoreAcademySeeder extends Seeder
{
    /**
     * Minimal local academy sample. Not invoked by DatabaseSeeder.
     * Do not run on production unless a smoke check is explicitly needed.
     */
    public function run(): void
    {
        $year = AcademicYear::current() ?? AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);

        $course = Course::query()->firstOrCreate(
            ['discipline' => 'academy', 'name' => 'Corso Accademia'],
            ['sort_order' => 1, 'study_starts_at' => '08:00:00', 'study_ends_at' => '13:00:00'],
        );

        $academyClass = AcademyClass::query()->firstOrCreate(
            ['course_id' => $course->id, 'name' => '1A'],
            ['color' => '#1A2B44', 'sort_order' => 1],
        );

        $lessons = collect([
            'Tecnica classica',
            'Punte',
            'Repertorio',
        ])->map(fn (string $name, int $index): Lesson => Lesson::query()->firstOrCreate(
            ['discipline' => 'ballet', 'name' => $name],
            ['duration_minutes' => 60, 'sort_order' => $index + 1],
        ));

        $firstTeacher = Teacher::query()->firstOrCreate(
            ['email' => 'teacher.one@example.test'],
            [
                'type' => 'permanent',
                'first_name' => 'Elena',
                'last_name' => 'Greco',
                'name' => 'Elena Greco',
            ],
        );
        $secondTeacher = Teacher::query()->firstOrCreate(
            ['email' => 'teacher.two@example.test'],
            [
                'type' => 'permanent',
                'first_name' => 'Marco',
                'last_name' => 'Conti',
                'name' => 'Marco Conti',
            ],
        );

        foreach ($lessons as $index => $lesson) {
            $classLesson = ClassLesson::query()->firstOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'academy_class_id' => $academyClass->id,
                    'lesson_id' => $lesson->id,
                ],
                ['hours' => 2, 'sort_order' => $index + 1],
            );

            $lesson->teachers()->syncWithoutDetaching([$firstTeacher->id, $secondTeacher->id]);
            $classLesson->teachers()->syncWithoutDetaching([
                $firstTeacher->id => ['hours' => 2],
                $secondTeacher->id => ['hours' => 1],
            ]);
        }

        $student = Student::query()->firstOrCreate(
            ['email' => 'student.one@example.test'],
            [
                'name' => 'Anna Rossi',
                'first_name' => 'Anna',
                'last_name' => 'Rossi',
                'status' => 'active',
            ],
        );

        $parent = AcademyParent::query()->firstOrCreate(
            ['email' => 'parent.one@example.test'],
            [
                'first_name' => 'Maria',
                'last_name' => 'Rossi',
                'phone' => '+39000000000',
            ],
        );

        $student->parents()->syncWithoutDetaching([
            $parent->id => ['relation_type' => 'mother'],
        ]);

        if (! $student->academyClasses()->exists()) {
            $academyClass->students()->attach($student->id);
        }
    }
}
