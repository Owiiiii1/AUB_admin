<?php

namespace App\Services;

use App\Exceptions\AttendanceNotEditableException;
use App\Models\AttendanceRecord;
use App\Models\ScheduledLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeacherAttendanceService
{
    /**
     * @return array<string, mixed>
     */
    public function show(Teacher $teacher, ScheduledLesson $lesson): array
    {
        $lesson = $this->resolveAccessibleLesson($teacher, $lesson);

        return $this->payload($lesson);
    }

    /**
     * Bulk partial upsert. Each supplied row is applied; omitted roster
     * students are left unchanged. status=null deletes the record.
     *
     * @param  list<array{student_id: int, status: string|null}>  $rows
     * @return array{payload: array<string, mixed>, changed_count: int}
     */
    public function save(Teacher $teacher, User $actor, ScheduledLesson $lesson, array $rows): array
    {
        $lesson = $this->resolveAccessibleLesson($teacher, $lesson);
        if (! $this->isEditable($lesson)) {
            throw new AttendanceNotEditableException;
        }

        $rosterIds = $this->roster($lesson)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rosterLookup = array_fill_keys($rosterIds, true);

        foreach ($rows as $row) {
            $studentId = (int) $row['student_id'];
            if (! isset($rosterLookup[$studentId])) {
                throw ValidationException::withMessages([
                    'attendance' => ['One or more students are not in this class roster.'],
                ]);
            }
        }

        $changed = 0;

        DB::transaction(function () use ($lesson, $actor, $rows, &$changed): void {
            foreach ($rows as $row) {
                $studentId = (int) $row['student_id'];
                $status = $row['status'];
                $existing = AttendanceRecord::query()
                    ->where('scheduled_lesson_id', $lesson->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($status === null) {
                    if ($existing !== null) {
                        $existing->delete();
                        $changed++;
                    }

                    continue;
                }

                if ($existing !== null && $existing->status === $status) {
                    continue;
                }

                AttendanceRecord::query()->updateOrCreate(
                    [
                        'scheduled_lesson_id' => $lesson->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $status,
                        'marked_by' => $actor->id,
                        'marked_at' => now(),
                    ],
                );
                $changed++;
            }
        });

        $lesson->unsetRelation('attendanceRecords');

        return [
            'payload' => $this->payload($lesson->fresh([
                'scheduleWeek',
                'academyClass',
                'building',
                'room',
                'lesson',
            ]) ?? $lesson),
            'changed_count' => $changed,
        ];
    }

    public function resolveAccessibleLesson(Teacher $teacher, ScheduledLesson $lesson): ScheduledLesson
    {
        $lesson->loadMissing(['scheduleWeek', 'academyClass', 'building', 'room', 'lesson']);

        if ((int) $lesson->teacher_id !== (int) $teacher->id) {
            throw new NotFoundHttpException('Not found.');
        }

        $week = $lesson->scheduleWeek;
        if ($week === null || ! $week->isOfficiallyPublished()) {
            throw new NotFoundHttpException('Not found.');
        }

        if (! in_array($lesson->status, [
            ScheduledLesson::STATUS_PUBLISHED,
            ScheduledLesson::STATUS_MOVED,
            ScheduledLesson::STATUS_CANCELLED,
        ], true)) {
            throw new NotFoundHttpException('Not found.');
        }

        if ($lesson->academyClass === null) {
            throw new NotFoundHttpException('Not found.');
        }

        return $lesson;
    }

    public function isEditable(ScheduledLesson $lesson): bool
    {
        return $lesson->status !== ScheduledLesson::STATUS_CANCELLED;
    }

    /**
     * @return Collection<int, Student>
     */
    public function roster(ScheduledLesson $lesson): Collection
    {
        return Student::query()
            ->select([
                'students.id',
                'students.first_name',
                'students.last_name',
                'students.name',
                'students.student_photo_path',
            ])
            ->join('academy_class_student', 'academy_class_student.student_id', '=', 'students.id')
            ->where('academy_class_student.academy_class_id', $lesson->academy_class_id)
            ->orderBy('students.last_name')
            ->orderBy('students.first_name')
            ->orderBy('students.id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ScheduledLesson $lesson): array
    {
        $students = $this->roster($lesson);
        $records = AttendanceRecord::query()
            ->where('scheduled_lesson_id', $lesson->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $timezone = (string) config('app.timezone', 'Europe/Rome');
        $editable = $this->isEditable($lesson);

        return [
            'lesson' => [
                'id' => $lesson->id,
                'date' => $lesson->lesson_date?->format('Y-m-d'),
                'starts_at' => $this->formatTime($lesson->starts_at),
                'ends_at' => $this->formatTime($lesson->ends_at),
                'title' => $this->title($lesson),
                'status' => $lesson->status,
                'academy_class' => [
                    'id' => $lesson->academyClass->id,
                    'name' => $lesson->academyClass->name,
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
            ],
            'attendance_editable' => $editable,
            'reason' => $editable ? null : 'cancelled',
            'students' => $students->map(function (Student $student) use ($records, $timezone): array {
                $record = $records->get($student->id);

                return [
                    'id' => $student->id,
                    'display_name' => $student->displayName(),
                    'photo_url' => $this->photoUrl($student),
                    'attendance' => $record === null ? null : [
                        'status' => $record->status,
                        'marked_at' => $record->marked_at?->timezone($timezone)->toIso8601String(),
                    ],
                ];
            })->values()->all(),
        ];
    }

    private function title(ScheduledLesson $lesson): string
    {
        $title = trim((string) ($lesson->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        return (string) ($lesson->lesson?->name ?? '');
    }

    private function formatTime(mixed $value): string
    {
        $raw = is_string($value) ? $value : (string) $value;

        return substr($raw, 0, 5);
    }

    private function photoUrl(Student $student): ?string
    {
        $path = $student->student_photo_path;
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
