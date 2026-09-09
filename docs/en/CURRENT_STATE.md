# AUB — Current State

Document reflects the **verified** state as of **2026-09-09** (application timezone `Europe/Rome` + Student/Parent schedule API + Identity Layer + isolated MySQL test DB + API Foundation `/api/v1`). Previous text dated 2026-07-20 / 2026-09-07 / 2026-09-08 is superseded where it conflicts.

See also [ARCHITECTURE.md](ARCHITECTURE.md) for the two-repository model.

## Versions

| Component | Version |
|-----------|---------|
| Laravel | 13.18.1 |
| PHP | 8.3.6 (production) |
| Node.js | 20.20.0 (production) |
| `laravel/sanctum` | v4.3.3 |
| `owlsolutions/custom-admin-kit` | v0.4.0 |
| `inertiajs/inertia-laravel` | 3.1.1 |
| `@inertiajs/react` | 2.3.27 |
| `tightenco/ziggy` | 2.6.3 |
| React | 18.3.1 |
| Vite | 8.1.3 |
| Tailwind CSS | 4.3.2 |

Production build exists **on the server** (`public/build/manifest.json`). The directory is **gitignored**, so a GitHub clone does not contain compiled assets.

`storage:link` exists on production.

Application timezone: **`Europe/Rome`** (`APP_TIMEZONE` in `.env`, `config('app.timezone')`). Schedule current-week bounds use this timezone. Existing DATETIME columns were not bulk-converted.

`laravel/sanctum` **v4.3.3** is installed. No Passport or JWT. Contract: [API.md](API.md).

## Automated tests

| Item | Value |
|------|--------|
| Engine | MySQL 8 (not SQLite) |
| Production DB | `aub` |
| Test DB | `aub_test` |
| Guard | `App\Testing\TestDatabaseGuard` — refuse anything other than MySQL `aub_test` |
| Last suite on production host | **65 passed**, 0 failed, 0 errors |

`php artisan test` uses phpunit.xml + server `.env.testing`. Feature tests use `RefreshDatabase` against `aub_test` only.

## Routes (web CRM + `/api/v1`)

`php artisan route:list --path=api`: **7** routes. Web CRM unchanged. No Passport/JWT.

### Mobile API

| Method | URI | Name |
|--------|-----|------|
| GET | `/api/v1/health` | `api.v1.health` |
| POST | `/api/v1/auth/login` | `api.v1.auth.login` |
| POST | `/api/v1/auth/logout` | `api.v1.auth.logout` |
| POST | `/api/v1/auth/logout-all` | `api.v1.auth.logout-all` |
| GET | `/api/v1/me` | `api.v1.me` |
| GET | `/api/v1/schedule` | `api.v1.schedule.student` |
| GET | `/api/v1/children/{student}/schedule` | `api.v1.schedule.child` |

Only `student` / `parent` / `teacher`. `staff` cannot use this login. Student schedule is own class only; parent schedule is own children via `student_parent`. Details: [API.md](API.md).

### Auth

| Method | URI | Name |
|--------|-----|------|
| GET | `/` | `login` |
| POST | `/` | — |
| GET | `/login` | redirect to `/` |
| POST | `/login` | — |
| POST | `/logout` | `logout` |

Password reset routes (`password.request` etc.) **do not exist**.

### Admin (auth + role middleware)

