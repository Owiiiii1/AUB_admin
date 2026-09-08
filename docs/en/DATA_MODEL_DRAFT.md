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

### customers (implemented — **students, interim / not the long-term model**)

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

**Enrollment constraint in code:** unique `customer_id` — at most one CourseGroup per student in the database. **DECIDED:** this conceptually matches the “one primary `Class`” rule. Target terminology: `Class` = permanent primary academic class; do not mix with additional activity groups. Table names (`course_groups` vs `Class`) still need normalization. Do not change migrations in this task. See [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

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

Do not treat this list as an approved database. Do **not** create migrations in a docs-only task. Core Data Model refactor comes **before** a stable mobile API.

### Student / Parent (DECIDED direction)

`customers` is interim / legacy, **not** the long-term Student domain model. Production has no valuable user data (test only). Target entities: `students`, `parents`, `student_parent`. Do not start migrations now.

### `Class` vs additional groups (DECIDED)

- `Class` is the permanent primary academic class; at most one active per child.
- Additional groups (events, productions, rehearsals) are **not** a `Class`. A child may belong to one `Class` and several activity groups.
- Preliminary future types (not a schema): `ProductionGroup`, `RehearsalGroup`, possibly others. Do not finalize a schema now.

### Identity (DECIDED)

One User has exactly one primary actor type: `student` | `parent` | `teacher`. Do not design multi-profile identity. Staff web RBAC stays separate. `teachers.user_id` does not exist in code.

### Teacher Check-in (semantics DECIDED)

Daily presence, not per lesson. Conceptual fields: teacher; date; checked_in_at; latitude; longitude; accuracy; academy_location; status; manual correction metadata; audit. Geofence radius / accuracy / window / anti-spoofing remain OPEN. QR = optional fallback. `academy_buildings` / `academy_rooms` currently have **no** lat/lng/radius.

### Final Assessment (core DECIDED, details OPEN)

Concept:

```
AcademicYear → Class → ClassLesson → TeacherAssignment
Student + Class + Lesson + AcademicYear → StudentFinalResult
Student + Class + AcademicYear → ReportCard
```

`StudentFinalResult` (example fields): student_id; class_id; lesson_id; academic_year_id; result; teacher_comment; finalized_at; updated_at; audit metadata.

`ReportCard` (preliminary): student_id; class_id; academic_year_id; status (`draft` / `finalized` / `printed`); finalized_at; generated_at; generated_by.

No running grades / per-lesson gradebook. An Administrator assembles the PDF from domain entities; the PDF is not a data source. Scale, PDF template, and who closes the card remain OPEN.

### Consent (direction; do not fix an age threshold)

Target entities: `ConsentType`, `ConsentDocumentVersion`, `ConsentRecord`. Possible types: privacy; data processing; photo/video; marketing; special activity. For a minor, link to parent/guardian where policy/law requires it. Do not hardcode an age threshold before legal review.

| Topic | Notes | Status |
|-------|--------|--------|
| `students` / `parents` / `student_parent` tables | Direction DECIDED; today `customers` | DECIDED direction / not implemented |
| Enrollment workflow around `Class` | Statuses, history, transfers | OPEN |
| **Student Attendance** | Presence on a **session**. Not teacher GPS | OPEN |
| **Teacher Check-in** | Daily geofence check-in. Radius/accuracy details OPEN | DECIDED semantics / not implemented |
| Final Assessment | `StudentFinalResult` / `ReportCard`. Scale OPEN | DECIDED core / details OPEN |
| Unified calendar | Variant A vs B. Tech Lead not decided. **Do not change `scheduled_lessons` now** | OPEN |
| Additional groups / Productions | ≠ `Class`; late future; do not finalize schema | DECIDED split / workflow OPEN |
| Document entity + **private** storage | Today: public disk paths | OPEN (required before wide mobile) |
| ConsentType / ConsentDocumentVersion / ConsentRecord | Do not hardcode age | DECIDED direction / not implemented |
| Teacher.user_id + mobile Teacher account | One actor type per User | DECIDED identity / not implemented |
| Payment / Invoice | Not started | OPEN |

Do not reuse kit `orders` as enrollments or invoices.

---

## Kit vs AUB names

| Kit | AUB now | Target direction |
|-----|---------|------------------|
| customers | Students (interim) | `students` / `parents` / `student_parent` — separate refactor task, not now |
| course_groups | Study groups in code | Product `Class` (terminology normalization OPEN) |
| services | courses | Separate table exists |
| staff | teachers + roles | Do not confuse |
| orders | enrollments / invoices | New domain, not kit |
| calendar page | `/schedule-service` | Implemented |
