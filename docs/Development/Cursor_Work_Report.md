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

## What landed

- Disk `aub_private` (`storage/app/aub-private`) + `aub_legacy_quarantine`. No public visibility, no nginx alias.
- Table `secure_files` (uuid, morph attachable, category, variant, opaque path, sha256, SoftDeletes).
- `config/aub-files.php` category registry; unknown = DENY.
- `SecureFileService` (sniff, size, re-encode images, opaque name, hash, replace-after-validate, soft delete).
- `FileAccessService` category-aware ACL. Unauthorized known UUID → 404. Unauthenticated API → 401.
- Web `GET /secure-files/{uuid}` (+ `/download`). API `GET /api/v1/files/{uuid}`.
- `php artisan aub:secure-files:migrate` (`--dry-run`, Phase A quarantine, not default purge).
- Architecture guard `PublicStorageArchitectureGuard` + PHPUnit.
- Flutter `AuthenticatedImage` / `AubAvatar` via existing Dio Bearer. No token in URL. Memory cache only.
- Docs + `.cursor/rules/aub-secure-files.mdc`.

## DB schema

`secure_files`: id, uuid, attachable_type/id, category, variant, parent_id, disk, path, original_name, stored_name, mime_type, extension, size_bytes, sha256, uploaded_by, timestamps, deleted_at.

Morph map: `student`, `teacher`, `parent`, `user`.

## ACL (foundation)

- Admin: full within existing RBAC.
- Staff web: `profile_photo` view for any web role (courses-groups avatars). Sensitive student docs: `customers.*`. Teacher files: `teachers.*`. Mutations: `can_write` / `can_delete`.
- Teacher: own `profile_photo`; student `profile_photo` only if class_lesson_teacher or scheduled_lessons + academy_class_student. Sensitive student docs: DENY.
- Parent: linked children `profile_photo` only. Identity/consent: DENY even for own child.
- Student: self `profile_photo` only.
- Inactive: DENY.

## Flutter

`lib/core/media/authenticated_image.dart`, `api_client_scope.dart`, `ApiClient.getBytes`. Student / Parent / Teacher UI unchanged except authenticated media. Tests: 112 passed, `flutter analyze` clean.

## Tests

Backend: `tests/Feature/SecureFilesTest.php`, `SecureFileArchitectureGuardTest.php`, updated `/me` key lists. Run on production `aub_test` after deploy (no local PHP on the Windows workstation).

## Production

See the follow-up section filled after deploy: dry-run counts, real migrate, smoke, cleanup (Phase A quarantine, no `--purge-legacy` in this task).

## Known remaining risks

- Field-level ACL for non-file CRM fields is still not this stage.
- ClamAV not wired (`NullFileSecurityScanner` hook).
- Quarantine is not purged; leftover public directories may exist empty.
- `storage:link` remains for genuine public website assets.
- Teacher photos in `/me` are new keys (`photo_url`, `photo`); Flutter ignores extras except where wired.
- SVG/DOCX still forbidden until an explicit future category change.
