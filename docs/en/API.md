# AUB — Mobile API

Base URL (production): `https://aub.owlsolutions.net/api/v1`

Versioning is in the path (`v1`). Do not mix these routes with web/Inertia session routes.

Flutter (`AUB_app`) must use **HTTPS + Bearer tokens only**. Do not put DB credentials or `APP_KEY` in the app.

## Auth scheme

Laravel Sanctum **v4.3.3** personal access tokens.

```http
Authorization: Bearer <token>
```

Tokens are stored hashed. The plaintext token is returned **only** on login. Ability on issued tokens: `mobile`. Actor type is **never** taken from the token; it is read from `users.account_type` on every request.

Default lifetime: **30 days** (`43200` minutes), configurable via `AUB_API_TOKEN_EXPIRATION_MINUTES` / `config/aub.php`. No refresh-token flow in this version; the client must log in again after expiry. Multiple devices are allowed.

## Who can use the mobile API

Allowed account types: `student`, `parent`, `teacher`.

Each user must be `is_active` and have the matching linked profile (`students.user_id` / `parents.user_id` / `teachers.user_id`).

`staff` uses web session CRM. Staff login on this API is rejected with the same public error as a bad password.

## Endpoints

| Method | Path | Auth |
|--------|------|------|
| GET | `/health` | No |
| POST | `/auth/login` | No (rate limited) |
| GET | `/me` | Bearer |
| POST | `/auth/logout` | Bearer |
| POST | `/auth/logout-all` | Bearer |
| GET | `/schedule` | Bearer, `student` only |
| GET | `/children/{student}/schedule` | Bearer, `parent` only |
| GET | `/teacher/schedule` | Bearer, `teacher` only |
| GET | `/attendance` | Bearer, `student` only |
| GET | `/children/{student}/attendance` | Bearer, `parent` only |
| GET | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, `teacher` only |
| PUT | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, `teacher` only |

