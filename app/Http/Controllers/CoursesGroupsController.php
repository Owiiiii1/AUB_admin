<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AcademyClass;
use App\Models\ClassLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CoursesGroupsController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger
    ) {}

    public function index(Request $request): Response
    {
        $tab = $this->resolveTab($request->query('tab', 'academy'));
        $view = $this->resolveView($request->query('view', 'students'));
        $lessonDiscipline = $this->lessonDisciplineFor($tab);

        $courses = Course::query()
            ->where('discipline', $tab)
            ->with([
                'academyClasses' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with([
                        'students' => fn ($studentQuery) => $studentQuery->orderBy('name'),
                    ]),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $teacherNames = Teacher::query()
            ->pluck('name', 'id')
            ->map(static fn ($name, $id): string => (string) $name)
            ->all();

        $serializedCourses = $courses
            ->map(fn (Course $course): array => $this->serializeCourse($course, $teacherNames))
            ->all();

        $catalogLessons = Lesson::query()
            ->where('discipline', $lessonDiscipline)
            ->with(['teachers:id,name,first_name,last_name'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Lesson $lesson): array => $this->catalogLessonPayload($lesson))
            ->all();

        $students = Student::query()
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email', 'student_photo_path'])
            ->map(fn (Student $student): array => [
                'id' => $student->id,
                'name' => $this->studentLabel($student),
                'email' => $student->email,
                'student_photo_path' => $student->student_photo_path,
            ])
            ->all();

        return Inertia::render('CoursesGroups/Index', [
            'tab' => $tab,
            'view' => $view,
            'courses' => $serializedCourses,
            'students' => $students,
            'studentAssignments' => $this->studentAssignmentsPayload(),
            'catalogLessons' => $catalogLessons,
            'lessonAssignments' => $this->lessonAssignmentsPayload(),
        ]);
    }

    public function storeCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'discipline' => ['required', 'in:academy,tam,carcano'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $sortOrder = (int) Course::query()
            ->where('discipline', $validated['discipline'])
            ->max('sort_order') + 1;

        $course = Course::query()->create([
            'discipline' => $validated['discipline'],
            'name' => $validated['name'],
            'sort_order' => $sortOrder,
            'study_starts_at' => '08:00',
            'study_ends_at' => '13:00',
        ]);

        $this->activityLogger->logForModel(
            $request,
            'created',
            $course,
            'course',
            null,
            null,
            $course->only(['discipline', 'name', 'study_starts_at', 'study_ends_at']),
        );

        return $this->redirectBack($validated['discipline'], $this->resolveView($request->input('view')));
    }

    public function updateCourse(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'study_starts_at' => ['required', 'date_format:H:i'],
            'study_ends_at' => ['required', 'date_format:H:i', 'after:study_starts_at'],
        ]);

        $before = $course->only(['name', 'study_starts_at', 'study_ends_at']);
        $course->update([
            'name' => $validated['name'],
            'study_starts_at' => $validated['study_starts_at'],
            'study_ends_at' => $validated['study_ends_at'],
        ]);

        $this->activityLogger->logForModel(
            $request,
            'updated',
            $course,
            'course',
            null,
            $before,
            $course->only(['name', 'study_starts_at', 'study_ends_at']),
        );

        return $this->redirectBack($course->discipline, $this->resolveView($request->input('view')));
    }

    public function destroyCourse(Request $request, Course $course): RedirectResponse
    {
        $discipline = $course->discipline;
        $before = $course->only(['discipline', 'name']);
        $courseId = $course->id;
        $label = $course->name;

        $course->delete();

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'course',
            $courseId,
            $label,
            null,
            $before,
        );

        return $this->redirectBack($discipline, $this->resolveView($request->input('view')));
    }

    public function storeGroup(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $sortOrder = (int) $course->academyClasses()->max('sort_order') + 1;

        $group = $course->academyClasses()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        $this->activityLogger->log(
            $request,
            'created',
            'academy_class',
            $group->id,
            $group->name,
            null,
            [
                'attributes' => [
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'name' => $group->name,
                ],
            ],
        );

        return $this->redirectBack($course->discipline, $this->resolveView($request->input('view')));
    }

    public function updateGroup(Request $request, AcademyClass $academyClass): RedirectResponse
    {
        $academyClass->loadMissing('course');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $before = $academyClass->only(['name', 'color']);
        $academyClass->update([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        $this->activityLogger->logModelChange(
            $request,
            'updated',
            'academy_class',
            $academyClass->id,
            $academyClass->name,
            null,
            $before,
            $academyClass->only(['name', 'color']),
        );

        return $this->redirectBack(
            $academyClass->course->discipline,
            $this->resolveView($request->input('view')),
        );
    }

    public function destroyGroup(Request $request, AcademyClass $academyClass): RedirectResponse
    {
        $academyClass->loadMissing('course');
        $discipline = $academyClass->course->discipline;
        $groupId = $academyClass->id;
        $label = $academyClass->name;

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'academy_class',
            $groupId,
            $label,
            null,
            [
                'course_id' => $academyClass->course_id,
                'course_name' => $academyClass->course->name,
                'name' => $academyClass->name,
            ],
        );

        $academyClass->delete();

        return $this->redirectBack($discipline, $this->resolveView($request->input('view')));
    }

    public function attachStudent(Request $request, AcademyClass $academyClass): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['required', 'integer', 'exists:students,id'],
            'confirm_move' => ['sometimes', 'boolean'],
        ]);

        $academyClass->loadMissing('course');

        $requestedIds = array_values(array_unique(array_map('intval', $validated['student_ids'])));
        $existingIds = $academyClass->students()
            ->whereIn('students.id', $requestedIds)
            ->pluck('students.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $newIds = array_values(array_diff($requestedIds, $existingIds));

        if ($newIds === []) {
            return $this->redirectBack($academyClass->course->discipline, 'students')->withErrors([
                'student_ids' => 'Selected students are already in this group.',
            ]);
        }

        $discipline = $academyClass->course->discipline;
        $confirmMove = (bool) ($validated['confirm_move'] ?? false);
        $occupiedIds = $this->findStudentsInOtherGroups($academyClass->id, $newIds);

        if ($occupiedIds !== [] && ! $confirmMove) {
            return $this->redirectBack($discipline, 'students')->withErrors([
                'student_ids' => 'Some selected students are already assigned to another group.',
            ]);
        }

        $this->detachStudentsFromOtherGroups(
            $request,
            $academyClass->id,
            $newIds,
        );

        $academyClass->students()->attach($newIds);

        $students = Student::query()->whereIn('id', $newIds)->get();

        foreach ($students as $student) {
            $this->activityLogger->log(
                $request,
                'created',
                'academy_class_student',
                $academyClass->id,
                $this->studentLabel($student),
                null,
                [
                    'attributes' => [
                        'group_id' => $academyClass->id,
                        'group_name' => $academyClass->name,
                        'course_name' => $academyClass->course->name,
                        'student_id' => $student->id,
                        'student_name' => $this->studentLabel($student),
                    ],
                ],
                $student->id,
            );
        }

        return $this->redirectBack($discipline, 'students');
    }

    public function detachStudent(Request $request, AcademyClass $academyClass, Student $student): RedirectResponse
    {
        $academyClass->loadMissing('course');

        if (! $academyClass->students()->where('students.id', $student->id)->exists()) {
            return $this->redirectBack($academyClass->course->discipline, 'students');
        }

        $academyClass->students()->detach($student->id);

        $this->activityLogger->log(
            $request,
            'deleted',
            'academy_class_student',
            $academyClass->id,
            $this->studentLabel($student),
            null,
            [
                'attributes' => [
                    'group_id' => $academyClass->id,
                    'group_name' => $academyClass->name,
                    'course_name' => $academyClass->course->name,
                    'student_id' => $student->id,
                    'student_name' => $this->studentLabel($student),
                ],
            ],
            $student->id,
        );

        return $this->redirectBack($academyClass->course->discipline, 'students');
    }

    public function attachLesson(Request $request, AcademyClass $academyClass): RedirectResponse
    {
        $academyClass->loadMissing('course');
        $discipline = $academyClass->course->discipline;
        $lessonDiscipline = $this->lessonDisciplineFor($discipline);
        $year = $this->currentAcademicYear();

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.lesson_id' => [
                'required',
                'integer',
                Rule::exists('lessons', 'id')->where(static fn ($query) => $query->where('discipline', $lessonDiscipline)),
            ],
            'items.*.teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'items.*.hours' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $items = collect($validated['items'])
            ->map(static fn (array $item): array => [
                'lesson_id' => (int) $item['lesson_id'],
                'teacher_id' => (int) $item['teacher_id'],
                'hours' => (int) $item['hours'],
            ])
            ->unique('lesson_id')
            ->values()
            ->all();

        $lessonIds = array_column($items, 'lesson_id');
        $existingIds = ClassLesson::query()
            ->where('academy_class_id', $academyClass->id)
            ->where('academic_year_id', $year->id)
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('lesson_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $newItems = array_values(array_filter(
            $items,
            static fn (array $item): bool => ! in_array($item['lesson_id'], $existingIds, true),
        ));

        if ($newItems === []) {
            return $this->redirectBack($discipline, 'lessons')->withErrors([
                'items' => 'Selected lessons are already in this group.',
            ]);
        }

        $lessons = Lesson::query()
            ->whereIn('id', array_column($newItems, 'lesson_id'))
            ->with(['teachers:id'])
            ->get()
            ->keyBy('id');

        foreach ($newItems as $item) {
            $lesson = $lessons->get($item['lesson_id']);
            if (! $lesson) {
                continue;
            }

            $allowedTeacherIds = $lesson->teachers->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            if (! in_array($item['teacher_id'], $allowedTeacherIds, true)) {
                return $this->redirectBack($discipline, 'lessons')->withErrors([
                    'items' => 'Selected teacher is not available for one of the lessons.',
                ]);
            }
        }

        foreach ($newItems as $item) {
            $lesson = $lessons->get($item['lesson_id']);
            if (! $lesson) {
                continue;
            }

            $classLesson = ClassLesson::query()->create([
                'academic_year_id' => $year->id,
                'academy_class_id' => $academyClass->id,
                'lesson_id' => $lesson->id,
                'hours' => $item['hours'],
            ]);
            $classLesson->teachers()->attach($item['teacher_id'], ['hours' => $item['hours']]);

            $teacherName = Teacher::query()->whereKey($item['teacher_id'])->value('name');

            $this->activityLogger->log(
                $request,
                'created',
                'class_lesson',
                $classLesson->id,
                $lesson->name,
                null,
                [
                    'attributes' => [
                        'group_id' => $academyClass->id,
                        'group_name' => $academyClass->name,
                        'course_name' => $academyClass->course->name,
                        'lesson_id' => $lesson->id,
                        'lesson_name' => $lesson->name,
                        'teacher_id' => $item['teacher_id'],
                        'teacher_name' => $teacherName,
                        'hours' => $item['hours'],
                    ],
                ],
            );
        }

        return $this->redirectBack($discipline, 'lessons');
    }

    public function detachLesson(Request $request, AcademyClass $academyClass, Lesson $lesson): RedirectResponse
    {
        $academyClass->loadMissing('course');
        $year = $this->currentAcademicYear();

        $classLesson = ClassLesson::query()
            ->where('academy_class_id', $academyClass->id)
            ->where('academic_year_id', $year->id)
            ->where('lesson_id', $lesson->id)
            ->with('teachers')
            ->first();

        if (! $classLesson) {
            return $this->redirectBack($academyClass->course->discipline, 'lessons');
        }

        $firstTeacher = $classLesson->teachers->first();
        $classLesson->delete();

        $this->activityLogger->log(
            $request,
            'deleted',
            'class_lesson',
            $academyClass->id,
            $lesson->name,
            null,
            [
                'attributes' => [
                    'group_id' => $academyClass->id,
                    'group_name' => $academyClass->name,
                    'course_name' => $academyClass->course->name,
                    'lesson_id' => $lesson->id,
                    'lesson_name' => $lesson->name,
                    'teacher_id' => $firstTeacher?->id,
                    'teacher_name' => $firstTeacher?->name,
                    'hours' => $classLesson->hours,
                ],
            ],
        );

        return $this->redirectBack($academyClass->course->discipline, 'lessons');
    }

    public function updateGroupLesson(Request $request, AcademyClass $academyClass, Lesson $lesson): RedirectResponse
    {
        $academyClass->loadMissing('course');
        $discipline = $academyClass->course->discipline;
        $year = $this->currentAcademicYear();

        $classLesson = ClassLesson::query()
            ->where('academy_class_id', $academyClass->id)
            ->where('academic_year_id', $year->id)
            ->where('lesson_id', $lesson->id)
            ->with('teachers')
            ->first();

        if (! $classLesson) {
            return $this->redirectBack($discipline, 'lessons');
        }

        $validated = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'assignments.*.hours' => ['required', 'integer', 'min:1', 'max:999'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
        ]);

        $assignments = collect($validated['assignments'])
            ->map(static fn (array $item): array => [
                'teacher_id' => (int) $item['teacher_id'],
                'hours' => (int) $item['hours'],
            ])
            ->unique('teacher_id')
            ->values()
            ->all();

        $lesson->load(['teachers:id']);
        $allowedTeacherIds = $lesson->teachers
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        foreach ($assignments as $assignment) {
            if (! in_array($assignment['teacher_id'], $allowedTeacherIds, true)) {
                return $this->redirectBack($discipline, 'lessons')->withErrors([
                    'assignments' => 'Selected teacher is not available for this lesson.',
                ]);
            }
        }

        $existingRows = $classLesson->teachers->map(static fn (Teacher $teacher): array => [
            'teacher_id' => (int) $teacher->id,
            'hours' => (int) ($teacher->pivot->hours ?? $classLesson->hours),
        ]);

        DB::transaction(function () use ($classLesson, $lesson, $assignments, $validated): void {
            $lesson->update([
                'duration_minutes' => (int) $validated['duration_minutes'],
            ]);

            $classLesson->update([
                'hours' => (int) collect($assignments)->sum('hours'),
            ]);

            $sync = [];
            foreach ($assignments as $assignment) {
                $sync[$assignment['teacher_id']] = ['hours' => $assignment['hours']];
            }
            $classLesson->teachers()->sync($sync);
        });

        $teacherNames = Teacher::query()
            ->whereIn('id', array_column($assignments, 'teacher_id'))
            ->pluck('name', 'id');

        $this->activityLogger->log(
            $request,
            'updated',
            'class_lesson',
            $classLesson->id,
            $lesson->name,
            null,
            [
                'old' => $existingRows->values()->all(),
                'attributes' => [
                    'group_id' => $academyClass->id,
                    'group_name' => $academyClass->name,
                    'course_name' => $academyClass->course->name,
                    'lesson_id' => $lesson->id,
                    'lesson_name' => $lesson->name,
                    'duration_minutes' => (int) $validated['duration_minutes'],
                    'assignments' => collect($assignments)->map(static fn (array $assignment): array => [
                        'teacher_id' => $assignment['teacher_id'],
                        'teacher_name' => (string) ($teacherNames[$assignment['teacher_id']] ?? ''),
                        'hours' => $assignment['hours'],
                    ])->all(),
                ],
            ],
        );

        return $this->redirectBack($discipline, 'lessons');
    }

    private function currentAcademicYear(): AcademicYear
    {
        $year = AcademicYear::current();
        if ($year) {
            return $year;
        }

        return AcademicYear::query()->create([
            'name' => now()->year.'/'.(now()->year + 1),
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
            'is_active' => true,
        ]);
    }

    private function resolveTab(?string $tab): string
    {
        return in_array($tab, ['academy', 'tam', 'carcano'], true) ? $tab : 'academy';
    }

    private function lessonDisciplineFor(string $discipline): string
    {
        return match ($discipline) {
            'tam' => 'tam',
            'carcano' => 'carcano',
            default => 'ballet',
        };
    }

    private function resolveView(?string $view): string
    {
        return in_array($view, ['students', 'lessons'], true) ? $view : 'students';
    }

    private function redirectBack(string $discipline, string $view = 'students'): RedirectResponse
    {
        return redirect()->route('courses-groups.index', [
            'tab' => $discipline,
            'view' => $this->resolveView($view),
        ]);
    }

    /**
     * @param  array<int, string>  $teacherNames
     * @return array<string, mixed>
     */
    private function serializeCourse(Course $course, array $teacherNames): array
    {
        return [
            'id' => $course->id,
            'name' => $course->name,
            'discipline' => $course->discipline,
            'study_starts_at' => $course->studyStartsAt(),
            'study_ends_at' => $course->studyEndsAt(),
            'groups' => $course->academyClasses->map(fn (AcademyClass $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'color' => $group->color,
                'students' => $group->students->map(fn (Student $student): array => [
                    'id' => $student->id,
                    'name' => $this->studentLabel($student),
                    'email' => $student->email,
                    'student_photo_path' => $student->student_photo_path,
                ])->all(),
                'lessons' => $this->serializeGroupLessons($group->id, $teacherNames),
            ])->all(),
        ];
    }

    /**
     * @param  array<int, string>  $teacherNames
     * @return list<array<string, mixed>>
     */
    private function serializeGroupLessons(int $groupId, array $teacherNames): array
    {
        $rows = DB::table('class_lesson_teacher as clt')
            ->join('class_lessons as cl', 'cl.id', '=', 'clt.class_lesson_id')
            ->join('lessons as l', 'l.id', '=', 'cl.lesson_id')
            ->where('cl.academy_class_id', $groupId)
            ->where('cl.academic_year_id', $this->currentAcademicYear()->id)
            ->orderBy('l.name')
            ->orderBy('clt.teacher_id')
            ->get([
                'l.id as lesson_id',
                'l.name',
                'l.description',
                'l.duration_minutes',
                'clt.teacher_id',
                'clt.hours',
            ]);

        return $rows
            ->groupBy('lesson_id')
            ->map(function ($lessonRows, $lessonId) use ($teacherNames): array {
                $first = $lessonRows->first();
                $assignments = $lessonRows->map(static fn ($row): array => [
                    'teacher_id' => (int) $row->teacher_id,
                    'teacher_name' => $teacherNames[(int) $row->teacher_id] ?? ('#'.$row->teacher_id),
                    'hours' => (int) $row->hours,
                ])->values()->all();

                return [
                    'id' => (int) $lessonId,
                    'name' => (string) $first->name,
                    'description' => $first->description,
                    'duration_minutes' => $first->duration_minutes !== null
                        ? (int) $first->duration_minutes
                        : null,
                    'assignments' => $assignments,
                    'teacher_id' => $assignments[0]['teacher_id'] ?? null,
                    'teacher_name' => collect($assignments)->pluck('teacher_name')->implode(', '),
                    'hours' => (int) collect($assignments)->sum('hours'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogLessonPayload(Lesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'name' => $lesson->name,
            'description' => $lesson->description,
            'duration_minutes' => $lesson->duration_minutes,
            'teachers' => $lesson->teachers->map(static fn (Teacher $teacher): array => [
                'id' => $teacher->id,
                'name' => (string) ($teacher->name ?: trim($teacher->first_name.' '.$teacher->last_name)),
            ])->all(),
        ];
    }

    private function studentLabel(Student $student): string
    {
        $name = trim((string) $student->name);
        if ($name !== '') {
            return $name;
        }

        $fullName = trim(implode(' ', array_filter([
            $student->first_name,
            $student->last_name,
        ])));

        return $fullName !== '' ? $fullName : ('Student #'.$student->id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function studentAssignmentsPayload(): array
    {
        return DB::table('academy_class_student as cgc')
            ->join('academy_classes as cg', 'cg.id', '=', 'cgc.academy_class_id')
            ->join('courses as c', 'c.id', '=', 'cg.course_id')
            ->select([
                'cgc.student_id',
                'cg.id as group_id',
                'cg.name as group_name',
                'c.name as course_name',
                'c.discipline',
            ])
            ->get()
            ->mapWithKeys(static fn ($row): array => [
                (int) $row->student_id => [
                    'group_id' => (int) $row->group_id,
                    'group_name' => (string) $row->group_name,
                    'course_name' => (string) $row->course_name,
                    'discipline' => (string) $row->discipline,
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, list<array<string, mixed>>>
     */
    private function lessonAssignmentsPayload(): array
    {
        return DB::table('class_lessons as cl')
            ->join('academy_classes as cg', 'cg.id', '=', 'cl.academy_class_id')
            ->join('courses as c', 'c.id', '=', 'cg.course_id')
            ->select([
                'cl.lesson_id',
                'cg.id as group_id',
                'cg.name as group_name',
                'c.name as course_name',
                'c.discipline',
            ])
            ->orderBy('c.name')
            ->orderBy('cg.name')
            ->get()
            ->groupBy(static fn ($row): int => (int) $row->lesson_id)
            ->map(static fn ($rows): array => $rows->map(static fn ($row): array => [
                'group_id' => (int) $row->group_id,
                'group_name' => (string) $row->group_name,
                'course_name' => (string) $row->course_name,
                'discipline' => (string) $row->discipline,
            ])->values()->all())
            ->all();
    }

    /**
     * @param  list<int>  $studentIds
     * @return list<int>
     */
    private function findStudentsInOtherGroups(int $targetGroupId, array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        return DB::table('academy_class_student')
            ->where('academy_class_id', '!=', $targetGroupId)
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $studentIds
     */
    private function detachStudentsFromOtherGroups(
        Request $request,
        int $targetGroupId,
        array $studentIds,
    ): void {
        if ($studentIds === []) {
            return;
        }

        $assignments = DB::table('academy_class_student')
            ->join('academy_classes', 'academy_classes.id', '=', 'academy_class_student.academy_class_id')
            ->join('courses', 'courses.id', '=', 'academy_classes.course_id')
            ->where('academy_class_student.academy_class_id', '!=', $targetGroupId)
            ->whereIn('academy_class_student.student_id', $studentIds)
            ->select([
                'academy_class_student.student_id',
                'academy_class_student.academy_class_id',
                'academy_classes.name as group_name',
                'courses.name as course_name',
            ])
            ->get();

        if ($assignments->isEmpty()) {
            return;
        }

        $students = Student::query()
            ->whereIn('id', $assignments->pluck('student_id')->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($assignments as $assignment) {
            DB::table('academy_class_student')
                ->where('academy_class_id', $assignment->academy_class_id)
                ->where('student_id', $assignment->student_id)
                ->delete();

            $student = $students->get($assignment->student_id);
            if (! $student) {
                continue;
            }

            $this->activityLogger->log(
                $request,
                'deleted',
                'academy_class_student',
                (int) $assignment->academy_class_id,
                $this->studentLabel($student),
                null,
                [
                    'attributes' => [
                        'group_id' => (int) $assignment->academy_class_id,
                        'group_name' => $assignment->group_name,
                        'course_name' => $assignment->course_name,
                        'student_id' => $student->id,
                        'student_name' => $this->studentLabel($student),
                        'moved_to_group_id' => $targetGroupId,
                    ],
                ],
                $student->id,
            );
        }
    }
}
