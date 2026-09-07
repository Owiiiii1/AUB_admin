# AUB — MVP Scope

## Distinction

`custom-admin-kit` shipped **generic CRM stubs**. They are not academy modules. Kit routes for orders/services/staff/calendar are **unrouted**. Students currently reuse `customers`.

AUB MVP is the **academy core** plus the **interfaces** that attach to it (web admin, staff workplaces, Flutter). Do not rebuild session auth or user CRUD. Do not design everything around one admin panel.

Numeric “Phase 0–6” labels below are **historical web milestones**, not the product roadmap. The living product directions are in [MODULE_ROADMAP.md](MODULE_ROADMAP.md). Open product questions: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Phase 1 — Access (done)

Staff roles and workplaces: implemented 2026-07-06. See [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md).

Gaps: no field-level ACL; extra routes always allowed for any role. **Security Foundation** (private documents, field ACL, scoped teachers, view audit, API auth matrix, mobile tokens, admin 2FA, consent records) is required before a **wide** mobile rollout — not implemented.

## Core records (Phase 2)

| Module | Status | Notes |
|--------|--------|-------|
| Students | Partial | Entity is `Customer` / table `customers` |
| Parents | Partial | Embedded columns; no Parent model |
| Teachers | Directory done | No `user_id` |
| Courses / groups | Done | `/courses-groups` |
| Lessons catalog | Done | Settings → Academy; no `Lessons/Index.jsx` |
| Enrollments | Partial | `course_group_customer`; unique `customer_id` in **code**. Whether one student may join several CourseGroups is **HIGH PRIORITY OPEN** — do not treat the unique index as a business rule |

## Operations (Phase 3)

| Module | Status |
|--------|--------|
| Weekly schedule | Implemented (`/schedule-service`) — regular `scheduled_lessons` only |
| **Student Attendance** | Not started — child on a **session**; distinct from teacher presence |
| **Teacher Check-in / Staff Presence** | Not started — **PRELIMINARY** mechanism: button «Пришёл» + one-shot GPS + backend geofence |
| Documents | Profile uploads on **public** disk; `/documents` placeholder |
| Internal notes / communication | Placeholder |

## Accounting (Phase 4)

Payments / invoices / reports — not started. Kit `orders.total` is not academy billing. `/statistics/logs` is an activity log, not a report dashboard.

## Large modules (not “optional leftovers”)

| Module | Notes |
|--------|-------|
| Academic Progress / grades / report cards | Large future module. Grading system **OPEN**. No final DB. |
| Productions / Shows / Rehearsals | Domain subsystem tied to core schedule — **not** “optional Phase 6 event placeholder”. RehearsalGroup ≠ CourseGroup (**PRELIMINARY**). Rehearsals must join the child’s unified calendar and conflict detection (**PRELIMINARY**). PM sets priority after product discovery. Costume Service may stay a separate integrated service. |
| Unified calendar | Future: lessons + rehearsals + performances (+ possibly exams/events). Architecture Variant A vs B **OPEN**. Do not change `scheduled_lessons` now. |

## API and Flutter

| Item | Status |
|------|--------|
| Flutter repo `Owiiiii1/AUB_app` | Exists (`aub`, `com.owlsolutions.aub`) |
| HTTPS API | **Absent** |
| Token auth | **Absent** (Sanctum = candidate) |
| Store distribution | **OPEN** — one app with modes vs several Store apps / flavors |

Flutter **may be developed in parallel**. MVP mobile **features** are blocked on API Foundation, not on “web must be 100% finished”. Backend and Flutter evolve in parallel; the API contract joins them.

## Dependency graph (historical web + next technical)

```
Phase 0 kit ✅
    → Phase 1 roles ✅
    → Phase 2 records (partial)
    → Phase 3 schedule ✅ / student attendance ❌ / teacher check-in ❌
    → Phase 4 accounting ❌
    → Security Foundation (before wide mobile)
    → API Foundation (next technical)
    → Flutter (parallel; distribution OPEN)
    → Identity / Student+Parent MVP / Teacher workplace
    → Academic Progress, Secretariat workflow, Documents, Communications, Payments
    → Schedule evolution + Productions (priority: PM after discovery)
```
