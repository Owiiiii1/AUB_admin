# AUB — Next Steps

What is built: [CURRENT_STATE.md](CURRENT_STATE.md). Full question list: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md). Directions: [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

**Status (2026-09-07):** Web core in use. Flutter repo exists. **No HTTPS API.** Direction numbers are **not** a frozen PM priority.

**DECIDED:** `AUB_admin` core; `AUB_app` Flutter; HTTPS API only; separate GitHub repos; parallel backend/Flutter.

## Immediate technical track

1. Keep docs honest (this stream).
2. **Security Foundation** before wide mobile (private storage, field-level ACL, scoped teachers, view audit, API authz matrix, token security, **admin 2FA**, consent records) — design/implement in dedicated tasks.
3. **API Foundation** (Sanctum = candidate, not locked).
4. **Flutter Foundation** once a contract exists (Store distribution **OPEN**: one app vs flavors).

Flutter **may** be developed in parallel (shell, navigation). Real academy features wait on the API.

## HIGH PRIORITY product question

**Can a child be in several CourseGroups at once?** Code: unique `customer_id` on `course_group_customer` ⇒ **one group total**. If the academy allows multiple disciplines, the constraint is wrong. **Do not migrate until answered.**

## Do not mix

| Student Attendance | Teacher Check-in |
|--------------------|------------------|
| Child on a **session** (lesson/rehearsal/…) | Staff **physically at the academy** |
| Not built | PRELIMINARY: button «Пришёл» + one-shot GPS + geofence; QR fallback only |
| | OPEN: day/shift vs specific session; radius; accuracy; anti-spoofing |

## Other large modules (not scheduled as “do next” without PM)

- **Academic Progress** — grades, periods, report cards; grading system **OPEN** (ask academy).
- **Productions / rehearsals** — RehearsalGroup ≠ CourseGroup (**PRELIMINARY**). Rehearsals **must** hit the child’s unified calendar and conflict detection. **Not** an optional Phase-6 placeholder. PM sets priority after discovery.
- **Unified calendar architecture** — Variant A (`ScheduledSession` types) vs Variant B (separate entities + aggregator). Tech Lead **not** decided. Do not change `scheduled_lessons` yet.
- Identity model (User vs Teacher/Parent/Student profiles) — **OPEN**.
- Enrollment workflow, documents, communications, payments.

## Already done (web)

Students-on-customers, teachers directory, courses/groups, lessons in Settings, weekly schedule + hybrid AI, CRUD logs, Flutter GitHub template.

### Optional web schedule polish

Actionable AI, PDF, 5-min UI snap, room capacity vs study windows.

## Do not do in a docs-only / unspecified task

- Do not delete kit legacy tables
- Do not edit `vendor/`
- Do not expose secrets
- Do not lock Sanctum
- Do not “fix” enrollment unique without the academy answer
- Do not implement check-in, grades, or productions here
