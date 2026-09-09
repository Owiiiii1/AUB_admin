# AUB — Teacher Attendance

Attendance covers two mobile flows on the same `attendance_records` table:

1. **Teacher marking** — open an official lesson, see the class roster, mark each student, save.
2. **Student / Parent history** — read-only month history of existing marks.

There is **no** second history entity. Identity remains **Student + ScheduledLesson**.

Details also live in [API.md](API.md).

## Identity

Attendance belongs to **Student + ScheduledLesson**, not Student+date, Student+Class, or Student+ClassLesson.

`ScheduledLesson` is the actual instance on a calendar date/time.

Table: `attendance_records`. PHP: `AttendanceRecord`.

## Schema

| Column | Notes |
|--------|--------|
| `id` | PK |
| `scheduled_lesson_id` | FK `scheduled_lessons`, `cascadeOnDelete` |
| `student_id` | FK `students`, `cascadeOnDelete` |
| `status` | `present` / `absent` / `excused` |
| `marked_by` | FK `users`, **nullable**, `nullOnDelete` |
| `marked_at` | datetime |
| `created_at` / `updated_at` | timestamps |

**UNIQUE (`scheduled_lesson_id`, `student_id`)**. One current mark per student per lesson.

`marked_by` is nullable so an audit row can survive if a staff user is deleted (`nullOnDelete`). Teacher mobile save always sets `marked_by` to the current User.

## Statuses (MVP)

```text
present | absent | excused
```

Not in this version: `late`, `left_early`, `sick`, `remote`, custom.

There is **no** stored `unmarked` status. If the row is missing, the student is unmarked (`attendance: null` on the teacher API). The backend does **not** pre-create one row per student per lesson.

**`no AttendanceRecord` ≠ `absent`.** An unmarked lesson is not a visit, is not an absence, and must not enter Student/Parent history or summary counts.

`status = null` on PUT **deletes** the row.

## Roster source

Roster for a `ScheduledLesson` is the **current** class membership:

```text
ScheduledLesson.academy_class_id → AcademyClass → academy_class_student → students
```

Sort: `last_name`, `first_name`, `id`.

### Technical debt

If a later product needs “who was in the class on the lesson date”, that requires enrollment history or a roster snapshot. **Not built now.**

## Ownership

The teacher may load/save attendance only when:

```text
scheduled_lesson.teacher_id == current teacher profile id
```

Another teacher’s lesson → **404** `not_found` (GET and PUT). Student / parent → **403**. Staff cannot hold a mobile session → **401**.

## Publication

Same official visibility as Teacher Schedule, plus cancelled GET:

| Week | Lesson | GET | PUT |
|------|--------|-----|-----|
| draft | any | 404 | 404 |
| published / locked | draft / scheduled | 404 | 404 |
| published / locked | published / moved | roster, `attendance_editable=true` | allowed |
| published / locked | cancelled | roster, `attendance_editable=false`, `reason=cancelled` | **409** `attendance_not_editable` |

Draft/scheduled admin work is not visible on mobile.

## Time editing

MVP does **not** restrict marking to the lesson clock window. A teacher may mark or correct after the lesson ends.

The teacher still cannot edit unofficial or other teachers’ lessons.

**OPEN:** attendance finalization / edit window (lock after N days). See [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Endpoints

Teacher only (`auth:sanctum` + `mobile.actor` + `account_type=teacher`):

```text
GET /api/v1/teacher/lessons/{scheduledLesson}/attendance
PUT /api/v1/teacher/lessons/{scheduledLesson}/attendance
```

PUT is **bulk partial upsert**: each supplied row is applied; omitted roster students are unchanged. Flutter may send the full roster or only changed rows.

If any `student_id` is not in the class roster, the whole request is rejected (**422**, atomic, no partial write).

PUT success returns the same payload as GET (fresh roster + marks).

Audit: `marked_by` / `marked_at` update on a real status change (`Europe/Rome` `now()`). Activity log `attendance.updated` stores `scheduled_lesson_id` + `changed_count` only (no child list).

## Student / Parent history

Read-only. Service: `AttendanceHistoryService` (not mixed into `TeacherAttendanceService`).

```text
GET /api/v1/attendance
GET /api/v1/children/{student}/attendance
```

| Actor | `/attendance` | `/children/{student}/attendance` |
|-------|---------------|----------------------------------|
| Student | own history via `studentProfile` (no client `student_id`) | 403 |
| Parent | 403 | own children via `student_parent`; stranger → **404** |
| Teacher | 403 | 403 |
| Staff mobile | 401 | 401 |

Query: `?month=YYYY-MM`. If omitted, current month in `Europe/Rome`. Backend computes `starts_on` / `ends_on`. Invalid month → **422** `validation_error`.

A row is included only when:

- an `AttendanceRecord` exists;
- `schedule_weeks.status` is `published` or `locked`;
- `scheduled_lessons.status` is `published` or `moved`.

Cancelled / draft / scheduled lessons are excluded even if a leftover record exists. Another month is excluded. Unmarked lessons are excluded.

Sort: `lesson_date DESC`, `starts_at DESC`, `attendance_records.id DESC`.

Summary (absolute counts only, no percentages):

```text
marked = present + absent + excused
```

Whitelist: student `{id, display_name}`; record `{id, date, starts_at, ends_at, status, lesson{id,name}, title, teacher{id,display_name}, location.building/room {id,name}}`. No `marked_by`, `marked_at`, contacts, tax_code, medical, notes, documents, parent data, AI metadata, RBAC.

## Out of this stage

Percentages, absence notifications, justification upload, medical reason, late, comments, admin dashboard, teacher check-in/geolocation, lesson completion, roster snapshots, finalization lock.