### GET `/health`

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "api_version": "v1"
  }
}
```

Does not expose DB credentials, package versions, paths, or secrets.

### POST `/auth/login`

Request:

```json
{
  "email": "user@example.com",
  "password": "…",
  "device_name": "Owl iPhone"
}
```

`device_name` is required (max 120). It becomes the Sanctum token name.

Success `200`:

```json
{
  "success": true,
  "data": {
    "token": "<plaintext once>",
    "token_type": "Bearer",
    "expires_at": "2026-10-08T20:00:00+00:00"
  }
}
```

Auth failure `401` (unknown email, bad password, inactive, staff, missing profile — same body):

```json
{
  "success": false,
  "error": {
    "code": "invalid_credentials",
    "message": "Invalid credentials."
  }
}
```

Missing `device_name` / invalid email: `422` `validation_error`.

Login rate limit: **5 / minute** per normalized email + IP → `429` `too_many_requests`.

### GET `/me`

Actor-aware whitelist. Never includes password hashes, `remember_token`, web `role_id`, `can_write`, `can_delete`, tax codes, medical data, notes, documents, or AI settings.

**Student profile:** `id`, `first_name`, `last_name`, `display_name`, `photo_url` (public disk URL or `null`), `academy_class` `{id,name}` or `null`, `academic_year` `{id,name}` or `null`.

**Parent profile:** `id`, `first_name`, `last_name`, `display_name`, `children[]` with `id`, `first_name`, `last_name`, `display_name`, `academy_class`. Only children linked via `student_parent`.

**Teacher profile:** `id`, `first_name`, `last_name`, `display_name`.

### GET `/schedule`

Student only (`account_type = student`). The student is taken from `request.user.studentProfile`. There is no `student_id` query/body.

### GET `/children/{student}/schedule`

Parent only. The child must be linked via `student_parent`. Unrelated or unknown children return `404 not_found` (same body), so client code cannot probe whether a student id exists.

Teacher and staff cannot use either student/parent schedule endpoint (`403` for a valid teacher token; staff never gets a mobile session).

### GET `/teacher/schedule`

Teacher only (`account_type = teacher`). The teacher is taken from `request.user.teacherProfile`. There is no `teacher_id` query/body. Extra query keys such as `teacher_id` are ignored.

Student and parent tokens receive `403`. Staff cannot obtain a mobile session (`401`).

Same `?week=YYYY-MM-DD` contract, publication rules, Europe/Rome current week, and empty-state HTTP 200 as student/parent schedule.

Lessons are all official `ScheduledLesson` rows for that teacher (`teacher_id`), across classes. Sorted by `lesson_date`, `starts_at`, `ends_at`, `id`.

Each lesson includes `academy_class {id,name}`. The lesson object does **not** include the teacher (the caller is that teacher) and does **not** include a class roster, student names/IDs, parent data, emails, phones, tax codes, notes, AI metadata, or conflicts.

Empty states:

| Case | `week.published` | `days` | `empty_reason` |
|------|------------------|--------|----------------|
| No official week | `false` | 7 empty days | `unpublished` |
| Official week, no lessons for this teacher | `true` | 7 empty days | `no_lessons` |

Root whitelist: `teacher {id, display_name}`, `week`, `days`, `empty_reason`.

Query:

```text
?week=YYYY-MM-DD
```

Any day of the week is accepted. The backend converts it to Monday–Sunday using Laravel `now()` / `config('app.timezone')` (`APP_TIMEZONE`, default `Europe/Rome`). Flutter must not compute the week as source of truth.

If `week` is omitted, the current week in `Europe/Rome` is used. Invalid `week` → `422 validation_error`.

#### Publication rules (same as admin Publish)

Admin `WeeklyScheduleController::publish` sets `schedule_weeks.status = published` and rewrites every lesson on that week to `scheduled_lessons.status = published`. `locked` exists in schema as a frozen official week; the admin UI does not set it yet. Mobile treats **`published` and `locked`** as official.

Draft weeks are never returned.

Visible lesson statuses on an official week:

| Lesson status | Mobile |
|---------------|--------|
| `draft` | Hidden (admin work) |
| `scheduled` | Hidden (default placement; also used for lessons added after Publish without re-publishing) |
| `published` | Shown |
| `cancelled` | Shown with `status: cancelled` |
| `moved` | Shown with current date/time and `status: moved` (no old/new history in the model) |

#### Empty states (always HTTP 200)

| Case | `academy_class` | `week.published` | `days` | `empty_reason` |
|------|-----------------|------------------|--------|----------------|
| Student has no class | `null` | `false` | `[]` | `no_class` |
| No official week | class object | `false` | 7 empty days | `unpublished` |
| Official week, no visible lessons | class object | `true` | 7 empty days | `no_lessons` |

`week.starts_on` is Monday; `week.ends_on` is Sunday (calendar week for the app). Admin `week_end_date` remains Friday for the Mon–Fri board.

Whitelist: student `{id, display_name, academy_class}`, lesson `{id, starts_at, ends_at, title, lesson{id,name}, teacher{id,display_name}, location.building/room {id,name}, status}`. No notes, AI metadata, conflicts, emails, phones, tax codes, medical/document fields.

Lessons are grouped by day and sorted by `starts_at`.

### GET `/teacher/lessons/{scheduledLesson}/attendance`

Teacher only. See [ATTENDANCE.md](ATTENDANCE.md).

The lesson must belong to `request.user.teacherProfile` and be mobile-visible (`schedule_weeks.status` in `published`/`locked`, lesson `published`/`moved`/`cancelled`). Otherwise **404** `not_found` (including another teacher’s lesson).

Roster is the **current** `academy_class_student` membership of `scheduled_lesson.academy_class_id`, sorted by `last_name`, `first_name`, `id`. Missing `AttendanceRecord` → `attendance: null` (unmarked). No pre-created rows.

Student whitelist: `id`, `display_name`, `photo_url` (public disk URL or `null`). No email, phone, address, parents, medical, tax_code, notes, documents.

Cancelled lesson: HTTP 200, roster shown, `attendance_editable: false`, `reason: cancelled`.

### PUT `/teacher/lessons/{scheduledLesson}/attendance`

Same visibility/ownership as GET. Body:

```json
{
  "attendance": [
    { "student_id": 100, "status": "present" },
    { "student_id": 101, "status": "absent" },
    { "student_id": 102, "status": "excused" },
    { "student_id": 103, "status": null }
  ]
}
```

Statuses: `present`, `absent`, `excused`. `null` deletes the record. Semantics: **bulk partial upsert** — supplied rows change; omitted roster students are unchanged.

Every `student_id` must be in that lesson’s class roster; otherwise **422** `validation_error` and **no** writes (atomic). Duplicate `student_id` in the payload → 422.

Cancelled (or otherwise non-editable) lesson → **409**:

```json
{
  "success": false,
  "error": {
    "code": "attendance_not_editable",
    "message": "Attendance cannot be edited for this lesson."
  }
}
```

Success **200** returns the same `data` shape as GET. `marked_by` is the current User; `marked_at` is `now()` in `Europe/Rome` when the status actually changes.

### GET `/attendance`

Student only. See [ATTENDANCE.md](ATTENDANCE.md).

Student is taken from `request.user.studentProfile`. There is no client `student_id`. Teacher / parent → **403**. Staff mobile → **401**.

Query `month=YYYY-MM` (optional). Omitted → current month in `Europe/Rome`. Backend returns `period.month`, `period.starts_on`, `period.ends_on`. Invalid month → **422** `validation_error`.

Only existing `AttendanceRecord` rows are returned. **`no AttendanceRecord` ≠ `absent`** — unmarked lessons are not history and are not counted.

Included only when the lesson’s week is `published`/`locked` and the lesson is `published`/`moved`. Cancelled / draft / scheduled are excluded even if a leftover row exists.

Sort: `lesson_date DESC`, `starts_at DESC`, `attendance_record.id DESC`.

`summary.marked = present + absent + excused`. Absolute counts only.

Whitelist: student `{id, display_name}`; records `{id, date, starts_at, ends_at, status, lesson{id,name}, title, teacher{id,display_name}, location.building/room}`. No `marked_by`, `marked_at`, contacts, tax_code, medical, notes, documents, parent data, AI metadata.

### GET `/children/{student}/attendance`

Parent only. Same payload as student history. Ownership via `parentProfile.students`. Unrelated child → **404** `not_found` (not 403). Student / teacher → **403**.

### POST `/auth/logout`

Revokes **only** the current token.

### POST `/auth/logout-all`

Revokes **all** Sanctum tokens for that user.

## Error contract

```json
{
  "success": false,
  "error": {
    "code": "…",
    "message": "…",
    "fields": { "email": ["…"] }
  }
}
```

`fields` is present only for `validation_error`.

| HTTP | code |
|------|------|
| 401 | `unauthenticated` or `invalid_credentials` |
| 403 | `forbidden` |
| 404 | `not_found` |
| 409 | `attendance_not_editable` |
| 422 | `validation_error` |
| 429 | `too_many_requests` |
| 500 | `server_error` (no stack/SQL/paths on production) |

`/api/*` always JSON. Web HTML errors are unchanged.

## Runtime identity

Protected routes use `auth:sanctum` + `EnsureMobileActorIsValid`. On every request the user is reloaded from the database:

- inactive → tokens revoked, `401`
- `staff` or missing matching profile → current token revoked, `401`

Admin `is_active = false` also deletes all tokens via the User model. Unlinking a profile deletes that user's tokens.

Authenticated API rate limit: **120 / minute / user**.

## CORS

Native Flutter does not use browser CORS. Allowed origins are empty. Do not set `*` unless Flutter Web is a separate, approved task.

## Out of scope (not in this contract)

Registration, forgot/reset password, email verification, refresh tokens, push tokens, check-in, grades, documents, messages, payments, productions, timetable editing from mobile.
