# AUB — Development Rules

Rules for Cursor AI and all future development on the AUB project.

## Core principles

1. **Do not implement undocumented business modules** without updating documentation first.
2. **Do not mix AUB-specific business logic into `custom-admin-kit`.** The package is only the base admin/CRM foundation.
3. **AUB modules must live in the AUB project** (`/var/www/aub`), not in `vendor/`.
4. **Do not duplicate already installed generic CRM/admin functionality.**
5. **Staff Roles & Role-Based Workplaces is the first AUB-specific step** unless manually changed by the project owner.

## Before adding any module

- [ ] Read [CURRENT_STATE.md](CURRENT_STATE.md) and [MVP_SCOPE.md](MVP_SCOPE.md)
- [ ] Run `php artisan route:list` — check for existing routes
- [ ] Inspect `app/Models/`, `app/Http/Controllers/`, `resources/js/Pages/` — avoid duplicates
- [ ] Confirm the module is not already provided by the admin kit
- [ ] Update documentation (en + ru) before or alongside implementation

## Generic kit — do not duplicate

These are **already installed and working**. Do not rebuild:

| Feature | Location |
|---------|----------|
| Auth (login, logout) | `AuthenticatedSessionController`, `Auth/Login.jsx` |
| Dashboard | `Dashboard.jsx`, route `dashboard` |
| User management | `Settings\UserController`, `/settings` |
| Profile | `ProfileController`, `/profile` |
| Generic customers | `CustomersController`, `/customers` — **extended as Students module** |
| Generic orders | Removed from routes (legacy table remains) |
| Generic services | Removed from routes (legacy table remains) |
| Generic staff directory | Removed from routes; use `TeachersController` |
| Calendar placeholder | Removed; use `/schedule-service` |
| AI Settings | Consolidated into `/settings?tab=ai` |
| App settings page | Consolidated into `/settings?tab=app` |
| Statistics/logs page | `/statistics/logs` — activity log viewer |
| Health check | `/owl-admin/health` |
| Admin layout + UI components | `AdminLayout.jsx`, `Components/ui/` |
| Doctor/smoke commands | `owl-admin:doctor`, `owl-admin:smoke` |

**Extend, don't rebuild.** Example: add role selector to existing user form, don't create a second user management module.

When an admin menu placeholder exists (e.g. **Schedule service** at `/schedule-service`), implement the AUB module on that route — do not add a duplicate menu entry. Document in `WEEKLY_SCHEDULE_SERVICE.md`.

## Package boundaries

```
vendor/owlsolutions/custom-admin-kit/   ← DO NOT EDIT (base foundation)
/var/www/aub/app/                       ← AUB business logic here
/var/www/aub/resources/js/Pages/        ← AUB pages here
/var/www/aub/database/migrations/       ← AUB migrations here
/var/www/aub/docs/                      ← Documentation here
```

If kit files need customization, edit the **published copies** in the AUB project, not vendor stubs.

## MVP scope rules

- Do **not** add costume/show/ticket/rental modules to core MVP prematurely
- Mark optional services as Phase 6 in [MODULE_ROADMAP.md](MODULE_ROADMAP.md)
- Access control (Phase 1) must precede academy domain modules (Phase 2+)

## Security and secrets

- **Never expose secrets** in documentation, logs, chat, or commits
- Do not print: `DB_PASSWORD`, `APP_KEY`, API keys, tokens, credentials
- Store secrets only in `.env` (not committed)
- Test admin credentials (`admin@admin.com`) are for development only

## After backend/frontend changes

Run relevant commands:

```bash
php artisan migrate          # after new migrations
npm run build                # after every frontend change (required for UI updates)
php artisan optimize:clear   # clear caches after build/config changes
php artisan view:cache       # production
php artisan config:cache     # production
php artisan route:cache      # production
php artisan storage:link     # required for student/teacher photo uploads
php artisan owl-admin:smoke --preset=admin   # verify install
```

**Rule:** After any change to `resources/js/`, `resources/css/`, or Inertia pages, run `npm run build` and clear caches before verifying in the browser.

## Documentation rules

- **Always update both** `docs/en/` and `docs/ru/` in the same task
- Russian version must be a **full faithful translation**, not a summary
- Update [CURRENT_STATE.md](CURRENT_STATE.md) after each implemented module
- Update [DATA_MODEL_DRAFT.md](DATA_MODEL_DRAFT.md) when adding/changing entities
- Update [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md) when changing permissions
- For changes affecting children's data, update [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md)

## Code style

- Use clean module boundaries (Model → Controller → Page → Route)
- Prefer small verified steps over large unverified changes
- Match existing code conventions (Inertia pages, AdminLayout, UI components)
- Minimize scope — only change what the task requires
- Do not over-engineer (no premature abstractions)

## File/folder deletion

- **Before deleting files or folders outside the current task scope, ask for confirmation**
- Do not delete databases unless explicitly requested
- Do not create manual backups unless explicitly requested

## Git and deployment

- Do not commit `.env` or secrets
- Do not force-push to main without explicit approval
- Follow existing commit message style

## Host customizations already applied

These differ from kit defaults — preserve unless intentionally changing:

- Login at `/` instead of `/login` (`routes/web.php`)
- `/login` redirects to `/` (`routes/owl-admin-auth.php`)
- AUB branding: login redesign, sidebar `#1A2B44`, widget cards `#EBF1FF`, Singo Sans font
- Italian (`it`) as default UI locale
- Generic kit CRM routes removed; students use extended `customers`
- Settings/roles/AI consolidated under `/settings` tabs

Document any new customizations in [CURRENT_STATE.md](CURRENT_STATE.md).

## Privacy development

- Design all modules with role-based access from the start
- Minimize fields shown to each role
- Log access to sensitive data (future audit trail)
- See [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md)

## Cursor workflow

1. Read relevant docs before coding
2. Inspect current project state (routes, models, pages)
3. Implement smallest correct change
4. Run migrations/build/smoke
5. Update docs (en + ru)
6. Report what changed and what was verified
