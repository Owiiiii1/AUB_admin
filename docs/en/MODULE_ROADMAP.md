# AUB — Module Roadmap

Phases describe **product capability**. They are not a strict “finish web entirely before Flutter” gate. **API Foundation** can run in parallel with remaining Phase 2–3 work.

## Phase 0 — Base platform ✅

- [x] Laravel 13 at `/var/www/aub` (also GitHub `Owiiiii1/AUB_admin`)
- [x] `custom-admin-kit` v0.4.0
- [x] Session auth at `/`
- [x] Users in settings
- [x] Locales it/en/ru/uk
- [x] Generic CRM stubs installed; orders/services/staff/calendar **later unrouted**
- [x] Inertia + React + Vite + Tailwind

## Phase 1 — Access foundation ✅ (2026-07-06)

- [x] `roles`, `role_menu_items`, `users.role_id`
- [x] `can_write`, `can_delete`
- [x] Roles UI in Settings
- [x] Dynamic menu + middleware
- [x] Lockout protection

Known gaps (not Phase 1 failures): field-level ACL absent; `always_allowed_route_patterns` wider than role menu.

## Phase 2 — Core academy records (in progress)

- [x] Students — **partial:** extended `customers`
- [x] Parents — **partial:** embedded father/mother
- [x] Teachers directory
- [x] Courses / `course_groups`
- [x] Lessons catalog (Settings → Academy)
- [~] Enrollments — pivot only
- [ ] `teachers.user_id`
- [ ] Dedicated `students` / `parents` tables (optional later decision)

## Phase 3 — Operations

- [x] Weekly schedule + hybrid AI
- [~] Student file upload in profile (public disk)
- [ ] Attendance
- [ ] Documents module (`/documents` placeholder)
- [ ] Communication (`/communication` placeholder)
- [ ] Events / archive / costumes (placeholders)

## Phase 4 — Accounting

- [ ] Payments, invoices, debt, reports

## API Foundation (next technical stage, parallel)

Not a replacement for Phase 5 product goals; it is the **contract** those goals need.

- [ ] API routing + versioning
- [ ] Token auth (Sanctum = candidate)
- [ ] `/me`, login, revoke
- [ ] Resources / errors / rate limits / tests
- [ ] Flutter login against that API

Flutter repo `Owiiiii1/AUB_app` already exists (`aub`, `com.owlsolutions.aub`).

## Phase 5 — Access channels (mobile features)

Depends on API Foundation.

- [x] Flutter repository created
- [ ] Student / parent / teacher modes with real data
- [ ] Push notifications
- [ ] Mobile consent / privacy flow

Native Flutter is the chosen client (not PWA). Token mechanism is **not** frozen.

## Phase 6 — Optional services

Costume, shows, tickets, rental — not MVP core.

## Diagram

```
Phase 0 ✅
    │
    ▼
Phase 1 ✅
    │
    ▼
Phase 2 (records) ← IN PROGRESS
    │
    ├──────────────┐
    ▼              ▼
Phase 3          Phase 4
(operations)     (accounting)
    │              │
    └──────┬───────┘
           ▼
    API Foundation  ← NEXT TECHNICAL STAGE (parallel OK)
           │
           ▼
    Phase 5 (Flutter features)
           │
           ▼
    Phase 6 (optional)
```
