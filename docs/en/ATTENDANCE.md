# AUB — Teacher Attendance

Teacher Attendance MVP: a teacher opens an official lesson from Teacher Schedule, sees the class roster, marks each student, saves, and sees the same marks on reopen.

Student / Parent attendance history is **not** in this stage.

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

There is **no** stored `unmarked` status. If the row is missing, the student is unmarked (`attendance: null` in the API). The backend does **not** pre-create one row per student per lesson.

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

## Out of this stage

Student/parent history, percentages, absence notifications, justification upload, medical reason, late, comments, admin dashboard, teacher check-in/geolocation, lesson completion, roster snapshots, finalization lock.
