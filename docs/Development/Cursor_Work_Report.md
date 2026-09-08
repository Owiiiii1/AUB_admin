# Cursor Work Report

## Task

Establish the mobile API Foundation in `AUB_admin`: `/api/v1` health, login, logout, logout-all, and `/me` for student / parent / teacher. No Flutter changes. No schedule, attendance, registration, or refresh-token work.

## Architecture decision

Laravel Sanctum **v4.3.3** (`laravel/sanctum`, compatible with Laravel 13). Official personal access tokens, hashed at rest. Not JWT, not Passport, not a custom token table.

Flutter will send `Authorization: Bearer <token>`. Token ability is `mobile`. Actor type is always read from `users.account_type`, never from the token.

## API routes

| Method | Path | Auth |
|--------|------|------|
| GET | `/api/v1/health` | none |
| POST | `/api/v1/auth/login` | none, 5/min email+IP |
| GET | `/api/v1/me` | `auth:sanctum` + `mobile.actor` |
| POST | `/api/v1/auth/logout` | same |
| POST | `/api/v1/auth/logout-all` | same |

`routes/api.php` is registered from `bootstrap/app.php`. Not mixed with Inertia/session web routes.

## Authentication

`POST /api/v1/auth/login` requires `email`, `password`, `device_name`. Checks password, `is_active`, account_type ∈ student/parent/teacher, and matching linked profile. Failures share `401 invalid_credentials`. Success issues a Sanctum PAT named with `device_name`, expiration **43200 minutes** (`AUB_API_TOKEN_EXPIRATION_MINUTES`). Multiple devices allowed. Logout deletes the current token; logout-all deletes all of the user's tokens. Plaintext token is returned only on login.

`staff` is rejected with the same public 401 as a bad password.

## Runtime identity protection

`EnsureMobileActorIsValid` reloads the User from the database on every protected request. Inactive users have all tokens revoked. Staff or missing matching profile revokes the current token. `AccountIdentityService::setActive(false)` and `unlink()` also delete tokens. User `updated` observer deletes tokens when `is_active` becomes false.

## JSON contract

Success: `{ success: true, data }`. Errors: `{ success: false, error: { code, message, fields? } }`. `/api/*` always JSON (validation 422, unauthenticated 401, not found 404, 429, generic 500 without internals). Web HTML rendering is unchanged.

## Rate limits

- Login: 5 / minute / (normalized email + IP)
- Authenticated API: 120 / minute / user
- CORS origins empty (native Flutter does not need browser CORS)

## API Resources

Whitelist only (`UserResource`, `StudentProfileResource`, `ParentProfileResource`, `TeacherProfileResource`, `MeResource`).

**Student:** id, first_name, last_name, display_name, photo_url, academy_class `{id,name}`, academic_year `{id,name}`.

**Parent:** id, first_name, last_name, display_name, children[] (id, names, display_name, academy_class). Only `student_parent` children.

**Teacher:** id, first_name, last_name, display_name.

Not returned: tax_code, medical fields, notes, documents, password, remember_token, role_id, can_write, can_delete, AI settings.

## Database changes

Migration `2026_09_08_230000_create_personal_access_tokens_table` (Sanctum). Production batch 3 Ran. Production database remains `aub`. Tests remain on `aub_test`. `TestDatabaseGuard` unchanged.

## Tests

Production host, `php artisan test` after `optimize:clear`:

| Metric | Count |
|--------|-------|
| Total | 44 |
| Passed | 44 |
| Failed | 0 |
| Errors | 0 |
| Assertions | 252 |

`ApiFoundationTest` covers health, actor logins, anti-enumeration, validation, rate limit, hashed token, multi-device, logout, logout-all, `/me` whitelist, parent isolation, disable/unlink, JSON 401/404.

## Security checks

- Staff cannot obtain a mobile token
- Inactive / missing profile cannot obtain a token
- Disabled or unlinked users lose protected API access
- `/me` whitelist tested against tax_code, medical, notes, documents, password, RBAC flags
- Tokens stored as 64-char hashes
- Failed login logs a reason code without password or token
- `ActivityLogger` redacts `token` / `authorization`

## Production deploy

Files copied to `/var/www/aub`. `composer update laravel/sanctum` → **v4.3.3**. `php artisan migrate --force` created `personal_access_tokens`. `AUB_API_TOKEN_EXPIRATION_MINUTES=43200` appended to server `.env` / `.env.testing` without publishing secrets. `.env` was not overwritten. `optimize:clear`. No `npm run build` (no frontend change). No `config:cache` before tests.

## Smoke tests

Public:

- `GET /api/v1/health` → 200, `{success:true, data.status=ok}`
- `GET /` → 200 (web login unchanged)
- `GET /owl-admin/health` → 200

Temporary student actor created only for HTTP login/me/logout, then deleted. Statuses only (no plaintext token in this report): login 200, me 200, logout 200. Production admin still present; teacher count unchanged after cleanup.

## Files changed

Created: `routes/api.php`, `config/aub.php`, `config/sanctum.php`, `config/cors.php`, API controllers/resources/requests/middleware/exception renderer, `MobileAuthService`, Sanctum migration, `tests/Feature/ApiFoundationTest.php`, `docs/en/API.md`, `docs/ru/API.md`.

Modified: `composer.json`, `composer.lock`, `bootstrap/app.php`, `User`, `Teacher`, `AccountIdentityService`, `ActivityLogger`, `AppServiceProvider`, env examples, bilingual docs listed in the task.

Not committed: `.env`, `.env.testing`, passwords, tokens, `vendor`.

## Technical debt / OPEN

- Refresh / re-auth strategy for mobile UX
- Password reset / invitation / activation
- Device management UI
- Flutter Authentication Foundation (next)
- Staff mobile API (not in this contract)
- Feature endpoints (schedule, attendance, …)
- Security Foundation still required before wide rollout (private storage, field-level ACL, 2FA, …)
- CORS for Flutter Web later, if ever

## Next recommended step

**Flutter Authentication Foundation**

Do **not** implement it in this task.
