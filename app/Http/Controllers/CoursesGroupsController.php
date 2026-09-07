<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Customer;
use App\Models\Lesson;
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
                'groups' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with([
                        'customers' => fn ($customerQuery) => $customerQuery->orderBy('name'),
                        'lessons' => fn ($lessonQuery) => $lessonQuery->orderBy('lessons.name'),
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

        $students = Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'first_name', 'last_name', 'email', 'student_photo_path'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $this->studentLabel($customer),
                'email' => $customer->email,
                'student_photo_path' => $customer->student_photo_path,
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

        $sortOrder = (int) $course->groups()->max('sort_order') + 1;

        $group = $course->groups()->create([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
            'sort_order' => $sortOrder,
        ]);

        $this->activityLogger->log(
            $request,
            'created',
            'course_group',
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

    public function updateGroup(Request $request, CourseGroup $courseGroup): RedirectResponse
    {
        $courseGroup->loadMissing('course');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $before = $courseGroup->only(['name', 'color']);
        $courseGroup->update([
            'name' => $validated['name'],
            'color' => $validated['color'] ?? null,
        ]);

        $this->activityLogger->logModelChange(
            $request,
            'updated',
            'course_group',
            $courseGroup->id,
            $courseGroup->name,
            null,
            $before,
            $courseGroup->only(['name', 'color']),
        );

        return $this->redirectBack(
            $courseGroup->course->discipline,
            $this->resolveView($request->input('view')),
        );
    }

    public function destroyGroup(Request $request, CourseGroup $courseGroup): RedirectResponse
    {
        $courseGroup->loadMissing('course');
        $discipline = $courseGroup->course->discipline;
        $groupId = $courseGroup->id;
        $label = $courseGroup->name;

        $this->activityLogger->logModelChange(
            $request,
            'deleted',
            'course_group',
            $groupId,
            $label,
            null,
            [
                'course_id' => $courseGroup->course_id,
                'course_name' => $courseGroup->course->name,
                'name' => $courseGroup->name,
            ],
        );

        $courseGroup->delete();

        return $this->redirectBack($discipline, $this->resolveView($request->input('view')));
    }

    public function attachStudent(Request $request, CourseGroup $courseGroup): RedirectResponse
    {
        $validated = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['required', 'integer', 'exists:customers,id'],
            'confirm_move' => ['sometimes', 'boolean'],
        ]);

        $courseGroup->loadMissing('course');

        $requestedIds = array_values(array_unique(array_map('intval', $validated['customer_ids'])));
        $existingIds = $courseGroup->customers()
            ->whereIn('customers.id', $requestedIds)
            ->pluck('customers.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $newIds = array_values(array_diff($requestedIds, $existingIds));

        if ($newIds === []) {
            return $this->redirectBack($courseGroup->course->discipline, 'students')->withErrors([
                'customer_ids' => 'Selected students are already in this group.',
            ]);
        }

        $discipline = $courseGroup->course->discipline;
        $confirmMove = (bool) ($validated['confirm_move'] ?? false);
        $occupiedIds = $this->findStudentsInOtherGroups($courseGroup->id, $newIds);

        if ($occupiedIds !== [] && ! $confirmMove) {
            return $this->redirectBack($discipline, 'students')->withErrors([
                'customer_ids' => 'Some selected students are already assigned to another group.',
            ]);
        }

        $this->detachStudentsFromOtherGroups(
            $request,
            $courseGroup->id,
            $newIds,
        );

        $attachData = collect($newIds)
            ->mapWithKeys(static fn (int $id): array => [$id => ['discipline' => $discipline]])
            ->all();

        $courseGroup->customers()->attach($attachData);

        $customers = Customer::query()->whereIn('id', $newIds)->get();

        foreach ($customers as $customer) {
            $this->activityLogger->log(
                $request,
                'created',
                'course_group_student',
                $courseGroup->id,
                $this->studentLabel($customer),
                $customer->id,
                [
                    'attributes' => [
                        'group_id' => $courseGroup->id,
                        'group_name' => $courseGroup->name,
                        'course_name' => $courseGroup->course->name,
                        'customer_id' => $customer->id,
                        'customer_name' => $this->studentLabel($customer),
                    ],
                ],
            );
        }

        return $this->redirectBack($discipline, 'students');
    }

    public function detachStudent(Request $request, CourseGroup $courseGroup, Customer $customer): RedirectResponse
    {
        $courseGroup->loadMissing('course');

        if (! $courseGroup->customers()->where('customers.id', $customer->id)->exists()) {
            return $this->redirectBack($courseGroup->course->discipline, 'students');
        }

        $courseGroup->customers()->detach($customer->id);

        $this->activityLogger->log(
            $request,
            'deleted',
            'course_group_student',
            $courseGroup->id,
            $this->studentLabel($customer),
            $customer->id,
            [
                'attributes' => [
                    'group_id' => $courseGroup->id,
                    'group_name' => $courseGroup->name,
                    'course_name' => $courseGroup->course->name,
                    'customer_id' => $customer->id,
                    'customer_name' => $this->studentLabel($customer),
                ],
            ],
        );

        return $this->redirectBack($courseGroup->course->discipline, 'students');
    }

    public function attachLesson(Request $request, CourseGroup $courseGroup): RedirectResponse
    {
        $courseGroup->loadMissing('course');
        $discipline = $courseGroup->course->discipline;
        $lessonDiscipline = $this->lessonDisciplineFor($discipline);

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
        $existingIds = $courseGroup->lessons()
            ->whereIn('lessons.id', $lessonIds)
            ->pluck('lessons.id')
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

        $newLessonIds = array_column($newItems, 'lesson_id');

        $lessons = Lesson::query()
            ->whereIn('id', $newLessonIds)
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

            $courseGroup->lessons()->attach($item['lesson_id'], [
                'teacher_id' => $item['teacher_id'],
                'hours' => $item['hours'],
            ]);

            $teacherName = Teacher::query()->whereKey($item['teacher_id'])->value('name');

            $this->activityLogger->log(
                $request,
                'created',
                'course_group_lesson',
                $courseGroup->id,
                $lesson->name,
                $lesson->id,
                [
                    'attributes' => [
                        'group_id' => $courseGroup->id,
                        'group_name' => $courseGroup->name,
                        'course_name' => $courseGroup->course->name,
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

    public function detachLesson(Request $request, CourseGroup $courseGroup, Lesson $lesson): RedirectResponse
    {
        $courseGroup->loadMissing('course');

        if (! $courseGroup->lessons()->where('lessons.id', $lesson->id)->exists()) {
            return $this->redirectBack($courseGroup->course->discipline, 'lessons');
        }

        $assignment = DB::table('course_group_lesson')
            ->where('course_group_id', $courseGroup->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        $courseGroup->lessons()->detach($lesson->id);

        $teacherName = $assignment
            ? Teacher::query()->whereKey($assignment->teacher_id)->value('name')
            : null;

        $this->activityLogger->log(
            $request,
            'deleted',
            'course_group_lesson',
            $courseGroup->id,
            $lesson->name,
            $lesson->id,
            [
                'attributes' => [
                    'group_id' => $courseGroup->id,
                    'group_name' => $courseGroup->name,
                    'course_name' => $courseGroup->course->name,
                    'lesson_id' => $lesson->id,
                    'lesson_name' => $lesson->name,
                    'teacher_id' => $assignment?->teacher_id,
                    'teacher_name' => $teacherName,
                    'hours' => $assignment?->hours,
                ],
            ],
        );

        return $this->redirectBack($courseGroup->course->discipline, 'lessons');
    }

    public function updateGroupLesson(Request $request, CourseGroup $courseGroup, Lesson $lesson): RedirectResponse
    {
        $courseGroup->loadMissing('course');
        $discipline = $courseGroup->course->discipline;

        if (! DB::table('course_group_lesson')
            ->where('course_group_id', $courseGroup->id)
            ->where('lesson_id', $lesson->id)
            ->exists()) {
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

        $existingRows = DB::table('course_group_lesson')
            ->where('course_group_id', $courseGroup->id)
            ->where('lesson_id', $lesson->id)
            ->get()
            ->keyBy('teacher_id');

        DB::transaction(function () use ($courseGroup, $lesson, $assignments, $validated): void {
            $lesson->update([
                'duration_minutes' => (int) $validated['duration_minutes'],
            ]);

            DB::table('course_group_lesson')
                ->where('course_group_id', $courseGroup->id)
                ->where('lesson_id', $lesson->id)
                ->delete();

            $now = now();

            foreach ($assignments as $assignment) {
                DB::table('course_group_lesson')->insert([
                    'course_group_id' => $courseGroup->id,
                    'lesson_id' => $lesson->id,
                    'teacher_id' => $assignment['teacher_id'],
                    'hours' => $assignment['hours'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        $teacherNames = Teacher::query()
            ->whereIn('id', array_column($assignments, 'teacher_id'))
            ->pluck('name', 'id');

        $this->activityLogger->log(
            $request,
            'updated',
            'course_group_lesson',
            $courseGroup->id,
            $lesson->name,
            $lesson->id,
            [
                'old' => $existingRows->map(static fn ($row): array => [
                    'teacher_id' => (int) $row->teacher_id,
                    'hours' => (int) $row->hours,
                ])->values()->all(),
                'attributes' => [
                    'group_id' => $courseGroup->id,
                    'group_name' => $courseGroup->name,
                    'course_name' => $courseGroup->course->name,
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
            'groups' => $course->groups->map(fn (CourseGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'color' => $group->color,
                'students' => $group->customers->map(fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'name' => $this->studentLabel($customer),
                    'email' => $customer->email,
                    'student_photo_path' => $customer->student_photo_path,
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
        $rows = DB::table('course_group_lesson as cgl')
            ->join('lessons as l', 'l.id', '=', 'cgl.lesson_id')
            ->where('cgl.course_group_id', $groupId)
            ->orderBy('l.name')
            ->orderBy('cgl.teacher_id')
            ->get([
                'l.id as lesson_id',
                'l.name',
                'l.description',
                'l.duration_minutes',
                'cgl.teacher_id',
                'cgl.hours',
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

    private function studentLabel(Customer $customer): string
    {
        $name = trim((string) $customer->name);
        if ($name !== '') {
            return $name;
        }

        $fullName = trim(implode(' ', array_filter([
            $customer->first_name,
            $customer->last_name,
        ])));

        return $fullName !== '' ? $fullName : ('Student #'.$customer->id);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function studentAssignmentsPayload(): array
    {
        return DB::table('course_group_customer as cgc')
            ->join('course_groups as cg', 'cg.id', '=', 'cgc.course_group_id')
            ->join('courses as c', 'c.id', '=', 'cg.course_id')
            ->select([
                'cgc.customer_id',
                'cg.id as group_id',
                'cg.name as group_name',
                'c.name as course_name',
                'c.discipline',
            ])
            ->get()
            ->mapWithKeys(static fn ($row): array => [
                (int) $row->customer_id => [
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
        return DB::table('course_group_lesson as cgl')
            ->join('course_groups as cg', 'cg.id', '=', 'cgl.course_group_id')
            ->join('courses as c', 'c.id', '=', 'cg.course_id')
            ->select([
                'cgl.lesson_id',
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
     * @param  list<int>  $customerIds
     * @return list<int>
     */
    private function findStudentsInOtherGroups(int $targetGroupId, array $customerIds): array
    {
        if ($customerIds === []) {
            return [];
        }

        return DB::table('course_group_customer')
            ->where('course_group_id', '!=', $targetGroupId)
            ->whereIn('customer_id', $customerIds)
            ->pluck('customer_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $customerIds
     */
    private function detachStudentsFromOtherGroups(
        Request $request,
        int $targetGroupId,
        array $customerIds,
    ): void {
        if ($customerIds === []) {
            return;
        }

        $assignments = DB::table('course_group_customer')
            ->join('course_groups', 'course_groups.id', '=', 'course_group_customer.course_group_id')
            ->join('courses', 'courses.id', '=', 'course_groups.course_id')
            ->where('course_group_customer.course_group_id', '!=', $targetGroupId)
            ->whereIn('course_group_customer.customer_id', $customerIds)
            ->select([
                'course_group_customer.customer_id',
                'course_group_customer.course_group_id',
                'course_groups.name as group_name',
                'courses.name as course_name',
            ])
            ->get();

        if ($assignments->isEmpty()) {
            return;
        }

        $customers = Customer::query()
            ->whereIn('id', $assignments->pluck('customer_id')->unique()->all())
            ->get()
            ->keyBy('id');

        foreach ($assignments as $assignment) {
            DB::table('course_group_customer')
                ->where('course_group_id', $assignment->course_group_id)
                ->where('customer_id', $assignment->customer_id)
                ->delete();

            $customer = $customers->get($assignment->customer_id);
            if (! $customer) {
                continue;
            }

            $this->activityLogger->log(
                $request,
                'deleted',
                'course_group_student',
                (int) $assignment->course_group_id,
                $this->studentLabel($customer),
                $customer->id,
                [
                    'attributes' => [
                        'group_id' => (int) $assignment->course_group_id,
                        'group_name' => $assignment->group_name,
                        'course_name' => $assignment->course_name,
                        'customer_id' => $customer->id,
                        'customer_name' => $this->studentLabel($customer),
                        'moved_to_group_id' => $targetGroupId,
                    ],
                ],
            );
        }
    }
}
