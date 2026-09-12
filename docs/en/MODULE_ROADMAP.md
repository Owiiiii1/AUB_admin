# AUB — Module Roadmap

Numbers below are **major product directions**. The recommended **starting sequence** (1–5) is mandatory: Core Data Model refactor must **precede** a stable API contract. Other modules may be reordered by the PM, except Productions (late future). Backend and Flutter evolve **in parallel**; the **API contract** joins them.

Implemented web snapshot: [CURRENT_STATE.md](CURRENT_STATE.md). Open items: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Already in production (web core)

- Laravel 13 + kit v0.4.0, session auth, RBAC Phase 1
- Students on `students` + parents on `parents` / `student_parent`
- Teachers directory (`user_id` + create/link account)
- Identity Layer: `account_type` staff/student/parent/teacher; one User = one actor type
- Courses / `AcademyClass`; lessons catalog in Settings → Academy
- `ClassLesson` + teacher assignments; weekly schedule on `academy_class_id`
- CRUD activity log; settings tabs
- Flutter repo exists; **API Foundation `/api/v1` is done**; **student/parent/teacher schedule API is done**; **Teacher Attendance API is done**; **Student/Parent Attendance History API is done**; Flutter client has auth + student/parent schedule
- Application timezone `Europe/Rome` (`APP_TIMEZONE`)

---

## Recommended starting sequence

| # | Direction | Notes |
|---|-----------|--------|
| 0 | Baseline / GitHub / documentation | In progress as living docs |
| 1 | **Security Foundation** | Private storage for person files is **done** (`SecureFileService`, [Security/Secure_Files.md](Security/Secure_Files.md)). Remaining: field-level ACL, admin 2FA, consent product. |
| 2 | **Core Data Model refactor** | **Done** 2026-09-08. `students`, `parents`, `AcademyClass`, `ClassLesson`. `customers` leftover. |
| 3 | **Identity model implementation** | **Done** 2026-09-08. One User = one actor type (`staff` / `student` / `parent` / `teacher`). Identity ≠ admin web RBAC. |
| 4 | **API Foundation** | **Done** 2026-09-08. `/api/v1`, Sanctum v4.3.3, login/logout/me/health. See [API.md](API.md). |
| 5 | **Flutter Foundation** | Auth foundation is in `AUB_app`. Store distribution model OPEN |
| 6 | Student + Parent mobile MVP | **Schedule API done** 2026-09-09 (`GET /schedule`, `GET /children/{student}/schedule`). Flutter schedule UI is in `AUB_app`. |
| 7 | Teacher App / Teacher Workplace | **Teacher schedule API done** 2026-09-09 (`GET /teacher/schedule`). **Teacher Attendance API done** 2026-09-09. Flutter teacher Orario + attendance follows. Identity/app foundation **before** check-in |
| 8 | **Student Attendance** | Teacher write + Student/Parent history **done** (`attendance_records`; `no AttendanceRecord` ≠ `absent`). **Not** teacher geofence |
| 9 | **Teacher Check-in / Staff Presence** | Separate module **after** Teacher identity/app foundation. Daily presence, not per-lesson. Button «Пришёл» + one-shot GPS + geofence. QR = optional fallback |
| 10 | **Final Assessment / Report Cards** | **Separate product module.** No running grades; only `StudentFinalResult` + `ReportCard`; Administrator generates the PDF |
| 11 | Secretariat / Enrollment Workflow | Statuses, transfers, history around one `Class` |
| 12 | Documents | Standalone module; move off public disk |
| 13 | Communications + Push | Placeholder today |
| 14 | Payments / Accounting | Fiscal rules OPEN |
| 15 | Schedule evolution | Unified calendar; Variant A vs B OPEN; do not change `scheduled_lessons` until chosen |
| 16 | Dashboard / Reporting | Current dashboard is a placeholder; logs ≠ reports |
| 17 | Consent / Privacy | Target `ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`; do not hardcode an age threshold |
| 18 | Infrastructure / Backup / Monitoring | OPEN |
| 19 | CI/CD / Staging / Deployment | Production is not a git repo |
| 20 | App Store / Google Play release | After privacy + API + distribution choice |

Legacy numeric “Phase 0–6” from earlier docs is **historical**. Do not treat “Phase 6 = shows” as the product model.

---

## Late future (discovery-needed)

| Direction | Notes |
|-----------|--------|
| **Productions / Shows** | Late stage. Do not detail workflow now. Activity groups ≠ `Class`. Future group schedules join the child’s unified calendar. Costume Service may stay a separate service |
| Costume Service / Archive / extra services | May integrate with productions later; still separate services |

---

## Domain splits (do not mix)

| Domain | Meaning |
|--------|---------|
| `Class` | Permanent primary academic class; at most one active per child |
| Additional activity groups | Production / rehearsal / other; **not** a `Class`; a child may join several |
| Student Attendance | Child on a session |
| Teacher Presence | Staff physically at the academy (daily check-in) |
| Final Assessment | End-of-period result: `StudentFinalResult` / `ReportCard`; not running grades |

## Parallelism

```
AUB_admin  ── API contract ──  AUB_app
   │                              │
   web workplaces / admin         student / parent / teacher
                                  (one User = one actor type;
                                   Store distribution OPEN)
```

Do **not** publish a stable API contract before Core Data Model refactor + Identity model.
