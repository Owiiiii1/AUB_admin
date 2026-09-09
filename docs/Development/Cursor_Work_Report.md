# Cursor Work Report

## Task

Add **Teacher Attendance MVP** API in `AUB_admin`. Identity is Student + ScheduledLesson (`attendance_records`). Flutter Teacher Attendance is the paired client task after this deploy.

## Schema

Table `attendance_records`:

- `scheduled_lesson_id` FK `scheduled_lessons` `cascadeOnDelete`
- `student_id` FK `students` `cascadeOnDelete`
- `status` `present` / `absent` / `excused`
- `marked_by` FK `users` nullable `nullOnDelete`
- `marked_at`
- timestamps
- **UNIQUE (`scheduled_lesson_id`, `student_id`)**

Unmarked = no row. PUT `status=null` deletes the row. Roster is current `academy_class_student` (historical snapshot is OPEN debt).

## Endpoints

```text
GET /api/v1/teacher/lessons/{scheduledLesson}/attendance
PUT /api/v1/teacher/lessons/{scheduledLesson}/attendance
```

Ownership: `scheduled_lesson.teacher_id == current teacher profile`. Other teacher → 404. Student/parent → 403. Staff mobile → 401.

Publication: official week `published`/`locked`; lesson `published`/`moved` editable; `cancelled` GET read-only (`attendance_editable=false`); PUT 409 `attendance_not_editable`. Draft/scheduled → 404.

PUT = bulk partial upsert; invalid roster student rejects the whole request. Success returns the GET payload. Audit: `marked_by` / `marked_at`; activity `attendance.updated` with `scheduled_lesson_id` + `changed_count` only.

## Tests

Exact production suite after `optimize:clear`:

| Metric | Count |
|--------|-------|
| Total | 82 |
| Passed | 82 |
| Failed | 0 |
| Errors | 0 |
| Assertions | 588 |

(`71` previous + `11` attendance tests.) `composer validate` PASS. `aub_test` green **before** production migrate.

## Deploy

Migration `2026_09_09_200000_create_attendance_records_table`. Files copied to `/var/www/aub`. `.env` not touched. `php artisan optimize:clear`. `route:list --path=api` shows **10** routes after migrate.

## Production smoke

Isolated week `2026-12-28` / class `Mobile Test` / teacher `teacher@admin.com`. Temporary Sanctum token deleted after. Cleanup attendance rows. No plaintext tokens in this report.

## Files

Created: `AttendanceRecord`, `TeacherAttendanceService`, `AttendanceController`, `SaveTeacherAttendanceRequest`, `TeacherAttendanceResource`, `AttendanceNotEditableException`, migration, `AttendanceApiTest`, `docs/en/ATTENDANCE.md`, `docs/ru/ATTENDANCE.md`.

Modified: `ScheduledLesson`, `Student`, `routes/api.php`, `ApiExceptionRenderer`, bilingual API / architecture / current-state / data-model / roadmap / next-steps / open-questions / README.

## Technical debt / OPEN

- Historical roster snapshot.
- Attendance finalization / edit window.
- Student/Parent attendance history (next slice).
