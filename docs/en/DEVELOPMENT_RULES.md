# AUB — Development Rules

Rules for Cursor and all AUB development.

## Repositories and roles

| Role | Who |
|------|-----|
| Project Manager / tester | User |
| Tech Lead | ChatGPT (GitHub is the Tech Lead source of truth) |
| Programmer | Cursor |

| Repo | GitHub | Responsibility |
|------|--------|----------------|
| AUB_admin | `Owiiiii1/AUB_admin` | Core: CRM, DB, business logic, web workplaces, API |
| AUB_app | `Owiiiii1/AUB_app` | Flutter only; HTTPS API client |

Production `/var/www/aub` is **not** a git repository. Deploy is **file copy** to `deploy@178.156.234.23:/var/www/aub` immediately after push. Do not `git pull` on the server.

Flutter must **never** contain Bitrix/webhooks, DB credentials, or `APP_KEY`. Only the API.

## Core principles

1. Do not implement undocumented business modules without updating docs first.
2. Do not put AUB business logic in `custom-admin-kit`.
3. AUB modules live in `AUB_admin` (or `AUB_app` for Flutter UI), never in `vendor/`.
4. Do not duplicate installed generic CRM/admin behaviour; extend it.
5. AUB is a **core + interfaces** system, not “the admin panel”.
6. Mobile work may proceed in parallel; **features** need an API contract. A stable API comes **after** Identity (done). Next: API Foundation.
7. Any `AUB_admin` change is deployed to production immediately. No exceptions.

## Before adding a module

- [ ] Read [CURRENT_STATE.md](CURRENT_STATE.md), [MVP_SCOPE.md](MVP_SCOPE.md), and [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md)
- [ ] Do **not** invent answers to OPEN / PRELIMINARY items
- [ ] `php artisan route:list` (or equivalent in `AUB_app`)
- [ ] Inspect existing models/controllers/pages
- [ ] Update **en + ru** docs in the same task
- [ ] Fully overwrite `docs/Development/Cursor_Work_Report.md` (AUB_admin)

## Generic kit — do not rebuild

| Feature | Location / note |
|---------|-----------------|
| Auth (session login/logout) | `AuthenticatedSessionController`, `Auth/Login.jsx` |
| Dashboard | `Dashboard.jsx` — **placeholder** |
| Users | `Settings\UserController` |
| Profile | `ProfileController` |
| Students | `StudentsController` / `students` (URL `/customers`) |
| Orders / services / staff / calendar | Unrouted legacy |
| AI / app settings | `/settings` tabs |
| Lessons catalog | Settings → Academy (`LessonsTab.jsx`); `/lessons` redirects |
| Schedule | `/schedule-service` |
| Activity log | `/statistics/logs` |
| Health | `/owl-admin/health` |

## Package boundaries

```
vendor/owlsolutions/custom-admin-kit/   ← DO NOT EDIT
AUB_admin app/, resources/, database/, docs/
AUB_app lib/                            ← Flutter only
```

## Security and secrets

- Never print `DB_PASSWORD`, `APP_KEY`, API keys, tokens
- `.env` is not committed
- Document security honestly: see [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md)

## After backend/frontend changes (AUB_admin)

```bash
php artisan migrate          # only when new migrations exist
npm run build                # after JS/CSS/Inertia changes
php artisan optimize:clear
php artisan view:cache       # production
php artisan config:cache     # production
php artisan route:cache      # production
php artisan storage:link     # student/teacher uploads (public disk today)
```

Production deploy is **file copy immediately after push**, not `git pull`. Target: `deploy@178.156.234.23:/var/www/aub`.

## Documentation

- Always update `docs/en/` and `docs/ru/` together
- Russian = full translation
- After each Cursor task, overwrite `docs/Development/Cursor_Work_Report.md`
- Status vocabulary: **DECIDED** / **PRELIMINARY** / **OPEN**. Do not present assumptions as decisions. See [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Cursor workflow (mandatory)

After every Cursor task:

1. Make the changes
2. Run required checks (non-destructive)
3. Update corresponding documentation (en + ru)
4. **Fully overwrite** `docs/Development/Cursor_Work_Report.md`
5. Commit
6. Push to `main`
7. **Immediately copy changed AUB_admin files to production** `deploy@178.156.234.23:/var/www/aub` (no exceptions, including docs)
8. Reply to the user **only**: `готово`

Tech Lead then reviews the report + GitHub diff.

An `AUB_admin` task is **not finished** until the files are on the server. Never upload `.env`, `vendor`, `node_modules`, storage runtime, or Flutter. In a docs-only task do not change PHP/JS/migrations/routes/config/`.env`/`vendor`, but docs **must** still be copied to production.

## Host customizations to preserve

- Login at `/`
- Kit CRM routes removed
- Settings consolidation including lessons catalog
- Default locale `it`
- Students on extended `customers` until an explicit Core Data Model refactor task (direction `students` / `parents` is DECIDED)

## Privacy development

- Design with RBAC from the start
- Field-level and scoped access are **not** implemented yet — do not assume they are
- Children’s files currently sit on the **public** disk — treat as a known gap

## Code style

- Small verified steps
- Match existing conventions
- Minimize scope
- Ask before deleting files outside the task
