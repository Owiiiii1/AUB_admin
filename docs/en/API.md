# AUB — Mobile API

Base URL (production): `https://staff.accademiaucraina.it/api/v1`

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
| GET | `/app/version-check` | No |
| POST | `/auth/login` | No (rate limited) |
| POST | `/auth/set-password` | No (rate limited) |
| GET | `/me` | Bearer |
| POST | `/me/photo` | Bearer |
| PUT | `/me/password` | Bearer |
| GET | `/me/devices` | Bearer |
| DELETE | `/me/devices/{id}` | Bearer |
| POST | `/auth/logout` | Bearer |
| POST | `/auth/logout-all` | Bearer |
| GET | `/schedule` | Bearer, `student` only |
| GET | `/children/{student}/schedule` | Bearer, `parent` only |
| GET | `/teacher/schedule` | Bearer, `teacher` only |
| GET | `/rooms` | Bearer, `student` / `teacher` |
| GET | `/rooms/{id}/occupancy` | Bearer, `student` / `teacher` |
| GET | `/rooms/{id}/day` | Bearer, `student` / `teacher` |
| GET | `/attendance` | Bearer, `student` only |
| GET | `/children/{student}/attendance` | Bearer, `parent` only |
| POST | `/teacher/lessons/{scheduledLesson}/check-in` | Bearer, `teacher` only |
| GET | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, `teacher` only |
| PUT | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, `teacher` only |
| GET | `/files/{uuid}` | Bearer |
| GET | `/chat` | Bearer |
| GET | `/chat/unread` | Bearer |
| POST | `/chat/read` | Bearer |
| POST | `/chat/messages` | Bearer |
| PATCH | `/chat/messages/{id}` | Bearer |
| DELETE | `/chat/messages/{id}` | Bearer |
| POST | `/chat/messages/{id}/comments` | Bearer |
| GET | `/chats` | Bearer |
| GET | `/chats/{id}` | Bearer |
| POST | `/chats/{id}/read` | Bearer |
| POST | `/chats/{id}/messages` | Bearer |
| PATCH | `/chats/{id}/messages/{id}` | Bearer |
| DELETE | `/chats/{id}/messages/{id}` | Bearer |
| POST | `/chats/{id}/messages/{id}/comments` | Bearer |
| GET | `/notifications` | Bearer |
| GET | `/notifications/unread` | Bearer |
| GET | `/notifications/pending-ack` | Bearer |
| POST | `/notifications/{id}/read` | Bearer |
| POST | `/notifications/{id}/ack` | Bearer |
| GET | `/events` | Bearer, `student` only |
| GET | `/events/archive` | Bearer, `student` only |
| GET | `/events/{id}` | Bearer, `student` only |
| GET | `/news` | Bearer |
| GET | `/news/{id}` | Bearer |
| GET | `/documents` | Bearer |
| GET | `/children/{student}/documents` | Bearer, parent |
| POST | `/children/{student}/documents/{slot}` | Bearer, parent |
| GET | `/children/{student}/medical-certificate` | Bearer, parent |
| POST | `/children/{student}/medical-certificate` | Bearer, parent |

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

### GET `/app/version-check`

Public. Used by the Flutter client **before login**. Query:

- `platform` = `ios` | `android`
- `version` = installed app version name (`1.0.0`)

```json
{
  "success": true,
  "data": {
    "update_required": false,
    "store_url": "https://apps.apple.com/..."
  }
}
```

`update_required` is `true` when at least one **active** row exists for that platform and the installed version is not among them. If the platform has no active rows, the app is allowed (bootstrap). `store_url` comes from Settings → App settings → Version control (Apple or Play link). Network errors on the client must not lock the user out. Rate limit: **60 / minute / IP**.

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

If `users.must_set_password` is true for a mobile actor (admin **Azzera password** / reset), login does **not** issue a token. Success `200`:

```json
{
  "success": true,
  "data": {
    "password_setup_required": true
  }
}
```

The client then shows the create-password screen. The previous password is no longer valid.

### POST `/auth/set-password`

Public, same login rate limit. Allowed only while `must_set_password` is true.

```json
{
  "email": "user@example.com",
  "password": "…",
  "password_confirmation": "…",
  "device_name": "Owl iPhone"
}
```

