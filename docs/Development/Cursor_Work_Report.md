# Cursor Work Report

## Task

Set the AUB application timezone to **Europe/Rome** so Student/Parent schedule current-week bounds follow Italian local time. `AUB_admin` only. No schema change. No bulk datetime conversion.

## Before

- `config/app.php` hardcoded `'timezone' => 'UTC'`
- `APP_TIMEZONE` was not read
- Mobile `GET /schedule` without `?week=` used UTC `now()`
- Sunday 22:30 UTC (already Monday 00:30 in Rome) still belonged to the previous Monday–Sunday week

## After

- `config/app.php`: `'timezone' => env('APP_TIMEZONE', 'Europe/Rome')`
- `.env.example` / `.env.testing.example`: `APP_TIMEZONE=Europe/Rome`
- `phpunit.xml` force-sets `APP_TIMEZONE=Europe/Rome`
- Production `.env` has `APP_TIMEZONE=Europe/Rome` (other keys untouched)
- `MobileScheduleService::resolveWeekBounds()` uses `config('app.timezone')` (`Europe/Rome`)
- Date-only `week_start_date` / `lesson_date` and existing DATETIME values were **not** rewritten

## Tests

New unit tests in `MobileScheduleWeekBoundsTest`:

- config timezone is `Europe/Rome`
- Sunday 22:30 UTC → week Monday 2026-09-14 … Sunday 2026-09-20
- Monday 00:15 Europe/Rome → same new week
- DST spring-forward Sunday 2026-03-29 03:30 Rome stays on week starting 2026-03-23; Monday 00:15 starts 2026-03-30

New feature test: omitted `?week=` on `GET /api/v1/schedule` uses Rome, not UTC.

Exact production suite after `optimize:clear`:

| Metric | Count |
|--------|-------|
| Total | 65 |
| Passed | 65 |
| Failed | 0 |
| Errors | 0 |
| Assertions | 401 |

(`60` previous + `4` unit + `1` feature.)

## Deploy

No migration. `.env` not overwritten — only `APP_TIMEZONE` set. Files copied to `/var/www/aub`. `php artisan optimize:clear`. `php artisan route:list --path=api` still **7** routes. `php artisan test` green.

## Smoke

- `config('app.timezone')` = `Europe/Rome`
- Student `GET /api/v1/schedule` without `week` → `200`, `starts_on=2026-09-07`, `ends_on=2026-09-13` (current Rome week; unpublished)
- `?week=2026-09-09` → same Monday–Sunday bounds
- `?week=2026-12-28` → `2026-12-28`…`2027-01-03`, `published=true` (isolated test week)
- Unauthenticated `/schedule` → 401
- `/health` → 200

Temporary Sanctum tokens named `smoke-timezone` are deleted after the check. No plaintext tokens in this report.

## Files changed

Modified: `config/app.php`, `.env.example`, `.env.testing.example`, `phpunit.xml`, `MobileScheduleService`, `ScheduleApiTest`, bilingual API / architecture / current-state / roadmap / next-steps / weekly-schedule / development-rules docs.

Created: `tests/Unit/MobileScheduleWeekBoundsTest.php`.

## Technical debt / OPEN

- Naive MySQL DATETIME values stored while the app was UTC are not shifted. New `now()` writes use Rome wall clock.
- Teacher schedule still out of scope.
