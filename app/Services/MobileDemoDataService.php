<?php

namespace App\Services;

use App\Models\AcademicYear;
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
use App\Services\WeeklySchedule\ScheduleConflictService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MobileDemoDataService
{
    public const NOTES_MARKER = '__mobile_demo__';

    public const STUDENT_EMAIL = 'student@admin.com';

    public const PARENT_EMAIL = 'parent@admin.com';

    public const TEACHER_EMAIL = 'teacher@admin.com';

    /**
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const DAY_SLOTS = [
        ['08:30:00', '10:00:00', 'LEZIONE CLASSICO'],
        ['10:15:00', '11:45:00', 'LEZIONE PILATES'],
        ['16:00:00', '17:30:00', 'LEZIONE CONTEMPORANEO'],
    ];

    /**
     * @var list<array{first: string, last: string, email: string, gender: string, birth_date: string, phone: string, address: string, city: string, postal: string}>
     */
    private const CLASSMATES = [
        ['first' => 'Giulia', 'last' => 'Bianchi', 'email' => 'giulia.bianchi.demo@example.test', 'gender' => 'female', 'birth_date' => '2010-03-14', 'phone' => '+39 333 441 2201', 'address' => 'Via Torino 18', 'city' => 'Milano (MI)', 'postal' => '20123'],
        ['first' => 'Luca', 'last' => 'Conti', 'email' => 'luca.conti.demo@example.test', 'gender' => 'male', 'birth_date' => '2009-11-02', 'phone' => '+39 347 882 3302', 'address' => 'Via Padova 54', 'city' => 'Milano (MI)', 'postal' => '20127'],
        ['first' => 'Sofia', 'last' => 'Ricci', 'email' => 'sofia.ricci.demo@example.test', 'gender' => 'female', 'birth_date' => '2010-07-21', 'phone' => '+39 320 554 4403', 'address' => 'Corso Buenos Aires 12', 'city' => 'Milano (MI)', 'postal' => '20124'],
        ['first' => 'Elena', 'last' => 'Greco', 'email' => 'elena.greco.demo@example.test', 'gender' => 'female', 'birth_date' => '2009-05-09', 'phone' => '+39 328 990 5504', 'address' => 'Via Ripamonti 88', 'city' => 'Milano (MI)', 'postal' => '20141'],
        ['first' => 'Marco', 'last' => 'Neri', 'email' => 'marco.neri.demo@example.test', 'gender' => 'male', 'birth_date' => '2009-01-30', 'phone' => '+39 331 204 6605', 'address' => 'Viale Monza 41', 'city' => 'Milano (MI)', 'postal' => '20125'],
    ];

    /**
     * @return array<string, int|string>
     */
    public function fill(): array
    {
        return DB::transaction(function (): array {
            $now = Carbon::now((string) config('app.timezone', 'Europe/Rome'));
            $currentMonday = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $mondays = [
                $currentMonday->copy()->subDays(7),
                $currentMonday,
                $currentMonday->copy()->addDays(7),
            ];

            $staff = $this->ensureStaff();
            $course = $this->ensureCourse();
            $class = $this->ensureClass($course);
            $teacher = $this->ensureLinkedTeacher();
            $student = $this->ensureLinkedStudent();
            $parent = $this->ensureLinkedParent();
            $this->enroll($student, $class);
            $parent->students()->syncWithoutDetaching([
                $student->id => ['relation_type' => 'mother'],
            ]);

            $classmates = [];
            foreach (self::CLASSMATES as $row) {
                $mate = $this->ensureStudent($row['first'], $row['last'], $row['email']);
                $this->applyStudentDetails($mate, $row);
                $this->enroll($mate, $class);
                $parent->students()->syncWithoutDetaching([
                    $mate->id => ['relation_type' => 'guardian'],
                ]);
                $classmates[] = $mate;
            }

            $this->applyStudentDetails($student, [
                'gender' => 'male',
                'birth_date' => '2009-04-18',
                'phone' => '+39 333 120 8801',
                'address' => 'Via Padova 128',
                'city' => 'Milano (MI)',
                'postal' => '20127',
            ]);
            $this->attachPortraitIfPresent($student);
            $this->attachTeacherPortraitIfPresent($teacher);
            foreach ($classmates as $mate) {
                $this->attachPortraitIfPresent($mate);
            }

            $roster = collect([$student, ...$classmates]);
            $room = $this->pickRoom();
            $subjects = $this->ensureSubjects();

            $weeksCreated = 0;
            $lessonsCreated = 0;

            foreach ($mondays as $monday) {
                $week = $this->ensurePublishedWeek($monday, $staff);
                $weeksCreated++;
                $this->removeDemoLessons($week);
                $lessonsCreated += $this->placeWeekLessons(
                    $week,
                    $class,
                    $teacher,
                    $room,
                    $subjects,
                    $monday,
                    $currentMonday,
                );
            }

            $attendanceCreated = $this->markPastAttendance($roster, $staff, $now);
            $this->ensurePrimaryStudentHasAllStatuses($student, $staff, $now);

            return [
                'weeks' => $weeksCreated,
                'lessons' => $lessonsCreated,
                'attendance' => AttendanceRecord::query()->count(),
                'marked_this_run' => $attendanceCreated,
                'class_id' => $class->id,
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'parent_id' => $parent->id,
                'current_monday' => $currentMonday->format('Y-m-d'),
            ];
        });
    }

    private function ensureStaff(): User
    {
        $staff = User::query()
            ->where('account_type', User::TYPE_STAFF)
            ->orderBy('id')
            ->first();

        if ($staff !== null) {
            return $staff;
        }

        $role = Role::query()->where('slug', 'administrator')->firstOrFail();

        return User::query()->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'password' => 'password',
            'account_type' => User::TYPE_STAFF,
            'is_active' => true,
            'role_id' => $role->id,
            'can_write' => true,
            'can_delete' => true,
        ]);
    }

    private function ensureCourse(): Course
    {
        return Course::query()->first() ?? Course::query()->create([
            'discipline' => 'academy',
            'name' => 'Mobile Test Course',
            'sort_order' => 1,
            'study_starts_at' => '08:00:00',
            'study_ends_at' => '22:30:00',
        ]);
    }

    private function ensureClass(Course $course): AcademyClass
    {
        $named = AcademyClass::query()->where('name', 'Mobile Test')->first();
        if ($named !== null) {
            return $named;
        }

        $existing = AcademyClass::query()->orderBy('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        return AcademyClass::query()->create([
            'course_id' => $course->id,
            'name' => 'Mobile Test',
            'color' => '#8B2635',
            'sort_order' => 1,
        ]);
    }

    private function ensureLinkedTeacher(): Teacher
    {
        $user = User::query()->where('email', self::TEACHER_EMAIL)->first();
        if ($user !== null) {
            $profile = Teacher::query()->where('user_id', $user->id)->first();
            if ($profile !== null) {
                return $profile;
            }
        }

        $existing = Teacher::query()->whereNotNull('user_id')->orderBy('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        $teacher = Teacher::query()->firstOrCreate(
            ['email' => 'martina.barbieri@example.it'],
            [
                'type' => 'permanent',
                'first_name' => 'Martina',
                'last_name' => 'Barbieri',
                'name' => 'Martina Barbieri',
            ],
        );

        if ($teacher->user_id === null) {
            $this->ensureUser(self::TEACHER_EMAIL, $teacher->name, User::TYPE_TEACHER);
            $teacher->forceFill([
                'user_id' => User::query()->where('email', self::TEACHER_EMAIL)->value('id'),
            ])->save();
        }

        return $teacher->refresh();
    }

    private function ensureLinkedStudent(): Student
    {
        $user = User::query()->where('email', self::STUDENT_EMAIL)->first();
        if ($user !== null) {
            $profile = Student::query()->where('user_id', $user->id)->first();
            if ($profile !== null) {
                return $profile;
            }
        }

        $existing = Student::query()->whereNotNull('user_id')->orderBy('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        $student = $this->ensureStudent('Anna', 'Rossi', 'anna.rossi.demo@example.test');
        $this->ensureUser(self::STUDENT_EMAIL, $student->displayName(), User::TYPE_STUDENT);
        $student->forceFill([
            'user_id' => User::query()->where('email', self::STUDENT_EMAIL)->value('id'),
        ])->save();

        return $student->refresh();
    }

    private function ensureLinkedParent(): AcademyParent
    {
        $user = User::query()->where('email', self::PARENT_EMAIL)->first();
        if ($user !== null) {
            $profile = AcademyParent::query()->where('user_id', $user->id)->first();
            if ($profile !== null) {
                return $profile;
            }
        }

        $existing = AcademyParent::query()->whereNotNull('user_id')->orderBy('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        $parent = AcademyParent::query()->firstOrCreate(
            ['email' => 'parent.one@example.test'],
            [
                'first_name' => 'Maria',
                'last_name' => 'Rossi',
                'phone' => '+39000000000',
            ],
        );

        if ($parent->user_id === null) {
            $this->ensureUser(self::PARENT_EMAIL, $parent->displayName(), User::TYPE_PARENT);
            $parent->forceFill([
                'user_id' => User::query()->where('email', self::PARENT_EMAIL)->value('id'),
            ])->save();
        }

        return $parent->refresh();
    }

    private function ensureStudent(string $first, string $last, string $email): Student
    {
        return Student::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => trim($first.' '.$last),
                'first_name' => $first,
                'last_name' => $last,
                'status' => 'active',
            ],
        );
    }

    /**
     * @param  array{gender: string, birth_date: string, phone: string, address: string, city: string, postal: string}  $row
     */
    private function applyStudentDetails(Student $student, array $row): void
    {
        $student->forceFill([
            'gender' => $row['gender'],
            'birth_date' => $row['birth_date'],
            'phone' => $row['phone'],
            'residence_address' => $row['address'],
            'residence_city_province' => $row['city'],
            'residence_postal_code' => $row['postal'],
        ])->save();
    }

    private function attachPortraitIfPresent(Student $student): void
    {
        $path = "students/{$student->id}/portrait.jpg";
        if (! Storage::disk('public')->exists($path)) {
            return;
        }

        $student->forceFill(['student_photo_path' => $path])->save();
    }

    private function attachTeacherPortraitIfPresent(Teacher $teacher): void
    {
        $path = "teachers/{$teacher->id}/photos/avatar.jpg";
        if (! Storage::disk('public')->exists($path)) {
            return;
        }

        $teacher->forceFill(['photo_path' => $path])->save();
    }

    private function ensureUser(string $email, string $name, string $type): User
    {
        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            return $existing;
        }

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'account_type' => $type,
            'is_active' => true,
            'role_id' => null,
            'can_write' => false,
            'can_delete' => false,
        ]);
    }

    private function enroll(Student $student, AcademyClass $class): void
    {
        $already = DB::table('academy_class_student')
            ->where('student_id', $student->id)
            ->exists();

        if ($already) {
            return;
        }

        $class->students()->attach($student->id);
    }

    private function pickRoom(): AcademyRoom
    {
        $named = AcademyRoom::query()->where('name', 'SALA VAGANOVA')->first();
        if ($named !== null) {
            return $named;
        }

        return AcademyRoom::query()->orderBy('id')->firstOrFail();
    }

    /**
     * @return array<string, Lesson>
     */
    private function ensureSubjects(): array
    {
        $names = [
            'LEZIONE CLASSICO',
            'LEZIONE PILATES',
            'LEZIONE CONTEMPORANEO',
            'LEZIONE IMPROVVISAZIONE LUNELLA',
        ];

        $map = [];
        foreach ($names as $index => $name) {
            $map[$name] = Lesson::query()->firstOrCreate(
                ['discipline' => 'ballet', 'name' => $name],
                ['duration_minutes' => $name === 'LEZIONE IMPROVVISAZIONE LUNELLA' ? 30 : 90, 'sort_order' => $index + 1],
            );
        }

        AcademicYear::current() ?? AcademicYear::query()->create([
            'name' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-06-30',
            'is_active' => true,
        ]);

        return $map;
    }

    private function ensurePublishedWeek(Carbon $monday, User $staff): ScheduleWeek
    {
        $friday = $monday->copy()->addDays(4);
        $week = ScheduleWeek::query()->firstOrCreate(
            ['week_start_date' => $monday->format('Y-m-d')],
            [
                'week_end_date' => $friday->format('Y-m-d'),
                'title' => 'Week '.$monday->format('d M Y'),
                'status' => ScheduleWeek::STATUS_PUBLISHED,
                'work_starts_at' => ScheduleConflictService::GRID_START,
                'work_ends_at' => ScheduleConflictService::GRID_END,
                'published_at' => now(),
                'published_by' => $staff->id,
            ],
        );

        $week->update([
            'week_end_date' => $friday->format('Y-m-d'),
            'status' => ScheduleWeek::STATUS_PUBLISHED,
            'published_at' => $week->published_at ?? now(),
            'published_by' => $week->published_by ?? $staff->id,
        ]);

        return $week->refresh();
    }

    private function removeDemoLessons(ScheduleWeek $week): void
    {
        ScheduledLesson::query()
            ->where('schedule_week_id', $week->id)
            ->where('notes', self::NOTES_MARKER)
            ->delete();
    }

    /**
     * @param  array<string, Lesson>  $subjects
     */
    private function placeWeekLessons(
        ScheduleWeek $week,
        AcademyClass $class,
        Teacher $teacher,
        AcademyRoom $room,
        array $subjects,
        Carbon $monday,
        Carbon $currentMonday,
    ): int {
        $created = 0;
        $isCurrent = $monday->equalTo($currentMonday);

        for ($offset = 0; $offset < 5; $offset++) {
            $date = $monday->copy()->addDays($offset);
            $slots = self::DAY_SLOTS;

            if ($isCurrent && $offset === 4) {
                $slots[] = ['22:00:00', '22:30:00', 'LEZIONE IMPROVVISAZIONE LUNELLA'];
            }

            foreach ($slots as $index => [$startsAt, $endsAt, $subjectName]) {
                $status = ScheduledLesson::STATUS_PUBLISHED;
                if ($isCurrent && $offset === 2 && $index === 0) {
                    $status = ScheduledLesson::STATUS_CANCELLED;
                }
                if ($isCurrent && $offset === 3 && $index === 2) {
                    $status = ScheduledLesson::STATUS_MOVED;
                }

                $subject = $subjects[$subjectName];
                ScheduledLesson::query()->create([
                    'schedule_week_id' => $week->id,
                    'academy_building_id' => $room->academy_building_id,
                    'academy_room_id' => $room->id,
                    'academy_class_id' => $class->id,
                    'teacher_id' => $teacher->id,
                    'lesson_id' => $subject->id,
                    'lesson_date' => $date->format('Y-m-d'),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'title' => $subject->name,
                    'notes' => self::NOTES_MARKER,
                    'color' => $class->color,
                    'status' => $status,
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Student>  $roster
     */
    private function markPastAttendance($roster, User $staff, Carbon $now): int
    {
        $created = 0;
        $lessons = ScheduledLesson::query()
            ->where('notes', self::NOTES_MARKER)
            ->whereIn('status', [ScheduledLesson::STATUS_PUBLISHED, ScheduledLesson::STATUS_MOVED])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $statuses = [
            AttendanceRecord::STATUS_PRESENT,
            AttendanceRecord::STATUS_PRESENT,
            AttendanceRecord::STATUS_PRESENT,
            AttendanceRecord::STATUS_ABSENT,
            AttendanceRecord::STATUS_EXCUSED,
        ];

        foreach ($lessons as $lesson) {
            if (! $this->lessonHasStarted($lesson, $now)) {
                continue;
            }

            foreach ($roster as $student) {
                $status = $statuses[($student->id + $lesson->id) % count($statuses)];
                $record = AttendanceRecord::query()->firstOrCreate(
                    [
                        'scheduled_lesson_id' => $lesson->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => $status,
                        'marked_by' => $staff->id,
                        'marked_at' => $now->copy()->subMinutes(15),
                    ],
                );
                if ($record->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }

    private function ensurePrimaryStudentHasAllStatuses(Student $student, User $staff, Carbon $now): void
    {
        $needed = [
            AttendanceRecord::STATUS_PRESENT,
            AttendanceRecord::STATUS_ABSENT,
            AttendanceRecord::STATUS_EXCUSED,
        ];

        $have = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->pluck('status')
            ->unique()
            ->all();

        $missing = array_values(array_diff($needed, $have));
        if ($missing === []) {
            return;
        }

        $candidates = ScheduledLesson::query()
            ->where('notes', self::NOTES_MARKER)
            ->whereIn('status', [ScheduledLesson::STATUS_PUBLISHED, ScheduledLesson::STATUS_MOVED])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (ScheduledLesson $lesson): bool => $this->lessonHasStarted($lesson, $now))
            ->values();

        foreach ($missing as $index => $status) {
            $lesson = $candidates[$index] ?? $candidates->first();
            if ($lesson === null) {
                continue;
            }

            AttendanceRecord::query()->updateOrCreate(
                [
                    'scheduled_lesson_id' => $lesson->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => $status,
                    'marked_by' => $staff->id,
                    'marked_at' => $now->copy()->subMinutes(10),
                ],
            );
        }
    }

    private function lessonHasStarted(ScheduledLesson $lesson, Carbon $now): bool
    {
        $date = $lesson->lesson_date?->format('Y-m-d');
        $starts = substr((string) $lesson->starts_at, 0, 8);
        if ($date === null || $starts === '') {
            return false;
        }

        $start = Carbon::parse($date.' '.$starts, $now->timezone);

        return $start->lte($now);
    }
}