`password` min 8, must match `password_confirmation`. Success is the same token envelope as login; the flag is cleared. If the email is unknown, inactive, not a mobile actor, or the flag is false → the same `401 invalid_credentials` body as a bad login.

### GET `/me`

Actor-aware whitelist. Never includes password hashes, `remember_token`, web `role_id`, `can_write`, `can_delete`, tax codes, medical data, notes, documents, or AI settings.

**Student profile:** `id`, `first_name`, `last_name`, `display_name`, `photo_url` (authenticated `GET /api/v1/files/{uuid}` or `null`), `photo` `{file_uuid, url}` or `null`, `phone`, `birth_date` (`YYYY-MM-DD` or `null`), `residence_address`, `residence_city_province`, `residence_postal_code`, `academy_class` `{id,name,course:{id,name}|null}` or `null`, `academic_year` `{id,name}` or `null`. Contact fields are read-only. Still never includes tax codes, medical data, notes, or documents. Never a public `/storage` URL.

**Parent profile:** `id`, `first_name`, `last_name`, `display_name`, `photo_url` (authenticated `GET /api/v1/files/{uuid}` or `null`), `photo` `{file_uuid, url}` or `null`, `children[]` with `id`, `first_name`, `last_name`, `display_name`, `photo_url`, `academy_class`. Only children linked via `student_parent`.

**Teacher profile:** `id`, `first_name`, `last_name`, `display_name`, `photo_url`, `photo`.

### GET `/files/{uuid}`

Authenticated binary. Sanctum + `mobile.actor` + `FileAccessService`. Unauthenticated → `401`. Unauthorized or unknown UUID → `404 not_found` (same body). No query-string tokens. See [Security/Secure_Files.md](Security/Secure_Files.md).

### POST `/me/photo`

Parent and teacher only. Students can view the photo set in admin but cannot upload. Multipart field `photo` (jpeg/png/webp, max 5 MB). Stored as `profile_photo` on the actor via `SecureFileService`. Success returns `{ photo_url, photo: { file_uuid, url } }`. Invalid file is `422`. Student → `404`. The photo is optional. Admin CRM shows the parent photo on the student profile.

### PUT `/me/password`

Any mobile actor (`student`, `parent`, `teacher`). Body: `current_password`, `password`, `password_confirmation`. Minimum 8 characters. Wrong current password is `422` `validation_error` with `fields.current_password`. On success the other devices' tokens are revoked; the current token stays valid.

### GET `/me/devices`

Any mobile actor. Returns `{ devices: [{ id, name, last_used_at, created_at, current }] }`. `name` is the login `device_name`. Token hashes are never returned. `current` is the token used for this request.

### DELETE `/me/devices/{id}`

Revokes that Sanctum token if it belongs to the caller. The current device cannot be revoked here (`422`); use `POST /auth/logout`. Unknown or another user's id → `404 not_found` (same body).

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

### Teacher teaching

Teacher only.

| Method | Path | Purpose |
|---|---|---|
| GET | `/teacher/teaching/groups` | Groups that contain students assigned to this teacher |
| GET | `/teacher/teaching/groups/{academyClass}/students` | Photo, name, whether the student has an app account. Other groups are 404 |
| GET | `/teacher/teaching/students/{student}` | Photo, name, age, birth date, gender, group, course, attendance totals, and that teacher’s private note. No documents, parents, address, or tax code |
| PUT | `/teacher/teaching/students/{student}/note` | Saves `body` (max 5000). Empty body clears the note. Visible only to the teacher who wrote it |
| GET | `/teacher/teaching/rehearsals` | Rehearsal slots assigned to this teacher |
| POST | `/teacher/teaching/students/{student}/messages` | Body plus `requires_ack`. Creates a personal student notice from the teacher |

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

Admin `WeeklyScheduleController::publish` sets `schedule_weeks.status = published`. The **first** publish rewrites every lesson on that week to `scheduled_lessons.status = published` (clean, no mobile badge). Later Publish only promotes new `draft`/`scheduled` lessons to `published` and **does not** reset `cancelled` / `moved` / `changed`. `locked` exists in schema as a frozen official week; the admin UI does not set it yet. Mobile treats **`published` and `locked`** as official.

Draft weeks are never returned.

After the first publish, admin edits set negative lesson statuses automatically:

