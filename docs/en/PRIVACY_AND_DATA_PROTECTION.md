# AUB — Privacy and Data Protection

> Technical/project notes, **not** final legal text. Counsel must review before App Store / public policies.

AUB processes **personal data** and **children’s data**. Interfaces: web admin, staff workplaces, future Flutter (`AUB_app`) via HTTPS API (API not built).

## Role-based access as a privacy requirement

RBAC is required for data minimization. **Web RBAC exists.** Field-level rules and Flutter API authorization **do not**.

- Staff should see only what their job needs — **intent**, not fully enforced
- Teachers should not see payments or parent contacts unless granted — **not implemented**
- Parent/student Flutter access must be limited to own data — **no API yet**

## Controller / processor

| Role | Entity |
|------|--------|
| Data Controller | The academy (AUB) |
| Data Processor | OwlSolutions / developers when they access server, DB, backups, admin |

DPA still recommended; not a system feature.

## Data types

| Category | Examples | Sensitivity |
|----------|----------|-------------|
| Staff | Names, emails of users/teachers | Personal |
| Students | Names, birth data, addresses on `customers` | **Children’s data** |
| Parents | Embedded father/mother fields | Personal |
| Health-adjacent | Medical certificate expiry, document scans | May be **special category** |
| AI keys | `ai_provider_settings.api_key` | Secrets (encrypted at rest in Laravel) |
| Logs | IP, user agent on `activity_logs` | Limited personal |

Consent records and privacy-policy versions are **planned entities only**.

## Children’s data

Italy: under 14, online consent generally needs a parent/guardian. Digital channels (web + future Flutter) must record consent. **Not implemented.**

Student files are stored on the Laravel **public** disk (`students/{id}/documents`). Treat as a **privacy gap** (URLs may be guessable/public if the symlink is live). Do not describe this as private storage.

## GDPR principles vs reality

| Principle | Reality |
|-----------|---------|
| Transparency | No public Privacy Policy yet |
| Minimization | RBAC exists; field-level visibility **does not** |
| Accuracy | Edit workflows exist; view-audit **does not** |
| Storage limitation | Retention policy not defined |
| Integrity | HTTPS, hashed passwords, CSRF on web |

## Technical measures

### Implemented

- HTTPS (`https://aub.owlsolutions.net`)
- Session authentication (web)
- Password hashing
- CSRF (Laravel + Inertia)
- `.env` not in git
- Encrypted AI API keys in DB
- Role-based **menu/route** access (`role.*` middleware)
- `can_write` / `can_delete`
- CRUD activity logging (`ActivityLogger`) — not a full access/view audit

### Requires follow-up (not implemented)

- Private storage for children’s documents
- Field-level access
- Teacher → only assigned students
- Access/view audit for sensitive records
- Consent / privacy-policy entities
- Mobile token security
- API authorization matrix
- Subject access export / erasure workflows

Do not list RBAC or “access logging” as planned if the reader might think nothing exists — RBAC and CRUD logs **exist**; they are incomplete.

## AI

Schedule AI sends **preferences / operational schedule data** to the configured provider. Keys must never appear in docs. If personal data is sent, DPIA may be required — not documented per field today.

## App Store / Flutter

Repository `Owiiiii1/AUB_app` exists. Publication still needs a Privacy Policy URL, accurate data disclosures, and parental consent for minors. Token design is part of API Foundation (Sanctum is a candidate only).

## Retention

Undefined with the academy. Typical ranges (guidance only): enrollment + legal period; financial often 10 years in Italy; logs 90–365 days.

## Legal docs

| Document | Status |
|----------|--------|
| This technical note | Current as of 2026-09-07 |
| Public Privacy Policy / ToS / DPA / cookie policy | Not created |

Update this file when modules collect new personal data, roles change, Flutter ships, or AI starts sending personal fields.
