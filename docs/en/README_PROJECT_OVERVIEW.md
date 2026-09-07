# AUB — Project Overview

## What is AUB?

**AUB** is a full electronic management and accounting system for an academy. It is not a mobile app alone. The goal is to automate academy management processes: student records, staff workflows, schedules, attendance, payments, documents, and reporting.

## Business goal

Create a centralized digital platform for the academy that:

- replaces fragmented spreadsheets and manual processes;
- gives administrators a full CRM/admin panel;
- gives staff role-specific workplaces with limited access;
- in the future, provides mobile access for students, parents, and teachers.

## Main system concept

The system has a **central CRM/admin core** and multiple future access channels:

| Channel | Audience | Status |
|---------|----------|--------|
| Full admin panel | Administrators | Installed + AUB modules in progress |
| Role-based workplaces | Secretariat, teachers, other staff | **Implemented** (Phase 1, 2026-07-06) |
| Mobile app — students | Students | Future |
| Mobile app — parents | Parents/guardians | Future |
| Mobile app — teachers | Teachers | Future |

## Current installed state

The project at `/var/www/aub` was freshly installed on **2026-07-06** with:

- **Laravel** 13.18.1
- **`owlsolutions/custom-admin-kit`** v0.4.0 (admin preset)
- **Inertia.js + React + Vite + Tailwind + Ziggy**
- **MySQL** database `aub`

Login page is served at **`/`** (root). Legacy `/login` redirects to `/`.

Test administrator exists: `admin@admin.com` (created via `owl-admin:make-admin`).

## Installed admin/CRM foundation

The following are **working** in the current deployment:

| Route | Purpose |
|-------|---------|
| `/` | Login (guest, redesigned AUB UI) |
| `/dashboard` | Admin dashboard |
| `/customers` | **Students** — list + full-page profile |
| `/teachers` | Teachers list + profile |
| `/courses-groups` | Courses and groups |
| `/lessons` | Lesson catalog |
| `/schedule-service` | Weekly schedule board + AI scheduling |
| `/settings` | Users, roles, app, AI, academy (tabbed) |
| `/statistics/logs` | Activity audit log |
| `/profile` | User profile |
| `/workplace` | Non-admin landing |
| `/documents`, `/communication`, `/events`, `/archive`, `/costume-service` | Placeholders ("Coming soon") |
| `/owl-admin/health` | Kit health check |

**Removed from active UI** (kit legacy, tables remain): `/orders`, `/services`, `/staff`, `/calendar`.

## Generic kit vs AUB-specific modules

| Generic kit (installed) | AUB implementation | Status |
|-------------------------|-------------------|--------|
| `customers` table/page | **Students** — extended profile on `customers` | Partial (2026-07-06+) |
| `services` table/page | **Courses** — separate `courses` table | Implemented |
| `staff` table/page (directory) | **Teachers** + **Staff Roles** | Teachers done; RBAC done |
| `orders` table/page | **Enrollments** — `course_group_customer` pivot | Partial |
| `calendar` page | **Weekly schedule** at `/schedule-service` | Implemented |
| User management in `/settings` | **Role-based access control** | Implemented |

**Important:** The kit's `staff` module is a generic CRM staff directory. It is **not** the planned Staff Roles & Role-Based Workplaces system. The kit's `staff.role` column is a free-text field, not RBAC.

## Full admin panel vs role-based workplaces

- **Full admin panel** — all menu items, all CRM modules, settings, user management. Intended only for **Administrator** role users.
- **Role-based workplace** — a limited screen set for non-admin staff (e.g. Secretariat, Teacher). Same visual style (AdminLayout), but menu shows only allowed items. **Implemented** — see [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md).

## MVP scope summary

**Completed or in progress:**

1. Staff Roles & Role-Based Workplaces ✅ (Phase 1, 2026-07-06)
2. Core academy records: students (partial), teachers, courses, groups, lessons, enrollments (partial)
3. Operations: weekly schedule + hybrid AI ✅; attendance, standalone documents — pending
4. Accounting basics: not started
5. Admin users and permissions (via roles system) ✅

**Explicitly out of MVP Phase 1 core:**

- Costume management (`servizio costumi`)
- Show/event participation (`servizio spettacoli`)
- Tickets, costume rental, participation fees
- Mobile apps (planned for later phases)

## Server and domain

| Item | Value |
|------|-------|
| Project path | `/var/www/aub` |
| Web root | `/var/www/aub/public` |
| Domain | `https://aub.owlsolutions.net` |
| Server IP | `178.156.234.23` |
| Linux user | `deploy` |
| Admin kit | `owlsolutions/custom-admin-kit` v0.4.0 |

## First planned AUB-specific development step

**Staff Roles & Role-Based Workplaces** — see [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md), [MODULE_ROADMAP.md](MODULE_ROADMAP.md), and [NEXT_STEPS.md](NEXT_STEPS.md).

Status: **implemented (Phase 1, 2026-07-06)**. Current work: **Phase 2–3** (students, teachers, courses, schedule).
