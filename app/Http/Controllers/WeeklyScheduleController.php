<?php

namespace App\Http\Controllers;

use App\Models\AcademyBuilding;
use App\Models\CourseGroup;
use App\Models\Lesson;
use App\Models\ScheduleAiRun;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Models\Teacher;
use App\Services\ActivityLogger;
use App\Services\WeeklySchedule\ScheduleConflictService;
use App\Services\WeeklySchedule\WeeklyScheduleAiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyScheduleController extends Controller
{
    /**
     * @var list<string>
     */
    private const LESSON_STATUSES = [
        ScheduledLesson::STATUS_DRAFT,
        ScheduledLesson::STATUS_SCHEDULED,
        ScheduledLesson::STATUS_PUBLISHED,
        ScheduledLesson::STATUS_CANCELLED,
        ScheduledLesson::STATUS_MOVED,
    ];

    public function __construct(
        private readonly ScheduleConflictService $conflicts,
        private readonly ActivityLogger $activityLogger,
        private readonly WeeklyScheduleAiService $weeklyScheduleAi,
    ) {}

    public function index(Request $request): Response
    {
        $weekStart = $this->resolveWeekStart($request->query('week'));
        $week = $this->findOrCreateWeek($weekStart);

        return Inertia::render('WeeklySchedule/Index', $this->pagePayload($week));
    }

    public function storeWeek(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week_start_date' => ['required', 'date'],
        ]);

        $weekStart = Carbon::parse($validated['week_start_date'])->startOfWeek(Carbon::MONDAY);
        $week = $this->findOrCreateWeek($weekStart);

        return redirect()->route('weekly-schedule.index', [
            'week' => $week->week_start_date->format('Y-m-d'),
        ]);
    }

    public function storeLesson(Request $request, ScheduleWeek $week): RedirectResponse
    {
        $this->ensureWeekEditable($week);

        $validated = $this->validateLesson($request, $week);
        $conflicts = $this->conflicts->validatePayload($validated);

        if ($conflicts !== []) {
            return back()->withErrors([
                'lesson' => collect($conflicts)->pluck('message')->unique()->implode(' '),
                'conflicts' => $conflicts,
            ]);
        }

        $lesson = $week->scheduledLessons()->create($validated);

        $this->activityLogger->log(
            $request,
            'created',
            'scheduled_lesson',
            $lesson->id,
            $this->lessonLogLabel($lesson),
            null,
            ['schedule_week_id' => $week->id],
        );

        return back()->with('success', 'Lesson scheduled.');
    }

    public function updateLesson(Request $request, ScheduleWeek $week, ScheduledLesson $lesson): RedirectResponse
    {
        $this->ensureWeekEditable($week);
        abort_unless($lesson->schedule_week_id === $week->id, 404);

        $validated = $this->validateLesson($request, $week, $lesson->id);
        $conflicts = $this->conflicts->validatePayload($validated, $lesson->id);

        if ($conflicts !== []) {
            return back()->withErrors([
                'lesson' => collect($conflicts)->pluck('message')->unique()->implode(' '),
                'conflicts' => $conflicts,
            ]);
        }

        $before = $lesson->only([
            'lesson_date', 'starts_at', 'ends_at', 'academy_building_id', 'academy_room_id',
            'course_group_id', 'teacher_id', 'lesson_id', 'status',
        ]);

        $lesson->update($validated);

        $this->activityLogger->logModelChange(
            $request,
            'updated',
            'scheduled_lesson',
            $lesson->id,
            $this->lessonLogLabel($lesson),
            null,
            $before,
            $lesson->fresh()?->only([
                'lesson_date', 'starts_at', 'ends_at', 'academy_building_id', 'academy_room_id',
                'course_group_id', 'teacher_id', 'lesson_id', 'status',
            ]),
        );

        return back()->with('success', 'Lesson updated.');
    }

    public function destroyLesson(Request $request, ScheduleWeek $week, ScheduledLesson $lesson): RedirectResponse
    {
        $this->ensureWeekEditable($week);
        abort_unless($lesson->schedule_week_id === $week->id, 404);

        $label = $this->lessonLogLabel($lesson);
        $lesson->delete();

        $this->activityLogger->log(
            $request,
            'deleted',
            'scheduled_lesson',
            $lesson->id,
            $label,
            null,
            ['schedule_week_id' => $week->id],
        );

        return back()->with('success', 'Lesson removed.');
    }

    public function duplicateLesson(Request $request, ScheduleWeek $week, ScheduledLesson $lesson): RedirectResponse
    {
        $this->ensureWeekEditable($week);
        abort_unless($lesson->schedule_week_id === $week->id, 404);

        $copy = $lesson->replicate([
            'status',
        ]);
        $copy->status = ScheduledLesson::STATUS_SCHEDULED;
        $copy->title = trim(($lesson->title ?? '').' (copy)');

        $endsAt = $this->conflicts->normalizeTime((string) $copy->ends_at) ?? '09:00';
        $endsMinutes = $this->timeToMinutes($endsAt);
        $gridEndMinutes = $this->timeToMinutes($week->workEndsAt());
        $newStartMinutes = min($endsMinutes + 30, $gridEndMinutes - 30);
        $newEndMinutes = min($newStartMinutes + 60, $gridEndMinutes);

        $copy->starts_at = $this->minutesToTime($newStartMinutes);
        $copy->ends_at = $this->minutesToTime($newEndMinutes);
        $copy->save();

        return back()->with('success', 'Lesson duplicated.');
    }

    public function publish(Request $request, ScheduleWeek $week): RedirectResponse
    {
        $this->ensureWeekEditable($week);

        $conflicts = $this->conflicts->detectForWeek($week);
        if ($conflicts !== []) {
            return back()->withErrors([
                'publish' => 'Cannot publish while conflicts exist.',
                'conflicts' => $conflicts,
            ]);
        }

        $week->update([
            'status' => ScheduleWeek::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => $request->user()?->id,
        ]);

        $week->scheduledLessons()->update([
            'status' => ScheduledLesson::STATUS_PUBLISHED,
        ]);

        $this->activityLogger->log(
            $request,
            'published',
            'schedule_week',
            $week->id,
            $week->title ?? $week->week_start_date->format('Y-m-d'),
            null,
            ['week_start_date' => $week->week_start_date->format('Y-m-d')],
        );

        return back()->with('success', 'Schedule published.');
    }

    public function copyPrevious(Request $request, ScheduleWeek $week): RedirectResponse
    {
        $this->ensureWeekEditable($week);

        $replace = $request->boolean('replace');
        $existingCount = $week->scheduledLessons()->count();

        if ($existingCount > 0 && ! $replace) {
            return back()->withErrors([
                'copy' => 'Current week already has lessons. Confirm replace to continue.',
                'requires_confirmation' => true,
                'existing_lessons_count' => $existingCount,
            ]);
        }

        $previousStart = $week->week_start_date->copy()->subDays(7);
        $previousWeek = ScheduleWeek::query()
            ->whereDate('week_start_date', $previousStart)
            ->first();

        if ($previousWeek === null) {
            return back()->withErrors([
                'copy' => 'Previous week schedule was not found.',
            ]);
        }

        if ($replace && $existingCount > 0) {
            $week->scheduledLessons()->delete();
        }

        $previousLessons = $previousWeek->scheduledLessons()->get();
        $copied = 0;

        foreach ($previousLessons as $source) {
            $sourceDate = Carbon::parse($source->lesson_date);
            if ($sourceDate->dayOfWeekIso >= 6) {
                continue;
            }

            $week->scheduledLessons()->create([
                'academy_building_id' => $source->academy_building_id,
                'academy_room_id' => $source->academy_room_id,
                'course_group_id' => $source->course_group_id,
                'teacher_id' => $source->teacher_id,
                'lesson_id' => $source->lesson_id,
                'lesson_date' => $sourceDate->copy()->addDays(7)->format('Y-m-d'),
                'starts_at' => $source->starts_at,
                'ends_at' => $source->ends_at,
                'title' => $source->title,
                'notes' => $source->notes,
                'color' => $source->color,
                'status' => ScheduledLesson::STATUS_SCHEDULED,
            ]);
            $copied++;
        }

        $week->update(['status' => ScheduleWeek::STATUS_DRAFT]);

        $this->activityLogger->log(
            $request,
            'copied',
            'schedule_week',
            $week->id,
            $week->title ?? $week->week_start_date->format('Y-m-d'),
            null,
            [
                'from_week_id' => $previousWeek->id,
                'copied_lessons' => $copied,
            ],
        );

        return back()->with('success', "Copied {$copied} lessons from previous week.");
    }

    public function clearTimeline(Request $request, ScheduleWeek $week): RedirectResponse
    {
        $this->ensureWeekEditable($week);

        $count = $week->scheduledLessons()->count();
        $week->scheduledLessons()->delete();
        $week->update(['status' => ScheduleWeek::STATUS_DRAFT]);

        $this->activityLogger->log(
            $request,
            'cleared',
            'schedule_week',
            $week->id,
            $week->title ?? $week->week_start_date->format('Y-m-d'),
            null,
            ['removed_lessons' => $count],
        );

        return back()->with('success', "Removed {$count} lessons from the timeline.");
    }

    public function updateSettings(Request $request, ScheduleWeek $week): RedirectResponse
    {
        $this->ensureWeekEditable($week);

        $validated = $request->validate([
            'work_starts_at' => ['required', 'date_format:H:i'],
            'work_ends_at' => ['required', 'date_format:H:i', 'after:work_starts_at'],
        ]);

        $start = $this->conflicts->normalizeTime($validated['work_starts_at']);
        $end = $this->conflicts->normalizeTime($validated['work_ends_at']);

        if ($start === null || $end === null || $this->timeToMinutes($end) <= $this->timeToMinutes($start)) {
            return back()->withErrors([
                'work_ends_at' => 'Working end time must be after start time.',
            ]);
        }

        if (
            ($this->timeToMinutes($start) % ScheduleConflictService::VISUAL_STEP_MINUTES) !== 0
            || ($this->timeToMinutes($end) % ScheduleConflictService::VISUAL_STEP_MINUTES) !== 0
        ) {
            return back()->withErrors([
                'work_starts_at' => 'Working hours must use '.ScheduleConflictService::VISUAL_STEP_MINUTES.'-minute steps.',
            ]);
        }

        $week->update([
            'work_starts_at' => $start,
            'work_ends_at' => $end,
        ]);

        return back()->with('success', 'Week settings saved.');
    }

    public function aiSchedule(Request $request, ScheduleWeek $week): JsonResponse
    {
        // Planner + AI preferences regularly exceed PHP-FPM's default 30s limit;
        // without this the worker returns an HTML error page and the UI fails to parse JSON.
        if (function_exists('set_time_limit')) {
            set_time_limit(300);
        }
        @ini_set('max_execution_time', '300');

        $this->ensureWeekEditable($week);

        $validated = $request->validate([
            'prompt' => ['nullable', 'string', 'max:5000'],
            'mode' => ['required', Rule::in(['edit', 'create'])],
            'allow_partial' => ['sometimes', 'boolean'],
        ]);

        if ($validated['mode'] === 'create') {
            $removedLessons = $week->scheduledLessons()->count();
            $week->scheduledLessons()->delete();
            $week->update(['status' => ScheduleWeek::STATUS_DRAFT]);

            if ($removedLessons > 0) {
                $this->activityLogger->log(
                    $request,
                    'ai_timeline_cleared',
                    'schedule_week',
                    $week->id,
                    $week->title ?? $week->week_start_date->format('Y-m-d'),
                    null,
                    [
                        'removed_lessons' => $removedLessons,
                        'mode' => 'create',
                    ],
                );
            }
        }

        $locale = (string) $request->session()->get('locale', config('app.locale', 'it'));
        $buildings = AcademyBuilding::query()
            ->where('is_active', true)
            ->with(['activeRooms'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (AcademyBuilding $building): array => [
                'id' => $building->id,
                'name' => $building->name,
                'rooms' => $building->activeRooms->map(fn ($room): array => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'building_id' => $building->id,
                ])->all(),
            ])
            ->all();

        $scheduledLessons = $week->scheduledLessons()
            ->with([
                'building:id,name',
                'room:id,name,academy_building_id',
                'courseGroup.course:id,name,discipline',
                'teacher:id,name',
                'lesson:id,name',
            ])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ScheduledLesson $lesson): array => $this->lessonPayload($lesson))
            ->all();

        $groupLessonCards = $this->groupLessonCardsPayload($week);

        $prompt = (string) ($validated['prompt'] ?? '');
        $mode = (string) $validated['mode'];
        $allowPartial = $request->boolean('allow_partial');

        try {
            $result = $this->weeklyScheduleAi->schedule(
                $week,
                $groupLessonCards,
                $buildings,
                $this->weekDays($week),
                $scheduledLessons,
                $prompt,
                $mode,
                $locale,
                $allowPartial,
            );

            $this->storeAiRun($request, $week, [
                'status' => (string) ($result['status'] ?? ScheduleAiRun::STATUS_SUCCESS),
                'mode' => $mode,
                'locale' => $locale,
                'allow_partial' => $allowPartial,
                'prompt' => $prompt,
                'preferences' => $result['analysis']['preferences'] ?? null,
                'metrics' => [
                    'placed_count' => $result['placed_count'] ?? null,
                    'skipped_count' => $result['skipped_count'] ?? null,
                    'analysis' => $result['analysis'] ?? null,
                ],
                'report' => $result['report'] ?? $result['message'] ?? null,
                'warnings' => $result['warnings'] ?? null,
            ]);

            if (($result['status'] ?? '') === 'needs_partial_confirmation') {
                return response()->json($result);
            }

            $this->activityLogger->log(
                $request,
                'ai_scheduled',
                'schedule_week',
                $week->id,
                $week->title ?? $week->week_start_date->format('Y-m-d'),
                null,
                [
                    'mode' => $mode,
                    'prompt' => $prompt,
                    'placed_count' => $result['placed_count'] ?? 0,
                    'skipped_count' => $result['skipped_count'] ?? 0,
                    'preferences' => $result['analysis']['preferences'] ?? null,
                ],
            );

            $week->update(['status' => ScheduleWeek::STATUS_DRAFT]);

            return response()->json($result);
        } catch (\Throwable $exception) {
            $this->storeAiRun($request, $week, [
                'status' => ScheduleAiRun::STATUS_ERROR,
                'mode' => $mode,
                'locale' => $locale,
                'allow_partial' => $allowPartial,
                'prompt' => $prompt,
                'error_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeAiRun(Request $request, ScheduleWeek $week, array $payload): void
    {
        try {
            ScheduleAiRun::query()->create([
                'schedule_week_id' => $week->id,
                'user_id' => $request->user()?->id,
                'status' => (string) ($payload['status'] ?? ScheduleAiRun::STATUS_ERROR),
                'mode' => (string) ($payload['mode'] ?? 'edit'),
                'locale' => $payload['locale'] ?? null,
                'allow_partial' => (bool) ($payload['allow_partial'] ?? false),
                'prompt' => $payload['prompt'] ?? null,
                'preferences' => $payload['preferences'] ?? null,
                'metrics' => $payload['metrics'] ?? null,
                'report' => $payload['report'] ?? null,
                'warnings' => $payload['warnings'] ?? null,
                'error_message' => $payload['error_message'] ?? null,
            ]);
        } catch (\Throwable) {
            // Persistence for analysis must never break scheduling.
        }
    }

    public function conflicts(ScheduleWeek $week): Response
    {
        return Inertia::render('WeeklySchedule/Index', array_merge(
            $this->pagePayload($week),
            [
                'conflictReport' => $this->conflicts->detectForWeek($week),
            ],
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePayload(ScheduleWeek $week): array
    {
        $week->loadCount('scheduledLessons');

        $buildings = AcademyBuilding::query()
            ->where('is_active', true)
            ->with(['activeRooms'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (AcademyBuilding $building): array => [
                'id' => $building->id,
                'name' => $building->name,
                'slug' => $building->slug,
                'rooms' => $building->activeRooms->map(fn ($room): array => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'slug' => $room->slug,
                    'building_id' => $building->id,
                ])->all(),
            ])
            ->all();

        $scheduledLessons = $week->scheduledLessons()
            ->with([
                'building:id,name',
                'room:id,name,academy_building_id',
                'courseGroup.course:id,name,discipline',
                'teacher:id,name',
                'lesson:id,name',
            ])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ScheduledLesson $lesson): array => $this->lessonPayload($lesson))
            ->all();

        $conflictMap = $this->buildConflictMap($week, $scheduledLessons);
        $groupLessonCards = $this->groupLessonCardsPayload($week);

        $scheduledLessonsWithMeta = $this->enrichScheduledLessonsWithBudget(
            array_map(
                static fn (array $lesson): array => array_merge($lesson, [
                    'has_conflict' => $conflictMap[$lesson['id']] ?? false,
                ]),
                $scheduledLessons,
            ),
            $groupLessonCards,
        );

        $gridBounds = ScheduleConflictService::resolveBounds($week);

        return [
            'week' => $this->weekPayload($week),
            'buildings' => $buildings,
            'timeSlots' => ScheduleConflictService::timeSlots($gridBounds['start'], $gridBounds['end']),
            'grid' => [
                'start' => $gridBounds['start'],
                'end' => $gridBounds['end'],
                'step_minutes' => ScheduleConflictService::VISUAL_STEP_MINUTES,
                'planning_step_minutes' => ScheduleConflictService::GRID_STEP_MINUTES,
                'span_minutes' => ScheduleConflictService::gridSpanMinutes($gridBounds['start'], $gridBounds['end']),
            ],
            'days' => $this->weekDays($week),
            'scheduledLessons' => $scheduledLessonsWithMeta,
            'unscheduledGroups' => $this->unscheduledGroups($week),
            'groups' => $this->groupsPayload(),
            'groupLessonCards' => $groupLessonCards,
            'teachers' => $this->teachersPayload(),
            'subjects' => $this->subjectsPayload(),
            'conflicts' => $this->conflicts->detectForWeek($week),
            'canExportPdf' => false,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lessons
     * @param  list<array<string, mixed>>  $groupLessonCards
     * @return list<array<string, mixed>>
     */
    private function enrichScheduledLessonsWithBudget(array $lessons, array $groupLessonCards): array
    {
        $cardsByTeacherKey = [];
        $teachersByGroupLesson = [];
        $budgetsByGroupLesson = [];

        foreach ($groupLessonCards as $card) {
            $cardsByTeacherKey[$card['key']] = $card;
            $groupLessonKey = $card['course_group_id'].'-'.$card['lesson_id'];
            $teachersByGroupLesson[$groupLessonKey] = $card['teachers'];

            if (! isset($budgetsByGroupLesson[$groupLessonKey])) {
                $budgetsByGroupLesson[$groupLessonKey] = [];
            }

            $budgetsByGroupLesson[$groupLessonKey][] = [
                'teacher_id' => $card['teacher_id'],
                'teacher_name' => $card['teacher_name'],
                'weekly_hours' => $card['weekly_hours'],
                'scheduled_hours' => $card['scheduled_hours'],
                'remaining_hours' => $card['remaining_hours'],
                'weekly_minutes' => $card['weekly_minutes'],
                'scheduled_minutes' => $card['scheduled_minutes'],
                'remaining_minutes' => $card['remaining_minutes'],
            ];
        }

        return array_map(function (array $lesson) use ($cardsByTeacherKey, $teachersByGroupLesson, $budgetsByGroupLesson): array {
            $durationMinutes = $this->lessonDurationMinutes($lesson);
            $durationHours = round($durationMinutes / 60, 2);

            if (empty($lesson['course_group_id']) || empty($lesson['lesson_id'])) {
                return array_merge($lesson, [
                    'duration_minutes' => $durationMinutes,
                    'duration_hours' => $durationHours,
                    'available_teachers' => [],
                    'weekly_minutes' => null,
                    'remaining_minutes' => null,
                    'weekly_hours' => null,
                    'remaining_hours' => null,
                    'teacher_budgets' => [],
                ]);
            }

            $groupLessonKey = $lesson['course_group_id'].'-'.$lesson['lesson_id'];
            $teacherKey = $groupLessonKey.'-'.$lesson['teacher_id'];
            $card = $cardsByTeacherKey[$teacherKey] ?? null;

            return array_merge($lesson, [
                'duration_minutes' => $durationMinutes,
                'duration_hours' => $durationHours,
                'available_teachers' => $teachersByGroupLesson[$groupLessonKey] ?? [],
                'weekly_minutes' => $card['weekly_minutes'] ?? null,
                'remaining_minutes' => $card['remaining_minutes'] ?? null,
                'weekly_hours' => $card['weekly_hours'] ?? null,
                'remaining_hours' => $card['remaining_hours'] ?? null,
                'teacher_budgets' => $budgetsByGroupLesson[$groupLessonKey] ?? [],
            ]);
        }, $lessons);
    }

    /**
     * @param  array<string, mixed>  $lesson
     */
    private function lessonDurationMinutes(array $lesson): int
    {
        $start = $this->timeToMinutes(substr((string) $lesson['starts_at'], 0, 5));
        $end = $this->timeToMinutes(substr((string) $lesson['ends_at'], 0, 5));

        return max(0, $end - $start);
    }

    private function buildConflictMap(ScheduleWeek $week, array $lessons): array
    {
        $map = [];

        foreach ($lessons as $lesson) {
            $payload = [
                'schedule_week_id' => $week->id,
                'lesson_date' => $lesson['lesson_date'],
                'starts_at' => $lesson['starts_at'],
                'ends_at' => $lesson['ends_at'],
                'academy_building_id' => $lesson['academy_building_id'],
                'academy_room_id' => $lesson['academy_room_id'],
                'teacher_id' => $lesson['teacher_id'],
                'course_group_id' => $lesson['course_group_id'],
            ];

            $map[$lesson['id']] = $this->conflicts->validatePayload($payload, $lesson['id']) !== [];
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function unscheduledGroups(ScheduleWeek $week): array
    {
        $scheduledGroupIds = $week->scheduledLessons()
            ->whereNotNull('course_group_id')
            ->pluck('course_group_id')
            ->unique()
            ->all();

        return CourseGroup::query()
            ->with(['course:id,name,discipline', 'lessons:id,name'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (CourseGroup $group): bool => ! in_array($group->id, $scheduledGroupIds, true))
            ->map(fn (CourseGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'course_name' => $group->course?->name,
                'discipline' => $group->course?->discipline,
                'subjects' => $group->lessons->pluck('name')->all(),
                'default_duration_minutes' => 60,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groupLessonCardsPayload(ScheduleWeek $week): array
    {
        $teacherNames = Teacher::query()
            ->pluck('name', 'id')
            ->map(static fn ($name, $id): string => (string) $name)
            ->all();

        $pivotRows = DB::table('course_group_lesson as cgl')
            ->join('course_groups as cg', 'cg.id', '=', 'cgl.course_group_id')
            ->join('courses as c', 'c.id', '=', 'cg.course_id')
            ->join('lessons as l', 'l.id', '=', 'cgl.lesson_id')
            ->select([
                'cgl.course_group_id',
                'cgl.lesson_id',
                'cgl.teacher_id',
                'cgl.hours as weekly_hours',
                'cg.name as group_name',
                'cg.color as group_color',
                'c.id as course_id',
                'c.name as course_name',
                'c.discipline',
                'c.study_starts_at',
                'c.study_ends_at',
                'l.name as lesson_name',
                'l.duration_minutes',
            ])
            ->orderBy('c.name')
            ->orderBy('cg.name')
            ->orderBy('l.name')
            ->orderBy('cgl.teacher_id')
            ->get();

        if ($pivotRows->isEmpty()) {
            return [];
        }

        $teachersByGroupLesson = $pivotRows
            ->groupBy(static fn ($row): string => $row->course_group_id.'-'.$row->lesson_id)
            ->map(static function ($rows) use ($teacherNames): array {
                return $rows
                    ->unique('teacher_id')
                    ->map(static fn ($row): array => [
                        'id' => (int) $row->teacher_id,
                        'name' => $teacherNames[(int) $row->teacher_id] ?? ('#'.$row->teacher_id),
                    ])
                    ->values()
                    ->all();
            })
            ->all();

        $scheduledMinutesByKey = $week->scheduledLessons()
            ->whereNotNull('course_group_id')
            ->whereNotNull('lesson_id')
            ->get(['course_group_id', 'lesson_id', 'teacher_id', 'starts_at', 'ends_at'])
            ->groupBy(static fn ($lesson): string => $lesson->course_group_id.'-'.$lesson->lesson_id.'-'.$lesson->teacher_id)
            ->map(function ($lessons): int {
                return (int) $lessons->sum(function ($lesson): int {
                    $start = $this->timeToMinutes(substr((string) $lesson->starts_at, 0, 5));
                    $end = $this->timeToMinutes(substr((string) $lesson->ends_at, 0, 5));

                    return max(0, $end - $start);
                });
            })
            ->all();

        return $pivotRows
            ->map(function ($row) use ($teacherNames, $teachersByGroupLesson, $scheduledMinutesByKey): array {
                $groupLessonKey = $row->course_group_id.'-'.$row->lesson_id;
                $cardKey = $groupLessonKey.'-'.$row->teacher_id;
                $weeklyHours = (float) $row->weekly_hours;
                $weeklyMinutes = (int) round($weeklyHours * 60);
                $scheduledMinutes = (int) ($scheduledMinutesByKey[$cardKey] ?? 0);
                $remainingMinutes = max(0, $weeklyMinutes - $scheduledMinutes);
                $scheduledHours = round($scheduledMinutes / 60, 2);
                $remainingHours = round($remainingMinutes / 60, 2);

                return [
                    'key' => $cardKey,
                    'course_group_id' => (int) $row->course_group_id,
                    'course_id' => (int) $row->course_id,
                    'lesson_id' => (int) $row->lesson_id,
                    'teacher_id' => (int) $row->teacher_id,
                    'group_name' => (string) $row->group_name,
                    'group_color' => $row->group_color ? (string) $row->group_color : null,
                    'course_name' => (string) $row->course_name,
                    'discipline' => (string) $row->discipline,
                    'study_starts_at' => $row->study_starts_at
                        ? substr((string) $row->study_starts_at, 0, 5)
                        : null,
                    'study_ends_at' => $row->study_ends_at
                        ? substr((string) $row->study_ends_at, 0, 5)
                        : null,
                    'lesson_name' => (string) $row->lesson_name,
                    'teacher_name' => $teacherNames[(int) $row->teacher_id] ?? ('#'.$row->teacher_id),
                    'weekly_hours' => $weeklyHours,
                    'scheduled_hours' => $scheduledHours,
                    'remaining_hours' => $remainingHours,
                    'weekly_minutes' => $weeklyMinutes,
                    'scheduled_minutes' => $scheduledMinutes,
                    'remaining_minutes' => $remainingMinutes,
                    'duration_minutes' => $row->duration_minutes !== null ? (int) $row->duration_minutes : null,
                    'teachers' => $teachersByGroupLesson[$groupLessonKey] ?? [],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function groupsPayload(): array
    {
        return CourseGroup::query()
            ->with('course:id,name,discipline')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CourseGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'color' => $group->color,
                'course_name' => $group->course?->name,
                'discipline' => $group->course?->discipline,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function teachersPayload(): array
    {
        return Teacher::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Teacher $teacher): array => [
                'id' => $teacher->id,
                'name' => $teacher->name,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function subjectsPayload(): array
    {
        return Lesson::query()
            ->orderBy('name')
            ->get(['id', 'name', 'discipline'])
            ->map(fn (Lesson $lesson): array => [
                'id' => $lesson->id,
                'name' => $lesson->name,
                'discipline' => $lesson->discipline,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function weekDays(ScheduleWeek $week): array
    {
        $days = [];
        $labels = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
        ];

        for ($offset = 0; $offset < 5; $offset++) {
            $date = $week->week_start_date->copy()->addDays($offset);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $labels[$date->dayOfWeekIso] ?? $date->format('l'),
                'short_label' => $date->format('D'),
            ];
        }

        return $days;
    }

    /**
     * @return array<string, mixed>
     */
    private function weekPayload(ScheduleWeek $week): array
    {
        return [
            'id' => $week->id,
            'week_start_date' => $week->week_start_date->format('Y-m-d'),
            'week_end_date' => $week->week_end_date->format('Y-m-d'),
            'title' => $week->title,
            'status' => $week->status,
            'work_starts_at' => $week->workStartsAt(),
            'work_ends_at' => $week->workEndsAt(),
            'published_at' => $week->published_at?->toIso8601String(),
            'published_by' => $week->published_by,
            'lessons_count' => $week->scheduled_lessons_count ?? $week->scheduledLessons()->count(),
            'is_editable' => $week->isEditable(),
            'prev_week_start' => $week->week_start_date->copy()->subDays(7)->format('Y-m-d'),
            'next_week_start' => $week->week_start_date->copy()->addDays(7)->format('Y-m-d'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lessonPayload(ScheduledLesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'schedule_week_id' => $lesson->schedule_week_id,
            'lesson_date' => $lesson->lesson_date?->format('Y-m-d'),
            'starts_at' => $this->conflicts->normalizeTime((string) $lesson->starts_at),
            'ends_at' => $this->conflicts->normalizeTime((string) $lesson->ends_at),
            'academy_building_id' => $lesson->academy_building_id,
            'academy_room_id' => $lesson->academy_room_id,
            'building_name' => $lesson->building?->name,
            'room_name' => $lesson->room?->name,
            'course_group_id' => $lesson->course_group_id,
            'group_name' => $lesson->courseGroup?->name,
            'group_color' => $lesson->courseGroup?->color,
            'course_name' => $lesson->courseGroup?->course?->name,
            'teacher_id' => $lesson->teacher_id,
            'teacher_name' => $lesson->teacher?->name,
            'lesson_id' => $lesson->lesson_id,
            'subject_name' => $lesson->lesson?->name,
            'title' => $lesson->title,
            'notes' => $lesson->notes,
            'color' => $lesson->color,
            'status' => $lesson->status,
            'label' => $this->lessonLogLabel($lesson),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLesson(Request $request, ScheduleWeek $week, ?int $excludeId = null): array
    {
        $validated = $request->validate([
            'lesson_date' => ['required', 'date'],
            'starts_at' => ['required', 'string'],
            'ends_at' => ['required', 'string'],
            'academy_building_id' => ['required', 'exists:academy_buildings,id'],
            'academy_room_id' => ['required', 'exists:academy_rooms,id'],
            'course_group_id' => ['nullable', 'exists:course_groups,id'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', Rule::in(self::LESSON_STATUSES)],
        ]);

        $validated['schedule_week_id'] = $week->id;
        $validated['starts_at'] = $this->conflicts->normalizeTime($validated['starts_at']);
        $validated['ends_at'] = $this->conflicts->normalizeTime($validated['ends_at']);
        $validated['status'] = $validated['status'] ?? ScheduledLesson::STATUS_SCHEDULED;

        $lessonDate = Carbon::parse($validated['lesson_date'])->startOfDay();
        $weekStart = $week->week_start_date->copy()->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(4);
        $allowedDates = array_column($this->weekDays($week), 'date');

        if (! in_array($lessonDate->format('Y-m-d'), $allowedDates, true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lesson_date' => 'Lessons can only be scheduled Monday–Friday.',
            ]);
        }

        if ($lessonDate->lt($weekStart) || $lessonDate->gt($weekEnd)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lesson_date' => 'Lesson date must be within the selected week (Monday–Friday).',
            ]);
        }

        return $validated;
    }

    private function findOrCreateWeek(Carbon $weekStart): ScheduleWeek
    {
        $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(4);

        $week = ScheduleWeek::query()->firstOrCreate(
            ['week_start_date' => $weekStart->format('Y-m-d')],
            [
                'week_end_date' => $weekEnd->format('Y-m-d'),
                'title' => 'Week '.$weekStart->format('d M Y'),
                'status' => ScheduleWeek::STATUS_DRAFT,
                'work_starts_at' => ScheduleConflictService::GRID_START,
                'work_ends_at' => ScheduleConflictService::GRID_END,
            ],
        );

        if ($week->week_end_date?->format('Y-m-d') !== $weekEnd->format('Y-m-d')) {
            $week->update(['week_end_date' => $weekEnd->format('Y-m-d')]);
        }

        return $week->refresh();
    }

    private function resolveWeekStart(?string $week): Carbon
    {
        if ($week !== null && $week !== '') {
            try {
                return Carbon::parse($week)->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable) {
                // fall through
            }
        }

        return now()->startOfWeek(Carbon::MONDAY);
    }

    private function ensureWeekEditable(ScheduleWeek $week): void
    {
        abort_unless($week->isEditable(), 403, 'This schedule week is locked.');
    }

    private function lessonLogLabel(ScheduledLesson $lesson): string
    {
        $parts = array_filter([
            $lesson->title,
            $lesson->courseGroup?->name,
            $lesson->lesson?->name,
        ]);

        return $parts !== [] ? implode(' — ', $parts) : 'Lesson #'.$lesson->id;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function minutesToTime(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
