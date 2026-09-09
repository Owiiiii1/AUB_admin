<?php

namespace App\Services\WeeklySchedule;

use App\Models\AcademyClass;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MobileScheduleService
{
    /**
     * Lesson statuses that belong on an officially published week.
     *
     * Publish copies every current lesson to `published`. Later admin
     * cancellations/moves keep those statuses. `draft` and `scheduled` are
     * unpublished placement work (including lessons added after Publish
     * without re-publishing) and stay hidden from mobile.
     *
     * @var list<string>
     */
    public const VISIBLE_LESSON_STATUSES = [
        ScheduledLesson::STATUS_PUBLISHED,
        ScheduledLesson::STATUS_CANCELLED,
        ScheduledLesson::STATUS_MOVED,
    ];

    /**
     * @return array<string, mixed>
     */
    public function forStudent(Student $student, ?string $weekParam): array
    {
        $student->loadMissing('academyClasses');
        $class = $student->academyClass();
        [$startsOn, $endsOn] = $this->resolveWeekBounds($weekParam);

        if ($class === null) {
            return $this->payload($student, null, $startsOn, $endsOn, false, 'no_class', collect());
        }

        $week = $this->findOfficialWeek($startsOn);
        if ($week === null) {
            return $this->payload($student, $class, $startsOn, $endsOn, false, 'unpublished', collect());
        }

        $lessons = $this->visibleLessonsForClass($week, $class->id);
        $emptyReason = $lessons->isEmpty() ? 'no_lessons' : null;

        return $this->payload($student, $class, $startsOn, $endsOn, true, $emptyReason, $lessons);
    }

    /**
     * @return array<string, mixed>
     */
    public function forTeacher(Teacher $teacher, ?string $weekParam): array
    {
        [$startsOn, $endsOn] = $this->resolveWeekBounds($weekParam);
        $week = $this->findOfficialWeek($startsOn);
        if ($week === null) {
            return $this->teacherPayload($teacher, $startsOn, $endsOn, false, 'unpublished', collect());
        }

        $lessons = $this->visibleLessonsForTeacher($week, $teacher->id);
        $emptyReason = $lessons->isEmpty() ? 'no_lessons' : null;

        return $this->teacherPayload($teacher, $startsOn, $endsOn, true, $emptyReason, $lessons);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveWeekBounds(?string $weekParam): array
    {
        $timezone = (string) config('app.timezone', 'Europe/Rome');
        $start = $weekParam
            ? Carbon::parse($weekParam, $timezone)->startOfWeek(Carbon::MONDAY)
            : Carbon::now($timezone)->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6)->startOfDay();

        return [$start->startOfDay(), $end];
    }

    public function findOfficialWeek(Carbon $monday): ?ScheduleWeek
    {
        $week = ScheduleWeek::query()
            ->whereDate('week_start_date', $monday->format('Y-m-d'))
            ->first();

        if ($week === null || ! $week->isOfficiallyPublished()) {
            return null;
        }

        return $week;
    }

    /**
     * @return Collection<int, ScheduledLesson>
     */
    public function visibleLessonsForClass(ScheduleWeek $week, int $classId): Collection
    {
        return ScheduledLesson::query()
            ->where('schedule_week_id', $week->id)
            ->where('academy_class_id', $classId)
            ->whereIn('status', self::VISIBLE_LESSON_STATUSES)
            ->with([
                'lesson:id,name',
                'teacher:id,name,first_name,last_name',
                'building:id,name',
                'room:id,name',
                'academyClass:id,name',
            ])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->orderBy('ends_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ScheduledLesson>
     */
    public function visibleLessonsForTeacher(ScheduleWeek $week, int $teacherId): Collection
    {
        return ScheduledLesson::query()
            ->where('schedule_week_id', $week->id)
            ->where('teacher_id', $teacherId)
            ->whereIn('status', self::VISIBLE_LESSON_STATUSES)
            ->with([
                'lesson:id,name',
                'academyClass:id,name',
                'building:id,name',
                'room:id,name',
            ])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->orderBy('ends_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, ScheduledLesson>  $lessons
     * @return array<string, mixed>
     */
    private function payload(
        Student $student,
        ?AcademyClass $class,
        Carbon $startsOn,
        Carbon $endsOn,
        bool $published,
        ?string $emptyReason,
        Collection $lessons,
    ): array {
        return [
            'student' => [
                'id' => $student->id,
                'display_name' => $student->displayName(),
                'academy_class' => $class === null ? null : [
                    'id' => $class->id,
                    'name' => $class->name,
                ],
            ],
            'week' => [
                'starts_on' => $startsOn->format('Y-m-d'),
                'ends_on' => $endsOn->format('Y-m-d'),
                'published' => $published,
            ],
            'days' => $class === null
                ? []
                : $this->days($startsOn, $lessons, forTeacher: false),
            'empty_reason' => $emptyReason,
        ];
    }

    /**
     * @param  Collection<int, ScheduledLesson>  $lessons
     * @return array<string, mixed>
     */
    private function teacherPayload(
        Teacher $teacher,
        Carbon $startsOn,
        Carbon $endsOn,
        bool $published,
        ?string $emptyReason,
        Collection $lessons,
    ): array {
        return [
            'teacher' => [
                'id' => $teacher->id,
                'display_name' => $teacher->displayName(),
            ],
            'week' => [
                'starts_on' => $startsOn->format('Y-m-d'),
                'ends_on' => $endsOn->format('Y-m-d'),
                'published' => $published,
            ],
            'days' => $this->days($startsOn, $lessons, forTeacher: true),
            'empty_reason' => $emptyReason,
        ];
    }

    /**
     * @param  Collection<int, ScheduledLesson>  $lessons
     * @return list<array<string, mixed>>
     */
    private function days(Carbon $startsOn, Collection $lessons, bool $forTeacher): array
    {
        $byDate = $lessons->groupBy(
            static fn (ScheduledLesson $lesson): string => $lesson->lesson_date?->format('Y-m-d') ?? '',
        );

        $days = [];
        for ($offset = 0; $offset < 7; $offset++) {
            $date = $startsOn->copy()->addDays($offset);
            $key = $date->format('Y-m-d');
            $dayLessons = $byDate->get($key) ?? collect();

            $days[] = [
                'date' => $key,
                'weekday' => $date->dayOfWeekIso,
                'lessons' => $dayLessons
                    ->values()
                    ->map(fn (ScheduledLesson $lesson): array => $this->lessonArray($lesson, $forTeacher))
                    ->all(),
            ];
        }

        return $days;
    }

    /**
     * @return array<string, mixed>
     */
    private function lessonArray(ScheduledLesson $lesson, bool $forTeacher = false): array
    {
        $title = trim((string) ($lesson->title ?? ''));
        if ($title === '') {
            $title = (string) ($lesson->lesson?->name ?? '');
        }

        $row = [
            'id' => $lesson->id,
            'starts_at' => $this->formatTime($lesson->starts_at),
            'ends_at' => $this->formatTime($lesson->ends_at),
            'title' => $title,
            'lesson' => $lesson->lesson === null ? null : [
                'id' => $lesson->lesson->id,
                'name' => $lesson->lesson->name,
            ],
            'location' => [
                'building' => $lesson->building === null ? null : [
                    'id' => $lesson->building->id,
                    'name' => $lesson->building->name,
                ],
                'room' => $lesson->room === null ? null : [
                    'id' => $lesson->room->id,
                    'name' => $lesson->room->name,
                ],
            ],
            'status' => $lesson->status,
        ];

        if ($forTeacher) {
            $row['academy_class'] = $lesson->academyClass === null ? null : [
                'id' => $lesson->academyClass->id,
                'name' => $lesson->academyClass->name,
            ];

            return $row;
        }

        $row['teacher'] = $lesson->teacher === null ? null : [
            'id' => $lesson->teacher->id,
            'display_name' => $lesson->teacher->displayName(),
        ];

        return $row;
    }

    private function formatTime(mixed $value): string
    {
        $raw = is_string($value) ? $value : (string) $value;

        return substr($raw, 0, 5);
    }
}
