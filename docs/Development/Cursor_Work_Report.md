# Cursor Work Report

## Task

Add **Student / Parent Attendance History** read-only API in `AUB_admin`. Identity stays Student + ScheduledLesson (`attendance_records`). No new migration. Teacher marking contract unchanged. Flutter history is a paired follow-up in `AUB_app`.

## Endpoints

```text
GET /api/v1/attendance
GET /api/v1/children/{student}/attendance
```

`auth:sanctum` + `mobile.actor` + `throttle:api-mobile`.

## Authorization

- Student `/attendance`: `account_type=student`, student from `studentProfile` only (no client `student_id`).
- Parent `/children/{student}/attendance`: `account_type=parent`, ownership via `parentProfile.students`. Stranger → **404**.
- Teacher → **403** on both. Staff mobile → **401**. Wrong actor on the other endpoint → **403**.

## Period

`?month=YYYY-MM`. Omitted → current month in `Europe/Rome`. Backend computes `starts_on` / `ends_on`. Invalid month → **422** `validation_error`.

## Filtering

A row is history only if an `AttendanceRecord` exists **and** the lesson is official:

- week `published` / `locked`
- lesson `published` / `moved`

Cancelled / draft / scheduled / other month excluded. Leftover records on cancelled lessons are not visits.

**`no AttendanceRecord` ≠ `absent`.** Unmarked lessons are not history and are not counted.

## Summary

Absolute counts only: `marked = present + absent + excused`. No percentages.

Sort: `lesson_date DESC`, `starts_at DESC`, `attendance_records.id DESC`.

## Privacy

Whitelist Resource `AttendanceHistoryResource`. No `marked_by`, `marked_at`, tax_code, medical, notes, documents, parent contacts, teacher email/phone, AI metadata, RBAC.

Read service: `AttendanceHistoryService` (not mixed into `TeacherAttendanceService`).

## Tests

`AttendanceHistoryApiTest` on production host `aub_test`. Full suite: **95 passed**, 0 failed, 0 errors (718 assertions).

`php artisan route:list --path=api`: **12** routes. No migration.

## Deploy

Copied PHP + tests + docs to `/var/www/aub`. `optimize:clear`. No schema change.

## Production smoke

Isolated week `2026-12-28`, class `Mobile Test`, student id **8**, lesson id **2**. Temporary extra lessons + records for present/absent/excused; Student GET, Parent GET, stranger 404, Teacher 403; extra rows deleted after.
