# AUB — Data model draft

Status legend:

- **Implemented** — in code and migrations, used
- **Generic kit** — from `custom-admin-kit` v0.4.0
- **Planned** — not built
- **Unverified leftover** — mentioned historically; Spatie-style tables were dropped in `2026_07_06_120000`; MySQL leftovers were **not** re-checked on 2026-09-07

Migrations: **39 files**, including Identity Layer `2026_09_08_220000_add_account_identity_layer`.

Privacy tags: Normal / Personal data / Children’s data / Special category / Secret.

---

## A. Implemented / kit tables

### users (implemented)

Auth. Extra columns: `account_type` (`staff` | `student` | `parent` | `teacher`), `is_active` (default true), `role_id` → `roles`, `can_write`, `can_delete`. Email unique.
Relations: `belongsTo Role`, `hasOne Student` (`studentProfile`), `hasOne AcademyParent` (`parentProfile`), `hasOne Teacher` (`teacherProfile`).
`account_type` ≠ web RBAC. Privacy: Personal data.

### customers (generic kit — **legacy, not academy Student**)

Kit columns remain. Academy student profiles live in `students`. Production test `customers` rows were cleared by `2026_09_08_200000`. Table kept for kit compatibility.

Relations in code: `hasMany Order` (legacy).

Student files: public disk `students/{id}/documents` — **privacy gap**.

Privacy: Children’s data / Personal data.

### services, staff, orders, order_staff (generic kit)

Unused in UI/routes. `staff.role` is free text, **not** RBAC.

### ai_provider_settings (generic kit, used)

`provider`, encrypted `api_key`, connection flags, models. Privacy: Secret.

### Laravel system

`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations`.

### activity_logs (implemented)

`user_id`, `customer_id` (legacy), `student_id`, `action`, `subject_*`, `properties`, `route_name`, `ip_address`, `user_agent`, `created_at` (no `updated_at`). CRUD + login/logout. **Not** a read/access audit.

---

## B. AUB access tables (implemented)

### roles

`name`, `slug`, `description`, `is_admin`, `is_system`, `is_active`.  
hasMany `RoleMenuItem`, hasMany `User`.

### role_menu_items

`role_id`, `menu_key`, `label`, `route_name`, `url`, `icon`, `sort_order`, `is_active`.

---

## C. Implemented academy tables

### academic_years

`name`, `starts_at`, `ends_at`, `is_active`. Current year = `is_active` (no auto-switch). PHP: `AcademicYear::current()`. No AcademicYear admin UI.

### students

Student profile: `name`, `first_name`, `last_name`, `gender`, `tax_code`, `birth_date`, `birth_place`, residence fields, `email`, `phone`, course flags, `medical_certificate_expiry`, document/photo paths, `notes`, `status`. **No** father_*/mother_* columns. Nullable unique `user_id` → `users.id` (`nullOnDelete`). `belongsTo User`.

`belongsToMany AcademyParent` (`student_parent`), `belongsToMany AcademyClass` (`academy_class_student`).

### parents (`AcademyParent`)

`first_name`, `last_name`, `email`, `phone`, `tax_code`, `notes`, nullable unique `user_id` → `users.id` (`nullOnDelete`). Table `parents`. PHP class is not `Parent` (reserved). `belongsTo User`.

### student_parent

`student_id`, `parent_id`, `relation_type` (nullable string: `father` / `mother` / `guardian` / `other`). Unique `(student_id, parent_id)`.

### teachers

`type`, `first_name`, `last_name`, `name`, `email`, `phone`, `tax_code`, `description`, `photo_path`, nullable unique `user_id`.  
`belongsToMany Lesson` (`lesson_teacher`), `belongsToMany ClassLesson` (`class_lesson_teacher`). `belongsTo User`. Identity Layer implemented (create/link from teacher profile).  
Photos: public disk `teachers/{id}/photos`.

### courses / academy_classes / academy_class_student

`courses`: `discipline`, `name`, `sort_order`, `study_starts_at`, `study_ends_at`. Learning direction.

