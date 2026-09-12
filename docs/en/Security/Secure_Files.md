# AUB — Secure Files

Project-wide invariant: **all user, person, and internal files are private by default.**

```
User/private/internal file → SecureFileService → aub_private disk → authorization → controlled response
```

Forbidden:

```
UploadedFile → public disk → direct /storage URL
```

Laravel participates in every access to a private file. New features that need a file must register a category in `config/aub-files.php` and use `SecureFileService`. Do not invent a second storage path for Students, Teachers, or a future module.

Public disk remains valid only for genuine public assets (logo, CSS/JS build, marketing). Those assets must not use `SecureFile`.

## Disk

| Disk | Root | HTTP |
|------|------|------|
| `aub_private` | `storage/app/aub-private` (override `AUB_PRIVATE_DISK_ROOT`) | None. No symlink, no nginx alias |
| `aub_legacy_quarantine` | `storage/app/aub-legacy-quarantine` | None. Phase A home for old public objects |

Optional env keys have safe config defaults. Do not put secrets in `.env` for this.

Physical object path is opaque: `objects/{aa}/{uuid}`. Original filename lives only in DB metadata.

## Entity

Table `secure_files` (soft deletes). Polymorphic `attachable` (`student`, `teacher`, `parent`, `user`). Variants: `original`, `thumbnail` (`parent_id`). Source of truth for photos/documents is this table, not `student_photo_path` / `photo_path`.

Helpers: `Student::secureFiles()`, `profilePhoto()`, `secureFile($category)`.

API never serializes `disk`, `path`, `stored_name`, `sha256`, `uploaded_by`.

## Categories

Unknown category: **DENY**. MIME is sniffed server-side (`finfo` on bytes). Client `Content-Type` and filename extension are not trusted. Images are decoded and re-encoded (EXIF/GPS stripped). SVG, HTML, PHP, JS, EXE, ZIP are not allowed.

| category | attachable | actors (view) | mime | max | inline | audit_view |
|----------|------------|---------------|------|-----|--------|------------|
| `profile_photo` | student, teacher, parent, user | admin; staff web; student self; parent linked child; teacher own + assigned students | jpeg, png, webp | 5 MB | yes | no |
| `identity_document` | student, parent | admin; staff with `customers.*` | pdf, jpeg, png | 10 MB | no | yes |
| `medical_document` | student | admin; staff with `customers.*` | pdf, jpeg, png | 10 MB | no | yes |
| `consent_document` | student | admin; staff with `customers.*` | pdf | 10 MB | no | yes |
| `consent_general_regulation` | student | same as consent | pdf | 10 MB | no | yes |
| `consent_minor_entry_exit` | student | same as consent | pdf | 10 MB | no | yes |
| `consent_rights_release` | student | admin; staff with `customers.*` | pdf | 10 MB | no | yes |
| `certificate` | student, teacher | admin; staff with matching CRM page | pdf | 10 MB | no | yes |
| `general_document` | student, teacher, parent | admin; staff with matching CRM page | pdf | 10 MB | no | yes |
| `teacher_document` | teacher | admin; staff with `teachers.*` | pdf | 10 MB | no | yes |
| `report_card` | student | admin; staff with `customers.*` | pdf | 10 MB | no | yes |
| `message_attachment` | student, teacher, parent, user | admin; staff with matching CRM page | pdf, jpeg, png, webp | 10 MB | no | yes |

Mobile actors never receive identity / medical / consent / general documents unless a later task adds an explicit rule. Teacher default for those categories is **DENY**.

## HTTP

| Route | Auth | Notes |
|-------|-------|-------|
| `GET /secure-files/{uuid}` | web session + role | Policy via `FileAccessService`. Unauthorized known UUID → **404** |
| `GET /secure-files/{uuid}/download` | web session + role | Attachment |
| `GET /api/v1/files/{uuid}` | Sanctum + `mobile.actor` | Binary. Unauthenticated → 401. Unauthorized → 404 |

No signed public URLs. No bearer token in the query string.

Headers: `X-Content-Type-Options: nosniff`. `Cache-Control: private` (images) or `private, no-store` (sensitive). Streamed responses.

## Audit

`ActivityLogger` events: `secure_file.uploaded`, `secure_file.replaced`, `secure_file.deleted`, `secure_file.downloaded`, `secure_file.viewed` (only if `audit_view`). Thumbnail / profile-photo GET is not logged.

## Migration

```
php artisan aub:secure-files:migrate --dry-run
php artisan aub:secure-files:migrate
```

Phase A: copy → private → verify SHA-256 → `secure_files` row → clear legacy path columns → **move** public object to quarantine. Phase B (`--purge-legacy`) is optional later. Command is idempotent.

## Architecture guard

`PublicStorageArchitectureGuard` + PHPUnit. Application `app/`, `resources/js/`, `routes/`, `database/seeders/` must not use `disk('public')`, `storePublicly`, `Storage::url`, or `/storage/` except the migrate command whitelist.

## Flutter

`AuthenticatedImage` / `AubAvatar` load `/api/v1/files/{uuid}` through the existing Dio client (Bearer header). Memory cache only. 401 uses global auth handling. Missing/404 → initials.
