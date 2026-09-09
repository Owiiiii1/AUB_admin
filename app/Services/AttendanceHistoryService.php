<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceHistoryService
{
    /**
     * @return array<string, mixed>
     */
    public function forStudent(Student $student, ?string $month): array
    {
        $period = $this->period($month);
        $records = $this->records($student, $period['starts_on'], $period['ends_on']);

        return [
            'student' => [
                'id' => $student->id,
                'display_name' => $student->displayName(),
            ],
            'period' => $period,
            'summary' => $this->summary($records),
            'records' => $records->map(fn (AttendanceRecord $record): array => $this->recordArray($record))->values()->all(),
        ];
    }

    /**
     * @return array{month: string, starts_on: string, ends_on: string}
     */
    public function period(?string $month): array
    {
        $timezone = (string) config('app.timezone', 'Europe/Rome');
        $start = $month === null
            ? Carbon::now($timezone)->startOfMonth()
            : Carbon::createFromFormat('Y-m', $month, $timezone)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return [
            'month' => $start->format('Y-m'),
            'starts_on' => $start->toDateString(),
            'ends_on' => $end->toDateString(),
        ];
    }

    /**
     * @return Collection<int, AttendanceRecord>
     */
    private function records(Student $student, string $startsOn, string $endsOn): Collection
    {
        return AttendanceRecord::query()
            ->select('attendance_records.*')
            ->join('scheduled_lessons', 'scheduled_lessons.id', '=', 'attendance_records.scheduled_lesson_id')
            ->join('schedule_weeks', 'schedule_weeks.id', '=', 'scheduled_lessons.schedule_week_id')
            ->where('attendance_records.student_id', $student->id)
            ->whereBetween('scheduled_lessons.lesson_date', [$startsOn, $endsOn])
            ->whereIn('scheduled_lessons.status', [
                ScheduledLesson::STATUS_PUBLISHED,
                ScheduledLesson::STATUS_MOVED,
            ])
            ->whereIn('schedule_weeks.status', [
                ScheduleWeek::STATUS_PUBLISHED,
                ScheduleWeek::STATUS_LOCKED,
            ])
            ->with([
                'scheduledLesson.lesson',
                'scheduledLesson.teacher',
                'scheduledLesson.building',
                'scheduledLesson.room',
                'scheduledLesson.scheduleWeek',
            ])
            ->orderByDesc('scheduled_lessons.lesson_date')
            ->orderByDesc('scheduled_lessons.starts_at')
            ->orderByDesc('attendance_records.id')
            ->get();
    }

    /**
     * @param  Collection<int, AttendanceRecord>  $records
     * @return array{marked: int, present: int, absent: int, excused: int}
     */
    private function summary(Collection $records): array
    {
        $present = $records->where('status', AttendanceRecord::STATUS_PRESENT)->count();
        $absent = $records->where('status', AttendanceRecord::STATUS_ABSENT)->count();
        $excused = $records->where('status', AttendanceRecord::STATUS_EXCUSED)->count();

        return [
            'marked' => $present + $absent + $excused,
            'present' => $present,
            'absent' => $absent,
            'excused' => $excused,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recordArray(AttendanceRecord $record): array
    {
        $lesson = $record->scheduledLesson;
        $catalog = $lesson?->lesson;
        $teacher = $lesson?->teacher;
        $title = trim((string) ($lesson?->title ?? ''));
        if ($title === '') {
            $title = (string) ($catalog?->name ?? '');
        }

        return [
            'id' => $record->id,
            'date' => $lesson?->lesson_date?->format('Y-m-d'),
            'starts_at' => $this->formatTime($lesson?->starts_at),
            'ends_at' => $this->formatTime($lesson?->ends_at),
            'status' => $record->status,
            'lesson' => $catalog === null ? null : [
                'id' => $catalog->id,
                'name' => $catalog->name,
            ],
            'title' => $title,
            'teacher' => $teacher === null ? null : [
                'id' => $teacher->id,
                'display_name' => $teacher->displayName(),
            ],
            'location' => [
                'building' => $lesson?->building === null ? null : [
                    'id' => $lesson->building->id,
                    'name' => $lesson->building->name,
                ],
                'room' => $lesson?->room === null ? null : [
                    'id' => $lesson->room->id,
                    'name' => $lesson->room->name,
                ],
            ],
        ];
    }

    private function formatTime(mixed $value): string
    {
        $raw = is_string($value) ? $value : (string) $value;

        return substr($raw, 0, 5);
    }
}