`academy_classes`: `course_id`, `name`, `color`, `sort_order`. Product **Class**. PHP model `AcademyClass` (`Class` is reserved).

`academy_class_student`: unique `student_id` — at most one active primary Class.

Tables `course_groups`, `course_group_customer`, `course_group_lesson` were **dropped**.

### lessons + ClassLesson

`lessons`: `discipline`, `name`, `description`, `duration_minutes`, `sort_order`.  
`lesson_teacher`, `lesson_course`.  
`class_lessons`: `academic_year_id`, `academy_class_id`, `lesson_id`, `hours`, `sort_order`; unique `(academic_year_id, academy_class_id, lesson_id)`.  
`class_lesson_teacher`: several teachers; `hours` on the pivot.

### Weekly schedule (2026-07-20, FK 2026-09-08)

`academy_buildings`, `academy_rooms` (`capacity`, `room_type`). **No lat/lng/radius columns today.**  
`schedule_weeks`: Monday `week_start_date`, Friday `week_end_date`, `work_starts_at` / `work_ends_at`, `draft`/`published`/`locked`.  
`scheduled_lessons`: week, building, room, **`academy_class_id`**, teacher, lesson, date/time, color, status.  
`schedule_ai_runs`: prompt, preferences JSON, metrics, report, warnings.

UI: visual 30 min, planning 5 min. See [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).  
`locked` exists in schema; UI does not set it.

---

## D. Planned / conceptual (no final schema, no migrations)

Do not treat this list as an approved database for modules that are not built yet. Core Data Model refactor and Identity Layer are **done**. Next stage before a stable mobile API is **API Foundation**.

### Student / Parent (**implemented**)

`students`, `parents`, `student_parent` are implemented. `customers` is an unused kit leftover. PHP: `Student`, `AcademyParent`.

### `Class` vs additional groups (DECIDED)

- `Class` is the permanent primary academic class; at most one active per child (**unique `academy_class_student.student_id`**).
- PHP model: `AcademyClass` / table `academy_classes`.
- Additional groups (events, productions, rehearsals) are **not** a `Class`. A child may belong to one `Class` and several activity groups (additional-group schema is not built now).

### Identity (implemented 2026-09-08)

```
User.account_type: staff | student | parent | teacher
one User = one actor type
Student.user_id / Parent.user_id / Teacher.user_id
account_type != web RBAC
```

Linking only through `AccountIdentityService`. One physical person with two functions = two Users (email unique). No invitation/activation. No API.

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
| `students` / `parents` / `student_parent` | Implemented 2026-09-08 | DECIDED / implemented |
| Enrollment workflow around `Class` | Statuses, history, transfers | OPEN |
| **Student Attendance** | Presence on a **session**. Not teacher GPS | OPEN |
| **Teacher Check-in** | Daily geofence check-in. Radius/accuracy details OPEN | DECIDED semantics / not implemented |
| Final Assessment | `StudentFinalResult` / `ReportCard`. Scale OPEN | DECIDED core / details OPEN |
| Unified calendar | Variant A vs B. Tech Lead not decided. **Do not change `scheduled_lessons` now** | OPEN |
| Additional groups / Productions | ≠ `Class`; late future; do not finalize schema | DECIDED split / workflow OPEN |
| Document entity + **private** storage | Today: public disk paths | OPEN (required before wide mobile) |
| ConsentType / ConsentDocumentVersion / ConsentRecord | Do not hardcode age | DECIDED direction / not implemented |
| Teacher.user_id + mobile Teacher account | Link and web create/link exist; no mobile API | Identity implemented / API OPEN |
| Payment / Invoice | Not started | OPEN |

Do not reuse kit `orders` as enrollments or invoices.

---

## Kit vs AUB names

| Kit | AUB now | Target direction |
|-----|---------|------------------|
| customers | Legacy kit leftover | Academy uses `students` / `parents` |
| course_groups | Dropped | `academy_classes` / `AcademyClass` = product `Class` |
| services | courses | Separate table exists |
| staff | teachers + roles | Do not confuse |
| orders | enrollments / invoices | New domain, not kit |
| calendar page | `/schedule-service` | Implemented |
