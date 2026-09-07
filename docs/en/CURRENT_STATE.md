# AUB — Current State

Document reflects the **verified** state as of **2026-09-07** (code + migrations + production `route:list` / `migrate:status`). Previous text dated 2026-07-20 is superseded where it conflicts.

See also [ARCHITECTURE.md](ARCHITECTURE.md) for the two-repository model.

## Versions

| Component | Version |
|-----------|---------|
| Laravel | 13.18.1 |
| PHP | 8.3.6 (production) |
| Node.js | 20.20.0 (production) |
| `owlsolutions/custom-admin-kit` | v0.4.0 |
| `inertiajs/inertia-laravel` | 3.1.1 |
| `@inertiajs/react` | 2.3.27 |
| `tightenco/ziggy` | 2.6.3 |
| React | 18.3.1 |
| Vite | 8.1.3 |
| Tailwind CSS | 4.3.2 |

Production build exists **on the server** (`public/build/manifest.json`). The directory is **gitignored**, so a GitHub clone does not contain compiled assets.

`storage:link` exists on production.

No `laravel/sanctum`, Passport, or JWT in the host app.

## Routes (84 registered on production)

`php artisan route:list` on 2026-09-07: **Showing [84] routes**. There is **no** `routes/api.php` and **no** Flutter-ready API.

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
| `/customers`, `/customers/create`, `/customers/{id}` | `customers.*` | Students on `customers` |
| `/teachers`, `/teachers/create`, `/teachers/{id}` | `teachers.*` | No `user_id` |
| `/courses-groups` | `courses-groups.*` | Courses, groups, attach students/lessons |
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

The extra routes versus the old “78” count include filesystem serve routes, `/up`, and the full schedule/academy write set. **84 is the measured number.**

## Migrations

**37 files** in `database/migrations/`. Production: **all Ran**, batch numbers **1 through 29**.

Laravel core: users (incl. sessions / password_reset_tokens), cache, jobs.

Kit: `customers`, `services`, `staff`, `orders`, `order_staff`, `ai_provider_settings`.

AUB: roles, menu seeds, activity_logs, student profile columns, teachers, courses/groups, lessons/pivots, `can_write` / `can_delete`, weekly schedule, AI runs, study windows, multi-teacher group lessons.

Do not confuse “29 batches” with “29 files”.

## Database tables

### In use

| Table | Purpose |
|-------|---------|
| `users` | Auth; `role_id`, `can_write`, `can_delete` |
| `roles`, `role_menu_items` | RBAC |
| `customers` | **Students** (interim kit table) |
| `teachers` | Academy teachers; **not** linked to `users` |
| `courses`, `course_groups` | Courses/groups; course study window |
| `course_group_customer` | Enrollment pivot; unique `customer_id` (**one CourseGroup per student in DB** — **HIGH PRIORITY OPEN** whether this is the academy rule) |
| `lessons` | Catalog (`duration_minutes`) |
| `lesson_teacher`, `lesson_course`, `course_group_lesson` | Lesson relations; several teachers per group+lesson |
| `academy_buildings`, `academy_rooms` | Locations (no geofence lat/lng/radius columns) |
| `schedule_weeks`, `scheduled_lessons` | Weekly schedule |
| `schedule_ai_runs` | AI run log |
| `activity_logs` | CRUD (+ login/logout) audit |
| `ai_provider_settings` | Encrypted provider keys |

### Legacy kit (no active routes)

`services`, `staff`, `orders`, `order_staff`.

## Models

| Model | Status |
|-------|--------|
| User | Implemented — role, `can_write`, `can_delete` |
| Role, RoleMenuItem | Implemented (Phase 1) |
| Customer | **Student profile** (no `courseGroups()` inverse) |
| Teacher | Implemented; no `user_id` |
| Course, CourseGroup | Implemented |
| Lesson | Implemented |
| AcademyBuilding, AcademyRoom | Implemented |
| ScheduleWeek, ScheduledLesson, ScheduleAiRun | Implemented |
| ActivityLog | Implemented |
| AiProviderSetting | Implemented (`api_key` encrypted) |
| Service, Staff, Order | Kit legacy — not routed |

No `Student` or `Parent` models. Father/mother fields live on `customers`.

## Controllers / services

| Area | Location |
|------|----------|
| Students | `CustomersController` |
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
| documents, communication, events, costumeService, archive | `placeholder.*` | UI placeholders. Product: Productions is a **domain subsystem**, not “optional Phase 6”. Costume Service may stay a separate service. |

There is **no** extra sidebar item `lessons`. Catalog is under Settings → Academy.

Config: `config/aub-menu.php`. The `admin_only` flag is **not** enforced in `RoleAccess::syncRoleMenuItems`.

## Localization

UI locales: **it** (default), en, ru, uk. Validation files exist for those locales. Student screens also use inline `translations` objects.

`.env.example` still has Laravel skeleton `APP_LOCALE=en`; `config/app.php` default is `it`.

## Students (interim)

Implemented on **`customers`**. Files: `storage/app/public/students/{id}/documents` (public disk) — **not** `customers/`. Teacher photos: `teachers/{id}/photos`.

No field-level restriction: a role that can open `customers.*` sees parent contacts and medical-certificate expiry.

## Phase summary

| Phase | Module | Status |
|-------|--------|--------|
| 0 | Admin kit foundation | Complete |
| 1 | Staff roles & workplaces | Complete (2026-07-06) |
| 2 | Students | Partial — `customers` |
| 2 | Parents | Partial — embedded fields |
| 2 | Teachers | Complete as directory; no user link |
| 2 | Courses / groups | Complete |
| 2 | Enrollments | Partial — pivot only |
| 2 | Lessons catalog | Complete (Settings → Academy) |
| 3 | Weekly schedule | Complete + hybrid AI (2026-07-20) |
| 3 | Student file uploads | Partial — public disk, no documents module |
| 3 | **Student Attendance** | Not started (child on a session) |
| 3 | **Teacher Check-in** | Not started (**PRELIMINARY** GPS snapshot + geofence) |
| 3 | Documents / communication menus | Placeholders |
| 4+ | Payments | Not started |
| future | Academic Progress / Productions | Documented as large modules; not started; Productions priority = PM after discovery |
| API / Flutter | API + token auth | **Not started**; Flutter repo exists; Store packaging **OPEN** |

## What is NOT implemented

- Separate `students` / `parents` tables
- Full enrollment workflow (statuses, transfers, history). Unique `customer_id` vs multi-group **OPEN**
- **Student Attendance** (session-level) and **Teacher Check-in** (presence)
- Academic Progress / grades / report cards
- Productions / RehearsalGroup / rehearsals / performances (web `/events` is only a placeholder)
- Payments / invoices
- Standalone documents and communication modules
- Archive, costume service (menu placeholders; costume may remain a separate service)
- **`routes/api.php`, API resources, Sanctum/Passport/JWT`**
- Flutter features beyond the default template; one-app vs flavors **OPEN**
- PDF export for schedule (button is a placeholder)
- Actionable AI recommendations (re-prompt only)
- Field-level visibility; teacher scoped to assigned students
- Consent / privacy-policy entities
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
