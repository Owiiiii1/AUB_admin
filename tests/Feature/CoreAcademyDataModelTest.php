<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AcademyBuilding;
use App\Models\AcademyClass;
use App\Models\AcademyParent;
use App\Models\AcademyRoom;
use App\Models\ClassLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreAcademyDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_belong_to_one_academy_class(): void
    {
        [$class] = $this->makeClasses(1);
        $student = $this->makeStudent('Anna', 'Rossi');

        $class->students()->attach($student->id);

        $this->assertSame($class->id, $student->fresh()->academyClass()?->id);
        $this->assertCount(1, $student->fresh()->academyClasses);
    }

    public function test_student_cannot_be_assigned_to_two_primary_classes(): void
    {
        [$first, $second] = $this->makeClasses(2);
        $student = $this->makeStudent('Luca', 'Bianchi');

        $first->students()->attach($student->id);

        $this->expectException(UniqueConstraintViolationException::class);
        $second->students()->attach($student->id);
    }

    public function test_parent_can_be_linked_to_several_students(): void
    {
        $parent = AcademyParent::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Verdi',
            'email' => 'maria@example.test',
        ]);
        $first = $this->makeStudent('Giulia', 'Verdi');
        $second = $this->makeStudent('Marco', 'Verdi');

        $parent->students()->attach($first->id, ['relation_type' => 'mother']);
        $parent->students()->attach($second->id, ['relation_type' => 'mother']);

        $this->assertCount(2, $parent->fresh()->students);
    }

    public function test_student_can_have_several_parents(): void
    {
        $student = $this->makeStudent('Elena', 'Neri');
        $father = AcademyParent::query()->create([
            'first_name' => 'Paolo',
            'last_name' => 'Neri',
        ]);
        $mother = AcademyParent::query()->create([
            'first_name' => 'Sara',
            'last_name' => 'Neri',
        ]);

        $student->parents()->attach($father->id, ['relation_type' => 'father']);
        $student->parents()->attach($mother->id, ['relation_type' => 'mother']);

        $this->assertCount(2, $student->fresh()->parents);
        $this->assertSame('father', $student->fresh()->parentOfType('father')?->pivot?->relation_type);
        $this->assertSame('mother', $student->fresh()->parentOfType('mother')?->pivot?->relation_type);
    }

    public function test_lesson_is_assigned_to_class_for_academic_year(): void
    {
        [$class] = $this->makeClasses(1);
        $year = AcademicYear::current();
        $this->assertNotNull($year);

        $lesson = Lesson::query()->create([
            'discipline' => 'ballet',
            'name' => 'Classical Technique',
            'duration_minutes' => 60,
        ]);

        $classLesson = ClassLesson::query()->create([
            'academic_year_id' => $year->id,
            'academy_class_id' => $class->id,
            'lesson_id' => $lesson->id,
            'hours' => 4,
        ]);

        $this->assertTrue($class->fresh()->classLessons->contains($classLesson));
        $this->assertSame($year->id, $classLesson->academicYear->id);
        $this->assertSame($lesson->id, $classLesson->lesson->id);
    }

    public function test_duplicate_class_lesson_for_same_year_is_forbidden(): void
    {
        [$class] = $this->makeClasses(1);
        $year = AcademicYear::current();
        $lesson = Lesson::query()->create([
            'discipline' => 'ballet',
            'name' => 'Repertoire',
            'duration_minutes' => 60,
        ]);

        ClassLesson::query()->create([
            'academic_year_id' => $year->id,
            'academy_class_id' => $class->id,
            'lesson_id' => $lesson->id,
            'hours' => 2,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);
        ClassLesson::query()->create([
            'academic_year_id' => $year->id,
            'academy_class_id' => $class->id,
            'lesson_id' => $lesson->id,
            'hours' => 3,
        ]);
    }

    public function test_several_teachers_can_be_assigned_to_one_class_lesson(): void
    {
        [$class] = $this->makeClasses(1);
        $year = AcademicYear::current();
        $lesson = Lesson::query()->create([
            'discipline' => 'ballet',
            'name' => 'Pointe',
            'duration_minutes' => 45,
        ]);
        $classLesson = ClassLesson::query()->create([
            'academic_year_id' => $year->id,
            'academy_class_id' => $class->id,
            'lesson_id' => $lesson->id,
            'hours' => 3,
        ]);
        $firstTeacher = $this->makeTeacher('Elena', 'Greco');
        $secondTeacher = $this->makeTeacher('Marco', 'Conti');

        $classLesson->teachers()->attach([
            $firstTeacher->id => ['hours' => 2],
            $secondTeacher->id => ['hours' => 1],
        ]);

        $this->assertCount(2, $classLesson->fresh()->teachers);
        $this->assertEqualsCanonicalizing(
            [$firstTeacher->id, $secondTeacher->id],
            $classLesson->fresh()->teachers->pluck('id')->all(),
        );
    }

    public function test_scheduled_lesson_belongs_to_academy_class_after_refactor(): void
    {
        [$class] = $this->makeClasses(1);
        $building = AcademyBuilding::query()->firstOrFail();
        $room = AcademyRoom::query()->where('academy_building_id', $building->id)->firstOrFail();
        $week = ScheduleWeek::query()->create([
            'week_start_date' => '2026-09-07',
            'week_end_date' => '2026-09-11',
            'title' => 'Test week',
            'status' => ScheduleWeek::STATUS_DRAFT,
        ]);
        $teacher = $this->makeTeacher('Ilaria', 'Fontana');
        $lesson = Lesson::query()->create([
            'discipline' => 'ballet',
            'name' => 'Pas de deux',
            'duration_minutes' => 60,
        ]);

        $scheduled = ScheduledLesson::query()->create([
            'schedule_week_id' => $week->id,
            'academy_building_id' => $building->id,
            'academy_room_id' => $room->id,
            'academy_class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'lesson_id' => $lesson->id,
            'lesson_date' => '2026-09-08',
            'starts_at' => '09:00:00',
            'ends_at' => '10:00:00',
            'status' => ScheduledLesson::STATUS_SCHEDULED,
        ]);

        $this->assertSame($class->id, $scheduled->fresh()->academyClass->id);
        $this->assertTrue($class->fresh()->scheduledLessons->contains($scheduled));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('scheduled_lessons', 'course_group_id'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('scheduled_lessons', 'academy_class_id'));
    }

    /**
     * @return list<AcademyClass>
     */
    private function makeClasses(int $count): array
    {
        $course = Course::query()->create([
            'discipline' => 'academy',
            'name' => 'Classical Academy',
            'sort_order' => 1,
        ]);

        $classes = [];
        for ($i = 1; $i <= $count; $i++) {
            $classes[] = AcademyClass::query()->create([
                'course_id' => $course->id,
                'name' => 'Class '.$i,
                'color' => '#1A2B44',
                'sort_order' => $i,
            ]);
        }

        return $classes;
    }

    private function makeStudent(string $firstName, string $lastName): Student
    {
        return Student::query()->create([
            'name' => trim($firstName.' '.$lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
        ]);
    }

    private function makeTeacher(string $firstName, string $lastName): Teacher
    {
        return Teacher::query()->create([
            'type' => 'permanent',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
        ]);
    }
}
