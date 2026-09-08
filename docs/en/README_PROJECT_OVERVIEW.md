# AUB — Project Overview

## What is AUB?

**AUB** is a centralized electronic management and accounting system for an academy. It is not a mobile app alone and not “admin panel + app”. The goal is to automate academy processes: student records, staff workflows, schedules, attendance, payments, documents, and reporting.

The **CRM/backend (`AUB_admin`) is the core**. Web workplaces, the admin interface, and Flutter clients are interfaces on that core.

## Business goal

Create one digital platform that:

- replaces fragmented spreadsheets and manual processes;
- gives administrators a full CRM / admin interface;
- gives staff role-specific **web workplaces**;
- gives students, parents, and teachers **mobile access** through Flutter, once an HTTPS API exists.

## Access channels

| Channel | Audience | Code | Status (2026-09-07) |
|---------|----------|------|---------------------|
| Admin / superadmin web | Administrators | `AUB_admin` Inertia | Implemented; dashboard is a placeholder |
| Staff web workplaces | Secretariat, teachers, other staff | `AUB_admin` | Phase 1 RBAC implemented; teacher record not linked to login |
| Flutter — students / parents / teachers | Students, parents, teachers | `AUB_app` | Repository exists; default template; **no API**. One Store app with modes vs several apps/flavors is **OPEN** |

## Repositories

| Entity | GitHub | Role |
|--------|--------|------|
| **AUB_admin** | [`Owiiiii1/AUB_admin`](https://github.com/Owiiiii1/AUB_admin) | CRM, database, business logic, web admin/workplaces, future API |
| **AUB_app** | [`Owiiiii1/AUB_app`](https://github.com/Owiiiii1/AUB_app) | Flutter app `aub`, bundle id `com.owlsolutions.aub` |

Flutter must use **HTTPS API only**. `routes/api.php` does not exist. Sanctum / Passport / JWT are not installed.

Production of the core: `/var/www/aub` at `https://aub.owlsolutions.net`. That folder is **not** a git repository. Tech Lead uses GitHub as source of truth.

## Installed core (web)

Fresh Laravel 13 host on **2026-07-06**, then academy modules through **2026-07-20**. Stack verified **2026-09-07**: Laravel **13.18.1**, kit **v0.4.0**, PHP **8.3.6**, MySQL **8.0.46**.

Login is at **`/`**. `/login` redirects to `/`.

| Route | Purpose | Notes |
|-------|---------|-------|
| `/` | Login | Guest |
| `/dashboard` | Home | **Placeholder** page |
| `/customers` | Students list + profile | Table `customers` |
| `/teachers` | Teachers list + profile | No `users` link |
| `/courses-groups` | Courses and groups | |
| `/lessons` | Lesson catalog | **Redirects** to Settings → Academy → Lessons |
| `/schedule-service` | Weekly schedule + hybrid AI | |
| `/settings` | Users, roles, app, AI, academy | Lessons catalog lives here |
| `/statistics/logs` | CRUD activity log | Not a full access audit |
| `/profile` | Current user | |
| `/workplace` | Non-admin landing | Thin page |
| `/documents`, `/communication`, `/events`, `/archive`, `/costume-service` | Coming soon | Placeholders |
| `/owl-admin/health` | Kit health | |

**Removed from routing/menu** (legacy kit tables remain): `/orders`, `/services`, `/staff`, `/calendar`.

Production `route:list`: **84** web routes. **Zero** Flutter API endpoints.

## Generic kit vs academy modules

| Generic kit | AUB meaning | Status |
|-------------|-------------|--------|
| `customers` | Students (interim; target `students` / `parents`) | Partial |
| `services` | Not courses — separate `courses` | Courses implemented |
| `staff` directory | Not RBAC — separate `teachers` + `roles` | Teachers + RBAC implemented |
| `orders` | Not enrollments — pivot `academy_class_student` | Partial |
| `calendar` page | Weekly schedule at `/schedule-service` | Implemented |

Kit `staff.role` is free text, **not** access control.

## MVP snapshot

**Ready or in use:** Phase 1 roles; teachers; courses/groups; lessons catalog; weekly schedule + hybrid AI; users/`can_write`/`can_delete`; CRUD activity log.

**Partial:** students on `customers`; parents as embedded fields; enrollments without statuses; document uploads in profile only.

**Missing:** student attendance; teacher check-in (daily semantics DECIDED); final assessment / report cards; payments; standalone documents/communication modules; mobile API; token auth; field-level ACL; Security Foundation items.

**Next major technical stage:** [Security Foundation](NEXT_STEPS.md) → Core Data Model refactor → Identity → API Foundation → Flutter Foundation. Stable API **after** Core Data Model. Directions: [MODULE_ROADMAP.md](MODULE_ROADMAP.md). Open questions: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

Productions / shows are **late future** / discovery-needed (activity groups ≠ `Class`). Do not detail workflow now. Costume Service may stay a separate integrated service. Web `/events` and `/costume-service` remain UI placeholders only.

## Server

| Item | Value |
|------|-------|
| GitHub core | `Owiiiii1/AUB_admin` |
| Production path | `/var/www/aub` (not a git repo) |
| Web root | `/var/www/aub/public` |
| Domain | `https://aub.owlsolutions.net` |
| Server IP | `178.156.234.23` |
| Linux user | `deploy` |
| Admin kit | `owlsolutions/custom-admin-kit` v0.4.0 |

Details: [ARCHITECTURE.md](ARCHITECTURE.md), [CURRENT_STATE.md](CURRENT_STATE.md), [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md).
