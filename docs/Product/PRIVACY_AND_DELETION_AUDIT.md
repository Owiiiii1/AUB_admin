# Privacy and deletion audit

Internal. This page is not published. The public text is `/privacy` and `/data-deletion`. Those pages describe only what this file and the code do.

Last reviewed: 2026-09-28.

The controller name used on the public pages is **Accademia Ucraina Ballet**, because that is the name supplied for the academy. The project does not contain a registered company name, address, VAT, PEC, privacy email, or DPO. Those fields stay empty. OwlSolutions is not the controller. OwlSolutions is the technical provider when developers access the server, database, or admin.

## Data inventory

### Student

Stored on `students` when the field is filled: first and last name, display name, gender, tax code, birth date, birth place, residence address, city, postal code, email, phone, course links, medical-certificate expiry, notes, status. Class membership is `academy_class_student`. Schedule rows point at the class and the lesson, not at a separate student copy. Attendance marks stay on the lesson roster. `teacher_student_notes` is a private note from one teacher about one student. Document slots and uploaded files hang off the student. `student_medical_certificates` plus `medical_document` files hang off the student. Profile photo is a secure file in category `profile_photo`.

### Parent

`parents`: first name, last name, email, phone, tax code, notes. The link to a child is `student_parent` (`relation_type`). A parent account is a `users` row with `account_type = parent`. Chat messages use `author_id`. Notice read and acknowledgement rows stay on the campaign recipient. Files uploaded for a child are attached to the student, not to the parent. A parent profile photo or identity document, when stored, is attached to the parent.

### Teacher

`teachers`: type, names, email, phone, tax code, description, photo. Lessons and class-lesson links point at the teacher id. Attendance the teacher saved stays on the lesson. `teacher_lesson_check_ins` stores the fact of a check-in and, until deletion, latitude, longitude, accuracy, distance, and device name. Private notes are `teacher_student_notes`.

### Staff

`users` with `account_type = staff`: name, email, password hash, optional encrypted `admin_visible_password`, role, active flag. `activity_logs` store the staff user id, action, subject, IP address, and user agent. There is no in-app deletion for staff.

### Accounts, sessions, files, other

- `users` for every login. `personal_access_tokens` are the app devices. `sessions` are the web sessions. `password_reset_tokens` exist if a reset was started. There is no separate push-token table.
- `secure_files` for profile photos, identity documents, medical documents, consent scans, certificates, and chat attachments. Bytes are outside the public disk.
- `chats` and `chat_messages` (soft deleted rows can still hold text until completion).
- `notice_recipients` for read and acknowledgement.
- News and events are academy content. A read position is not a separate personal archive beyond notices and chats.
- AI: `ai_provider_settings` holds an encrypted API key. If a provider is switched on, a medical-certificate file or a schedule prompt can be sent to OpenAI, Gemini, or Anthropic. The project does not record which provider is on in production, or the country of that provider.
- Mail: the default mailer is `log`. The deletion form does not send email.
- Hosting: the live site is `staff.accademiaucraina.it`. The contract, legal name of the host, and country of the server are not in the project.

## Deletion rules

A public form or the app creates `account_deletion_requests` with status `pending`. Neither one deletes academy records. The app also deletes the caller’s Sanctum tokens, so that session ends. The account stays active until an administrator completes the request.

The public form does not store the visitor IP on the request. It is not written to `activity_logs`. Rate limiting uses a short-lived cache key. The same success page is shown whether or not the email matches an account. A filled honeypot field shows the same page and stores nothing.

Completion runs `AccountDeletionService` for the linked user’s real `account_type`, not the role typed on the form. If no user is linked, the request is marked completed and nothing else is deleted.

### Removed when an administrator completes the request

| Actor | Removed | Why this is safe |
|---|---|---|
| All linked accounts | Password, email, name on `users`, sessions, Sanctum tokens, password-reset rows, `admin_visible_password`. `is_active` becomes false | The person must not be able to sign in |
| All linked accounts | Chat message body becomes `[removed]`. Files on those messages are purged, including soft-deleted messages | The text is that account’s own content. The chat thread stays |
| Student | Profile photo file | It is only the login portrait |
| Parent | Parent name, email, phone, tax code, notes. Parent profile photo and identity document | This is the guardian’s own card. The child is not part of it |
| Teacher | Teacher name, email, phone, tax code, description, profile photo. Notes that teacher wrote. GPS and device name on that teacher’s check-ins | The lesson row and the attendance marks do not need the phone or the coordinates |
| Staff, only if an administrator completes a matched request | Login fields, as for any user | There is no self-service path |

### Kept, and why

The academy has not defined a retention period. The code therefore does not invent one and does not erase records that other people or the school file still need.

| Kept | Reason recorded in the product |
|---|---|
| Student card, including name, birth date, address, tax code, contacts | No retention period is defined for the educational file |
| Class and course links | The timetable still points at a student |
| Attendance | It is the class record |
| Student documents, consent scans, medical certificate and its file | No retention period is defined. A parent request does not remove them |
| Notes other teachers wrote about a student | They are not the student’s login |
| The child, and the parent-child link, when the parent is removed | Deleting a parent must not delete the student |
| Notice read and acknowledgement rows | They are the send history |
| Lessons, the teacher row, and attendance the teacher saved | The history still needs a teacher id |
| The check-in row without coordinates | The fact that a check-in happened stays |
| Certificate files stored on the teacher | They are not the profile photo |
| `activity_logs` | They record administrative actions, including the deletion itself |

Anonymized markers are `Removed`, `deleted-user-{id}@account.invalid`, and `[removed]`. They are not a claim that the person is unidentifiable inside records that were kept on purpose.

## Store mapping

| Requirement | What exists |
|---|---|
| Google Play external deletion URL | `https://staff.accademiaucraina.it/data-deletion` accepts a request without the app |
| Apple and Google in-app path | Profile and the header menu: Privacy e dati → Elimina account. Password, checkbox, and a second confirmation |
| Account can be closed | After completion the user is inactive, the password is replaced, and tokens are gone. Login with the old email fails |

## Unresolved

- Registered legal name, address, VAT, PEC, privacy contact email, and whether a DPO exists
- Retention periods, including whether a student file must stay after the student leaves
- Which AI provider, if any, is enabled in production, and where it processes data
- Hosting provider’s legal name, role, and country
- A real email provider. Mail is the log driver, so the form does not send a confirmation
