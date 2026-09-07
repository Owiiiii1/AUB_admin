# AUB — Data Model Draft

This document separates **implemented/generic tables**, **planned access-control tables**, and **planned academy tables**.

Status legend:

- **Implemented** — exists in code and migrations, in use
- **Generic kit** — from `custom-admin-kit` v0.4.0, generic CRM purpose
- **Planned** — not implemented yet
- **Legacy (DB only)** — table exists in database from previous install, no app code

Privacy sensitivity:

- **Normal** — non-personal operational data
- **Personal data** — names, emails, phones, addresses (GDPR applies)
- **Children's data** — data about minors (enhanced protection)
- **Special category** — health, medical, sensitive notes (if stored)

---

## A. Currently implemented / generic kit tables

### users

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | Personal data |
| email | string unique | Personal data |
| email_verified_at | timestamp nullable | |
| password | string | Hidden |
| remember_token | string nullable | |
| role_id | FK nullable → roles | **Implemented** |
| can_write | boolean default false | **Implemented** — gates mutating routes |
| can_delete | boolean default false | **Implemented** — gates delete routes |
| created_at, updated_at | timestamps | |

**Status:** Implemented  
**Privacy:** Personal data  
**Relationships:** belongsTo Role

---

### customers (extended as students — interim)

**Status:** Implemented (AUB extension on kit table, 2026-07-06+)  
**Privacy:** Children's data / Personal data

Core kit fields retained: `name`, `email`, `phone`, `address`, `notes`, `status`.

Additional student profile fields (migration `2026_07_06_210000`, `2026_07_06_221000`):

| Field group | Fields |
|-------------|--------|
| Personal | `first_name`, `last_name`, `gender`, `tax_code`, `birth_date`, `birth_place` |
| Residence | `residence_address`, `residence_city_province`, `residence_postal_code`, `student_email`, `student_phone` |
| Legacy parent | `parent_phone`, `parent_email` |
| Course | `course_aa_2026_27`, `other_courses`, `is_existing_student`, `form_filled_at` |
| Documents | `medical_certificate_expiry`, `student_photo_path`, `parent_id_document_path`, `general_regulation_form_path`, `minor_entry_exit_form_path`, `rights_release_form_path` |
| Father | `father_first_name`, `father_last_name`, `father_phone`, `father_email`, `father_notes` |
| Mother | `mother_first_name`, `mother_last_name`, `mother_phone`, `mother_email`, `mother_notes` |
| Notes | `student_notes` |

**Relationships:** hasMany orders (kit legacy); belongsToMany CourseGroup via `course_group_customer`

**Note:** Dedicated `students` table in section C remains the long-term target; current implementation reuses `customers`.

---

### services (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | |
| description | text nullable | |
| price | decimal(10,2) nullable | |
| duration_minutes | unsigned int nullable | |
| is_active | boolean default true | |
| timestamps | | |

**Status:** Generic kit — **not** academy courses  
**Privacy:** Normal

---

### staff (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string nullable | |
| phone | string nullable | |
| role | string nullable | Free-text, **not RBAC** |
| is_active | boolean default true | |
| notes | text nullable | |
| timestamps | | |

**Status:** Generic kit — staff **directory**, not access roles  
**Privacy:** Personal data  
**Note:** Do not confuse `staff.role` (text) with planned `roles` table (RBAC)

---

### orders (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| customer_id | FK nullable → customers | |
| service_id | FK nullable → services | |
| title | string | |
| description | text nullable | |
| status | string default `new` | |
| scheduled_at | timestamp nullable | |
| completed_at | timestamp nullable | |
| total | decimal(10,2) nullable | |
| notes | text nullable | |
| timestamps | | |

**Status:** Generic kit — **not** enrollments or invoices  
**Privacy:** Normal / may contain personal data in notes

---

### order_staff (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| order_id | FK → orders | |
| staff_id | FK → staff | |
| timestamps | | |
| unique(order_id, staff_id) | | |

**Status:** Generic kit  
**Privacy:** Normal

---

### ai_provider_settings (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| provider | string unique | |
| label | string nullable | |
| api_key | text nullable | **Secret — never expose** |
| is_connected | boolean | |
| is_active | boolean | |
| active_model | string nullable | |
| available_models | json nullable | |
| last_checked_at | timestamp nullable | |
| last_error | text nullable | |
| timestamps | | |

**Status:** Generic kit  
**Privacy:** Normal (contains API secrets)

---

### Laravel system tables