| After-publish change | Lesson status |
|----------------------|---------------|
| Slot date / time / room / hall moved | `moved` |
| Teacher changed | `changed` |
| Cancel in slot settings (manual only) | `cancelled` |
| Restore original in slot settings | back to `published` from `published_snapshot` |

Each of those changes sends a **personal notice** (not chat) to students of the class who have an app account. The campaign is stored with `source=schedule` and appears on **Invio notifiche** with an Orario mark. Restore uses `source_reason=restored`.

Visible lesson statuses on an official week:

| Lesson status | Mobile |
|---------------|--------|
| `draft` | Hidden (admin work) |
| `scheduled` | Hidden (default placement; also used for lessons added after Publish without re-publishing) |
| `published` | Shown, no status badge |
| `cancelled` | Shown with `status: cancelled` |
| `moved` | Shown with current date/time and `status: moved`; original placement is in `published_snapshot` |
| `changed` | Shown with `status: changed` (teacher changed after publish) |

#### Empty states (always HTTP 200)

| Case | `academy_class` | `week.published` | `days` | `empty_reason` |
|------|-----------------|------------------|--------|----------------|
| Student has no class | `null` | `false` | `[]` | `no_class` |
| No official week and no rehearsals | class object | `false` | 7 empty days | `unpublished` |
| No official week, student has rehearsals | class object | `false` | 7 days, rehearsal cards only | `unpublished` |
| Official week, no visible lessons | class object | `true` | 7 empty days | `no_lessons` |

`week.starts_on` is Monday; `week.ends_on` is Sunday (calendar week for the app). Admin `week_end_date` remains Friday for the Mon–Fri board.

Whitelist: student `{id, display_name, academy_class {id,name,course:{id,name}|null}}`, lesson `{id, kind, event_id, starts_at, ends_at, title, lesson{id,name}, teacher{id,display_name}, location.building/room {id,name}, status}`. No notes, AI metadata, conflicts, emails, phones, tax codes, medical/document fields.

`kind` is `lesson` or `rehearsal`. Lessons have `event_id: null`. Rehearsals are visible event slots for this student (`academy_event_rehearsals`), merged only into `GET /schedule`. Parent and teacher payloads stay lessons (`kind: lesson`). Rows are grouped by day and sorted by time; a lesson precedes a rehearsal at the same time.

### GET `/rooms`

Student and teacher only. Parent → `403 forbidden`. Active halls (`academy_rooms` plus an active building): `id`, `name`, `building {id,name}`. Ordered by building then room `sort_order`.

### GET `/rooms/{id}/occupancy`

Same roles. The date is always **today** in `Europe/Rome`. Optional `?at=HH:MM`; omitted = now. Invalid `at` → `422 validation_error`. Inactive or unknown room → `404`.

Occupying lessons come from the official published/locked week only. Statuses `published` and `changed` occupy the hall when `starts_at <= at < ends_at`. `cancelled` and `moved` do **not** occupy.

```json
{
  "room": {"id": 4, "name": "Sala 2", "building": {"id": 1, "name": "SEDE ACCADEMIA"}},
  "date": "2026-09-20",
  "at": "14:30",
  "week_published": true,
  "free": true,
  "occupying": []
}
```

`occupying` uses the same lesson fields as schedule (`starts_at`, `ends_at`, `title`, `lesson`, `teacher`, `academy_class`, `status`, `location`).

### GET `/rooms/{id}/day`

Same roles. Official visible statuses (`published` / `cancelled` / `moved` / `changed`) for that room on today. Draft weeks return `lessons: []` and `week_published: false`.

Teacher schedule lessons include `teacher_checked_in` (`true` after a successful check-in). Student and parent schedule payloads do not.

### POST `/teacher/lessons/{scheduledLesson}/check-in`

Teacher only. Same ownership as attendance: the lesson must belong to this teacher and be on a published or locked week. Allowed lesson statuses: `published`, `moved`, `changed`. A cancelled lesson returns **422** `lesson: cancelled`. Another teacher’s lesson is **404**.

The server accepts the check-in from `app_settings.teacher_check_in_minutes_before` minutes before `starts_at` until `ends_at` (Europe/Rome). The default is 15. A value of 0 allows the check-in at any time. The hall must have coordinates. Distance is Haversine metres against `app_settings.teacher_check_in_radius_meters` (default 50, admin range 10–500). If `accuracy_meters` is greater than that radius, the fix is rejected. A second press returns the existing row and does not move it.