| URI | Name | Notes |
|-----|------|-------|
| `/dashboard` | `dashboard` | **Placeholder** home |
| `/customers`, `/customers/create`, `/customers/{student}` | `customers.*` | Students (`Student`); URL still `/customers`; Account create/link on profile |
| `/teachers`, `/teachers/create`, `/teachers/{id}` | `teachers.*` | `teachers.user_id` + Account create/link |
| `/courses-groups` | `courses-groups.*` | Courses + `AcademyClass`; URL still `/courses-groups` |
| `/lessons` | `lessons.*` | **GET index redirects** to `/settings?tab=academy&academyTab=lessons` |
| `/schedule-service` | `weekly-schedule.*` | Aliases `/schedules`, `/weekly-schedule` |
| `/documents` | `placeholder.documents` | Coming soon |
| `/communication` | `placeholder.communication` | Coming soon |
| `/events` | `placeholder.events` | Coming soon |
| `/archive` | `placeholder.archive` | Coming soon |
| `/costume-service` | `placeholder.costume-service` | Coming soon |
| `/settings` | `settings.*` | Tabs: users, roles, app, AI, academy (halls + **lessons catalog**) |
| `/roles` | `roles.*` | Redirect → `/settings?tab=roles` |
| `/ai-settings` | `ai-settings.*` | Redirect → `/settings?tab=ai` |
| `/app-settings` | — | Redirect → `/settings?tab=app` |
| `/statistics/logs` | `statistics.logs` | CRUD activity log, not view-audit |
| `/profile` | `profile.*`, `password.update` | |
| `/workplace` | `workplace` | Thin non-admin landing |

Write/delete routes use `can.write` / `can.delete`. Role CRUD also uses `administrator`.

### Removed from routing (generic kit)

Controllers and tables remain; **routes and menu items removed**: `/orders`, `/services`, `/staff`, `/calendar`.

### System

| URI | Name |
|-----|------|
| `/owl-admin/health` | `owl-admin.health` |
| `/up` | Laravel health |
| `GET/PUT storage/{path}` | `storage.local` / `storage.local.upload` (Laravel filesystem serve) |
| `/storage/{path}` via symlink | Public disk files (needs `storage:link`) |

The extra routes versus the old “84” count are 12 actor-account POST routes (student / parent / teacher). Plus 5 `/api/v1` routes. API Foundation is **done**.

## Migrations

**40 files** in `database/migrations/`. Identity Layer: `2026_09_08_220000_add_account_identity_layer`. Sanctum: `2026_09_08_230000_create_personal_access_tokens_table`.

Laravel core: users (incl. sessions / password_reset_tokens), cache, jobs.

Kit: `customers` (legacy; academy no longer uses it), `services`, `staff`, `orders`, `order_staff`, `ai_provider_settings`.

AUB: roles, menu seeds, activity_logs, `students` / `parents` / `student_parent`, `academic_years`, `academy_classes`, `class_lessons`, `class_lesson_teacher`, teachers (`user_id`), Identity (`users.account_type`, `users.is_active`, `students.user_id`, `parents.user_id`), courses, lessons/pivots, `can_write` / `can_delete`, weekly schedule (`academy_class_id`), AI runs, study windows.

## Database tables

### In use

| Table | Purpose |
|-------|---------|
| `users` | Auth; `account_type` (`staff` \| `student` \| `parent` \| `teacher`), `is_active`, `role_id`, `can_write`, `can_delete`. Email unique |
| `roles`, `role_menu_items` | RBAC (web permissions only; not actor type) |
| `academic_years` | Academic year; one `is_active` current year |
| `students` | Student profile; nullable unique `user_id` |
| `parents` | Parents/guardians; PHP model `AcademyParent`; nullable unique `user_id` |
| `student_parent` | M2M + `relation_type` (`father` / `mother` / `guardian` / `other`) |
| `teachers` | Teachers; nullable unique `user_id` |
| `courses` | Direction/course; study window |
| `academy_classes` | Product `Class`; PHP model `AcademyClass` |
| `academy_class_student` | Enrollment; **unique `student_id`** = at most one active Class |
| `class_lessons` | Class program for an AcademicYear; unique (year, class, lesson) |
| `class_lesson_teacher` | Several teachers per ClassLesson (`hours` on pivot) |
| `lessons` | Discipline catalog (`duration_minutes`) |
| `lesson_teacher`, `lesson_course` | Catalog eligibility / course link |
| `academy_buildings`, `academy_rooms` | Locations (no geofence lat/lng/radius columns) |
| `schedule_weeks`, `scheduled_lessons` | Weekly schedule; `scheduled_lessons.academy_class_id` |
| `schedule_ai_runs` | AI run log |
| `activity_logs` | CRUD (+ login/logout); `student_id` + legacy `customer_id` |
| `ai_provider_settings` | Encrypted provider keys |
| `customers` | **Kit legacy**; academy logic no longer uses it |

