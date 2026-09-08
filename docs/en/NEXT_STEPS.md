# AUB — Next Steps

What is built: [CURRENT_STATE.md](CURRENT_STATE.md). Full question list: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md). Directions: [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

**Status (2026-09-08):** Web core in use. Flutter repo exists. **No HTTPS API.** Core Data Model is **done**. Identity Layer is **done**. Next stage is **API Foundation**.

**DECIDED:** `AUB_admin` core; `AUB_app` Flutter; HTTPS API only; separate GitHub repos; parallel backend/Flutter; one active `Class`; one User = one actor type; `customers` is a kit leftover.

## Immediate technical track

1. Keep docs honest (this stream).
2. **Security Foundation** before wide mobile (private storage, field-level ACL, scoped teachers, view audit, API authz matrix, token security, **admin 2FA**, consent records) — design/implement in dedicated tasks.
3. **Core Data Model refactor** — **done** (`students` / `parents` / `AcademyClass` / `ClassLesson`).
4. **Identity model implementation** — **done** (`account_type`, profile `user_id`, `AccountIdentityService`, admin UI).
5. **API Foundation** (Sanctum = candidate, not locked) — **next stage**.
6. **Flutter Foundation** once a contract exists (Store distribution **OPEN**: one app vs flavors).

Flutter **may** be developed in parallel (shell, navigation). Real academy features wait on the API. Do not publish a stable contract before Identity.

## Locked product rules (not OPEN)

- A child: at most one active primary `Class` (`unique academy_class_student.student_id`); plus separate additional groups ≠ `Class`.
- PHP Class model = `AcademyClass` / `academy_classes`.
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

Students on `students` + parents on `parents`, `AcademyClass`, `ClassLesson`, teachers directory (`user_id` foundation), courses, lessons in Settings, weekly schedule on `academy_class_id` + hybrid AI, CRUD logs, Flutter GitHub template.

### Optional web schedule polish

Actionable AI, PDF, 5-min UI snap, room capacity vs study windows.

## Do not do in a docs-only / unspecified task

- Do not delete kit legacy tables
- Do not edit `vendor/`
- Do not expose secrets
- Do not lock Sanctum
- Do not implement API / Flutter login in this task (Identity is already done)
- Do not publish a stable mobile API before Identity
- Do not implement check-in, report cards, or productions here
- Do not hardcode a consent age threshold