| Table | Status |
|-------|--------|
| cache, cache_locks | Implemented |
| jobs, job_batches, failed_jobs | Implemented |
| sessions | Implemented |
| password_reset_tokens | Implemented |
| migrations | Implemented |

---

### Legacy tables (DB only, no app code)

| Table | Status | Notes |
|-------|--------|-------|
| permissions | Legacy (DB only) | Empty; Spatie-style, not used |
| model_has_roles | Legacy (DB only) | Empty |
| model_has_permissions | Legacy (DB only) | Empty |
| role_has_permissions | Legacy (DB only) | Empty |
| media | Legacy (DB only) | Empty |
| settings | Legacy (DB only) | Empty |
| personal_access_tokens | Legacy (DB only) | Empty |

**Note:** AUB `roles` and `role_menu_items` tables **are implemented** (Phase 1). Legacy Spatie `roles` was replaced.

### activity_logs (implemented)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| user_id | FK nullable | Actor |
| customer_id | FK nullable | Related student |
| action | string | create, update, delete |
| subject_type, subject_id | | Polymorphic subject |
| subject_label | string nullable | |
| properties | json nullable | Changed fields |
| route_name, ip_address, user_agent | | Audit metadata |
| created_at | timestamp | |

**Status:** Implemented — used by `ActivityLogController`, `/statistics/logs`

---

## B. Planned AUB access-control tables

### Role

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | Display name, e.g. "Administrator" |
| slug | string unique | e.g. `administrator`, `secretariat` |
| description | text nullable | |
| is_admin | boolean default false | Full admin panel access |
| is_system | boolean default false | Cannot delete system roles |
| is_active | boolean default true | |
| created_at, updated_at | timestamps | |

**Status:** Implemented (Phase 1, 2026-07-06)  
**Privacy:** Normal  
**Relationships:** hasMany RoleMenuItem, hasMany User

**Business rules:**

- Default `Administrator` role with `is_admin=true`, `is_system=true`
- Cannot delete/disable last active administrator role
- Cannot remove admin access from only active administrator

---

### RoleMenuItem

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| role_id | FK → roles | |
| menu_key | string | Internal key, e.g. `customers`, `dashboard` |
| label | string nullable | Display override |
| route_name | string nullable | Laravel route name |
| url | string nullable | Fallback URL |
| icon | string nullable | Icon identifier |
| sort_order | unsigned int default 0 | |
| is_active | boolean default true | |
| created_at, updated_at | timestamps | |

**Status:** Implemented  
**Privacy:** Normal  
**Relationships:** belongsTo Role

**Purpose:** Define which menu items each role can see. Administrator role gets all items (or `is_admin` bypasses filtering).

---

### users.role_id (implemented)

| Field | Type | Notes |
|-------|------|-------|
| role_id | FK nullable → roles | Assigned on user create/edit |
| can_write | boolean | Write permission flag |
| can_delete | boolean | Delete permission flag |

**Status:** Implemented  
**Privacy:** Normal

---

## C. Implemented AUB academy tables (2026-07-08+)

### teachers

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| type | string | e.g. permanent |
| first_name, last_name, name | string | |
| email, phone, tax_code | string nullable | Personal data |
| description | text nullable | |
| photo_path | string nullable | |
| timestamps | | |

**Status:** Implemented  
**Relationships:** belongsToMany Lesson (`lesson_teacher`)

---

### courses / course_groups / course_group_customer

| Table | Key fields |
|-------|------------|
| courses | discipline, name, sort_order |
| course_groups | course_id, name, sort_order |
| course_group_customer | course_group_id, customer_id (unique pair) |

**Status:** Implemented  
**Rules:** One active group per student; one group per discipline (migrations enforce)

---

### lessons + pivots

| Table | Purpose |
|-------|---------|
| lessons | discipline, name, description, sort_order |
| lesson_teacher | lesson ↔ teacher |
| lesson_course | lesson ↔ course |
| course_group_lesson | group ↔ lesson + teacher_id, hours |

**Status:** Implemented

---

## D. Planned AUB academy tables (not yet separate entities)

### Student

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| first_name, last_name | string | |
| date_of_birth | date nullable | |
| gender | string nullable | |
| email, phone | string nullable | If applicable |
| address | text nullable | |
| status | enum/string | active, inactive, graduated |
| enrollment_date | date nullable | |
| notes | text nullable | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Children's data / Personal data

---

### Parent / Guardian

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| first_name, last_name | string | |
| email, phone | string | |
| address | text nullable | |
| relationship | string nullable | mother, father, guardian |
| user_id | FK nullable → users | Future mobile access |
| timestamps | | |

**Status:** Planned  
**Privacy:** Personal data

