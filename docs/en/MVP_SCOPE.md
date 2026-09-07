# AUB — MVP Scope

## Distinction

`custom-admin-kit` shipped **generic CRM stubs**. They are not academy modules. Kit routes for orders/services/staff/calendar are **unrouted**. Students currently reuse `customers`.

AUB MVP is the **academy core** plus the **interfaces** that attach to it (web admin, staff workplaces, Flutter). Do not rebuild session auth or user CRUD. Do not design everything around one admin panel.

## Phase 1 — Access (done)

Staff roles and workplaces: implemented 2026-07-06. See [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md).

Gaps: no field-level ACL; extra routes always allowed for any role.

## Core records (Phase 2)

| Module | Status | Notes |
|--------|--------|-------|
| Students | Partial | Entity is `Customer` / table `customers` |
| Parents | Partial | Embedded columns; no Parent model |
| Teachers | Directory done | No `user_id` |
| Courses / groups | Done | `/courses-groups` |
| Lessons catalog | Done | Settings → Academy; no `Lessons/Index.jsx` |
| Enrollments | Partial | `course_group_customer`; unique one group per student |

## Operations (Phase 3)

| Module | Status |
|--------|--------|
| Weekly schedule | Implemented (`/schedule-service`) |
| Attendance | Not started |
| Documents | Profile uploads on **public** disk; `/documents` placeholder |
| Internal notes / communication | Placeholder |

## Accounting (Phase 4)

Payments / invoices / reports — not started. Kit `orders.total` is not academy billing. `/statistics/logs` is an activity log, not a report dashboard.

## API and Flutter

| Item | Status |
|------|--------|
| Flutter repo `Owiiiii1/AUB_app` | Exists (`aub`, `com.owlsolutions.aub`) |
| HTTPS API | **Absent** |
| Token auth | **Absent** (Sanctum = candidate) |

Flutter **may be developed in parallel**. MVP mobile **features** are blocked on API Foundation, not on “web must be 100% finished”.

Out of Phase 1: costumes, shows, tickets, push notifications.

## Dependency graph

```
Phase 0 kit ✅
    → Phase 1 roles ✅
    → Phase 2 records (partial)
    → Phase 3 schedule ✅ / attendance ❌
    → Phase 4 accounting ❌
    → API Foundation (next technical)
    → Flutter modes (Phase 5)
    → Optional Phase 6
```
