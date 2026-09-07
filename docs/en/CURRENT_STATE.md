# AUB — Current State

Document reflects the actual state as of **2026-07-20**.

## Versions

| Component | Version |
|-----------|---------|
| Laravel | 13.18.1 |
| PHP | 8.3.6 |
| Node.js | 20.20.0 |
| `owlsolutions/custom-admin-kit` | v0.4.0 |
| `inertiajs/inertia-laravel` | 3.1.1 |
| `tightenco/ziggy` | 2.6.3 |

Production build exists: `public/build/manifest.json`

## Routes (78 registered)

### Auth

| Method | URI | Name |
|--------|-----|------|
| GET | `/` | `login` |
| POST | `/` | — |
| GET | `/login` | redirect to `/` |
| POST | `/login` | — |
| POST | `/logout` | `logout` |

### Admin (auth + role middleware required)

| URI | Name | Notes |
|-----|------|-------|
| `/dashboard` | `dashboard` | |
| `/customers`, `/customers/create`, `/customers/{id}` | `customers.*` | Students list + full-page profile |
| `/teachers`, `/teachers/create`, `/teachers/{id}` | `teachers.*` | Teacher list + profile |
| `/courses-groups` | `courses-groups.*` | Courses, groups, student/lesson assignment |
| `/lessons` | `lessons.*` | Lesson catalog by discipline |
| `/schedule-service` | `weekly-schedule.*` | Weekly schedule board (alias `/schedules`, `/weekly-schedule`) |
| `/documents` | `placeholder.documents` | Coming soon |
| `/communication` | `placeholder.communication` | Coming soon |
| `/events` | `placeholder.events` | Coming soon |
| `/archive` | `placeholder.archive` | Coming soon |
| `/costume-service` | `placeholder.costume-service` | Coming soon |
| `/settings` | `settings.*` | Tabs: users, roles, app, AI, academy |
| `/roles` | `roles.*` | Redirect → `/settings?tab=roles` |
| `/ai-settings` | `ai-settings.*` | Redirect → `/settings?tab=ai` |
| `/app-settings` | — | Redirect → `/settings?tab=app` |
| `/statistics/logs` | `statistics.logs` | Activity log viewer |
| `/profile` | `profile.*`, `password.update` | |
| `/workplace` | `workplace` | Non-admin landing |

Write/delete routes are gated by `can.write` / `can.delete` middleware on top of role access.

### Removed from active routes (generic kit)

These kit modules still have DB tables and controller files, but **routes and menu items were removed**:

- `/orders`, `/services`, `/staff`, `/calendar`

### System

| URI | Name |
|-----|------|
| `/owl-admin/health` | `owl-admin.health` |
| `/up` | Laravel health |
| `/storage/{path}` | Public file access (requires `php artisan storage:link`) |

---

## Migrations (29 batch entries ran)

| Migration | Purpose |
|-----------|---------|
| Laravel core (users, cache, jobs) | System |
| Kit CRM tables (customers, services, staff, orders, order_staff, ai_provider_settings) | Legacy kit schema — partially superseded |
| `2026_07_06_120000` … `120100` | Roles + Administrator seed |
| `2026_07_06_130000` … `143000` | Menu cleanup: rename students, remove orders/calendar/staff/services/ai-settings from menu |
| `2026_07_06_140000` | Consolidate settings menu |
| `2026_07_06_150000` | `activity_logs` table |
| `2026_07_06_210000` | Student profile fields on `customers` |
| `2026_07_06_221000` | `gender` on `customers` |
| `2026_07_08_120000` | `courses`, `course_groups`, `course_group_customer` |
| `2026_07_08_140000` … `140100` | `teachers` + menu seed |
| `2026_07_08_150000` | Single course group per discipline rule |
| `2026_07_08_160000` | `users.can_delete` |
| `2026_07_08_170000` | Single course group per student rule |
| `2026_07_08_180000` … `182000` | `lessons`, pivots, `course_group_lesson` |
| `2026_07_08_181000` | Lesson relations; drop `duration_minutes` from lessons |
| `2026_07_08_190000` | `users.can_write` |
| `2026_07_20_100000` | Weekly schedule tables |
| `2026_07_20_153500` | Drop unique constraint on `course_group_lesson.lesson_id` |
| `2026_07_20_174500` | Multiple teachers per group+lesson |
| `2026_07_20_175000` | `lessons.duration_minutes` |
| `2026_07_20_183000` | `course_groups.color` |
| `2026_07_20_203000` | `schedule_weeks.work_starts_at` / `work_ends_at` |
| `2026_07_20_221000` | `schedule_ai_runs` table |
| `2026_07_20_224500` | `courses.study_starts_at` / `study_ends_at` + shift seed |

