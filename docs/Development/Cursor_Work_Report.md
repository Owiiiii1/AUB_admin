# Cursor Work Report

## Task

One-time client security snapshot: `docs/Product/SECURITY_REPORT_CLIENT.md` for Accademia Ucraina Ballet leadership (not living product docs).

## Audit sources

- Laravel routes (`web.php`, `api.php`, `owl-admin-pages.php`), middleware, Sanctum, `EnsureMobileActorIsValid`, `User` actor/web gates
- Rate limiters in `AppServiceProvider` / `config/aub.php`; web `LoginRequest` lockout
- Password hashing casts; `admin_visible_password` encrypted; AI `api_key` encrypted
- `config/aub-files.php`, `SecureFileService`, `FileAccessService`, `ImageNormalizer`, `NullFileSecurityScanner`
- Flutter `SecureTokenStorage`, HTTPS release gate, `AuthenticatedImage` memory cache, Bearer header (not query)
- `TestDatabaseGuard`, `PublicStorageArchitectureGuard`, feature tests (`SecureFilesTest`, API isolation tests)
- `.cursor/rules/aub-secure-files.mdc`
- Production 20 Sep 2026: HTTPS headers, HTTP 301, API/files 401, `/storage/` 403, `aub-private` outside web root, Let’s Encrypt, Nginx 1.24, `APP_ENV=local` / `APP_DEBUG=true`, fail2ban absent, ufw service active

## Delivered

- Client report: data held, layers, infra, transport, auth, actor validation, throttles, passwords, RBAC, family isolation, secure files, ACL, validation, mobile media, DB, API minimization, audit, AI, app controls, development rules, tests, production probes, matrices, diagrams, known gaps, GDPR wording without “fully compliant”, technical appendix
- No application/production code changed

## Classification (detailed capability rows, excluding the executive summary)

Docs-only snapshot. Product behaviour unchanged.

FUNCTIONALITY_MATRIX updated: no

Reason: one-time security report; no user-visible capability, route, permission, or access-control change.

## Confirmed controls (short)

HTTPS/TLS, Sanctum + session/CSRF, mobile actor re-check + token revoke, parent/student/teacher isolation with 404, `aub_private` + FileAccessService, MIME/image rebuild, secure token storage, hashed passwords, encrypted AI keys, TestDatabaseGuard, architecture guard, selected activity logging

## Known gaps recorded (not fixed)

Admin 2FA, field-level CRM ACL, broader staff route tightening, malware scanner, pinning (optional), backup encryption proof, SSH extra hardening, fail2ban, monitoring, consent/GDPR product workflow, retention policy, production `APP_DEBUG`/`APP_ENV`

## Ambiguous / easy-to-misread items

- Staff “always allowed” academy routes are wider than the role menu; file bytes still go through FileAccessService
- Parent file API allows catalog + medical for linked children, not identity_document
- No named historical production smoke-report file; live probes + tests used instead
