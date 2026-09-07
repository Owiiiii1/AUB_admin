# AUB — Architecture

## Overview

AUB is a Laravel 13 monolith with an Inertia.js + React frontend. Business logic lives in the AUB project (`/var/www/aub`). The reusable admin shell comes from `owlsolutions/custom-admin-kit` v0.4.0, but **AUB-specific modules must not be placed inside the package**.

## Laravel project structure

```
/var/www/aub/
├── app/
│   ├── Http/Controllers/       # Auth, CRM, Settings, Academy modules
│   ├── Http/Middleware/        # role.access, can.write, can.delete, administrator
│   ├── Models/                 # User, Role, Customer, Teacher, Course, Lesson, Schedule…
│   ├── Services/               # ActivityLogger, ScheduleConflictService, Ai/
│   └── Support/                # RoleAccess, MenuRegistry, AdministratorLockoutGuard
├── config/
│   ├── owl-admin.php           # Branding, locales
│   └── aub-menu.php            # Menu registry for RBAC
├── database/migrations/        # Laravel + kit CRM migrations
├── resources/
│   ├── js/
│   │   ├── Pages/              # Inertia pages (Dashboard, CRM, Auth, Settings…)
│   │   ├── Layouts/            # AdminLayout, AuthLayout
│   │   └── Components/ui/      # shadcn-style UI components
│   ├── css/                    # app.css, owl-admin.css
│   └── views/app.blade.php     # Inertia root template
├── routes/
│   ├── web.php                 # Root login + includes kit routes
│   ├── owl-admin-pages.php     # Protected admin/CRM pages
│   ├── owl-admin-auth.php      # Logout + /login redirect
│   └── owl-admin-core.php      # Health route
└── public/build/               # Vite production assets
```

## Installed admin base

Package: **`owlsolutions/custom-admin-kit` v0.4.0**, preset **`admin`**.

Provides:

- Auth shell (login, logout, profile)
- Admin layout with sidebar menu
- Settings (language, user CRUD)
- AI Settings (OpenAI, Anthropic, Gemini)
- Generic CRM: customers, orders, services, staff, calendar
- Health endpoint: `/owl-admin/health`
- Artisan commands: `owl-admin:install`, `owl-admin:frontend-setup`, `owl-admin:doctor`, `owl-admin:smoke`, `owl-admin:make-admin`

## Frontend stack (installed)

| Technology | Version | Role |
|------------|---------|------|
| Inertia.js (Laravel) | 3.1.1 | Server-driven SPA bridge |
| Inertia.js (React) | 2.3.27 | React adapter |
| React | 18.3.1 | UI framework |
| Vite | 8.1.3 | Build tool |
| Tailwind CSS | 4.3.2 | Styling |
| Ziggy | 2.6.3 | Laravel named routes in JS |
| Radix UI | 1.6.1 | Accessible components |
| lucide-react | 1.23.0 | Icons |

Entry point: `resources/js/app.jsx`  
Layouts: `resources/js/Layouts/AdminLayout.jsx`, `AuthLayout.jsx`

## Backend stack

| Technology | Version |
|------------|---------|
| PHP | 8.3.6 |
| Laravel | 13.18.1 |
| MySQL | 8.0.46 |
| Node.js | 20.20.0 |

## Database approach

- Primary connection: **MySQL** (`DB_DATABASE=aub`)
- Migrations in `database/migrations/` (31 ran as of 2026-07-20)
- AUB academy tables: customers (extended), teachers, courses, lessons, schedule, activity_logs
- Legacy kit tables (orders, services, staff) exist but routes removed
- Legacy Spatie permission tables replaced with AUB roles schema (2026-07-06)

## Authentication and admin structure

- Session-based auth via Laravel `web` guard
- Login at **`/`** (customized; kit default was `/login`)
- Protected routes use `AdminRouteMiddleware::stack()` → `['web', 'auth']`
- Guest redirect: unauthenticated users → login
- Post-login redirect: admin → `/dashboard`, non-admin → `/workplace` or first allowed route
- User CRUD: `Settings\UserController` at `/settings`
- **Role-based access control implemented** (Phase 1) — middleware `role.assigned`, `role.access`, `can.write`, `can.delete`

Config: `config/owl-admin.php`, `config/aub-menu.php`  
Supported UI locales: `it` (default), `en`, `ru`, `uk`

## Routing structure

| File | Contents |
|------|----------|
| `routes/web.php` | Root login (`GET/POST /`), includes kit route files |
| `routes/owl-admin-auth.php` | `GET /login` → redirect `/`, `POST /login`, `POST /logout` |
| `routes/owl-admin-pages.php` | All protected CRM/admin pages |
| `routes/owl-admin-core.php` | `GET /owl-admin/health` |

Route cache is enabled in production (`bootstrap/cache/routes-v7.php`).

## Existing CRM modules (generic kit)

These are **starter CRM modules**, not academy-specific:

- **Customers** — generic client records
- **Orders** — generic orders linked to customers/services/staff
- **Services** — generic service catalog with price/duration
- **Staff** — generic staff directory (name, email, phone, free-text `role`)
- **Calendar** — simple calendar page
- **AI Settings** — AI provider API keys and activation

## Where to add AUB-specific modules

All AUB business logic goes inside `/var/www/aub`:

```
app/Models/Student.php
app/Http/Controllers/StudentsController.php
resources/js/Pages/Students/Index.jsx
database/migrations/xxxx_create_students_table.php
routes/aub-*.php  (or extend owl-admin-pages.php with AUB sections)
```

**Do not modify** `vendor/owlsolutions/custom-admin-kit/`.

## Separation of access layers

```
┌─────────────────────────────────────────────────────────┐
│                    Future mobile API                     │
│         (students / parents / teachers apps)             │
└────────────────────────┬────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────┐
│              Role-based staff workplaces                 │
│    (Secretariat, Teacher — limited menu/screens)         │
│                   [PLANNED]                              │
└────────────────────────┬────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────┐
│              Full admin panel (Administrator)            │
│   dashboard, CRM kit modules, settings, users, roles     │
│                   [INSTALLED]                            │
└─────────────────────────────────────────────────────────┘
```

## Role/menu architecture (implemented, Phase 1)

**Status: implemented (2026-07-06).**

```
roles
  └── role_menu_items (menu_key, route_name, sort_order…)
users
  └── role_id → roles
```

Behavior:

- Administrator role → full admin menu, redirect to `/dashboard`
- Non-admin role → dynamic menu from `role_menu_items`, redirect to `/workplace` or first allowed route
- Guest → `/` (login)
- Lockout protection for last active administrator

Implementation will extend:

- `app/Models/Role.php`, `RoleMenuItem.php`
- `resources/js/Layouts/AdminLayout.jsx` — dynamic menu generation
- `Settings\UserController` — role selector on create/edit
- New `RolesController` + `resources/js/Pages/Roles/`

## Recommended module boundaries

| Layer | Location | Examples |
|-------|----------|----------|
| Reusable admin shell | `vendor/owlsolutions/custom-admin-kit` | Auth layout, UI components, doctor/smoke |
| Generic CRM starter | Published stubs in AUB project | customers, orders, services, staff |
| AUB access control | AUB project only | roles, role_menu_items, middleware |
| AUB academy domain | AUB project only | students, parents, teachers, courses |
| Future mobile API | AUB project (`routes/api.php`) | Parent/student/teacher endpoints |

## Key rule

**Never put AUB-specific business logic into `custom-admin-kit`.** The package is only the base admin/CRM foundation.
