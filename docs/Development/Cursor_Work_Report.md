# Cursor Work Report

## Task

Add **Teacher Schedule** mobile API in `AUB_admin`. Reuse `ScheduleWeek` / `ScheduledLesson`. Do not change the Student/Parent contract. No new tables.

## Endpoint

```text
GET /api/v1/teacher/schedule
```

`auth:sanctum` + `mobile.actor` + `throttle:api-mobile`. Query: `?week=YYYY-MM-DD` (any day → Monday–Sunday). Omitted = current week in `Europe/Rome`.

Existing:

```text
GET /api/v1/schedule
GET /api/v1/children/{student}/schedule
```

unchanged.

## Authorization

- Teacher identity only from `request.user.teacherProfile`. No client `teacher_id`.
- Extra `teacher_id` query is ignored.
- Missing teacher profile → `403` (does not 500).
- Student / parent → `403`.
- Staff cannot hold a mobile session → `401`.

## Query rules

`scheduled_lessons.teacher_id = current teacher`. All academy classes. Eager load `lesson`, `academyClass`, `building`, `room`. Sort: `lesson_date`, `starts_at`, `ends_at`, `id`. Week bounds reuse `MobileScheduleService::resolveWeekBounds()`.

## Publication rules

Same as student/parent: official week `published`/`locked`; visible lessons `published`/`cancelled`/`moved`; hidden `draft`/`scheduled`.

## Payload

Root: `teacher {id, display_name}`, `week`, `days` (7), `empty_reason`. Each lesson includes `academy_class` and omits the teacher object.

Empty HTTP 200: `unpublished` / `no_lessons`.

## Privacy

No students, parent data, emails, phones, tax codes, notes, AI metadata, conflicts, RBAC fields.

## Tests

Exact production suite after `optimize:clear`:

| Metric | Count |
|--------|-------|
| Total | 71 |
| Passed | 71 |
| Failed | 0 |
| Errors | 0 |
| Assertions | 490 |

(`65` previous + `6` teacher feature tests.) `composer validate` PASS.

## Deploy

No migration. Files copied to `/var/www/aub`. `.env` not touched. `php artisan optimize:clear`. `route:list --path=api` shows **8** routes.

## Production smoke

Temporary Sanctum token `smoke-teacher-schedule` (deleted after):

- Teacher `GET /teacher/schedule` → 200, current Rome week `2026-09-07`…`2026-09-13`, unpublished
- Teacher `GET /teacher/schedule?week=2026-12-28` → 200, published, one isolated lesson `LEZIONE CLASSICO` / class `Mobile Test`
- Student token on `/teacher/schedule` → 403 `forbidden`

Isolated week/class only; real academy published weeks not changed.

No plaintext tokens in this report.

## Files

Created: `TeacherScheduleResource`.

Modified: `MobileScheduleService`, `ScheduleController`, `routes/api.php`, `ScheduleApiTest`, bilingual API / architecture / current-state / roadmap / next-steps / weekly-schedule docs.

## Technical debt / OPEN

- Teacher Flutter Orario is the paired client task.
- Attendance, roster, check-in remain out of scope.
