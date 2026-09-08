# Cursor Work Report

## Task

Move Laravel PHPUnit off SQLite `:memory:` onto an isolated MySQL test database. Infrastructure only: dedicated `aub_test`, hard safety guard, config without secrets in git. No API, Sanctum, Flutter, attendance, grades, communications, payments, or productions.

## Before

SQLite `:memory:`, missing `pdo_sqlite` on production PHP 8.3.6, suite broken.

- `phpunit.xml` forced `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.
- Production PHP has **no** `pdo_sqlite`. Feature tests using `RefreshDatabase` failed with `could not find driver (Connection: sqlite, Database: :memory:)`.
- Last Identity-task result: 2 passed, 24 errors. Production MySQL `aub` was not the test target in that run (config was uncached).
- No dedicated test database. No second-line guard if phpunit env was ignored (for example after `config:cache`).

## After

MySQL `aub_test`.

- Production app database remains **`aub`**.
- PHPUnit and `php artisan --env=testing` use **`aub_test`**.
- Server has a dedicated MySQL user with rights **only** on `aub_test` (credentials are not in git or this report).
- Full suite on the production host: **29 passed**, 0 failed, 0 errors.

## Test DB safety

Hard guard: `App\Testing\TestDatabaseGuard`.

When `app()->environment('testing')`:

1. `AppServiceProvider::boot()` calls `TestDatabaseGuard::assertSafe()` (covers `php artisan --env=testing`).
2. `Tests\TestCase::refreshApplication()` calls the same guard **after** the app boots and **before** `RefreshDatabase` runs migrations.

The guard requires `database.default === mysql` and the resolved database name **exactly** `aub_test`. Anything else (including production `aub` and SQLite `:memory:`) throws immediately:

`Refusing to run tests against non-test database.`

This is independent of `.env.testing`. phpunit.xml also force-sets `DB_DATABASE=aub_test`. Cached config is still dangerous for *running* tests (phpunit env would be ignored), but the guard then aborts instead of migrating `aub`. Always `php artisan optimize:clear` before `php artisan test`.

Unit coverage: `tests/Unit/TestDatabaseGuardTest.php` (allows `aub_test`; refuses `aub` and sqlite `:memory:`).

## Config

Git-safe:

- `phpunit.xml` — `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=aub_test`, empty `DB_URL`. **No username/password.**
- `.env.testing.example` — same DB name, empty credentials.
- `.gitignore` includes `.env.testing`.

Server-only (not committed): `/var/www/aub/.env.testing` with test credentials.

How the CLI actually loads testing:

- `php artisan test` → PHPUnit → phpunit.xml force env + Laravel loads `.env.testing` when config is not cached.
- `php artisan migrate:status --env=testing` and `php artisan db:show --env=testing` resolve database **`aub_test`** (confirmed on the server). Empty `aub_test` before the first `RefreshDatabase` run reports “Migration table not found”; after the suite, all 39 migrations show Ran.

## Test results

Exact suite on production host after the identity-link fix:

| Metric | Count |
|--------|-------|
| Total | 29 |
| Passed | 29 |
| Failed | 0 |
| Errors | 0 |
| Assertions | 88 |
| Duration | 7.06s |

First MySQL run had **1 failed** (`AccountIdentityLayerTest`: student user can be linked to one student only): expected `AccountIdentityException`, got `UniqueConstraintViolationException` on `students.students_user_id_unique`. Root cause: `AccountIdentityService::linkedProfile()` read cached Eloquent relations (`null` after `assertCanLink`), so the second `link()` hit the unique index instead of the domain exception. SQLite never exposed this because the suite could not run. Fix: query `user_id` on the three actor tables; also reject a second same-type profile in `assertNoCrossLinks`. Re-run: 29 passed.

## Production isolation

Confirmed after the green suite:

| Check | Result |
|-------|--------|
| Production DB name | `aub` |
| Test DB name | `aub_test` |
| Production `users` count | 1 (unchanged) |
| Production admin | present (`admin@admin.com`) |
| Production `teachers` count | 20 (unchanged) |
| App `config` database | `aub` |
| `config:cache` | not active |
| HTTP `/` | 200 |
| HTTP `/owl-admin/health` | 200 |

`RefreshDatabase` migrated and rolled schema on `aub_test` only. Production migration history on `aub` is unchanged (identity migration still batch 2). Test DB may be wiped/refreshed without touching `aub`.

## Files changed

Created:

- `app/Testing/TestDatabaseGuard.php`
- `tests/Unit/TestDatabaseGuardTest.php`
- `.env.testing.example`

Modified:

- `phpunit.xml`
- `.gitignore`
- `tests/TestCase.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/AccountIdentityService.php` (necessary MySQL-exposed validation fix)
- `docs/en/DEVELOPMENT_RULES.md`
- `docs/ru/DEVELOPMENT_RULES.md`
- `docs/en/SERVER_DEPLOYMENT.md`
- `docs/ru/SERVER_DEPLOYMENT.md`
- `docs/en/CURRENT_STATE.md`
- `docs/ru/CURRENT_STATE.md`
- `docs/en/NEXT_STEPS.md`
- `docs/ru/NEXT_STEPS.md`
- `docs/Development/Cursor_Work_Report.md`

Not committed: `.env`, `.env.testing`, passwords, any credentials.

## Commands executed

| Command | Result |
|---------|--------|
| Create MySQL database `aub_test` + dedicated user (rights only on `aub_test`) | PASS |
| Write server-only `.env.testing` | PASS |
| `php artisan optimize:clear` | PASS |
| `php artisan migrate:status --env=testing` (before first suite) | FAIL — expected: Migration table not found on empty `aub_test` |
| `php artisan test` (first MySQL run) | FAIL — 1 failed, 28 passed |
| Identity `linkedProfile` / same-type link fix + recopy service | PASS |
| `php artisan test` (after fix) | PASS — 29 passed, 0 failed, 0 errors |
| `php artisan migrate:status --env=testing` (after suite) | PASS — 39 Ran on `aub_test` |
| `php artisan migrate:status` (default `.env`) | PASS — production `aub` unchanged |
| `php artisan db:show --env=testing` | PASS — Database `aub_test` |
| Production user/teacher/admin snapshot | PASS — 1 / 20 / admin present |
| `curl -I https://aub.owlsolutions.net/` | PASS — 200 |
| `curl -I https://aub.owlsolutions.net/owl-admin/health` | PASS — 200 |

## Remaining technical debt

- Production PHP still has no `pdo_sqlite` (irrelevant if tests stay on MySQL).
- Do not `config:cache` then `php artisan test`. Guard now aborts, but the suite will not run until `optimize:clear`.
- `.env.example` still has Laravel skeleton `DB_CONNECTION=sqlite`; production `.env` already uses MySQL `aub`. Out of scope to redesign local skeleton.
- Identity same-type link now throws domain exception on MySQL; no extra Feature tests added beyond the existing case that failed.

## Next recommended step

`API Foundation`

Do **not** implement API, Sanctum, or Flutter in this task.
