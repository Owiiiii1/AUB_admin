<?php

namespace Tests\Feature;

use App\Models\AcademyBuilding;
use App\Models\AcademyClass;
use App\Models\AcademyParent;
use App\Models\AcademyRoom;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AccountIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = $this->app->make(AccountIdentityService::class);
    }

    public function test_student_sees_own_class_schedule_only(): void
    {
        $classA = $this->makeClass('Classe A');
        $classB = $this->makeClass('Classe B');
        $studentA = $this->makeStudentInClass('Mario', 'Rossi', $classA);
        $this->makeStudentInClass('Lucia', 'Bianchi', $classB);
        $user = $this->linkStudent($studentA, 'mario@example.test');

        $week = $this->makePublishedWeek('2026-09-07');
        $own = $this->makeLesson($week, $classA, '2026-09-07', '16:00:00', '17:30:00', 'Danza classica');
        $this->makeLesson($week, $classB, '2026-09-07', '16:00:00', '17:30:00', 'Other class');

        $json = $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/schedule?week=2026-09-09')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.id', $studentA->id)
            ->assertJsonPath('data.student.display_name', 'Mario Rossi')
            ->assertJsonPath('data.student.academy_class.id', $classA->id)
            ->assertJsonPath('data.week.starts_on', '2026-09-07')
            ->assertJsonPath('data.week.ends_on', '2026-09-13')
            ->assertJsonPath('data.week.published', true)
            ->assertJsonPath('data.empty_reason', null)
            ->json('data');

        $mondayLessons = $json['days'][0]['lessons'];
        $this->assertCount(1, $mondayLessons);
        $this->assertSame($own->id, $mondayLessons[0]['id']);
        $this->assertSame('16:00', $mondayLessons[0]['starts_at']);
        $this->assertSame('17:30', $mondayLessons[0]['ends_at']);
        $this->assertSame('Danza classica', $mondayLessons[0]['title']);
        $this->assertSame('published', $mondayLessons[0]['status']);
        $this->assertCount(7, $json['days']);
        $this->assertSame(1, $json['days'][0]['weekday']);
        $this->assertSensitiveSchedulePayload(json_encode($json, JSON_THROW_ON_ERROR));
    }

    public function test_student_cannot_use_parent_child_schedule_endpoint(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $other = $this->makeStudentInClass('Lucia', 'Bianchi', $class);
        $user = $this->linkStudent($student, 'mario-own@example.test');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/children/'.$other->id.'/schedule?week=2026-09-07')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_draft_week_is_hidden(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $user = $this->linkStudent($student, 'draft@example.test');
        $week = $this->makeWeek('2026-09-07', ScheduleWeek::STATUS_DRAFT);
        $this->makeLesson($week, $class, '2026-09-07', '16:00:00', '17:00:00', 'Hidden draft', ScheduledLesson::STATUS_SCHEDULED);

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.week.published', false)
            ->assertJsonPath('data.empty_reason', 'unpublished')
            ->assertJsonPath('data.days.0.lessons', []);
    }

    public function test_published_week_is_visible(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $user = $this->linkStudent($student, 'published@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $this->makeLesson($week, $class, '2026-09-08', '10:00:00', '11:00:00', 'Visible');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.week.published', true)
            ->assertJsonPath('data.days.1.lessons.0.title', 'Visible');
    }

    public function test_cancelled_and_moved_lessons_are_visible_on_published_week(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $user = $this->linkStudent($student, 'status@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $this->makeLesson($week, $class, '2026-09-07', '16:00:00', '17:00:00', 'Cancelled', ScheduledLesson::STATUS_CANCELLED);
        $this->makeLesson($week, $class, '2026-09-08', '11:00:00', '12:00:00', 'Moved', ScheduledLesson::STATUS_MOVED);
        $this->makeLesson($week, $class, '2026-09-07', '09:00:00', '10:00:00', 'Still draft', ScheduledLesson::STATUS_DRAFT);
        $this->makeLesson($week, $class, '2026-09-07', '18:00:00', '19:00:00', 'Unpublished add', ScheduledLesson::STATUS_SCHEDULED);

        $days = $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertOk()
            ->json('data.days');

        $mondayTitles = array_column($days[0]['lessons'], 'title');
        $this->assertSame(['Cancelled'], $mondayTitles);
        $this->assertSame('cancelled', $days[0]['lessons'][0]['status']);
        $this->assertSame('Moved', $days[1]['lessons'][0]['title']);
        $this->assertSame('moved', $days[1]['lessons'][0]['status']);
        $this->assertSame('11:00', $days[1]['lessons'][0]['starts_at']);
    }

    public function test_lessons_are_sorted_by_start_time(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $user = $this->linkStudent($student, 'sort@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $this->makeLesson($week, $class, '2026-09-07', '18:00:00', '19:00:00', 'Evening');
        $this->makeLesson($week, $class, '2026-09-07', '16:00:00', '17:00:00', 'Afternoon');

        $titles = array_column(
            $this->withToken($this->loginToken($user))
                ->getJson('/api/v1/schedule?week=2026-09-07')
                ->assertOk()
                ->json('data.days.0.lessons'),
            'title',
        );

        $this->assertSame(['Afternoon', 'Evening'], $titles);
    }

    public function test_student_without_class_returns_empty_schedule(): void
    {
        $student = $this->makeStudent('Mario', 'Rossi');
        $user = $this->linkStudent($student, 'noclass@example.test');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.student.academy_class', null)
            ->assertJsonPath('data.week.published', false)
            ->assertJsonPath('data.empty_reason', 'no_class')
            ->assertJsonPath('data.days', []);
    }

    public function test_no_published_week_returns_unpublished_empty_days(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $user = $this->linkStudent($student, 'none@example.test');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.week.published', false)
            ->assertJsonPath('data.empty_reason', 'unpublished')
            ->assertJsonCount(7, 'data.days')
            ->assertJsonPath('data.days.0.lessons', []);
    }

    public function test_parent_sees_own_child_schedule(): void
    {
        $class = $this->makeClass('Classe A');
        $child = $this->makeStudentInClass('Sofia', 'Verdi', $class);
        $parentUser = $this->linkParentWithChildren('parent-one@example.test', [$child]);
        $week = $this->makePublishedWeek('2026-09-07');
        $this->makeLesson($week, $class, '2026-09-07', '16:00:00', '17:00:00', 'Child lesson');

        $this->withToken($this->loginToken($parentUser))
            ->getJson('/api/v1/children/'.$child->id.'/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.student.id', $child->id)
            ->assertJsonPath('data.student.display_name', 'Sofia Verdi')
            ->assertJsonPath('data.days.0.lessons.0.title', 'Child lesson');
    }

    public function test_parent_sees_two_children_independently(): void
    {
        $classA = $this->makeClass('Classe A');
        $classB = $this->makeClass('Classe B');
        $sofia = $this->makeStudentInClass('Sofia', 'Verdi', $classA);
        $luca = $this->makeStudentInClass('Luca', 'Verdi', $classB);
        $parentUser = $this->linkParentWithChildren('parent-two@example.test', [$sofia, $luca]);
        $week = $this->makePublishedWeek('2026-09-07');
        $this->makeLesson($week, $classA, '2026-09-07', '16:00:00', '17:00:00', 'Sofia class');
        $this->makeLesson($week, $classB, '2026-09-07', '18:00:00', '19:00:00', 'Luca class');

        $token = $this->loginToken($parentUser);

        $this->withToken($token)
            ->getJson('/api/v1/children/'.$sofia->id.'/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.days.0.lessons.0.title', 'Sofia class')
            ->assertJsonCount(1, 'data.days.0.lessons');

        $this->forgetAuthGuards();

        $this->withToken($token)
            ->getJson('/api/v1/children/'.$luca->id.'/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.days.0.lessons.0.title', 'Luca class')
            ->assertJsonCount(1, 'data.days.0.lessons');
    }

    public function test_parent_cannot_see_unrelated_child(): void
    {
        $class = $this->makeClass('Classe A');
        $own = $this->makeStudentInClass('Sofia', 'Verdi', $class);
        $stranger = $this->makeStudentInClass('Marco', 'Neri', $class, [
            'tax_code' => 'NRIMRC10A01H501X',
            'notes' => 'secret-child-note',
        ]);
        $parentUser = $this->linkParentWithChildren('parent-stranger@example.test', [$own]);
        $week = $this->makePublishedWeek('2026-09-07');
        $this->makeLesson($week, $class, '2026-09-07', '16:00:00', '17:00:00', 'Shared class');

        $this->withToken($this->loginToken($parentUser))
            ->getJson('/api/v1/children/'.$stranger->id.'/schedule?week=2026-09-07')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_parent_schedule_does_not_leak_private_fields(): void
    {
        $class = $this->makeClass('Classe A');
        $child = $this->makeStudentInClass('Sofia', 'Verdi', $class, [
            'tax_code' => 'VRDSFO10A01H501X',
            'notes' => 'internal-student-note',
            'medical_certificate_expiry' => '2027-01-01',
            'parent_id_document_path' => 'students/1/id.pdf',
        ]);
        $parentUser = $this->linkParentWithChildren('parent-privacy@example.test', [$child]);
        $week = $this->makePublishedWeek('2026-09-07');
        $this->makeLesson(
            $week,
            $class,
            '2026-09-07',
            '16:00:00',
            '17:00:00',
            'Public title',
            ScheduledLesson::STATUS_PUBLISHED,
            ['notes' => 'internal-schedule-note'],
        );

        $payload = $this->withToken($this->loginToken($parentUser))
            ->getJson('/api/v1/children/'.$child->id.'/schedule?week=2026-09-07')
            ->assertOk()
            ->json();

        $this->assertSensitiveSchedulePayload(json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('internal-student-note', json_encode($payload));
        $this->assertStringNotContainsString('internal-schedule-note', json_encode($payload));
        $this->assertStringNotContainsString('VRDSFO10A01H501X', json_encode($payload));
    }

    public function test_teacher_cannot_use_student_or_parent_schedule_endpoints(): void
    {
        $class = $this->makeClass('Classe A');
        $child = $this->makeStudentInClass('Sofia', 'Verdi', $class);
        $teacher = $this->makeTeacher('Elena', 'Bianchi');
        $teacherUser = $this->identity->createAndLink($teacher, [
            'name' => $teacher->displayName(),
            'email' => 'teacher-sched@example.test',
            'password' => 'password',
        ]);

        $token = $this->loginToken($teacherUser);

        $this->withToken($token)
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');

        $this->forgetAuthGuards();

        $this->withToken($token)
            ->getJson('/api/v1/children/'.$child->id.'/schedule?week=2026-09-07')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_staff_cannot_use_schedule_api(): void
    {
        $class = $this->makeClass('Classe A');
        $child = $this->makeStudentInClass('Sofia', 'Verdi', $class);
        $staff = $this->makeStaffAdmin();
        $token = $staff->createToken('Owl iPhone', ['mobile'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertUnauthorized();

        $this->forgetAuthGuards();

        $this->withToken($token)
            ->getJson('/api/v1/children/'.$child->id.'/schedule?week=2026-09-07')
            ->assertUnauthorized();
    }

    public function test_parent_cannot_use_student_schedule_endpoint(): void
    {
        $class = $this->makeClass('Classe A');
        $child = $this->makeStudentInClass('Sofia', 'Verdi', $class);
        $parentUser = $this->linkParentWithChildren('parent-wrong-ep@example.test', [$child]);

        $this->withToken($this->loginToken($parentUser))
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_locked_week_is_visible_like_published(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $user = $this->linkStudent($student, 'locked@example.test');
        $week = $this->makeWeek('2026-09-07', ScheduleWeek::STATUS_LOCKED);
        $this->makeLesson($week, $class, '2026-09-07', '16:00:00', '17:00:00', 'Locked lesson');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/schedule?week=2026-09-07')
            ->assertOk()
            ->assertJsonPath('data.week.published', true)
            ->assertJsonPath('data.days.0.lessons.0.title', 'Locked lesson');
    }

    private function forgetAuthGuards(): void
    {
        $this->app['auth']->forgetGuards();
    }

    private function loginToken(User $user): string
    {
        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Owl iPhone',
        ])->assertOk()->json('data.token');

        $this->assertIsString($token);

        return $token;
    }

    private function linkStudent(Student $student, string $email): User
    {
        return $this->identity->createAndLink($student, [
            'name' => $student->displayName(),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    /**
     * @param  list<Student>  $children
     */
    private function linkParentWithChildren(string $email, array $children): User
    {
        $parent = AcademyParent::query()->create([
            'first_name' => 'Maria',
            'last_name' => 'Verdi',
            'email' => $email,
        ]);

        foreach ($children as $child) {
            $parent->students()->attach($child->id, ['relation_type' => 'mother']);
        }

        return $this->identity->createAndLink($parent, [
            'name' => $parent->displayName(),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStudent(string $firstName, string $lastName, array $overrides = []): Student
    {
        return Student::query()->create([
            'name' => trim($firstName.' '.$lastName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => 'active',
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStudentInClass(string $firstName, string $lastName, AcademyClass $class, array $overrides = []): Student
    {
        $student = $this->makeStudent($firstName, $lastName, $overrides);
        $class->students()->attach($student->id);

        return $student;
    }

    private function makeTeacher(string $firstName, string $lastName): Teacher
    {
        return Teacher::query()->create([
            'type' => 'permanent',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($firstName.' '.$lastName),
            'email' => strtolower($firstName.'.'.$lastName.'.'.uniqid('', true)).'@academy.test',
            'phone' => '3331234567',
            'tax_code' => 'BNCLNE80A01H501X',
        ]);
    }

    private function makeClass(string $name): AcademyClass
    {
        $course = Course::query()->first() ?? Course::query()->create([
            'discipline' => 'academy',
            'name' => 'Classical Academy',
            'sort_order' => 1,
        ]);

        return AcademyClass::query()->create([
            'course_id' => $course->id,
            'name' => $name,
            'color' => '#1A2B44',
            'sort_order' => AcademyClass::query()->count() + 1,
        ]);
    }

    private function makeWeek(string $monday, string $status): ScheduleWeek
    {
        return ScheduleWeek::query()->create([
            'week_start_date' => $monday,
            'week_end_date' => '2026-09-11',
            'title' => 'Test week',
            'status' => $status,
            'published_at' => $status === ScheduleWeek::STATUS_PUBLISHED ? now() : null,
        ]);
    }

    private function makePublishedWeek(string $monday): ScheduleWeek
    {
        return $this->makeWeek($monday, ScheduleWeek::STATUS_PUBLISHED);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeLesson(
        ScheduleWeek $week,
        AcademyClass $class,
        string $date,
        string $startsAt,
        string $endsAt,
        string $title,
        string $status = ScheduledLesson::STATUS_PUBLISHED,
        array $overrides = [],
    ): ScheduledLesson {
        $building = AcademyBuilding::query()->firstOrFail();
        $room = AcademyRoom::query()->where('academy_building_id', $building->id)->firstOrFail();
        $teacher = Teacher::query()->first() ?? $this->makeTeacher('Maria', 'Rossi');
        $subject = Lesson::query()->first() ?? Lesson::query()->create([
            'discipline' => 'ballet',
            'name' => 'Danza classica',
            'duration_minutes' => 90,
        ]);

        return ScheduledLesson::query()->create([
            'schedule_week_id' => $week->id,
            'academy_building_id' => $building->id,
            'academy_room_id' => $room->id,
            'academy_class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'lesson_id' => $subject->id,
            'lesson_date' => $date,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'title' => $title,
            'status' => $status,
            ...$overrides,
        ]);
    }

    private function makeStaffAdmin(): User
    {
        $role = Role::query()->where('slug', 'administrator')->firstOrFail();

        return User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin-schedule@example.test',
            'role_id' => $role->id,
            'account_type' => User::TYPE_STAFF,
            'is_active' => true,
            'can_write' => true,
            'can_delete' => true,
            'password' => 'password',
        ]);
    }

    private function assertSensitiveSchedulePayload(string $json): void
    {
        foreach ([
            'tax_code',
            'medical_certificate',
            'notes',
            'password',
            'remember_token',
            'can_write',
            'can_delete',
            'role_id',
            'parent_id_document',
            'general_regulation',
            'api_key',
            'email',
            'phone',
            'preferences',
            'schedule_ai',
            'conflict',
            'published_by',
        ] as $fragment) {
            $this->assertStringNotContainsString('"'.$fragment, $json);
        }
    }
}
