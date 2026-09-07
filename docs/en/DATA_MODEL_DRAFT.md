# AUB — Data model draft

Status legend:

- **Implemented** — in code and migrations, used
- **Generic kit** — from `custom-admin-kit` v0.4.0
- **Planned** — not built
- **Unverified leftover** — mentioned historically; Spatie-style tables were dropped in `2026_07_06_120000`; MySQL leftovers were **not** re-checked on 2026-09-07

Migrations: **37 files**, all **Ran** on production, batches **1–29**.

Privacy tags: Normal / Personal data / Children’s data / Special category / Secret.

---

## A. Implemented / kit tables

### users (implemented)

Auth. Extra columns: `role_id` → `roles`, `can_write`, `can_delete`.  
Relations: `belongsTo Role`.  
Privacy: Personal data.

### customers (implemented — **students, interim**)

Kit columns: `name`, `email`, `phone`, `address`, `notes`, `status`.

AUB profile columns (migrations `2026_07_06_210000`, `2026_07_06_221000`): first/last name, gender, tax code, birth, residence, student email/phone, course flags, document paths, father/mother blocks, `student_notes`. Legacy `parent_phone` / `parent_email` kept.

Relations in code: `hasMany Order` (legacy). **No** `courseGroups()` inverse; enrollments queried via `course_group_customer`.

Files: public disk `students/{id}/documents` — **privacy gap**.

Privacy: Children’s data / Personal data.

### services, staff, orders, order_staff (generic kit)

Unused in UI/routes. `staff.role` is free text, **not** RBAC.

### ai_provider_settings (generic kit, used)

`provider`, encrypted `api_key`, connection flags, models. Privacy: Secret.

### Laravel system

`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations`.

### activity_logs (implemented)

`user_id`, `customer_id`, `action`, `subject_*`, `properties`, `route_name`, `ip_address`, `user_agent`, `created_at` (no `updated_at`). CRUD + login/logout. **Not** a read/access audit.

---

## B. AUB access tables (implemented)

### roles

`name`, `slug`, `description`, `is_admin`, `is_system`, `is_active`.  
hasMany `RoleMenuItem`, hasMany `User`.

### role_menu_items

`role_id`, `menu_key`, `label`, `route_name`, `url`, `icon`, `sort_order`, `is_active`.

---

## C. Implemented academy tables (2026-07-08+)

### teachers

`type`, `first_name`, `last_name`, `name`, `email`, `phone`, `tax_code`, `description`, `photo_path`.  
`belongsToMany Lesson` (`lesson_teacher`). **No `user_id`.**  
Photos: public disk `teachers/{id}/photos`.

### courses / course_groups / course_group_customer

`courses`: `discipline`, `name`, `sort_order`, `study_starts_at`, `study_ends_at`.  
`course_groups`: `course_id`, `name`, `color`, `sort_order`.  
`course_group_customer`: `course_group_id`, `customer_id`, `discipline` (column remains).  

**Enrollment constraint in code:** unique `customer_id` — **at most one CourseGroup per student in the database**. This is **not** a confirmed academy business rule. If a child may attend several disciplines at once, the unique index is a **HIGH PRIORITY product/architecture conflict**. Do not change the migration until PM/academy answers. See [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

The earlier unique `(customer_id, discipline)` was replaced by `2026_07_08_170000`.

### lessons + pivots

`lessons`: `discipline`, `name`, `description`, `duration_minutes`, `sort_order`.  
`lesson_teacher`, `lesson_course`.  
`course_group_lesson`: `teacher_id`, `hours`; unique `(course_group_id, lesson_id, teacher_id)`.

### Weekly schedule (2026-07-20)

`academy_buildings`, `academy_rooms` (`capacity`, `room_type`). **No lat/lng/radius columns today.** Geofence fields are a **conceptual** requirement for Teacher Check-in, not implemented.  
`schedule_weeks`: Monday `week_start_date`, Friday `week_end_date`, `work_starts_at` / `work_ends_at`, `draft`/`published`/`locked`.  
`scheduled_lessons`: week, building, room, group, teacher, lesson, date/time, color, status.  
`schedule_ai_runs`: prompt, preferences JSON, metrics, report, warnings.

UI: visual 30 min, planning 5 min. See [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).  
`locked` exists in schema; UI does not set it.

---

## D. Planned / conceptual (no final schema, no migrations)

Do not treat this list as an approved database.

| Topic | Notes | Status |
|-------|--------|--------|
| Student / Parent tables | Today: `customers` + embedded parents | OPEN |
| Enrollment workflow | Statuses, history | OPEN |
| **Student Attendance** | Presence on a **session** (lesson/rehearsal/…). Not teacher GPS | OPEN |
| **Teacher Check-in** | One-shot GPS + geofence (**PRELIMINARY** mechanism). Bind to day vs session **OPEN**. Possible fields: lat, lng, accuracy, timestamp, status `on_time`/`late`/`manual`/`rejected` | PRELIMINARY / OPEN |
| Academic Progress | Grades, periods, report cards, history — grading system **OPEN** | OPEN |
| Unified calendar | Variant A `ScheduledSession` vs Variant B separate entities. Tech Lead not decided. **Do not change `scheduled_lessons` now** | OPEN |
| Production / RehearsalGroup / Rehearsal / Performance / Cast | RehearsalGroup **≠** CourseGroup (**PRELIMINARY**). Rehearsals on child calendar + conflicts (**PRELIMINARY**). ProductionStaff unapproved | PRELIMINARY / OPEN |
| Document entity + **private** storage | Today: public disk paths | OPEN (required before wide mobile) |
| ConsentRecord / PrivacyPolicyVersion | Not started | OPEN |
| Teacher.user_id + User actor profiles | Identity model OPEN | OPEN |
| Payment / Invoice | Not started | OPEN |

Do not reuse kit `orders` as enrollments or invoices.

---

## Kit vs AUB names

| Kit | AUB now | Action |
|-----|---------|--------|
| customers | Students (interim) | Keep until a migration task |
| services | courses | Separate table exists |
| staff | teachers + roles | Do not confuse |
| orders | enrollments / invoices | New domain, not kit |
| calendar page | `/schedule-service` | Implemented |
