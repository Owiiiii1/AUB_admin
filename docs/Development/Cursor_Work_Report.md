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

`tests/Feature/ScheduleApiTest.php` plus the existing suite. Full `php artisan test` on production `aub_test` after deploy (see below).

## Deploy

No migration. Files copied to `/var/www/aub`. `.env` not touched. `optimize:clear`. `route:list --path=api`. `php artisan test`. Production smoke against `/api/v1/health` and schedule endpoints with existing test actors (no plaintext tokens in this report).

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
