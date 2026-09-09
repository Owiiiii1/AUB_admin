# Cursor Work Report

## Task

Add the first academy mobile feature API: **Student / Parent Schedule**. `AUB_admin` only for this backend slice. No new tables. Flutter follows in `AUB_app`.

## Endpoints

| Method | Path | Authz |
|--------|------|--------|
| GET | `/api/v1/schedule` | `auth:sanctum` + `mobile.actor`, `account_type=student` |
| GET | `/api/v1/children/{student}/schedule` | same, `account_type=parent` + `student_parent` |

Query: `?week=YYYY-MM-DD` (any day → Monday–Sunday). Omitted = current week via `now()` / `config('app.timezone')`.

## Authorization

- Student identity comes only from `request.user.studentProfile`. No client-supplied student id.
- Parent must own the requested student (`student_parent`). Unrelated/missing → `404`.
- Teacher with a valid mobile token → `403`.
- Staff cannot hold a mobile session (`401` via existing `mobile.actor`).

## Publication rules

Taken from `WeeklyScheduleController::publish`:

- Official weeks: `schedule_weeks.status` ∈ `published`, `locked` (`locked` is schema-only today; treated as frozen official).
- Draft weeks are never returned.
- Visible lessons: `published`, `cancelled`, `moved`.
- Hidden lessons: `draft`, `scheduled` (unpublished placement, including lessons added after Publish without re-publishing).

`empty_reason`: `no_class` | `unpublished` | `no_lessons` | `null`. Always HTTP 200 for empty states.

Timezone: `config/app.php` is hardcoded **`UTC`** (not `APP_TIMEZONE`). Documented; not changed in this task.

## Tests

Production host, `php artisan test` after `optimize:clear`:

| Metric | Count |
|--------|-------|
| Total | 60 |
| Passed | 60 |
| Failed | 0 |
| Errors | 0 |
| Assertions | 387 |

`ScheduleApiTest` covers own-class isolation, draft hidden, published/locked visible, cancelled/moved, sort, empty class, unpublished week, parent own/two children/unrelated 404, privacy whitelist, teacher 403, staff 401.

## Deploy

No migration. Files copied to `/var/www/aub`. `.env` not touched. `optimize:clear`. `route:list --path=api` shows **7** routes. `php artisan test` green.

## Smoke tests

Public:

- `GET /api/v1/health` → 200
- `GET /api/v1/schedule` without token → 401

Existing test actors (`student@admin.com`, `parent@admin.com`): student assigned to isolated class `Mobile Test`; published week `2026-12-28` with one lesson. Kernel smoke (temporary tokens, then deleted): student 200 published, parent own child 200, unrelated child 404, prev/next week 200. No plaintext tokens in this report. Real academy weeks were not published or overwritten.

## Files changed

Created: `MobileScheduleService`, `ScheduleController`, `ShowScheduleRequest`, `ScheduleResource`, `ScheduleApiTest`.

Modified: `routes/api.php`, `ScheduleWeek`, bilingual API/architecture/current-state/roadmap/next-steps/weekly-schedule docs.

## Technical debt / OPEN

- App timezone is UTC; academy is Italy — Sunday-night week boundary may not match Rome.
- `locked` is unused by admin UI.
- Teacher schedule not in this API.
- No old/new time history for `moved`.

## Next recommended step

Flutter student/parent schedule screens in `AUB_app` (paired task in the same PM request).
