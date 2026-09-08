# AUB — Next Steps

What is built: [CURRENT_STATE.md](CURRENT_STATE.md). Full question list: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md). Directions: [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

**Status (2026-09-08):** Web core in use. Flutter repo exists. **No HTTPS API.** Core Data Model refactor must precede a stable API contract.

**DECIDED:** `AUB_admin` core; `AUB_app` Flutter; HTTPS API only; separate GitHub repos; parallel backend/Flutter; one active `Class`; one User = one actor type; `customers` is interim.

## Immediate technical track

1. Keep docs honest (this stream).
2. **Security Foundation** before wide mobile (private storage, field-level ACL, scoped teachers, view audit, API authz matrix, token security, **admin 2FA**, consent records) — design/implement in dedicated tasks.
3. **Core Data Model refactor** (`students` / `parents` / `student_parent`; one `Class`) — **before** a stable API. No migrations in this task.
4. **Identity model implementation** (one User = one actor type; identity ≠ web RBAC).
5. **API Foundation** (Sanctum = candidate, not locked) — only after items 3–4.
6. **Flutter Foundation** once a contract exists (Store distribution **OPEN**: one app vs flavors).

Flutter **may** be developed in parallel (shell, navigation). Real academy features wait on the API. Do not publish a stable contract on top of `customers`.

## Locked product rules (not OPEN)

- A child: at most one active primary `Class`; plus separate additional groups ≠ `Class`.
- Unique `customer_id` on `course_group_customer` conceptually matches “one primary class”; terminology still needs normalization.
- Teacher Check-in = **daily presence**, not per lesson.
- No running grades. Final result: `StudentFinalResult` (Student + Class + Lesson + AcademicYear). Administrator generates the report-card PDF.

## Do not mix

| Student Attendance | Teacher Check-in |
|--------------------|------------------|
| Child on a **session** (lesson/rehearsal/…) | Staff **physically at the academy** |
| Not built | DECIDED: button «Пришёл» + one-shot GPS + geofence → daily check-in; QR optional fallback |
| | OPEN: radius; accuracy; time window; anti-spoofing |

## Other large modules (not scheduled as “do next” without PM)

- **Final Assessment / Report Cards** — separate product module after foundation. Scale / PDF template / who closes the card — **OPEN**.
- **Teacher Check-in** — separate module after Teacher identity/app foundation.
- **Productions / Shows** — late future / discovery-needed. Activity groups ≠ `Class`. Future group schedules join the unified calendar. Do not detail workflow now.
- **Unified calendar architecture** — Variant A vs B. Tech Lead **not** decided. Do not change `scheduled_lessons` yet.
- Enrollment workflow around `Class`, documents, communications, payments.

## Already done (web)

Students-on-customers (interim), teachers directory, courses/groups, lessons in Settings, weekly schedule + hybrid AI, CRUD logs, Flutter GitHub template.

### Optional web schedule polish

Actionable AI, PDF, 5-min UI snap, room capacity vs study windows.

## Do not do in a docs-only / unspecified task

- Do not delete kit legacy tables
- Do not edit `vendor/`
- Do not expose secrets
- Do not lock Sanctum
- Do not implement `students` / `parents` migrations
- Do not publish a stable mobile API before Core Data Model refactor
- Do not implement check-in, report cards, or productions here
- Do not hardcode a consent age threshold
