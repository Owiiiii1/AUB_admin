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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private AccountIdentityService $identity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identity = $this->app->make(AccountIdentityService::class);
    }

    public function test_teacher_sees_sorted_roster_and_existing_marks(): void
    {
        $class = $this->makeClass('Classe A');
        $anna = $this->makeStudentInClass('Anna', 'Rossi', $class, [
            'notes' => 'secret-note',
            'tax_code' => 'VRDLCU10A01H501X',
            'email' => 'anna-secret@example.test',
            'phone' => '3330001111',
            'residence_address' => 'Via Segreta 1',
        ]);
        $bruno = $this->makeStudentInClass('Bruno', 'Neri', $class);
        $outsider = $this->makeStudent('Lucia', 'Bianchi');
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-att@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:30:00', 'Danza classica');
        AttendanceRecord::query()->create([
            'scheduled_lesson_id' => $lesson->id,
            'student_id' => $anna->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'marked_by' => $user->id,
            'marked_at' => now(),
        ]);

        $json = $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lesson.id', $lesson->id)
            ->assertJsonPath('data.lesson.title', 'Danza classica')
            ->assertJsonPath('data.attendance_editable', true)
            ->assertJsonPath('data.reason', null)
            ->json('data');

        $this->assertCount(2, $json['students']);
        $this->assertSame($bruno->id, $json['students'][0]['id']);
        $this->assertSame($anna->id, $json['students'][1]['id']);
        $this->assertSame('present', $json['students'][1]['attendance']['status']);
        $this->assertNull($json['students'][0]['attendance']);
        $ids = array_column($json['students'], 'id');
        $this->assertNotContains($outsider->id, $ids);
        $this->assertSensitive($json);
        $encoded = json_encode($json, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('secret-note', $encoded);
        $this->assertStringNotContainsString('VRDLCU10A01H501X', $encoded);
        $this->assertStringNotContainsString('anna-secret@example.test', $encoded);
        $this->assertStringNotContainsString('3330001111', $encoded);
        $this->assertStringNotContainsString('Via Segreta 1', $encoded);
    }

    public function test_teacher_cannot_access_another_teachers_lesson(): void
    {
        $class = $this->makeClass('Classe A');
        $this->makeStudentInClass('Anna', 'Rossi', $class);
        $owner = $this->makeTeacher('Elena', 'Verdi');
        $other = $this->makeTeacher('Marco', 'Neri');
        $user = $this->linkTeacher($other, 'teacher-other@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $owner, '2026-09-07', '16:00:00', '17:00:00', 'Danza');

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');

        $this->forgetAuthGuards();

        $this->withToken($this->loginToken($user))
            ->putJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance', [
                'attendance' => [['student_id' => 1, 'status' => 'present']],
            ])
            ->assertNotFound();
    }

    public function test_student_and_parent_cannot_use_attendance_api(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Mario', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Danza');
        $studentUser = $this->linkStudent($student, 'student-att@example.test');
        $parentUser = $this->linkParentWithChildren('parent-att@example.test', [$student]);

        $this->withToken($this->loginToken($studentUser))
            ->getJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance')
            ->assertForbidden();

        $this->forgetAuthGuards();

        $this->withToken($this->loginToken($parentUser))
            ->putJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance', [
                'attendance' => [['student_id' => $student->id, 'status' => 'present']],
            ])
            ->assertForbidden();
    }

    public function test_staff_cannot_use_attendance_api(): void
    {
        $class = $this->makeClass('Classe A');
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Danza');
        $staff = $this->makeStaffAdmin();
        $token = $staff->createToken('Owl iPhone', ['mobile'])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance')
            ->assertUnauthorized();
    }

    public function test_draft_week_and_unpublished_lessons_are_inaccessible(): void
    {
        $class = $this->makeClass('Classe A');
        $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-draft-att@example.test');
        $draftWeek = $this->makeWeek('2026-09-07', ScheduleWeek::STATUS_DRAFT);
        $draftLesson = $this->makeLesson($draftWeek, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Draft week', ScheduledLesson::STATUS_PUBLISHED);
        $publishedWeek = $this->makePublishedWeek('2026-09-14');
        $scheduled = $this->makeLesson($publishedWeek, $class, $teacher, '2026-09-14', '16:00:00', '17:00:00', 'Scheduled', ScheduledLesson::STATUS_SCHEDULED);
        $draftStatus = $this->makeLesson($publishedWeek, $class, $teacher, '2026-09-14', '17:00:00', '18:00:00', 'Draft lesson', ScheduledLesson::STATUS_DRAFT);

        $token = $this->loginToken($user);
        $this->withToken($token)->getJson('/api/v1/teacher/lessons/'.$draftLesson->id.'/attendance')->assertNotFound();
        $this->forgetAuthGuards();
        $this->withToken($token)->getJson('/api/v1/teacher/lessons/'.$scheduled->id.'/attendance')->assertNotFound();
        $this->forgetAuthGuards();
        $this->withToken($token)->getJson('/api/v1/teacher/lessons/'.$draftStatus->id.'/attendance')->assertNotFound();
    }

    public function test_moved_lesson_is_editable_and_cancelled_is_read_only(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-status-att@example.test');
        $week = $this->makeWeek('2026-09-07', ScheduleWeek::STATUS_LOCKED);
        $moved = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Moved', ScheduledLesson::STATUS_MOVED);
        $cancelled = $this->makeLesson($week, $class, $teacher, '2026-09-07', '18:00:00', '19:00:00', 'Cancelled', ScheduledLesson::STATUS_CANCELLED);

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/teacher/lessons/'.$moved->id.'/attendance')
            ->assertOk()
            ->assertJsonPath('data.attendance_editable', true);

        $this->forgetAuthGuards();

        $this->withToken($this->loginToken($user))
            ->getJson('/api/v1/teacher/lessons/'.$cancelled->id.'/attendance')
            ->assertOk()
            ->assertJsonPath('data.attendance_editable', false)
            ->assertJsonPath('data.reason', 'cancelled')
            ->assertJsonPath('data.students.0.id', $student->id);

        $this->forgetAuthGuards();

        $this->withToken($this->loginToken($user))
            ->putJson('/api/v1/teacher/lessons/'.$cancelled->id.'/attendance', [
                'attendance' => [['student_id' => $student->id, 'status' => 'present']],
            ])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'attendance_not_editable');
    }

    public function test_put_upserts_updates_and_null_removes_marks(): void
    {
        $class = $this->makeClass('Classe A');
        $anna = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $bruno = $this->makeStudentInClass('Bruno', 'Neri', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-save@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Danza');

        $token = $this->loginToken($user);
        $this->withToken($token)
            ->putJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance', [
                'attendance' => [
                    ['student_id' => $anna->id, 'status' => 'present'],
                    ['student_id' => $bruno->id, 'status' => 'absent'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.students.0.attendance.status', 'absent')
            ->assertJsonPath('data.students.1.attendance.status', 'present');

        $this->assertDatabaseHas('attendance_records', [
            'scheduled_lesson_id' => $lesson->id,
            'student_id' => $anna->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'marked_by' => $user->id,
        ]);
        $this->assertNotNull(AttendanceRecord::query()->where('student_id', $anna->id)->value('marked_at'));

        $this->forgetAuthGuards();
        $this->withToken($token)
            ->putJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance', [
                'attendance' => [
                    ['student_id' => $anna->id, 'status' => 'excused'],
                    ['student_id' => $bruno->id, 'status' => null],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $anna->id,
            'status' => AttendanceRecord::STATUS_EXCUSED,
            'marked_by' => $user->id,
        ]);
        $this->assertDatabaseMissing('attendance_records', [
            'scheduled_lesson_id' => $lesson->id,
            'student_id' => $bruno->id,
        ]);

        $log = \App\Models\ActivityLog::query()->where('action', 'attendance.updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($lesson->id, $log->properties['scheduled_lesson_id'] ?? null);
        $this->assertSame(2, $log->properties['changed_count'] ?? null);
        $this->assertArrayNotHasKey('students', $log->properties ?? []);
        $this->assertArrayNotHasKey('attendance', $log->properties ?? []);
        $this->assertStringNotContainsString('Rossi', json_encode($log->properties));

        $this->forgetAuthGuards();
        $this->withToken($token)
            ->getJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance')
            ->assertOk()
            ->assertJsonPath('data.students.1.attendance.status', 'excused')
            ->assertJsonPath('data.students.0.attendance', null);
    }

    public function test_arbitrary_student_is_rejected_atomically(): void
    {
        $class = $this->makeClass('Classe A');
        $anna = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $stranger = $this->makeStudent('Lucia', 'Bianchi');
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-reject@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Danza');

        $this->withToken($this->loginToken($user))
            ->putJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance', [
                'attendance' => [
                    ['student_id' => $anna->id, 'status' => 'present'],
                    ['student_id' => $stranger->id, 'status' => 'absent'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $class = $this->makeClass('Classe A');
        $anna = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-late@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Danza');

        $this->withToken($this->loginToken($user))
            ->putJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance', [
                'attendance' => [
                    ['student_id' => $anna->id, 'status' => 'late'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_unique_index_prevents_duplicate_records(): void
    {
        $class = $this->makeClass('Classe A');
        $student = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-unique@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Danza');

        AttendanceRecord::query()->create([
            'scheduled_lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'marked_by' => $user->id,
            'marked_at' => now(),
        ]);

        $this->expectException(UniqueConstraintViolationException::class);
        AttendanceRecord::query()->create([
            'scheduled_lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => AttendanceRecord::STATUS_ABSENT,
            'marked_by' => $user->id,
            'marked_at' => now(),
        ]);
    }

    public function test_omitted_roster_rows_are_left_unchanged(): void
    {
        $class = $this->makeClass('Classe A');
        $anna = $this->makeStudentInClass('Anna', 'Rossi', $class);
        $bruno = $this->makeStudentInClass('Bruno', 'Neri', $class);
        $teacher = $this->makeTeacher('Elena', 'Verdi');
        $user = $this->linkTeacher($teacher, 'teacher-partial@example.test');
        $week = $this->makePublishedWeek('2026-09-07');
        $lesson = $this->makeLesson($week, $class, $teacher, '2026-09-07', '16:00:00', '17:00:00', 'Danza');
        AttendanceRecord::query()->create([
            'scheduled_lesson_id' => $lesson->id,
            'student_id' => $bruno->id,
            'status' => AttendanceRecord::STATUS_ABSENT,
            'marked_by' => $user->id,
            'marked_at' => now(),
        ]);

        $this->withToken($this->loginToken($user))
            ->putJson('/api/v1/teacher/lessons/'.$lesson->id.'/attendance', [
                'attendance' => [
                    ['student_id' => $anna->id, 'status' => 'present'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $bruno->id,
            'status' => AttendanceRecord::STATUS_ABSENT,
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $anna->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
        ]);
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
            'email' => 'admin-attendance@example.test',
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
            'tax_code',
            'medical_certificate',
            'notes',
            'password',
            'email',
            'phone',
            'parent',
            'documents',
            'residence',
            'medical',
            'documents',
        ] as $fragment) {
            $this->assertStringNotContainsString('"'.$fragment, $json);
        }
    }
}
