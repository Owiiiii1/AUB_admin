# AUB Project Documentation

Bilingual documentation for the AUB academy management system.

Source of truth for implemented behaviour: **code + migrations + routes** in GitHub, verified against production where noted. Documentation must follow the code, not the other way around.

| Language | Path |
|----------|------|
| English | [`docs/en/`](en/) |
| Russian | [`docs/ru/`](ru/) |

## Documentation index

| File | EN | RU |
|------|----|----|
| Project overview | [README_PROJECT_OVERVIEW.md](en/README_PROJECT_OVERVIEW.md) | [README_PROJECT_OVERVIEW.md](ru/README_PROJECT_OVERVIEW.md) |
| Architecture | [ARCHITECTURE.md](en/ARCHITECTURE.md) | [ARCHITECTURE.md](ru/ARCHITECTURE.md) |
| Current state | [CURRENT_STATE.md](en/CURRENT_STATE.md) | [CURRENT_STATE.md](ru/CURRENT_STATE.md) |
| MVP scope | [MVP_SCOPE.md](en/MVP_SCOPE.md) | [MVP_SCOPE.md](ru/MVP_SCOPE.md) |
| Data model draft | [DATA_MODEL_DRAFT.md](en/DATA_MODEL_DRAFT.md) | [DATA_MODEL_DRAFT.md](ru/DATA_MODEL_DRAFT.md) |
| User roles and access | [USER_ROLES_AND_ACCESS.md](en/USER_ROLES_AND_ACCESS.md) | [USER_ROLES_AND_ACCESS.md](ru/USER_ROLES_AND_ACCESS.md) |
| Module roadmap | [MODULE_ROADMAP.md](en/MODULE_ROADMAP.md) | [MODULE_ROADMAP.md](ru/MODULE_ROADMAP.md) |
| Open questions | [OPEN_QUESTIONS.md](en/OPEN_QUESTIONS.md) | [OPEN_QUESTIONS.md](ru/OPEN_QUESTIONS.md) |
| Development rules | [DEVELOPMENT_RULES.md](en/DEVELOPMENT_RULES.md) | [DEVELOPMENT_RULES.md](ru/DEVELOPMENT_RULES.md) |
| Server deployment | [SERVER_DEPLOYMENT.md](en/SERVER_DEPLOYMENT.md) | [SERVER_DEPLOYMENT.md](ru/SERVER_DEPLOYMENT.md) |
| Privacy and data protection | [PRIVACY_AND_DATA_PROTECTION.md](en/PRIVACY_AND_DATA_PROTECTION.md) | [PRIVACY_AND_DATA_PROTECTION.md](ru/PRIVACY_AND_DATA_PROTECTION.md) |
| Next steps | [NEXT_STEPS.md](en/NEXT_STEPS.md) | [NEXT_STEPS.md](ru/NEXT_STEPS.md) |
| Weekly schedule service | [WEEKLY_SCHEDULE_SERVICE.md](en/WEEKLY_SCHEDULE_SERVICE.md) | [WEEKLY_SCHEDULE_SERVICE.md](ru/WEEKLY_SCHEDULE_SERVICE.md) |

Cursor task reports (fully overwritten each task): [`docs/Development/Cursor_Work_Report.md`](Development/Cursor_Work_Report.md)

## Bilingual rule

Every documentation change must update **both** `docs/en/` and `docs/ru/` in the same task. The Russian version must be a full faithful translation, not a summary.

## Repositories

| Repository | GitHub | Role |
|------------|--------|------|
| **AUB_admin** | [`Owiiiii1/AUB_admin`](https://github.com/Owiiiii1/AUB_admin) | Central CRM / backend / API / web workplaces |
| **AUB_app** | [`Owiiiii1/AUB_app`](https://github.com/Owiiiii1/AUB_app) | Flutter mobile application |

Flutter talks to the core **only** through HTTPS API. The API does not exist yet.

## Project quick facts

- **AUB_admin GitHub:** `Owiiiii1/AUB_admin` (source of truth for Tech Lead)
- **Production path:** `/var/www/aub` (file tree; **not** a git repository as of 2026-09-07)
- **Domain:** `https://aub.owlsolutions.net`
- **Admin foundation:** `owlsolutions/custom-admin-kit` v0.4.0
- **Flutter package:** `aub` — bundle / application id `com.owlsolutions.aub`
- **Phase 1 (roles):** Complete (2026-07-06)
- **Web modules:** Phase 2–3 in use (students partial; weekly schedule + hybrid AI)
- **Next major technical stage:** API Foundation

Last updated: 2026-09-07
