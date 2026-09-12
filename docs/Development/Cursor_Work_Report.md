# Cursor Work Report

## Task

Global Secure Files Foundation: one private-file mechanism for every AUB user/person/internal file (not Student-only).

## Legacy audit (actual code)

All personal files used Laravel `disk('public')` → `storage/app/public` → `/storage/{path}` via `storage:link`.

| Source | Path / column | Planned category |
|--------|---------------|------------------|
| `StudentsController::storeUploadedFiles` | `students/{id}/documents` → `student_photo_path`, `parent_id_document_path`, `general_regulation_form_path`, `minor_entry_exit_form_path`, `rights_release_form_path` | `profile_photo`, `identity_document`, `consent_*` |
| `TeachersController::storeUploadedPhoto` | `teachers/{id}/photos` → `photo_path` | `profile_photo` |
| `TeachersSeeder` | `teachers/{id}/photos/avatar.jpg` | `profile_photo` |
| `MobileDemoDataService` | `students/{id}/portrait.jpg`, `portraits/students/{id}.jpg`, `teachers/{id}/photos/avatar.jpg` | (stopped writing public paths) |
| `CustomersController` leftover | same public store as students | neutralized (`[]`, no Storage) |
| API | `StudentProfileResource`, `TeacherAttendanceService` `Storage::disk('public')->url()` | authenticated `/api/v1/files/{uuid}` |
| Admin JS | `/storage/${path}` on Students, Teachers, CoursesGroups | `/secure-files/{uuid}` |

No MIME sniff, unsafe replace (delete old first), guessable paths, no auth on file bytes.

## Production legacy paths found

26 DB references, 26 physical files, 0 missing, 0 conflicts. All `profile_photo`. No identity/consent documents on production at migrate time.

| Type | IDs | Legacy path pattern |
|------|------|---------------------|
| Student photos | 8–13 | `portraits/students/{id}.jpg` |
| Teacher photos | 4–23 | `teachers/{id}/photos/avatar.jpg` |

## Migrated

Phase A `php artisan aub:secure-files:migrate` (no `--purge-legacy`): 26 migrated, 0 conflicts. Second run: 0 db_references (idempotent). Legacy path columns nulled. Public objects moved to `storage/app/aub-legacy-quarantine`. 26 originals + 26 thumbnails = 52 private objects. PHPUnit orphan objects (103) removed from production disk after isolating test roots.

## What landed

- Disk `aub_private` (`storage/app/aub-private`) + `aub_legacy_quarantine`. No public visibility, no nginx alias. Dirs `2770` `deploy:www-data`, files `0640` (php-fpm can read; Flysystem 0600/0700 overridden).
- Table `secure_files` (uuid, morph attachable, category, variant, opaque path, sha256, SoftDeletes).
- `config/aub-files.php` category registry; unknown = DENY.
- `SecureFileService` (sniff, size, re-encode images, opaque name, hash, replace-after-validate, soft delete).
- `FileAccessService` category-aware ACL. Unauthorized known UUID → 404. Unauthenticated API → 401.
- Web `GET /secure-files/{uuid}` (+ `/download`). API `GET /api/v1/files/{uuid}`.
- `php artisan aub:secure-files:migrate` (`--dry-run`, Phase A quarantine, not default purge).
- Architecture guard `PublicStorageArchitectureGuard` + PHPUnit.
- Flutter `AuthenticatedImage` / `AubAvatar` via existing Dio Bearer. No token in URL. Memory cache only.
- Docs + `.cursor/rules/aub-secure-files.mdc`.
- PHPUnit disks isolated to `/tmp/aub-phpunit-*`.

## DB schema

`secure_files`: id, uuid, attachable_type/id, category, variant, parent_id, disk, path, original_name, stored_name, mime_type, extension, size_bytes, sha256, uploaded_by, timestamps, deleted_at.

Morph map: `student`, `teacher`, `parent`, `user`.

## Category registry

See `docs/en/Security/Secure_Files.md`. Unknown category DENY. Images jpeg/png/webp; documents pdf (identity/medical also jpeg/png). No SVG/php/html/js/exe/zip.

## ACL (foundation)

- Admin: full within existing RBAC.
- Staff web: `profile_photo` view for any web role (courses-groups avatars). Sensitive student docs: `customers.*`. Teacher files: `teachers.*`. Mutations: `can_write` / `can_delete`.
- Teacher: own `profile_photo`; student `profile_photo` only if class_lesson_teacher or scheduled_lessons + academy_class_student. Sensitive student docs: DENY.
- Parent: linked children `profile_photo` only. Identity/consent: DENY even for own child.
- Student: self `profile_photo` only.
- Inactive: DENY.

## Web / API endpoints

- Web (session + RBAC + policy): `GET /secure-files/{uuid}`, `GET /secure-files/{uuid}/download`
- API: `GET /api/v1/files/{uuid}` (`auth:sanctum` + `mobile.actor`). Binary. No signed URLs. No token in query.

## Flutter

`lib/core/media/authenticated_image.dart`, `api_client_scope.dart`, `ApiClient.getBytes`. Student / Parent / Teacher UI unchanged except authenticated media. Tests: 112 passed, `flutter analyze` clean. Commit `55b9963 feat: support authenticated secure media`.

## Architecture guard

`tests/Feature/SecureFileArchitectureGuardTest.php` scans `app/`, `resources/js/`, `routes/`, `database/seeders/`. Whitelist: migrate command + the guard class. Fixture: `tests/Fixtures/Architecture/ForbiddenPublicStorageSample.php`.

## Tests

Backend: `tests/Feature/SecureFilesTest.php`, `SecureFileArchitectureGuardTest.php`, updated `/me` key lists. Production `aub_test`: **118 passed** (924 assertions). Flutter: 112 passed.

## Production dry run

```
db_references 26
physical_found 26
missing 0
conflicts 0
planned categories {"profile_photo":26}
```

## Production migration

Phase A completed. Idempotent remigrate empty. `.env` not changed.

## Production smoke (2026-09-12)

| Check | Result |
|-------|--------|
| Admin authorized image (controller + FileAccessService) | 200, `nosniff`, `Cache-Control: private` |
| Parent own allowed photo | 200 |
| Unrelated parent | 404 |
| Student own photo | 200 |
| Student other file | 404 |
| Teacher sensitive identity doc | 404 |
| Mobile authenticated photo | 200 |
| Unauthenticated API | 401 |
| Unauthenticated web | 302 (login) |
| Legacy `/storage/portraits/students/8.jpg` | 403 (not served) |
| Legacy `/storage/teachers/4/photos/avatar.jpg` | 403 (not served) |

Smoke tokens/users/temp identity doc removed. No leftover `smoke-%` accounts.

## Cleanup status

Phase A: quarantine kept, `--purge-legacy` **not** run. Empty public `portraits/students` directory remains. `storage:link` remains for genuine public website assets. Empty `objects/{aa}` prefix dirs from PHPUnit may remain after orphan file delete.

## Known remaining risks

- Field-level ACL for non-file CRM fields is still not this stage.
- ClamAV not wired (`NullFileSecurityScanner` hook).
- Quarantine is not purged; leftover empty public directories may exist.
- `storage:link` remains for genuine public website assets.
- Legacy `/storage/...` missing objects currently return **403** from nginx (not 404); bytes are not served.
- Teacher photos in `/me` are new keys (`photo_url`, `photo`); Flutter ignores extras except where wired.
- SVG/DOCX still forbidden until an explicit future category change.
- Local Windows workstation has no PHP; PHPUnit runs on the production host against `aub_test` only.