```json
{
  "latitude": 45.464211,
  "longitude": 9.191383,
  "accuracy_meters": 12,
  "device_name": "Owl phone"
}
```

Success:

```json
{
  "success": true,
  "data": {
    "checked_in": true,
    "already_recorded": false,
    "checked_in_at": "2026-09-07T16:05:00+02:00",
    "distance_meters": 4.2,
    "radius_meters": 50
  }
}
```

**422** `validation_error` field codes: `window` = `too_early` | `too_late` | `closed`; `room` = `missing_coordinates`; `accuracy` = `low_accuracy`; `location` = `outside_radius`.

The attendance GET lesson object also includes `teacher_checked_in` and `teacher_checked_in_at`.

### GET `/teacher/lessons/{scheduledLesson}/attendance`

Teacher only. See [ATTENDANCE.md](ATTENDANCE.md).

The lesson must belong to `request.user.teacherProfile` and be mobile-visible (`schedule_weeks.status` in `published`/`locked`, lesson `published`/`moved`/`changed`/`cancelled`). Otherwise **404** `not_found` (including another teacher’s lesson).

Roster is the **current** `academy_class_student` membership of `scheduled_lesson.academy_class_id`, sorted by `last_name`, `first_name`, `id`. Missing `AttendanceRecord` → `attendance: null` (unmarked). No pre-created rows.

Student whitelist: `id`, `display_name`, `photo_url` (authenticated `/api/v1/files/{uuid}` or `null`). No email, phone, address, parents, medical, tax_code, notes, documents.

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

Included only when the lesson’s week is `published`/`locked` and the lesson is `published`/`moved`/`changed`. Cancelled / draft / scheduled are excluded even if a leftover row exists.

Sort: `lesson_date DESC`, `starts_at DESC`, `attendance_record.id DESC`.

`summary.marked = present + absent + excused`. Absolute counts only.

Whitelist: student `{id, display_name}`; records `{id, date, starts_at, ends_at, status, lesson{id,name}, title, teacher{id,display_name}, location.building/room}`. No `marked_by`, `marked_at`, contacts, tax_code, medical, notes, documents, parent data, AI metadata.

### GET `/children/{student}/attendance`

Parent only. Same payload as student history. Ownership via `parentProfile.students`. Unrelated child → **404** `not_found` (not 403). Student / teacher → **403**.

### Chat

Two kinds of threads share `chats` / `chat_messages` (photos are `chat_photo` via `SecureFileService`, `GET /files/{uuid}`). Edit/delete own messages only (404 otherwise).

**Academy** (`type=academy`): one thread per mobile user with the office. All staff share it. `POST /chat/read` sets `actor_last_read_at`. Staff read is shared (`staff_last_read_at`) and does **not** clear the app badge. Legacy `GET /chat` still returns this thread.

**Class** (`type=class`): one thread per `AcademyClass`. Members are enrolled students and program teachers (`class_lesson_teacher`) who have an app account. Staff are not members but any web admin can write (author `kind=staff`, name = current login). Parents cannot join (403 on class endpoints). Unread is per user in `chat_reads` (own messages do not count). Class unread is **not** added to the Communication sidebar total.

