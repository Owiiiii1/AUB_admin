<?php

namespace App\Services\WeeklySchedule;

use App\Models\AcademyRoom;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScheduleConflictService
{
    public const GRID_START = '08:00';

    public const GRID_END = '22:30';

    /** Placement / AI planning snap (minutes). */
    public const GRID_STEP_MINUTES = 5;

    /** Timeline display row labels (visual only — do not change UI density). */
    public const VISUAL_STEP_MINUTES = 30;

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    public function validatePayload(array $payload, ?int $excludeLessonId = null): array
    {
        $conflicts = [];

        $startsAt = $this->normalizeTime((string) ($payload['starts_at'] ?? ''));
        $endsAt = $this->normalizeTime((string) ($payload['ends_at'] ?? ''));

        if ($startsAt === null || $endsAt === null) {
            return $conflicts;
        }

        if ($this->minutesFromMidnight($endsAt) <= $this->minutesFromMidnight($startsAt)) {
            $conflicts[] = [
                'type' => 'invalid_time_range',
                'message' => 'End time must be after start time.',
            ];
        }

        $buildingId = (int) ($payload['academy_building_id'] ?? 0);
        $roomId = (int) ($payload['academy_room_id'] ?? 0);

        if ($buildingId > 0 && $roomId > 0) {
            $roomBelongs = AcademyRoom::query()
                ->whereKey($roomId)
                ->where('academy_building_id', $buildingId)
                ->exists();

            if (! $roomBelongs) {
                $conflicts[] = [
                    'type' => 'invalid_room_building',
                    'message' => 'Selected room does not belong to the selected building.',
                ];
            }
        }

        $scheduleWeekId = (int) ($payload['schedule_week_id'] ?? 0);
        if ($scheduleWeekId <= 0) {
            return $conflicts;
        }

        return array_merge($conflicts, $this->detectOverlaps($scheduleWeekId, $payload, $excludeLessonId));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function detectForWeek(ScheduleWeek $week, ?int $excludeLessonId = null): array
    {
        $lessons = $week->scheduledLessons()
            ->with(['courseGroup.course', 'teacher:id,name', 'lesson:id,name', 'room:id,name', 'building:id,name'])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->get();

        $allConflicts = [];

        foreach ($lessons as $lesson) {
            if ($excludeLessonId !== null && $lesson->id === $excludeLessonId) {
                continue;
            }

            $payload = $this->lessonToPayload($lesson);
            $overlaps = $this->detectOverlaps($week->id, $payload, $lesson->id);

            foreach ($overlaps as $overlap) {
                $overlap['lesson_id'] = $lesson->id;
                $overlap['lesson_label'] = $this->lessonLabel($lesson);
                $allConflicts[] = $overlap;
            }
        }

        return $this->uniqueConflicts($allConflicts);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function detectOverlaps(int $scheduleWeekId, array $payload, ?int $excludeLessonId = null): array
    {
        $lessonDate = (string) ($payload['lesson_date'] ?? '');
        $startsAt = $this->normalizeTime((string) ($payload['starts_at'] ?? ''));
        $endsAt = $this->normalizeTime((string) ($payload['ends_at'] ?? ''));

        if ($lessonDate === '' || $startsAt === null || $endsAt === null) {
            return [];
        }

        if ($this->minutesFromMidnight($endsAt) <= $this->minutesFromMidnight($startsAt)) {
            return [];
        }

        $query = ScheduledLesson::query()
            ->where('schedule_week_id', $scheduleWeekId)
            ->whereDate('lesson_date', $lessonDate)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($excludeLessonId !== null) {
            $query->whereKeyNot($excludeLessonId);
        }

        /** @var Collection<int, ScheduledLesson> $existingLessons */
        $existingLessons = $query
            ->with(['courseGroup.course', 'teacher:id,name', 'lesson:id,name', 'room:id,name', 'building:id,name'])
            ->get();

        $conflicts = [];
        $roomId = (int) ($payload['academy_room_id'] ?? 0);
        $teacherId = isset($payload['teacher_id']) && $payload['teacher_id'] !== '' && $payload['teacher_id'] !== null
            ? (int) $payload['teacher_id']
            : null;
        $groupId = isset($payload['course_group_id']) && $payload['course_group_id'] !== '' && $payload['course_group_id'] !== null
            ? (int) $payload['course_group_id']
            : null;

        foreach ($existingLessons as $existing) {
            if ($roomId > 0 && (int) $existing->academy_room_id === $roomId) {
                $conflicts[] = $this->conflictEntry('room_overlap', $existing, 'Room is already booked for this time.');
            }

            if ($teacherId !== null && $existing->teacher_id !== null && (int) $existing->teacher_id === $teacherId) {
                $conflicts[] = $this->conflictEntry('teacher_overlap', $existing, 'Teacher is already assigned for this time.');
            }

            if ($groupId !== null && $existing->course_group_id !== null && (int) $existing->course_group_id === $groupId) {
                $conflicts[] = $this->conflictEntry('group_overlap', $existing, 'Group/class is already scheduled for this time.');
            }
        }

        return $this->uniqueConflicts($conflicts);
    }

    /**
     * @return array<string, mixed>
     */
    private function conflictEntry(string $type, ScheduledLesson $existing, string $message): array
    {
        return [
            'type' => $type,
            'message' => $message,
            'existing_lesson_id' => $existing->id,
            'existing_lesson_label' => $this->lessonLabel($existing),
        ];
    }

    /**
     * @param  array<string, mixed>  $lesson
     */
    private function lessonToPayload(ScheduledLesson $lesson): array
    {
        return [
            'schedule_week_id' => $lesson->schedule_week_id,
            'lesson_date' => $lesson->lesson_date?->format('Y-m-d'),
            'starts_at' => $this->formatTimeValue($lesson->starts_at),
            'ends_at' => $this->formatTimeValue($lesson->ends_at),
            'academy_building_id' => $lesson->academy_building_id,
            'academy_room_id' => $lesson->academy_room_id,
            'teacher_id' => $lesson->teacher_id,
            'course_group_id' => $lesson->course_group_id,
        ];
    }

    private function lessonLabel(ScheduledLesson $lesson): string
    {
        $parts = array_filter([
            $lesson->title,
            $lesson->courseGroup?->name,
            $lesson->lesson?->name,
        ]);

        return $parts !== [] ? implode(' — ', $parts) : 'Lesson #'.$lesson->id;
    }

    /**
     * @param  list<array<string, mixed>>  $conflicts
     * @return list<array<string, mixed>>
     */
    private function uniqueConflicts(array $conflicts): array
    {
        $seen = [];
        $unique = [];

        foreach ($conflicts as $conflict) {
            $key = implode('|', [
                $conflict['type'] ?? '',
                (string) ($conflict['lesson_id'] ?? ''),
                (string) ($conflict['existing_lesson_id'] ?? ''),
            ]);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $conflict;
        }

        return $unique;
    }

    public function normalizeTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i', substr($value, 0, 5))->format('H:i');
        } catch (\Throwable) {
            try {
                return Carbon::parse($value)->format('H:i');
            } catch (\Throwable) {
                return null;
            }
        }
    }

    private function formatTimeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->normalizeTime((string) $value);
    }

    private function minutesFromMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    /**
     * @return array{start: string, end: string}
     */
    public static function resolveBounds(?ScheduleWeek $week = null): array
    {
        $start = $week?->workStartsAt() ?? self::GRID_START;
        $end = $week?->workEndsAt() ?? self::GRID_END;

        $startMinutes = self::minutesFromMidnightStatic($start);
        $endMinutes = self::minutesFromMidnightStatic($end);

        if ($endMinutes <= $startMinutes) {
            return [
                'start' => self::GRID_START,
                'end' => self::GRID_END,
            ];
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @return list<string>
     */
    public static function timeSlots(?string $start = null, ?string $end = null): array
    {
        $slots = [];
        $cursor = Carbon::createFromTimeString($start ?? self::GRID_START);
        $endTime = Carbon::createFromTimeString($end ?? self::GRID_END);

        while ($cursor->lte($endTime)) {
            $slots[] = $cursor->format('H:i');
            $cursor->addMinutes(self::VISUAL_STEP_MINUTES);
        }

        return $slots;
    }

    public static function gridSpanMinutes(?string $start = null, ?string $end = null): int
    {
        $startTime = Carbon::createFromTimeString($start ?? self::GRID_START);
        $endTime = Carbon::createFromTimeString($end ?? self::GRID_END);

        return (int) $startTime->diffInMinutes($endTime);
    }

    private static function minutesFromMidnightStatic(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
