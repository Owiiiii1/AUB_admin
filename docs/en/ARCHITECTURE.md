# AUB — Architecture

## Product concept

AUB is a **centralized electronic academy management system**. It is not “an admin panel plus an app”.

The CRM/backend is the **core**. Interfaces attach to that core:

| Interface | Audience | Status (2026-09-07) |
|-----------|----------|---------------------|
| Admin / superadmin web | Administrators | Implemented (Inertia web) |
| Staff web workplaces | Secretariat, teachers, other staff | Phase 1 implemented; teacher login not linked to `teachers` |
| Flutter — students | Students | Repository exists; no API yet |
| Flutter — parents | Parents / guardians | Same app / mode planned; no API yet |
| Flutter — teachers | Teachers | Same app / mode planned; no API yet |

Do not design the product around a single admin panel.

## Two code entities

### AUB_admin — core

Central academy kernel:

- CRM;
- database;
- business logic;
- administrative web interfaces;
- staff workplaces;
- authentication / authorization;
- API for external clients (**not implemented yet**);
- audit / security;
- integrations.

| Item | Value |
|------|-------|
| GitHub | [`Owiiiii1/AUB_admin`](https://github.com/Owiiiii1/AUB_admin) |
| Production path | `/var/www/aub` |
| Domain | `https://aub.owlsolutions.net` |
| Git on production | **No** `.git` as of 2026-09-07 — do not change that in docs-only tasks |

Tech Lead treats **GitHub** as the source of truth. Production is a deployed file tree that can drift until a git-deploy workflow is introduced.

AUB-specific modules live in this repository, **not** in `vendor/owlsolutions/custom-admin-kit/`.

### AUB_app — Flutter client

Separate mobile application. It must call `AUB_admin` **only over HTTPS API**.

| Item | Value |
|------|-------|
| GitHub | [`Owiiiii1/AUB_app`](https://github.com/Owiiiii1/AUB_app) |
| Dart package | `aub` |
| Bundle / application id | `com.owlsolutions.aub` |
| Current code | Default Flutter template (`lib/main.dart`); no HTTP client |
| API | **Does not exist** (`routes/api.php` absent; no Sanctum / Passport / JWT) |

Flutter work may proceed in parallel with backend. Mobile **functionality** depends on a stable API contract (next stage: **API Foundation**).

```
┌──────────────────────────────────────────────────────────┐
│  Flutter AUB_app                                         │
│  students / parents / teachers (modes)                   │
│  GitHub: Owiiiii1/AUB_app                                │
└────────────────────────────┬─────────────────────────────┘
                             │ HTTPS API  (not built yet)
                             ▼
┌──────────────────────────────────────────────────────────┐
│  AUB_admin  (core)                                       │
│  CRM · DB · business logic · authz · audit · API         │
│  GitHub: Owiiiii1/AUB_admin                              │
│  Production: /var/www/aub                                │
│                                                          │
│   ┌────────────────────┐  ┌──────────────────────────┐   │
│   │ Admin / superadmin │  │ Staff web workplaces     │   │
│   │ Inertia + React    │  │ role-filtered AdminLayout│   │
│   └────────────────────┘  └──────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
```

## AUB_admin Laravel structure

```
AUB_admin / /var/www/aub/
├── app/
│   ├── Http/Controllers/       # Auth, academy CRM, Settings, schedule
│   ├── Http/Middleware/        # Inertia + role.assigned, role.access, can.write, can.delete, administrator
│   ├── Models/                 # User, Role, Customer, Teacher, Course, Lesson, Schedule…
│   ├── Services/               # ActivityLogger, WeeklySchedule/*, Ai/
│   └── Support/                # RoleAccess, MenuRegistry, AdministratorLockoutGuard
├── config/
│   ├── owl-admin.php           # Branding, locales
│   └── aub-menu.php            # Menu registry + always_allowed_route_patterns
├── database/migrations/        # 37 files (Laravel + kit + AUB)
├── resources/js/
│   ├── Pages/                  # Inertia pages
│   ├── Layouts/                # AdminLayout, AuthLayout
│   └── Components/ui/
├── routes/
│   ├── web.php                 # Root login + includes
│   ├── owl-admin-pages.php     # Protected web pages
│   ├── owl-admin-auth.php      # Logout + /login redirect
│   └── owl-admin-core.php      # Health (loaded by kit ServiceProvider)
│   # routes/api.php            # ABSENT
└── public/build/               # Vite assets (gitignored; present on production)
```

## Installed admin base

Package: **`owlsolutions/custom-admin-kit` v0.4.0**, preset **`admin`**.

Provides the reusable shell (auth, layout, doctor/smoke, generic CRM stubs). Host customizations: login at `/`, kit CRM routes removed from routing/menu, settings tabs consolidated.

**Do not put academy business logic in the kit.**

Generic kit CRM (`orders`, `services`, `staff`, `calendar`) remains as unused controllers, Inertia pages, and DB tables. Active UI does not use them.

## Stack (verified 2026-09-07)

| Technology | Version | Role |
|------------|---------|------|
| PHP | 8.3.6 | Runtime (production) |
| Laravel | 13.18.1 | Core framework |
| MySQL | 8.0.46 | Primary database |
| Node.js | 20.20.0 | Production frontend toolchain |
| `owlsolutions/custom-admin-kit` | v0.4.0 | Admin shell |
| Inertia.js (Laravel) | 3.1.1 | Server-driven SPA |
| Inertia.js (React) | 2.3.27 | React adapter |
| React | 18.3.1 | Web UI |
| Vite | 8.1.3 | Bundler |
| Tailwind CSS | 4.3.2 | Styling |
| Ziggy | 2.6.3 | Named routes in JS |
| Radix UI | 1.6.1 | UI primitives |
| lucide-react | 1.23.0 | Icons |

Web auth: Laravel `web` guard, sessions, CSRF. **No API token stack is installed.** Laravel Sanctum is a **recommended candidate** for API Foundation, not an approved decision.

## Database approach

- Primary connection: **MySQL** (production database name is configured in `.env`; values are not documented here)
- **37** migration files; on production **all Ran**, batches **1–29**
- Students: interim extended `customers` table (no `students` table)
- Parents: embedded columns on `customers` (no `parents` table)
- Teachers: `teachers` **not** linked to `users`
- Legacy kit tables (`orders`, `services`, `staff`, `order_staff`) exist; routes removed
- Spatie-style permission tables were dropped when AUB `roles` were created (2026-07-06). Whether empty leftover tables still exist in MySQL was not re-verified

## Web authentication and access

- Session auth; login at **`/`**
- `/login` GET redirects to `/`
- Protected pages: `AdminRouteMiddleware::stack()` → `['web', 'auth']` plus `role.assigned`, `role.access`
- Write/delete gated by `can.write` / `can.delete`
- Admin-only role CRUD: `administrator` middleware
- Post-login: Administrator → `/dashboard` (placeholder page); non-admin → first allowed menu route or `/workplace`
- Locales: `it` (default), `en`, `ru`, `uk`

**Implemented:** RBAC, `can_write`, `can_delete`, CRUD activity logging, session auth, encrypted AI keys.

**Not implemented (do not describe as done):** field-level ACL; teacher-only-assigned-students; view/access audit of sensitive records; consent entities; API authorization matrix; private disk for children’s documents (uploads use the **public** disk).

`config/aub-menu.php` `always_allowed_route_patterns` grants every authenticated user **with a role** access to `courses-groups.*`, `lessons.*`, `weekly-schedule.*`, placeholders, profile, workplace — wider than `role_menu_items`.

## Web routing (no API)

| File | Contents |
|------|----------|
| `routes/web.php` | `GET/POST /` login; includes pages + auth |
| `routes/owl-admin-auth.php` | `/login` redirect, `POST /logout` |
| `routes/owl-admin-pages.php` | All protected Inertia/web CRM routes |
| `routes/owl-admin-core.php` | `GET /owl-admin/health` |
| `bootstrap/app.php` | `web` + `commands` + health `/up` — **no `api`** |

Production `php artisan route:list`: **84** routes. None are versioned `/api/*` resources for Flutter.

## Where to add work

| Layer | Location | Notes |
|-------|----------|-------|
| Reusable admin shell | `vendor/owlsolutions/custom-admin-kit` | Do not edit |
| Web UI + academy domain | `AUB_admin` app / resources / migrations | Students currently on `customers` |
| API for Flutter | `AUB_admin` (`routes/api.php` to be created in API Foundation) | Not present |
| Flutter clients | `Owiiiii1/AUB_app` | HTTPS only; no Bitrix/direct DB |

## API Foundation (next technical stage — not built)

Planned contents, **not implemented**:

- API routing and versioning
- Token authentication (Sanctum is a candidate)
- Flutter login, `/me`, logout/revoke
- Authorization for API actors
- API Resources / DTO
- Error format, rate limiting
- Basic integration tests

Until that contract exists, the Flutter repository cannot implement real academy features against the backend.

## Key rules

1. **Never put AUB-specific business logic into `custom-admin-kit`.**
2. **Never call the database or secrets from Flutter.** Only HTTPS API.
3. **Do not treat production `/var/www/aub` as a git remote** until a deploy workflow is explicitly designed.
