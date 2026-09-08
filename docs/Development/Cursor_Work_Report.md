# Cursor Work Report

## Task

Implement the Identity Layer in `AUB_admin` as the foundation for a future mobile API.

Rule: **one User = one primary account/actor type** (`staff` | `student` | `parent` | `teacher`). `account_type` is identity. `role_id` is web RBAC only. No API, Sanctum, Flutter, attendance, grades, or invitation workflow in this task.

## Before

- `users` had `role_id`, `can_write`, `can_delete`. No `account_type`, no `is_active`.
- `teachers.user_id` existed as nullable unique FK. `students.user_id` and `parents.user_id` did not.
- Settings → Users created web users with a required `role_id`.
- Session login did not check account activity or actor type.
- Parent/Student were domain rows only; no login accounts.
- Teachers were not auto-linked to Users (correct: email matching is unsafe).
- Production users before migration: **1** (`admin@admin.com`, `role_id=1`). Teachers: 20. Students: 0. `pdo_sqlite`: no.

## After

Identity Layer is implemented and on production (`https://aub.owlsolutions.net`).

```
User.account_type: staff | student | parent | teacher
one User = one actor type
Student.user_id / Parent.user_id / Teacher.user_id
account_type != web RBAC
```

Existing admin kept: `admin@admin.com`, `account_type=staff`, `is_active=1`, `role_id=1`.

## Database changes

Migration `2026_09_08_220000_add_account_identity_layer`:

- `users.account_type` string, default `staff`, indexed. Existing rows set to `staff`.
- `users.is_active` boolean, default `true`. Existing rows set to `true`.
- `students.user_id` nullable unique FK → `users.id`, `nullOnDelete`.
- `parents.user_id` nullable unique FK → `users.id`, `nullOnDelete`.
- `teachers.user_id` unchanged (already nullable unique FK). Not backfilled by email.

39 migration files on production. Identity is batch 2.

## account_type rules

| Type | Profile | `role_id` | Web CRM |
|------|---------|-----------|---------|
| `staff` | none | required for workplace (existing RBAC) | yes, if active role |
| `student` | one `Student` | always null | no |
| `parent` | one `AcademyParent` | always null | no |
| `teacher` | one `Teacher` | null or web role | only if web role assigned |

`account_type=staff` grants no permissions by itself.

Constants live on `User`: `TYPE_STAFF`, `TYPE_STUDENT`, `TYPE_PARENT`, `TYPE_TEACHER`, `ACCOUNT_TYPES`. Invalid types are rejected in `User::saving`.

## Profile relations

- `User::studentProfile()`, `parentProfile()`, `teacherProfile()`
- `Student::user()`, `AcademyParent::user()`, `Teacher::user()`
- `user_id` is not mass-assignable on Student / Parent / Teacher (`Teacher::$fillable` no longer includes `user_id`)

## Identity validation

`AccountIdentityService` is the only bind/unlink/create-and-link path.

- staff cannot have an actor profile
- student/parent/teacher may link to at most one matching profile
- cross-type links throw `AccountIdentityException`
- unique FKs remain as a second line of defence; they do not cover cross-table conflicts

## Admin UI changes

- Student profile: Account section (create / link / unlink / disable)
- Parent (Father/Mother modal, after parent is saved): same Account section
- Teacher profile: same
- Settings → Users: name, email, account_type, web role, active/inactive, linked profile
- Settings create remains **staff** with a required web role
- Student/Parent cannot be given a web role in UI or backend
- Teacher web role is optional
- Admin sets a temporary password. No invitation email.

## RBAC isolation

- Login rejects inactive users and users who cannot access web admin (Parent/Student, Teacher without role, staff without role)
- `EnsureUserHasRole` also checks `is_active` and `canAccessWebAdmin()`
- `RoleAccess` uses `canAccessWebAdmin()` for menu, route access, and post-login redirect
- Actor accounts do not receive admin rights from `account_type`

## Migration result

```
php artisan migrate --force
```

**PASS.** `2026_09_08_220000_add_account_identity_layer` ... DONE (batch 2).

```
php artisan migrate:status
```

**PASS.** 39 migrations Ran, including Identity Layer.

Production after migrate: `admin@admin.com` `type=staff` `active=1` `role=1`. Teachers 20. Students 0. Admin account was not deleted.

## Tests

```
php artisan test
```

**FAIL on production (environment, not assertion failures).**

| Result | Count |
|--------|-------|
| Tests | 26 |
| Passed | 2 (`ExampleTest` unit + feature GET `/`) |
| Errors | 24 (all `RefreshDatabase` Feature tests) |
| Failed assertions | 0 |
| Cause | PHP 8.3.6 has **no `pdo_sqlite`**. phpunit.xml uses `sqlite :memory:`. Error: `could not find driver (Connection: sqlite, Database: :memory:)` |
| Production MySQL | **not wiped**. Config cache was absent. Tests targeted sqlite, not `aub`. |

Local Windows clone has no PHP in PATH, so the suite was not executed locally.

