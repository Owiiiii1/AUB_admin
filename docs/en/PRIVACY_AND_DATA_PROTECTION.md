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
| Location (future) | Teacher check-in lat/lng/accuracy/timestamp | Personal; not collected today |
| AI keys | `ai_provider_settings.api_key` | Secrets (encrypted at rest in Laravel) |
| Logs | IP, user agent on `activity_logs` | Limited personal |

Consent records are **planned**: target `ConsentType`, `ConsentDocumentVersion`, `ConsentRecord`. Possible types: privacy; data processing; photo/video; marketing; special activity. Not implemented.

## Children’s data

Digital channels (web + future Flutter) must record consent where academy policy and applicable law require it. For a minor, consent is linked to a parent/guardian **where required**. Do **not** hardcode a specific age threshold in the domain model until a legal / compliance review. **Not implemented.**

Student files are stored on the private `aub_private` disk via `SecureFileService`. Legacy public objects are quarantined by `php artisan aub:secure-files:migrate`. See [Security/Secure_Files.md](Security/Secure_Files.md).

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

### Security Foundation (required before wide mobile rollout — not implemented)

Raise these from “later improvements” to **mandatory foundation**:

- Private storage for children’s documents — **done** (`aub_private` + `SecureFileService`)
- Access/view audit of sensitive files — **done** for file categories with `audit_view`
- API authorization matrix for files — **done** (`FileAccessService`)
- Field-level ACL
- Scoped teacher access (assigned children/groups only)
- Access/view audit of sensitive data
- API authorization matrix
- Mobile token security
- **2FA for administrative staff**
- Consent / privacy records (`ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`; do not hardcode an age threshold)

Plus (still required, listed separately): subject access export / erasure workflows.

Do not list RBAC or “access logging” as planned if the reader might think nothing exists — RBAC and CRUD logs **exist**; they are incomplete.

## AI

Schedule AI sends **preferences / operational schedule data** to the configured provider. Keys must never appear in docs. If personal data is sent, DPIA may be required — not documented per field today.

## App Store / Flutter

Repository `Owiiiii1/AUB_app` exists. Publication still needs a Privacy Policy URL, accurate data disclosures, and parental consent for minors where law/policy require it (age threshold — after legal review). Token design: Sanctum PAT, contract [API.md](API.md). Refresh/re-auth UX remains OPEN.

## Retention

Undefined with the academy. Typical ranges (guidance only): enrollment + legal period; financial often 10 years in Italy; logs 90–365 days.

## Legal docs

| Document | Status |
|----------|--------|
| This technical note | Current as of 2026-09-08 |
| Public Privacy Policy / ToS / DPA / cookie policy | Not created |

Update this file when modules collect new personal data (including future teacher geolocation check-in), roles change, Flutter ships, or AI starts sending personal fields.

See [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md) for privacy/consent product questions.