---

### student_parent (pivot, planned)

| Field | Type |
|-------|------|
| student_id | FK → students |
| parent_id | FK → parents |
| is_primary | boolean |
| timestamps | |

---

### Teacher

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| first_name, last_name | string | |
| email, phone | string nullable | |
| specializations | json/text nullable | |
| employment_status | string | |
| user_id | FK nullable → users | Link to login account |
| staff_id | FK nullable → staff | Optional link to kit staff record |
| timestamps | | |

**Status:** Planned  
**Privacy:** Personal data

---

### Course

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | |
| description | text nullable | |
| age_min, age_max | int nullable | |
| is_active | boolean | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Normal

---

### Group / Class

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| course_id | FK → courses | |
| name | string | |
| max_students | int nullable | |
| room | string nullable | |
| teacher_id | FK nullable → teachers | |
| schedule_template | json nullable | |
| is_active | boolean | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Normal

---

### Enrollment

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| group_id | FK → groups | |
| start_date | date | |
| end_date | date nullable | |
| status | string | active, completed, withdrawn |
| timestamps | | |

**Status:** Planned  
**Privacy:** Children's data

---

### Lesson

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| group_id | FK → groups | |
| teacher_id | FK nullable → teachers | |
| date | date | |
| start_time, end_time | time | |
| room | string nullable | |
| status | string | scheduled, completed, cancelled |
| notes | text nullable | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Normal

---

### AttendanceRecord

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| lesson_id | FK → lessons | |
| student_id | FK → students | |
| status | string | present, absent, late, excused |
| notes | text nullable | |
| marked_by | FK → users | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Children's data

---

### Payment / Invoice

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| amount | decimal(10,2) | |
| due_date | date | |
| paid_date | date nullable | |
| status | string | pending, paid, overdue, cancelled |
| payment_method | string nullable | |
| description | text nullable | |
| invoice_number | string nullable | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Personal data / financial

---

### Document

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| type | string | contract, medical, consent, other |
| file_path | string | |
| uploaded_by | FK → users | |
| description | text nullable | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Personal data / Children's data / Special category (medical)

---

### Note / ActivityLog

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| notable_type, notable_id | morph | student, parent, etc. |
| author_id | FK → users | |
| content | text | |
| visibility | string | internal, restricted |
| timestamps | | |

**Status:** Planned  
**Privacy:** Personal data / may be special category

---

### ConsentRecord

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| parent_id | FK nullable → parents | |
| consent_type | string | photo, data processing, etc. |
| granted | boolean | |
| granted_at | timestamp nullable | |
| privacy_policy_version_id | FK | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Children's data

---

### PrivacyPolicyVersion

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| version | string | |
| content | text | |
| effective_from | date | |
| timestamps | | |

**Status:** Planned  
**Privacy:** Normal (legal document metadata)

---

## Entity relationship overview (planned)

```
Role ──< RoleMenuItem
  │
  └──< User (role_id)
         │
Student ──< Enrollment >── Group ──< Course
   │                          │
   └── student_parent ── Parent    └── Teacher
   │
   ├──< AttendanceRecord >── Lesson
   ├──< Document
   ├──< Payment
   └──< ConsentRecord
```

## Generic kit vs AUB naming

| Generic kit table | AUB equivalent | Status |
|-------------------|----------------|--------|
| customers | students (interim on `customers`) | Partial — profile UI done |
| services | courses | Implemented (`courses` table) |
| staff (directory) | teachers + staff roles | Teachers done; RBAC done |
| orders | enrollments + invoices | Enrollments partial (`course_group_customer`) |
| calendar (page) | weekly schedule service | Implemented at `/schedule-service` |

### Weekly schedule (implemented 2026-07-20)

| Table / field | Purpose |
|---------------|---------|
| `academy_buildings` | Locations (Accademia, Carcano, Danzadanza, Arcimboldi) |
| `academy_rooms` | Halls/rooms per building |
| `schedule_weeks` | Monday `week_start_date`; week end Friday; `work_starts_at` / `work_ends_at`; draft/published/locked |
| `scheduled_lessons` | Blocks: week, building, room, group, teacher, subject, date/time |
| `schedule_ai_runs` | AI run log (prompt, preferences, metrics, report) |
| `courses.study_starts_at` / `study_ends_at` | Course study window (shift) |
| `course_group_lesson` | Multiple teachers per group+lesson (`hours` per teacher) |

Grid: visual 30 min, planning/AI 5 min. See [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).

Do not rename or repurpose generic kit tables until academy modules are designed and approved.