Identity coverage that exists in `tests/Feature/AccountIdentityLayerTest.php` (16 cases) could not run on this host. A production smoke of `AccountIdentityService::createAndLink` succeeded (student account, hashed password, `role_id` null, `canAccessWebAdmin=false`, then cleaned up).

## Production deploy

Host: `deploy@178.156.234.23:/var/www/aub`. `.env` / `vendor` / `node_modules` not uploaded.

Copied:

- `app/Exceptions/AccountIdentityException.php`
- `app/Services/AccountIdentityService.php`
- `app/Http/Controllers/ActorAccountController.php`
- `app/Http/Controllers/Settings/{SettingsController,UserController}.php`
- `app/Http/Controllers/{StudentsController,TeachersController}.php`
- `app/Http/Middleware/{EnsureUserHasRole,HandleInertiaRequests}.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Models/{User,Student,AcademyParent,Teacher}.php`
- `app/Providers/AppServiceProvider.php`
- `app/Support/RoleAccess.php`
- `database/factories/UserFactory.php`
- `database/migrations/2026_09_08_220000_add_account_identity_layer.php`
- `routes/owl-admin-pages.php`
- `resources/js/Components/AccountPanel.jsx`
- `resources/js/Pages/Customers/Profile.jsx`
- `resources/js/Pages/Teachers/Profile.jsx`
- `resources/js/Pages/Settings/Index.jsx`
- `resources/js/Pages/Settings/Tabs/UsersTab.jsx`
- `tests/Feature/AccountIdentityLayerTest.php`
- RU + EN docs listed in Files changed
- this Work Report (after commit)

Commands:

| Command | Result |
|---------|--------|
| `php artisan migrate --force` | PASS |
| `php artisan migrate:status` | PASS (39 Ran) |
| `php artisan route:list` | PASS — Showing **[96] routes** |
| `php artisan test` | FAIL — 2 passed, 24 errors, no sqlite driver |
| `npm run build` | PASS (vite 8.1.3, 1.02s; `AccountPanel-*.js` emitted) |
| `php artisan optimize:clear` | PASS (before migrate, after build/test) |

## Smoke tests

| Check | Result |
|-------|--------|
| `GET https://aub.owlsolutions.net/` | HTTP 200 |
| `GET /customers`, `GET /settings` unauthenticated | HTTP 302 |
| Admin password `admin@admin.com` / `admin` Hash::check | PASS |
| Admin `canAccessWebAdmin()` | true |
| Create+link student actor via `AccountIdentityService` | PASS (`account_type=student`, `role_id=null`, no web access, password hashed) |
| Cleanup of smoke student/user | PASS |
| Users after smoke | still 1 admin |

## Files changed

Created:

- `app/Exceptions/AccountIdentityException.php`
- `app/Services/AccountIdentityService.php`
- `app/Http/Controllers/ActorAccountController.php`
- `database/migrations/2026_09_08_220000_add_account_identity_layer.php`
- `resources/js/Components/AccountPanel.jsx`
- `tests/Feature/AccountIdentityLayerTest.php`
- `docs/Development/Cursor_Work_Report.md` (rewritten)

Modified:

- `app/Models/User.php`, `Student.php`, `AcademyParent.php`, `Teacher.php`
- `database/factories/UserFactory.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Middleware/EnsureUserHasRole.php`, `HandleInertiaRequests.php`
- `app/Support/RoleAccess.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/Settings/UserController.php`, `SettingsController.php`
- `app/Http/Controllers/StudentsController.php`, `TeachersController.php`
- `routes/owl-admin-pages.php`
- `resources/js/Pages/Customers/Profile.jsx`
- `resources/js/Pages/Teachers/Profile.jsx`
- `resources/js/Pages/Settings/Index.jsx`
- `resources/js/Pages/Settings/Tabs/UsersTab.jsx`
- `docs/ru` + `docs/en`: CURRENT_STATE, ARCHITECTURE, DATA_MODEL_DRAFT, USER_ROLES_AND_ACCESS, OPEN_QUESTIONS, MODULE_ROADMAP, NEXT_STEPS, DEVELOPMENT_RULES

Deleted: none.

## Technical debt / OPEN

- One physical person with two actor types = **two User accounts**. Not merged by email.
- `users.email` stays unique, so two accounts need two login emails. Future: login identifier / alias / username.
- Invitation / activation workflow is absent; admin sets a temporary password.
- API / token auth is absent (`routes/api.php`, Sanctum, `/me`).
- Production cannot run sqlite Feature tests (`pdo_sqlite` missing). Do **not** `config:cache` then `php artisan test`.
- Teacher web-role `teacher` was not auto-created.
- Parent multi-child rules, older-student onboarding, and teacher workplace vs mobile UX remain OPEN.

## Next recommended step

**API Foundation** — routing, versioning, token auth (Sanctum = candidate, not locked), `/me`, resources, errors, rate limits, tests.

Do **not** implement it in this task.
