# Cursor Work Report

## Task

Create a living product functionality matrix and a permanent process so it stays in sync with the code.

## Audit sources

Checked against the current trees, not old roadmap copy:

- `routes/owl-admin-pages.php`, `routes/api.php`, `config/aub-menu.php`, `config/aub-files.php`
- Admin pages/controllers: students, teachers, courses, schedule, documents, chat, notices, news, settings, dashboard, activity log
- `FileAccessService`, Identity / `User` actor types vs web roles
- Flutter shells: Student (Home / Orario / Eventi / Profilo), Parent (Home / Figli / Orario / Profilo), Teacher (Oggi / Orario / Presenze / Profilo)
- `docs/en/CURRENT_STATE.md`, `docs/en/API.md`, `docs/en/Security/Secure_Files.md`, Flutter `docs/CURRENT_STATE.md`

Where CURRENT_STATE disagreed with code, code won (Dashboard is a medical attention inbox, not an empty placeholder; student Presenze is not in the nav; Eventi is an empty tab; documents/chat/news are live).

## Delivered

- `docs/Product/FUNCTIONALITY_MATRIX.md` created
- `.cursor/rules/aub-functionality-matrix.mdc` (`alwaysApply: true`) created
- Definition of Done added to `docs/en/DEVELOPMENT_RULES.md` and `docs/ru/DEVELOPMENT_RULES.md`
- Stale “children’s files on public disk” line in Development Rules corrected to `aub_private`

## Classification (detailed capability rows, excluding the executive summary)

| Status | Count |
|--------|-------|
| ✅ Implemented | 96 |
| 🟡 Partial | 9 |
| 🔵 Foundation / backend ready | 5 |
| ⚪ Planned (in actor tables) | 9 |
| 🚫 Not available / restricted (in actor tables) | 16 |

Major planned modules are listed separately at the end of the matrix (secretariat desk, tasks, pagelle, payments, consent product, productions, costume, teacher check-in, student Presenze tab, admin attendance, PDF export, week lock UI, academic-year UI, FCM, 2FA).

## Ambiguous / easy-to-misread items

- Student **Eventi** tab exists and is empty — 🟡, not a real events product
- Student attendance **API** exists, no student Presenze tab — 🔵
- Parent attendance is on **Figli**, not a separate Presenze tab — ✅
- Admin Home is medical attention cards only, not a full operational dashboard
- `locked` weeks are official on mobile; admin cannot lock a week in the UI
- File categories for identity/consent/report cards exist; no consent/pagelle product
- A Teacher **app** account is not staff; the same person may also have a web role
- Hall occupancy is student + teacher only; parent is 403

## FUNCTIONALITY_MATRIX updated: yes

New living passport; first version.

## Admin

Docs + Cursor rule + Development Rules. No PHP/JS/product behavior change.

## Flutter

No change in this task.
