<?php

namespace Tests\Feature;

use App\Models\AcademyBuilding;
use App\Models\AcademyClass;
use App\Models\AcademyParent;
use App\Models\AcademyRoom;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AccountIdentityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = $this->app->make(AccountIdentityService::class);
        Carbon::setTestNow(Carbon::parse('2026-09-15 12:00:00', 'Europe/Rome'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_student_sees_own_attendance_only(): void
    {
        $class = $this->makeClass('Classe A');
        $anna = $this->makeStudentInClass('Anna', 'Rossi', $class, [
            'notes' => 'secret-note',
            'tax_code' => 'VRDLCU10A01H501X',
            'email' => 'anna-secret@example.test',
            'phone' => '3330001111',
        ]);
        $bruno = $this->makeStudentInClass('Bruno', 'Neri', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-09', '16:00:00', '17:30:00', 'Danza classica');
        $this->mark($lesson, $anna, AttendanceRecord::STATUS_PRESENT);
        $this->mark($lesson, $bruno, AttendanceRecord::STATUS_ABSENT);
        $user = $this->linkStudent($anna, 'anna-history@example.test');

        $json = $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/attendance?month=2026-09')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.id', $anna->id)
            ->assertJsonPath('data.student.display_name', 'Anna Rossi')
            ->assertJsonPath('data.period.month', '2026-09')
            ->assertJsonPath('data.period.starts_on', '2026-09-01')
            ->assertJsonPath('data.period.ends_on', '2026-09-30')
            ->assertJsonPath('data.summary.marked', 1)
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonPath('data.summary.absent', 0)
            ->assertJsonPath('data.summary.excused', 0)
            ->json('data');

        $this->assertCount(1, $json['records']);
        $this->assertSame('present', $json['records'][0]['status']);
        $this->assertIsInt($json['records'][0]['id']);
        $this->assertSame($lesson->lesson_id, $json['records'][0]['lesson']['id']);
        $this->assertSame('2026-09-09', $json['records'][0]['date']);
        $this->assertSame('16:00', $json['records'][0]['starts_at']);
        $this->assertSame('17:30', $json['records'][0]['ends_at']);
        $this->assertSame('Danza classica', $json['records'][0]['title']);
        $this->assertSame('Elena Verdi', $json['records'][0]['teacher']['display_name']);
        $this->assertArrayNotHasKey('marked_by', $json['records'][0]);
        $this->assertArrayNotHasKey('marked_at', $json['records'][0]);
        $this->assertSensitive($json);
    }

    public function test_student_current_month_default_and_explicit_month(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-09', '16:00:00', '17:00:00', 'Danza');
        $this->mark($lesson, $student, AttendanceRecord::STATUS_PRESENT);
        $user = $this->linkStudent($student, 'anna-month@example.test');
        $token = $this->loginToken($user);

        $this->withToken($token)
            ->getJson('/api/v1/attendance')
            ->assertOk()
            ->assertJsonPath('data.period.month', '2026-09')
            ->assertJsonPath('data.summary.marked', 1);

        $this->forgetAuthGuards();

        $this->withToken($token)
            ->getJson('/api/v1/attendance?month=2026-08')
            ->assertOk()
            ->assertJsonPath('data.period.month', '2026-08')
            ->assertJsonPath('data.period.starts_on', '2026-08-01')
            ->assertJsonPath('data.period.ends_on', '2026-08-31')
            ->assertJsonPath('data.summary.marked', 0)
            ->assertJsonCount(0, 'data.records');
    }

    public function test_invalid_month_is_validation_error(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $user = $this->linkStudent($student, 'anna-invalid-month@example.test');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/attendance?month=2026-13')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->forgetAuthGuards();

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/attendance?month=2026-9')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_parent_sees_own_child_history_and_isolates_siblings(): void
    {
        $class = $this->makeClass('Classe A');
        $sofia = $this->makeStudentInClass('Sofia', 'Verdi', $class);
        $marco = $this->makeStudentInClass('Marco', 'Verdi', $class);
        $outsider = $this->makeStudentInClass('Lucia', 'Neri', $this->makeClass('Classe B'));
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $sofiaLesson = $this->makeLesson($week, $class, $teacher, '2026-09-09', '16:00:00', '17:00:00', 'Sofia lesson');
        $marcoLesson = $this->makeLesson($week, $class, $teacher, '2026-09-09', '18:00:00', '19:00:00', 'Marco lesson');
        $this->mark($sofiaLesson, $sofia, AttendanceRecord::STATUS_PRESENT);
        $this->mark($marcoLesson, $marco, AttendanceRecord::STATUS_ABSENT);
        $this->mark($sofiaLesson, $outsider, AttendanceRecord::STATUS_EXCUSED);
        $parent = $this->linkParentWithChildren('parent-history@example.test', [$sofia, $marco]);

        $sofiaJson = $this->withToken($this->loginToken($parent))
            ->getJson('/api/v1/children/'.$sofia->id.'/attendance?month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.student.id', $sofia->id)
            ->assertJsonPath('data.student.display_name', 'Sofia Verdi')
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonPath('data.summary.absent', 0)
            ->json('data');
        $this->assertCount(1, $sofiaJson['records']);
        $this->assertSame('present', $sofiaJson['records'][0]['status']);

        $this->forgetAuthGuards();

        $marcoJson = $this->withToken($this->loginToken($parent))
            ->getJson('/api/v1/children/'.$marco->id.'/attendance?month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.student.id', $marco->id)
            ->assertJsonPath('data.summary.absent', 1)
            ->json('data');
        $this->assertCount(1, $marcoJson['records']);
        $this->assertSame('absent', $marcoJson['records'][0]['status']);

        $this->forgetAuthGuards();

        $this->withToken($this->loginToken($parent))
            ->getJson('/api/v1/children/'.$outsider->id.'/attendance?month=2026-09')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_unrelated_parent_cannot_see_another_child(): void
    {
        $class = $this->makeClass('Classe A');
        $childA = $this->makeStudentInClass('Sofia', 'Verdi', $class);
        $childB = $this->makeStudentInClass('Lucia', 'Neri', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-09', '16:00:00', '17:00:00', 'Danza');
        $this->mark($lesson, $childB, AttendanceRecord::STATUS_PRESENT);
        $parentA = $this->linkParentWithChildren('parent-a@example.test', [$childA]);

        $this->withToken($this->loginToken($parentA))
            ->getJson('/api/v1/children/'.$childB->id.'/attendance?month=2026-09')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found')
            ->assertJsonMissingPath('data.records');
    }

    public function test_present_absent_excused_are_returned_and_sorted(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $first = $this->makeLesson($week, $class, $teacher, '2026-09-08', '16:00:00', '17:00:00', 'Early');
        $laterSameDay = $this->makeLesson($week, $class, $teacher, '2026-09-09', '18:00:00', '19:00:00', 'Evening');
        $earlierSameDay = $this->makeLesson($week, $class, $teacher, '2026-09-09', '16:00:00', '17:00:00', 'Afternoon');
        $this->mark($first, $student, AttendanceRecord::STATUS_EXCUSED);
        $this->mark($earlierSameDay, $student, AttendanceRecord::STATUS_ABSENT);
        $this->mark($laterSameDay, $student, AttendanceRecord::STATUS_PRESENT);
        $user = $this->linkStudent($student, 'anna-statuses@example.test');

        $json = $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/attendance?month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.summary.marked', 3)
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonPath('data.summary.absent', 1)
            ->assertJsonPath('data.summary.excused', 1)
            ->json('data');

        $this->assertSame(['present', 'absent', 'excused'], array_column($json['records'], 'status'));
        $this->assertSame(['Evening', 'Afternoon', 'Early'], array_column($json['records'], 'title'));
    }

    public function test_unmarked_lesson_is_not_absent_or_marked(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $marked = $this->makeLesson($week, $class, $teacher, '2026-09-09', '16:00:00', '17:00:00', 'Marked');
        $this->makeLesson($week, $class, $teacher, '2026-09-09', '18:00:00', '19:00:00', 'Unmarked');
        $this->mark($marked, $student, AttendanceRecord::STATUS_PRESENT);
        $user = $this->linkStudent($student, 'anna-unmarked@example.test');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/attendance?month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.summary.marked', 1)
            ->assertJsonPath('data.summary.absent', 0)
            ->assertJsonCount(1, 'data.records');
    }

    public function test_unofficial_weeks_and_lessons_are_excluded(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $publishedWeek = $this->makePublishedWeek('2026-09-07');
        $draftWeek = $this->makeWeek('2026-09-14', ScheduleWeek::STATUS_DRAFT);
        $lockedWeek = $this->makeWeek('2026-08-31', ScheduleWeek::STATUS_LOCKED);

        $visible = $this->makeLesson($publishedWeek, $class, $teacher, '2026-09-09', '16:00:00', '17:00:00', 'Visible');
        $moved = $this->makeLesson($publishedWeek, $class, $teacher, '2026-09-09', '17:00:00', '18:00:00', 'Moved', ScheduledLesson::STATUS_MOVED);
        $cancelled = $this->makeLesson($publishedWeek, $class, $teacher, '2026-09-09', '18:00:00', '19:00:00', 'Cancelled', ScheduledLesson::STATUS_CANCELLED);
        $scheduled = $this->makeLesson($publishedWeek, $class, $teacher, '2026-09-09', '15:00:00', '16:00:00', 'Scheduled', ScheduledLesson::STATUS_SCHEDULED);
        $draftLesson = $this->makeLesson($publishedWeek, $class, $teacher, '2026-09-09', '14:00:00', '15:00:00', 'Draft lesson', ScheduledLesson::STATUS_DRAFT);
        $draftWeekLesson = $this->makeLesson($draftWeek, $class, $teacher, '2026-09-15', '16:00:00', '17:00:00', 'Draft week');
        $locked = $this->makeLesson($lockedWeek, $class, $teacher, '2026-09-01', '10:00:00', '11:00:00', 'Locked');
        $otherMonth = $this->makeLesson($publishedWeek, $class, $teacher, '2026-08-31', '16:00:00', '17:00:00', 'August');

        $this->mark($visible, $student, AttendanceRecord::STATUS_PRESENT);
        $this->mark($moved, $student, AttendanceRecord::STATUS_ABSENT);
        $this->mark($cancelled, $student, AttendanceRecord::STATUS_EXCUSED);
        $this->mark($scheduled, $student, AttendanceRecord::STATUS_PRESENT);
        $this->mark($draftLesson, $student, AttendanceRecord::STATUS_PRESENT);
        $this->mark($draftWeekLesson, $student, AttendanceRecord::STATUS_PRESENT);
        $this->mark($locked, $student, AttendanceRecord::STATUS_EXCUSED);
        $this->mark($otherMonth, $student, AttendanceRecord::STATUS_ABSENT);

        $user = $this->linkStudent($student, 'anna-filter@example.test');

        $json = $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/attendance?month=2026-09')
            ->assertOk()
            ->assertJsonPath('data.summary.marked', 3)
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonPath('data.summary.absent', 1)
            ->assertJsonPath('data.summary.excused', 1)
            ->json('data');

        $titles = array_column($json['records'], 'title');
        $this->assertEqualsCanonicalizing(['Visible', 'Moved', 'Locked'], $titles);
        $this->assertNotContains('Cancelled', $titles);
        $this->assertNotContains('Scheduled', $titles);
        $this->assertNotContains('Draft lesson', $titles);
        $this->assertNotContains('Draft week', $titles);
        $this->assertNotContains('August', $titles);
    }

    public function test_teacher_cannot_access_history_endpoints(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $teacherUser = $this->linkTeacher($teacher, 'teacher-history@example.test');
        $token = $this->loginToken($teacherUser);

        $this->withToken($token)
            ->getJson('/api/v1/attendance')
            ->assertForbidden();

        $this->forgetAuthGuards();

        $this->withToken($token)
            ->getJson('/api/v1/children/'.$student->id.'/attendance')
            ->assertForbidden();
    }

    public function test_parent_cannot_use_student_history_endpoint(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $parentUser = $this->linkParentWithChildren('parent-history-access@example.test', [$student]);

        $this->withToken($this->loginToken($parentUser))
            ->getJson('/api/v1/attendance')
            ->assertForbidden();
    }

    public function test_student_cannot_use_parent_history_endpoint(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $studentUser = $this->linkStudent($student, 'student-history-access@example.test');

        $this->withToken($this->loginToken($studentUser))
            ->getJson('/api/v1/children/'.$student->id.'/attendance')
            ->assertForbidden();
    }

    public function test_staff_cannot_access_history(): void
    {
        $staff = $this->makeStaffAdmin();
        $staffToken = $staff->createToken('Owl iPhone', ['mobile'])->plainTextToken;

        $this->withToken($staffToken)
            ->getJson('/api/v1/attendance')
            ->assertUnauthorized();
    }

    public function test_existing_teacher_attendance_api_is_unchanged(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-regression@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-09', '16:00:00', '17:00:00', 'Danza');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance')
            ->assertOk()
            ->assertJsonPath('data.attendance_editable', true)
            ->assertJsonPath('data.students.0.id', $student->id);
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

    private function mark(ScheduledLesson $lesson, Student $student, string $status): AttendanceRecord
    {
        return AttendanceRecord::query()->create([
            'scheduled_lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => $status,
            'marked_by' => null,
            'marked_at' => now(),
        ]);
    }

    private function linkStudent(Student $student, string $email): User
    {
        return $this->identity->createAndLink($student, [
            'name' => $student->displayName(),
            'email' => $email,
            'password' => 'password',
        ]);
    }

    private function linkTeacher(Teacher $teacher, string $email): User
    {
        return $this->identity->createAndLink($teacher, [
            'name' => $teacher->displayName(),
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
        $start = Carbon::parse($monday);

        return ScheduleWeek::query()->create([
            'week_start_date' => $monday,
            'week_end_date' => $start->copy()->addDays(4)->toDateString(),
            'title' => 'Test week',
            'status' => $status,
            'published_at' => in_array($status, [ScheduleWeek::STATUS_PUBLISHED, ScheduleWeek::STATUS_LOCKED], true) ? now() : null,
        ]);
    }

    private function makePublishedWeek(string $monday): ScheduleWeek
    {
        return $this->makeWeek($monday, ScheduleWeek::STATUS_PUBLISHED);
    }

    private function makeLesson(
        ScheduleWeek $week,
        AcademyClass $class,
        Teacher $teacher,
        string $date,
        string $startsAt,
        string $endsAt,
        string $title,
        string $status = ScheduledLesson::STATUS_PUBLISHED,
    ): ScheduledLesson {
        $building = AcademyBuilding::query()->firstOrFail();
        $room = AcademyRoom::query()->where('academy_building_id', $building->id)->firstOrFail();
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
        ]);
    }

    private function makeStaffAdmin(): User
    {
        $role = Role::query()->where('slug', 'administrator')->firstOrFail();

        return User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin-attendance-history@example.test',
            'role_id' => $role->id,
            'account_type' => User::TYPE_STAFF,
            'is_active' => true,
            'can_write' => true,
            'can_delete' => true,
            'password' => 'password',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertSensitive(array $payload): void
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        foreach ([
            'marked_by',
            'marked_at',
            'tax_code',
            'medical',
            'notes',
            'documents',
            'password',
            'email',
            'phone',
            'parent',
            'ai_',
            'prompt',
        ] as $fragment) {
            $this->assertStringNotContainsString('"'.$fragment, $json);
        }
        $this->assertStringNotContainsString('secret-note', $json);
        $this->assertStringNotContainsString('VRDLCU10A01H501X', $json);
        $this->assertStringNotContainsString('anna-secret@example.test', $json);
        $this->assertStringNotContainsString('3330001111', $json);
        $this->assertStringNotContainsString('3331234567', $json);
        $this->assertStringNotContainsString('@academy.test', $json);
    }
}