### Legacy kit (no active routes)

`services`, `staff`, `orders`, `order_staff`.

## Models

| Model | Status |
|-------|--------|
| User | Implemented — `account_type`, `is_active`, role, `can_write`, `can_delete`; `studentProfile` / `parentProfile` / `teacherProfile` |
| Role, RoleMenuItem | Implemented (Phase 1) |
| Customer | Kit legacy — **not** academy Student |
| Student | Student profile; `user()` |
| AcademyParent | Parent (`parents`); `user()` |
| Teacher | Implemented; `user()` |
| Course | Direction |
| AcademyClass | Product `Class` |
| AcademicYear | Academic year |
| ClassLesson | Class lesson for a year |
| Lesson | Discipline catalog |
| AcademyBuilding, AcademyRoom | Implemented |
| ScheduleWeek, ScheduledLesson, ScheduleAiRun | Implemented; `ScheduledLesson` → `AcademyClass` |
| ActivityLog | Implemented (`student_id`) |
| AiProviderSetting | Implemented (`api_key` encrypted) |
| Service, Staff, Order | Kit legacy — not routed |

## Controllers / services

| Area | Location |
|------|----------|
| Students | `StudentsController` (Inertia `Customers/*`, URL `/customers`) |
| Teachers | `TeachersController` |
| Courses/groups | `CoursesGroupsController` |
| Lessons catalog | `LessonsController` (UI via Settings) |
| Weekly schedule | `WeeklyScheduleController` |
| AI planner | `Services/WeeklySchedule/*`, `Services/Ai/*` |
| Activity log | `ActivityLogController`, `ActivityLogger` |
| Roles / users / AI / academy settings | `RolesController`, `Settings/*` |
| Auth / profile | `AuthenticatedSessionController`, `ProfileController` |
| Workplace | `WorkplaceController` |

Kit controllers `OrdersController`, `ServicesController`, `StaffController`, `CalendarController` exist and are **not routed**.

## Inertia / React pages

| Page | Path | Status |
|------|------|--------|
| Login | `Auth/Login.jsx` | Implemented |
| Dashboard | `Dashboard.jsx` | **Placeholder** |
| Students | `Customers/Index.jsx`, `Customers/Profile.jsx` | Implemented |
| Teachers | `Teachers/Index.jsx`, `Teachers/Profile.jsx` | Implemented |
| Courses & groups | `CoursesGroups/Index.jsx` | Implemented |
| Lessons catalog | `Settings/Tabs/LessonsTab.jsx` | Implemented; **no** `Lessons/Index.jsx` |
| Weekly schedule | `WeeklySchedule/Index.jsx` | Implemented |
| Settings | `Settings/Index.jsx` + `Tabs/*` | Implemented; academy “general” tab is placeholder copy |
| Statistics | `Statistics/Logs.jsx` | Implemented |
| Profile | `Profile/Edit.jsx` | Implemented |
| Workplace | `Workplace/Index.jsx` | Thin landing |
| Coming soon | `Placeholder/ComingSoon.jsx` | Placeholders |
| Leftover kit pages | `Orders`, `Services`, `Staff`, `Calendar`, `Roles/Index`, `AiSettings/Index`, `AppSettings/Index` | Files exist; not the active UI |

Layouts: `AdminLayout.jsx`, `AuthLayout.jsx`.

## Admin menu

Dynamic items from `role_menu_items` (`adminMenu` prop): `dashboard`, `students` → `customers.index`, `teachers`, `settings` (admin_only flag in config), `statistics`.

**Always shown in `AdminLayout` extra list** (and allowed by `always_allowed_route_patterns` for any user with a role):

| Key | Route | Status |
|-----|-------|--------|
| coursesAndGroups | `courses-groups.index` | Implemented |
| scheduleService | `weekly-schedule.index` | Implemented |
| documents, communication, events, costumeService, archive | `placeholder.*` | UI placeholders. Productions is **late future**; `/events` stays a placeholder. Costume Service may stay a separate service. |