---

## Database tables (in use)

| Table | Purpose |
|-------|---------|
| `users` | Auth; `role_id`, `can_write`, `can_delete` |
| `roles`, `role_menu_items` | RBAC |
| `customers` | **Students** (extended profile — interim use of kit table) |
| `teachers` | Academy teachers |
| `courses`, `course_groups` | Courses and groups; course study window `study_starts_at` / `study_ends_at` |
| `course_group_customer` | Student ↔ group enrollment (pivot) |
| `lessons` | Lesson catalog (`duration_minutes`) |
| `lesson_teacher`, `lesson_course`, `course_group_lesson` | Lesson relations; multiple teachers per group+lesson |
| `academy_buildings`, `academy_rooms` | Locations for schedule |
| `schedule_weeks`, `scheduled_lessons` | Weekly schedule (week work hours) |
| `schedule_ai_runs` | AI scheduling run log |
| `activity_logs` | Audit trail for CRUD actions |
| `ai_provider_settings` | AI provider keys |
| Kit legacy (unused in UI): `services`, `staff`, `orders`, `order_staff` | Tables exist; routes removed |

---

## Models

| Model | Status |
|-------|--------|
| User | Implemented — role, `can_write`, `can_delete` |
| Role, RoleMenuItem | Implemented (Phase 1) |
| Customer | **Extended as student profile** |
| Teacher | Implemented |
| Course, CourseGroup | Implemented (Course has study window / shift) |
| Lesson | Implemented |
| AcademyBuilding, AcademyRoom | Implemented |
| ScheduleWeek, ScheduledLesson, ScheduleAiRun | Implemented |
| ActivityLog | Implemented |
| Service, Staff, Order | Kit legacy — no active routes |

No separate `Student` or `Parent` models yet — data stored on `customers` (student + embedded father/mother fields).

---

## Controllers

| Controller | Purpose |
|------------|---------|
| `CustomersController` | Student list, full-page profile CRUD, file uploads, password-protected delete |
| `TeachersController` | Teacher list + profile CRUD |
| `CoursesGroupsController` | Courses, groups, attach students/lessons |
| `LessonsController` | Lesson catalog CRUD |
| `WeeklyScheduleController` | Weekly schedule board |
| `ActivityLogController` | Statistics / activity logs |
| `RolesController` | Roles CRUD (settings tab) |
| `WorkplaceController` | Non-admin landing |
| `Settings/*` | Users, language, AI, academy buildings/rooms |
| `Auth/AuthenticatedSessionController` | Login, logout |
| `ProfileController` | User profile |

Kit controllers (`OrdersController`, `ServicesController`, `StaffController`, `CalendarController`) exist but are not routed.

---

## Inertia/React pages

| Page | Path |
|------|------|
| Login (redesigned) | `Auth/Login.jsx` |
| Dashboard | `Dashboard.jsx` |
| Students list | `Customers/Index.jsx` |
| Student profile (create/edit) | `Customers/Profile.jsx` |
| Teachers list | `Teachers/Index.jsx` |
| Teacher profile | `Teachers/Profile.jsx` |
| Courses & groups | `CoursesGroups/Index.jsx` |
| Lessons | `Lessons/Index.jsx` |
| Weekly schedule | `WeeklySchedule/Index.jsx` |
| Placeholder sections | `Placeholder/ComingSoon.jsx` |
| Settings (tabbed) | `Settings/Index.jsx` + `Tabs/*` |
| Statistics / logs | `Statistics/Logs.jsx` |
| Profile | `Profile/Edit.jsx` |
| Workplace | `Workplace/Index.jsx` |

Layouts: `AdminLayout.jsx`, `AuthLayout.jsx`

---

## Admin menu structure

Dynamic items from `role_menu_items` (via `adminMenu` prop):

| menu_key | Label (IT) | Route |
|----------|------------|-------|
| dashboard | Home | `dashboard` |
| students | Studenti | `customers.index` |
| teachers | Insegnanti | `teachers.index` |
| settings | Impostazioni | `settings.index` |
| statistics | Statistiche | `statistics.logs` |

Additional items in `AdminLayout` (always allowed for authenticated users):

| Key | Label (IT) | Route | Status |
|-----|------------|-------|--------|
| coursesAndGroups | Corsi e gruppi | `courses-groups.index` | Implemented |
| lessons | Lezioni | `lessons.index` | Implemented |
| scheduleService | Servizio orari | `weekly-schedule.index` | Implemented |
| documents | Documenti | `placeholder.documents` | Placeholder |
| communication | Comunicazioni | `placeholder.communication` | Placeholder |
| events | Eventi | `placeholder.events` | Placeholder |
| costumeService | Servizio costumi | `placeholder.costume-service` | Placeholder |
| archive | Archivio | `placeholder.archive` | Placeholder (divider above) |

