# AUB — Module Roadmap

## Phase 0 — Base platform ✅ (completed)

**Goal:** Clean project with admin foundation ready for AUB development.

**Deliverables:**

- [x] Clean Laravel 13 project at `/var/www/aub`
- [x] `owlsolutions/custom-admin-kit` v0.4.0 installed (admin preset)
- [x] Auth (login at `/`, logout, profile)
- [x] Users management in settings
- [x] Localization (en, ru, uk) on login and settings
- [x] Generic CRM modules: customers, orders, services, staff, calendar
- [x] AI Settings, app settings, statistics/logs pages
- [x] Inertia + React + Vite + Tailwind frontend
- [x] Smoke test passed

**Dependencies:** None  
**Risks:** Legacy DB tables from previous install may need cleanup before Phase 1

---

## Phase 1 — AUB access foundation ✅ (completed 2026-07-06)

**Goal:** Implement Staff Roles & Role-Based Workplaces before any academy domain modules.

**Deliverables:**

- [x] `roles` and `role_menu_items` tables (migrations)
- [x] `users.role_id` column
- [x] Default Administrator role; assign `admin@admin.com`
- [x] Roles management screen (CRUD + menu assignment)
- [x] Role selector in user create/edit form
- [x] Dynamic menu generation in AdminLayout by role
- [x] Middleware: block non-admin from unauthorized routes
- [x] Redirect logic: admin → `/dashboard`, non-admin → first allowed route
- [x] Lockout protection for last administrator
- [x] Documentation update (en + ru)

**Dependencies:** Phase 0  
**Risks/questions:** Resolved — legacy Spatie tables replaced with AUB schema

---

## Phase 2 — Core academy records (in progress)

**Goal:** Core entities for academy operations.

**Deliverables:**

- [x] Students module — **partial (2026-07-06+):** extended `customers` table + full-page profile UI; separate `students` table not created yet
- [x] Parents/guardians — **partial:** father/mother fields embedded in student profile (modals); no separate `parents` table
- [x] Teachers module — list + profile CRUD (`teachers` table)
- [x] Courses module — `courses` by discipline
- [x] Groups/classes module — `course_groups` + UI at `/courses-groups`
- [x] Lessons catalog — `lessons` table + `/lessons` page
- [~] Enrollments — **partial:** `course_group_customer` pivot; no full enrollment workflow

**Dependencies:** Phase 1 (roles must exist)  
**Risks/questions:**

- Interim use of `customers` for students — migrate to dedicated `students` table when enrollment/parent modules mature
- Parent account creation workflow — still pending
- Teachers not yet linked to `users` accounts

---

## Phase 3 — Operations

**Goal:** Day-to-day academy operations.

**Deliverables:**

- [x] Weekly schedule service (`/schedule-service`, draft/publish, conflicts, copy/clear, hybrid AI, course study windows, 5/30 grid) — see `WEEKLY_SCHEDULE_SERVICE.md`
- [~] Documents/student files upload — **partial:** upload fields in student profile; standalone `/documents` page is placeholder
- [ ] Attendance tracking
- [ ] Internal notes/communication history
- [ ] Communication module (`/communication` — placeholder)
- [ ] Events, archive, costume service menu sections (placeholders only)

**Dependencies:** Phase 2  
**Risks/questions:**

- Schedule rules (weekly template vs individual lessons)
- Document storage (local vs cloud)
- Note visibility levels

---

## Phase 4 — Accounting

**Goal:** Financial tracking and reporting.

**Deliverables:**

- [ ] Payments module
- [ ] Invoices module
- [ ] Payment statuses and debt tracking
- [ ] Basic reports (enrollment, attendance, payments)

**Dependencies:** Phase 2 (students), Phase 3 (attendance for reports)  
**Risks/questions:**

- Invoice format requirements (Italian fiscal rules?)
- Payment methods accepted
- Integration with external accounting software?

---

## Phase 5 — Communication / access channels

**Goal:** Mobile and external access for non-admin users.

**Deliverables:**

- [ ] REST/API layer for mobile apps
- [ ] Parent mobile app (or PWA)
- [ ] Student mobile app
- [ ] Teacher mobile app / web workplace enhancement
- [ ] Push notifications
- [ ] Privacy/consent flow for mobile

**Dependencies:** Phases 1–4  
**Risks/questions:**

- Native apps vs PWA
- App Store / Play Store requirements
- Authentication method for mobile (tokens, OAuth)

---

## Phase 6 — Optional integrated services

**Goal:** Separate business services integrated with core academy CRM.

**Deliverables (each as optional module):**

- [ ] Costume service (`servizio costumi`)
- [ ] Show/event participation service (`servizio spettacoli`)
- [ ] Tickets
- [ ] Costume rental
- [ ] Participation fees

**Dependencies:** Phase 2+ (students as base)  
**Risks/questions:**

- Separate billing or integrated with academy payments?
- Separate admin workplaces or sections in main admin?

**Important:** These are **not** part of MVP Phase 1 core. Do not implement prematurely.

---

## Timeline note

No fixed dates assigned. Each phase should be completed and verified (migrations, build, smoke, docs) before starting the next.

## Phase dependency diagram

```
Phase 0 ✅
    │
    ▼
Phase 1 ✅
    │
    ▼
Phase 2 (Students, Parents, Teachers, Courses, Enrollments) ← IN PROGRESS
    │
    ├──────────────┐
    ▼              ▼
Phase 3          Phase 4
(Operations)     (Accounting)
    │              │
    └──────┬───────┘
           ▼
       Phase 5 (Mobile)
           │
           ▼
       Phase 6 (Optional services)
```