There is **no** extra sidebar item `lessons`. Catalog is under Settings → Academy.

Config: `config/aub-menu.php`. The `admin_only` flag is **not** enforced in `RoleAccess::syncRoleMenuItems`.

## Localization

UI locales: **it** (default), en, ru, uk. Validation files exist for those locales. Student screens also use inline `translations` objects.

`.env.example` still has Laravel skeleton `APP_LOCALE=en`; `config/app.php` default is `it`.

## Students

Implemented on **`students`** + **`parents`** + **`student_parent`**. Father/Mother UI sections remain; the backend writes `AcademyParent` + `relation_type`. `/customers` URLs and Inertia `Customers/*` are kept without a redesign. Files: `storage/app/public/students/{id}/documents` (**public** disk) — security debt; private storage is a separate stage. Teacher photos: `teachers/{id}/photos`.

No field-level restriction: a role that can open `customers.*` sees parent contacts and medical-certificate expiry.

## Phase summary

| Phase | Module | Status |
|-------|--------|--------|
| 0 | Admin kit foundation | Complete |
| 1 | Staff roles & workplaces | Complete (2026-07-06) |
| 2 | Students | Complete — `students` |
| 2 | Parents | Complete — `parents` / `student_parent` |
| 2 | Teachers | Directory + Identity: create/link account; web role optional |
| 2 | Courses / Class | Complete — `Course` + `AcademyClass` |
| 2 | Enrollments | Partial — unique one Class; status workflow OPEN |
| 2 | Lessons catalog | Complete (Settings → Academy) |
| 3 | Weekly schedule | Complete + hybrid AI (2026-07-20) |
| 3 | Student file uploads | Partial — public disk, no documents module |
| 3 | **Student Attendance** | Not started (child on a session) |
| 3 | **Teacher Check-in** | Not started (**DECIDED**: daily GPS snapshot + geofence; not per lesson) |
| 3 | Documents / communication menus | Placeholders |
| 4+ | Payments | Not started |
| future | Final Assessment / Report Cards | Separate module; no running grades; not started |
| late future | Productions / Shows | Discovery-needed; activity groups ≠ `Class`; web `/events` is a placeholder |
| API / Flutter | API Foundation `/api/v1` | **Done** (Sanctum PAT). Flutter client **not** wired; Store packaging **OPEN** |

## What is NOT implemented

- **Flutter Authentication Foundation** — next stage (client is still the template)
- Invitation / activation workflow
- Separate login identifier besides unique email
- Full AcademicYear admin UI
- Full enrollment workflow (statuses, transfers, history). The “one active `Class`” rule is DECIDED
- **Student Attendance** (session-level) and **Teacher Check-in** (daily presence — semantics DECIDED, no code)
- Final Assessment / `StudentFinalResult` / `ReportCard` (core DECIDED; not implemented; no running grades)
- Productions / activity groups / rehearsals / performances (late future; web `/events` is only a placeholder)
- Payments / invoices
- Standalone documents and communication modules
- Archive, costume service (menu placeholders; costume may remain a separate service)
- Feature API: schedule, attendance, check-in, documents, messages, payments
- Flutter features beyond the default template; one-app vs flavors **OPEN**
- PDF export for schedule (button is a placeholder)
- Actionable AI recommendations (re-prompt only)
- Field-level visibility; teacher scoped to assigned students
- Consent entities (`ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`)
- View/access audit of sensitive records
- Private storage for children’s documents
- Admin 2FA
- Git deploy from GitHub to `/var/www/aub`

## Host customizations (preserve)

- Login at `/`
- Kit CRM routes removed
- Settings / roles / AI / academy (including lessons) under `/settings`
- Italian default UI locale
- AUB branding (login, sidebar `#1A2B44`, Singo Sans)

## After frontend/backend changes

See [DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md) and [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md). Remember: production is **not** a git checkout.