Config: `config/aub-menu.php`

---

## UI and branding customizations (2026-07-06 — 2026-07-20)

| Area | Change |
|------|--------|
| Login page | Split layout, custom background images, AUB logo, Singo Sans titles, `#1A2B44` button |
| AuthLayout | Desktop/mobile backgrounds (`login-chatgpt-reference.png`, `login-mobile-girl.png`) |
| Admin sidebar | Background `#1A2B44`, AUB white logo, localized "Admin panel" title |
| Language switcher | Dropdown with Globe icon (login + admin), locales: **it** (default), uk, en, ru |
| Primary buttons | `#1A2B44` / hover `#132033` across admin pages |
| Card widgets | `.app-widget` background `#EBF1FF` |
| Students table | Photo + age columns; clickable rows; message icon (non-functional) |
| Students profile | Full-page form; photo on avatar; parent modals; document uploads; delete with password |
| Settings | Consolidated tabs; roles and AI moved from separate menu items |
| Font | Singo Sans — `public/fonts/singo-sans/singo-sans-regular.ttf`, `.font-singo` in `app.css` |

Reference designs: `docs/ref/login/`, `docs/ref/loginMobile/`, `docs/ref/STprofile/`

---

## Localization

| Locale | UI | Laravel validation |
|--------|-----|-------------------|
| it | Default admin UI | `lang/it/validation.php` |
| en | Supported | `lang/en/validation.php` |
| ru | Supported | `lang/ru/validation.php` |
| uk | Supported | `lang/uk/validation.php` |

Student profile and list pages use inline `translations` objects keyed by locale.

---

## Students module status (interim architecture)

**Implemented on top of `customers` table** — not a separate `students` table yet.

### Profile fields

- Personal: first/last name (required on create), gender, tax code, birth date/place, residence, email, phone, notes
- Parents: father and mother blocks (name, phone, email, notes) via modals; legacy `parent_phone`/`parent_email` retained
- Course: `course_aa_2026_27`, `other_courses`, `is_existing_student`, `form_filled_at`
- Documents: medical certificate expiry, parent ID, regulation forms, photo (header avatar upload)
- Files stored under `storage/app/public/customers/` — **requires** `php artisan storage:link`

### UI behaviour

- List at `/customers` — photo, name, age, course; row opens profile
- Create at `/customers/create`, edit at `/customers/{id}`
- Save confirmation message; delete requires confirmation + current user password

---

## Phase implementation summary

| Phase | Module | Status |
|-------|--------|--------|
| 0 | Admin kit foundation | ✅ Complete |
| 1 | Staff roles & workplaces | ✅ Complete (2026-07-06) |
| 2 | Students | 🟡 Partial — extended `customers`, no separate entity |
| 2 | Parents | 🟡 Partial — embedded father/mother fields only |
| 2 | Teachers | ✅ Complete |
| 2 | Courses / groups | ✅ Complete |
| 2 | Enrollments | 🟡 Partial — `course_group_customer` pivot |
| 2 | Lessons catalog | ✅ Complete |
| 3 | Weekly schedule | ✅ Complete + hybrid AI, course windows, 5/30 grid (2026-07-20) |
| 3 | Student document uploads | 🟡 Partial — in profile, no standalone module |
| 3 | Attendance | ❌ Not started |
| 3 | Documents module (menu) | ❌ Placeholder only |
| 3 | Communication | ❌ Placeholder only |
| 4+ | Payments, mobile, optional services | ❌ Not started |

---

## What is NOT implemented

- Separate `students` / `parents` tables and models
- Full enrollment workflow (statuses, transfers, history)
- Attendance tracking
- Payments / invoices / accounting
- Standalone documents management page
- Communication / messaging
- Events, archive, costume service (menu placeholders only)
- Mobile API and parent/student apps
- PDF export for schedule
- Fully actionable AI recommendations (button only appends text to the prompt and re-runs create)
- Role-restricted field visibility on student profile (all admins see all fields)

Schedule module details: [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).

---

## Host customizations (preserve)

- Login at `/` instead of `/login`
- `/login` redirects to `/`
- Generic kit CRM routes removed from menu and routing
- Settings, roles, AI consolidated under `/settings` tabs
- Italian as default UI locale

---

## After frontend/backend changes

```bash
npm run build
php artisan optimize:clear
php artisan view:cache
# after new migrations:
php artisan migrate
# for student photos:
php artisan storage:link
```

See [DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md).