`GET /chat/unread` for student/teacher is academy **plus** class memberships. For parent it stays academy-only.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/chat` | Academy thread + nested comments + `unread_count` |
| GET | `/chat/unread` | `{ count }` — parent: academy; student/teacher: academy + class |
| POST | `/chat/read` | Clears actor unread on the academy thread |
| POST | `/chat/messages` | Academy `body` and/or `photo` |
| PATCH | `/chat/messages/{id}` | Own body (academy) |
| DELETE | `/chat/messages/{id}` | Own, soft-delete (academy) |
| POST | `/chat/messages/{id}/comments` | Comment on a root academy message |
| GET | `/chats` | `{ items: [{ id, type, title, course, unread_count }] }` — always academy; class rows only for members |
| GET | `/chats/{id}` | Same body as `/chat`, plus `type` / `class` |
| POST | `/chats/{id}/read` | Academy: `actor_last_read_at`; class: this user's `chat_reads` |
| POST | `/chats/{id}/messages` | Same as academy post |
| PATCH | `/chats/{id}/messages/{id}` | Own body |
| DELETE | `/chats/{id}/messages/{id}` | Own, soft-delete |
| POST | `/chats/{id}/messages/{id}/comments` | Comment on a root message |

Non-members: **404**. Parent on a class chat: **403**.

### Notifications (bell)

One-way academy notices. Recipients are app accounts (`student` / `parent` / `teacher`) with a linked profile. Polling, not FCM. Inbox `read` clears the app badge. `ack` is only valid when the campaign has `requires_ack` and sets the admin “Letto” checkmark.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/notifications` | `{ items: [{ id, body, audience, requires_ack, created_at, read_at, acknowledged_at }] }` |
| GET | `/notifications/unread` | `{ count }` — deliveries without `read_at` |
| GET | `/notifications/pending-ack` | `{ item }` — oldest un-acked delivery with `requires_ack`, or `null` |
| POST | `/notifications/{id}/read` | Inbox read; does **not** acknowledge |
| POST | `/notifications/{id}/ack` | Sets `acknowledged_at` + `read_at`; 404 if not required or not own |

### Events

Student only. Other actors receive `403`. Hidden events and events without an active participation row return `404`.

Future events (today and later) follow the current course roster. After the event date, participation stays even if the student changes course. Tickets, costume, and gallery contents are not in the payload.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/events` | `{ items: [{ id, title, occurs_at, venue_name, cover_url, course }] }` — visible, today or later, active participation |
| GET | `/events/archive` | `{ items: [{ id, title, occurs_at }] }` — visible past events the student still belongs to |
| GET | `/events/{id}` | Description, photos, own role `{ description, image_url }`, rehearsals. Another student’s role image is omitted |
| GET | `/children/{student}/events` | Parent of this child. Same list as the student |
| GET | `/children/{student}/events/archive` | Parent of this child. Same archive as the student |
| GET | `/children/{student}/events/{id}` | Parent of this child. Same detail, including that child’s role. Unrelated child → 404 |

### News

Audience content feed (not the bell inbox). Each post belongs to `student`, `parent`, or `teacher`. Only `is_active` rows of the caller’s `account_type` are returned, ordered by `priority` desc then `id` desc. Cover images are `news_image` via `SecureFileService` (`GET /files/{uuid}`). Wrong audience or inactive → **404**.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/news` | `{ items: [{ id, title, short_body, long_body, photo_url }] }` |
| GET | `/news/{id}` | Same fields as one item |

### Documents

Fillable PDF templates from catalog types that have **Has a file for download** enabled and an uploaded file (`/documents` → Catalogo documenti). The PDF is `document_form_template` on `document_type` via `SecureFileService` (`GET /files/{uuid}`). Same list for student, parent, and teacher. Types without the flag or without a file are omitted.

A parent also sees the slots assigned on the child’s admin questionnaire (`student_document_slots`). `has_download_file` is true only when that type has a template file. Upload is multipart `file` (pdf/jpeg/png, 10 MB) onto the slot as `catalog_document`. Stranger parent → **404**. Student/teacher → **403**.

The medical certificate is **not** a catalog slot. It is `student_medical_certificates` + category `medical_certificate`. Parent uploads only the file. AI fills `expires_on`; status becomes `pending_review` until staff accept. `GET /children/{student}/documents` also returns `medical_certificate`. Daily `medical-certificates:watch` sets `needs_renewal` at 30 and 7 days.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/documents` | `{ items: [{ id, document_type_id, type_name, file_name, file_url }] }` |
| GET | `/children/{student}/documents` | Parent of this child. `{ items: [...], medical_certificate: { id, status, uploaded_at, expires_on, renewal_stage, has_file, file_name, file_url, ai_is_certificate, ai_notes } }` |
| POST | `/children/{student}/documents/{slot}` | Parent of this child. Multipart `file`. `{ item }` same shape as one list row. |
| GET | `/children/{student}/medical-certificate` | Parent of this child. `{ item }` same shape as `medical_certificate`. |
| POST | `/children/{student}/medical-certificate` | Parent of this child. Multipart `file` only. `{ item }`. |

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

Registration, forgot/reset password, email verification, refresh tokens, push tokens, check-in, grades, student/teacher document cabinets, payments, productions, timetable editing from mobile.
