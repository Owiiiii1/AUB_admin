# AUB Security & Data Protection Report

**Date:** 20 September 2026

Current security architecture and data protection measures.

This is a **snapshot** of how Accademia Ucraina Ballet’s AUB system protects personal data **today**. It is written for school leadership. Technical names appear in parentheses where they help IT or a future auditor.

**Source of this report:** the live application code, automated security tests, production server checks performed on 20 September 2026, and existing project security rules. Roadmap items are listed only as future steps. They are not described as already in place.

AUB stores data about children. The architecture is therefore built around **least access**: each person sees only what their role and family/academic relationship allow.

---

## 1. Executive summary

AUB does **not** give parents, students, teachers, or the mobile app a direct connection to the database. All use goes through the academy application on a protected web address (`https://aub.owlsolutions.net`).

The mobile app talks only to an authenticated programming interface (API). A person must log in. After login, every request is checked again: is the account still active, is it a student / parent / teacher account, and is it linked to a real profile? If the academy deactivates an account, or the person is no longer linked to a student/parent/teacher record, the previous mobile access token is revoked and does not continue to work.

Private files — child photographs, identity papers, medical certificates, consent PDFs, chat photos, and similar records — are **not** published as open web links. They sit in a private storage area outside the public website folder. The application decides who may see each file. If someone tries to open a file they are not allowed to see, the system answers as if the file did not exist (HTTP 404). It does not confirm that the file is there.

Access is separated by type of user:

- A **parent** sees only children linked through the parent–child relation.
- A **student** sees only their own profile, timetable, and attendance.
- A **teacher** sees only their own lessons, the class roster for those lessons, and profile photos in that academic context. Sensitive documents (identity, medical, consent) are denied to teachers.
- **Administration** works in the staff web panel, with roles and write/delete flags. A teacher’s mobile account is not a staff web login.

Sensitive actions can be recorded in an activity log (login, logout, many create/update/delete operations, attendance updates, and file upload / replace / delete / download; viewing is logged for sensitive document categories).

The connection is encrypted with HTTPS/TLS. Login details, access tokens, and file bytes travel on that encrypted connection. Mobile access tokens are stored in the phone’s protected secure storage (`flutter_secure_storage`), not in ordinary app settings files. External AI provider keys used by the staff tools are stored encrypted in the database and are not sent to the mobile app.

This report does **not** claim that the system is “100% secure” or that a legal GDPR audit has been completed. It describes the protections that are actually running today, and the extra measures that are still planned or recommended.

---

## 2. What data AUB holds

AUB is the academy’s operational system. Depending on the module, it may hold:

| Area | Examples |
|---|---|
| Student identity | Name, class, contact and residence fields used by the academy, profile photo |
| Parent identity | Name, linked children, profile photo |
| Teacher identity | Name, assigned classes/lessons, profile photo |
| Accounts | Email, hashed password, account type, active/inactive flag, staff role (staff only) |
| School life | Weekly schedule, rooms, attendance |
| Communication | Academy and class chats, notices, news |
| Documents | Catalog document slots, downloadable forms |
| Health-related files | Medical certificates (where the module is in use) |
| Prepared categories for later use | Identity, medical, and consent document types in the private-file catalogue |

Mobile screens do **not** receive the full database record. Tax codes, internal notes, file disk paths, staff role flags, and AI configuration are kept off the mobile payload.

---

## 3. Data protection layers

Protection is stacked. An attacker, or a logged-in user looking at the wrong child, must pass each layer.

```text
Internet
    ↓
HTTPS / Nginx
    ↓
Authentication (session or mobile token)
    ↓
Role / actor validation
    ↓
Application authorization
    ↓
Business rules (family and class ownership)
    ↓
Secure storage / database
    ↓
Audit log
```

| Layer | What it does |
|---|---|
| **Internet → HTTPS / Nginx** | Users reach only the public website folder. HTTP is redirected to HTTPS. Private files are not in that folder. |
| **Authentication** | Staff use a browser session after login. Mobile users use a personal access token. Unknown or expired credentials are rejected. |
| **Role / actor validation** | Staff must have an active web role. Mobile users must be an active student, parent, or teacher with a matching profile. The token must be a mobile token. |
| **Application authorization** | Pages, write, and delete follow staff permissions. File and API actions follow dedicated access rules. |
| **Business rules** | Parent–child links, teacher–lesson ownership, and class membership decide whose timetable, attendance, and files appear. |
| **Secure storage / database** | MySQL holds records. Private files sit on a private disk with opaque names. Passwords are hashed. AI keys are encrypted. |
| **Audit** | Selected events are stored so the academy can later see who did what. |

No single layer is the whole defence. The design is **defence in depth**.

---

## 4. Server / infrastructure security

Confirmed on production (`https://aub.owlsolutions.net`, server `178.156.234.23`) on 20 September 2026:

| Item | Current state |
|---|---|
| Operating system | Ubuntu Linux |
| Web server | Nginx 1.24.0 |
| Application runtime | PHP 8.3.6, Laravel 13 |
| Database | MySQL 8.0.46 |
| Deploy account | Linux user `deploy`; application files owned `deploy:www-data` |
| Public web root | `/var/www/aub/public` only |
| Private file disk | `/var/www/aub/storage/app/aub-private` — outside the web root, directory mode `drwxrws---` (`deploy:www-data`) |

Server access is by the dedicated deploy user over SSH. The application is a file tree on the server (not a git checkout). Production `.env` is not overwritten during deploy.

A host firewall service (`ufw`) is reported **active** by the operating system. This audit did **not** enumerate the full firewall rule set.

The following were **not** found as installed or documented, and are therefore **not** claimed here: Fail2ban, SSH two-factor authentication, certificate pinning, backup encryption/retention proof, and a dedicated security-monitoring/alerting product.

---

## 5. HTTPS / transport security

Production traffic uses HTTPS with a Let’s Encrypt certificate for `aub.owlsolutions.net` (Certbot-managed; certificate observed valid from 24 August 2026 to 22 November 2026). Plain HTTP responds with **301** and redirects to HTTPS.

Observed browser cookies for the staff site use the `Secure` flag. The staff session cookie is also `HttpOnly` and `SameSite=Lax`. The server sends `X-Frame-Options: SAMEORIGIN` and `X-Content-Type-Options: nosniff`.

The Flutter release build **refuses to start** unless the API base URL is HTTPS. The default API address is `https://aub.owlsolutions.net/api/v1`. TLS certificate validation is not disabled in the app client. Login passwords, tokens, and file bytes therefore travel on an encrypted HTTPS connection.

**Certificate pinning** (the app trusting only one specific certificate) is **not** implemented. That is an optional extra control, not a missing basic transport protection.

---

## 6. Authentication

### Staff web

Staff open the administration site in a browser.

- Login uses Laravel session authentication. Sessions are stored in the database. Default idle lifetime is 120 minutes.
- The login form is protected against cross-site request forgery (CSRF). A CSRF cookie is issued over HTTPS.
- Inactive accounts cannot stay logged in. Student and parent accounts cannot use the staff site, even if they know a password.
- Access also requires an assigned, active staff role.
- Logout ends the session and is recorded in the activity log.

### Mobile app

The app uses Laravel Sanctum personal access tokens.

- After a successful login the server issues a Bearer token with the `mobile` ability.
- Tokens expire after **43,200 minutes (30 days)** unless a different value is set in server configuration.
- The token is stored in the operating system’s protected secure storage (`flutter_secure_storage`; on iOS, Keychain accessibility `first_unlock_this_device`). It is not kept in ordinary SharedPreferences or plain files.
- Logout revokes the current token. **Logout from all devices** (`logout-all`) is implemented and revokes every token for that user.
- Staff users cannot obtain a mobile session. Inactive, unlinked, or wrong-type accounts receive the same generic login failure as a bad password (the app is not told *why* in a way that would help guessing).

First login can require the person to set their own password (`must_set_password`) before a normal session is issued.

---

## 7. Mobile actor validation

Every authenticated mobile API request is checked again by `EnsureMobileActorIsValid`. In school language:

On **each** request the server reloads the user and asks:

1. Is the account still **active**?
2. Is it a **student, parent, or teacher** account (not staff)?
3. Does a matching **Student / Parent / Teacher** profile still exist?
4. Does this token have the right to use the **mobile** API?

If the answer is no, the current token is deleted. If the account was deactivated, **all** of that user’s tokens are deleted. The client then receives “unauthenticated” (HTTP 401). An old token on a phone does not keep working after the academy turns the account off or removes the person link.

---

## 8. Rate limiting

These limits are in the running configuration:

| Surface | Limit | Purpose |
|---|---|---|
| Mobile login and first-password setup | 5 attempts per minute, keyed by email + IP | Slow down password guessing |
| Authenticated mobile API | 120 requests per minute per user | Limit automated hammering of a logged-in session |
| Public version-check endpoint | 60 requests per minute per IP | Limit unauthenticated automation |
| Staff web login | 5 failed attempts, then a temporary lockout | Slow down browser password guessing |

This is **not** a full anti-DDoS product. It is protection against brute-force and simple automated probing.

---

## 9. Password security

Passwords are **not** stored as readable text. Laravel hashes them before save (`password` cast `hashed`). The framework default hashing driver is bcrypt; production does not override `HASH_DRIVER`.

Password change:

- Staff profile: current password must be confirmed (`current_password`).
- Mobile: current password must be confirmed; new password minimum 8 characters, with confirmation.
- First-time mobile password setup uses the same minimum length.

The staff tools can store an **encrypted** recovery copy (`admin_visible_password`) when an administrator sets a password for a person. That value is encrypted at rest, hidden from normal page data, redacted from activity-log payloads, and cleared when the person changes their own password. It is an operational recovery aid, not a mobile-app field.

---

## 10. Role-based access control

Three different ideas must not be mixed:

| Concept | Meaning |
|---|---|
| **Account type** | `student` / `parent` / `teacher` / `staff` — who the person is in the academy |
| **Staff web role** | Menu and page rights inside administration |
| **Write / delete flags** | `can_write` / `can_delete` — whether that staff user may change or remove records |

**Student, Parent, and Teacher are not web roles.** A teacher who uses the app has no administration website access unless a separate staff account with a role exists.

Staff website:

- Page-level checks (`role.assigned`, `role.access`).
- Administrators see the full menu.
- Other staff see the menu items assigned to their role.
- Some operational academy routes (courses/groups, weekly schedule, communication, news, documents, notifications, profile, secure-file delivery) are currently available to **any staff user who has a role**, not only via the role menu. Customer, teacher-directory, and settings pages follow the role menu more strictly.
- Last-administrator protection prevents deleting or switching off the last admin role/user (`AdministratorLockoutGuard`).
- Inactive staff cannot log in.

**Field-level ACL** for ordinary CRM fields (hiding individual form fields from some staff) is **not** fully implemented. File access is already field/category-specific; general CRM field hiding is not.

---

## 11. Child / parent data isolation

This is the core protection for family data. Examples from the live API and tests:

### Parent

- The parent profile returns only children linked through `student_parent`.
- Asking for another family’s child (schedule, attendance, documents, medical certificate, photo) returns **not found**, not a detailed “access denied”.
- The parent cannot use that response to confirm that the other child exists in the system.

### Student

- Profile, timetable, and attendance are **self only**.
- Another student’s file or record is **not found**.

### Teacher

- Timetable: own scheduled lessons.
- Attendance: only lessons that belong to that teacher; another teacher’s lesson is **not found**.
- Roster: students of the relevant class.
- Profile photos: own photo, plus photos of students they teach (class program or scheduled lessons).
- Identity / medical / consent files: **denied**.

Staff cannot hold a mobile session, so they cannot call these family APIs with a teacher/parent token.

---

## 12. Secure Files Foundation

AUB previously used the usual public-storage pattern (files under a public disk and `/storage/...` URLs). That approach was replaced with a **private-file architecture** for every person and internal file.

```text
User / person / internal file
    ↓
SecureFileService
    ↓
Private storage (aub_private)
    ↓
FileAccessService
    ↓
Authenticated endpoint
    ↓
Only an authorized user receives the bytes
```

How a file is stored:

- Disk name: `aub_private`.
- Physical location: `storage/app/aub-private`, **outside** the public web root. Production directory is not world-readable.
- Object name on disk: `objects/{first two characters of uuid}/{uuid}` — not the child’s name, not the original filename.
- The original filename is kept only as database metadata.
- Each object has a UUID. The physical path is never sent to mobile clients.
- SHA-256 checksum is stored.
- The server detects the real MIME type (`finfo`); it does not trust the filename or the phone’s Content-Type.
- Only whitelisted types per category are accepted (for photos: JPEG, PNG, WebP; documents also PDF where configured).
- Images are decoded on the server, orientation is applied, then the image is **re-encoded**. Embedded camera metadata (EXIF) is stripped by that rebuild.
- Dangerous types are blocked, including SVG, HTML, JavaScript, PHP, executables, scripts, and ZIP.
- Replace is atomic in the application sense: the old file is kept until the new one is valid; then previous versions are removed (soft-delete on the database record).
- Thumbnails, when allowed, are also private files — not public URLs.
- There is **no** direct `/storage/...` URL for person files.

Unknown file categories are denied.

---

## 13. File access control

| User | What they may access |
|---|---|
| **Administrator** | Person and internal files, subject to being an active admin |
| **Other staff** | According to page permission and category. Viewing profile photos is allowed for staff who can open the relevant person page. Uploading/replacing needs write permission; deleting needs delete permission |
| **Parent** | Own profile photo (view and replace); linked children’s profile photos (view); catalog documents and medical certificates of **linked children** (view/upload/replace). Identity documents of a child are **not** opened through the parent file API today |
| **Student** | Own profile photo (view/download only — no self-upload in the file ACL). Not other students’ photos. Not identity/medical/consent files |
| **Teacher** | Own profile photo (view/replace); profile photos of students they teach (view only). Sensitive documents denied |
| **News / forms / chat** | News images only for the matching audience; form templates as downloadable academy forms; chat photos only inside a chat the user belongs to |

If a known UUID is requested by someone who is not allowed to see it, the answer is **404**, the same as for a random unknown UUID. The system does not confirm that the file exists.

Unauthenticated mobile file requests return **401**.

---

## 14. File security validation

The server does **not** trust:

- the file extension;
- the original filename;
- the Content-Type sent by the phone or browser.

It inspects the real bytes, checks size limits (for example 5 MB for profile photos), and for images rebuilds a clean picture. Active or executable formats are rejected. Tests cover a PHP file disguised as a JPEG, SVG, and oversized uploads.

Architecture supports later antivirus integration. The current scanner component is a placeholder (`NullFileSecurityScanner`).

**Architecture supports integration of antivirus scanning; external malware scanner is not yet enabled.**

---

## 15. Mobile secure media

The mobile app does **not** receive a public image URL.

Photos are loaded through the same authenticated API client, as `GET /api/v1/files/{uuid}`, with the Bearer token in the **Authorization header**. The token is:

- not added to the query string of the URL;
- not written into ordinary activity-log property dumps (token / authorization / password / API key fields are redacted);
- used only by the existing API client.

Decoded image bytes are kept in **memory only** for the current app session. A persistent on-disk cache of private photos is not used.

---

## 16. Database security

- Engine: **MySQL**.
- The application connects with application credentials. Parents, students, teachers, and the mobile app **never** connect to MySQL themselves.
- Relations (parent–child, class membership, scheduled lessons) are stored as database links and enforced again in application code.
- Production database name: `aub`. Automated tests are forced onto a separate database: `aub_test`.

**TestDatabaseGuard** refuses to run the feature-test suite if the connection is not `mysql` / `aub_test`. That prevents a developer from accidentally pointing tests at production data.

---

## 17. API data minimization

Mobile responses are **whitelists**, not “send the whole record”.

`/me` returns id, name, email, account type, and a small profile (name, photo UUID/URL, and for students a short set of contact/class fields). It does **not** return password hashes, remember tokens, tax codes, medical notes, document disk paths, staff `role_id`, `can_write`, `can_delete`, or AI settings.

Automated tests assert that `tax_code` does not appear in those payloads.

Principle: **only data required for the specific mobile operation is returned.**

---

## 18. Activity / audit logging

AUB keeps an activity log for many important events. This is **not** a claim that every click in the product is audited.

Currently logged (non-exhaustive, from code):

- Staff login and logout; mobile login, logout, and logout-all.
- Many administration create / update / delete operations (users, roles, courses/groups, schedule, academy settings, AI settings, app versions, actor accounts, and others).
- Teacher attendance updates.
- Secure files: upload, replace, delete, download; **view** is logged for sensitive categories (`audit_view`), not for ordinary profile-photo views.

Passwords, tokens, API keys, and the admin recovery password field are stripped from log payloads.

---

## 19. AI / external services

Staff can configure optional AI providers (OpenAI, Anthropic, Gemini) in the administration settings.

- Provider API keys are stored **encrypted at rest** (`api_key` encrypted cast).
- Keys are **not** sent to mobile clients.
- The settings page is part of the staff administration UI (administrator settings), not the family app.

This report does **not** state that the AI providers are given children’s files or full student records as a general feature. AI is a staff configuration/tooling capability.

---

## 20. Application security controls

AUB uses the Laravel framework’s normal protections, together with academy-specific rules:

- Input validation on forms and API requests.
- Database access through the ORM / query builder rather than string-concatenated SQL from user input.
- CSRF tokens on the staff website.
- Authentication middleware on staff pages and Sanctum on the mobile API.
- Route middleware for role, write, and delete.
- Rate limiting (section 8).
- File validation (sections 12–14).
- No sensitive disk paths in API JSON.
- API errors use a consistent JSON shape; unauthorized file access looks like “not found” so existence is not leaked.

These are **framework and application controls**. They reduce common risks (for example injection and cross-site request forgery). They are not a promise that every class of attack is impossible.

---

## 21. Secure-by-default development rules

Protection is also built into how new features are written.

A project rule (`.cursor/rules/aub-secure-files.mdc`) is always applied during development. It requires:

- no public storage for person or internal files;
- an explicit file category in `config/aub-files.php`;
- storage only through `SecureFileService`;
- authorization only through `FileAccessService`;
- no bearer token inside a file URL;
- no trust of filename or client MIME type;
- child photos must be decoded and re-encoded on the server.

An **architecture guard** test (`PublicStorageArchitectureGuard` / `SecureFileArchitectureGuardTest`) scans application code. If a developer reintroduces forbidden public-storage patterns (`disk('public')`, `storePublicly`, `Storage::url`, `/storage/` for app code), the test suite fails.

The academy’s file protection is therefore not only “how files work today”. It is also a **process control** against accidental regression.

---

## 22. Automated security testing

Backend feature tests that specifically exercise isolation and file safety include:

| Test area | What is checked |
|---|---|
| `ApiFoundationTest` | Mobile login rules, inactive/staff/unlinked rejection, `/me` whitelist, parent isolation |
| `AccountIdentityLayerTest` | Account type vs web role, inactive web login |
| `AccountApiTest` | Password change, photo, unrelated access → not found, tax code absent |
| `ScheduleApiTest` | Own schedule only; unrelated child → not found |
| `AttendanceApiTest` / `AttendanceHistoryApiTest` | Teacher owns the lesson; unrelated parent → not found |
| `SecureFilesTest` | Public disk unused, opaque path, fake JPEG/PHP/SVG/oversized rejected, unknown category denied, parent/student/teacher file matrix, unknown UUID 404, unauthenticated 401, inactive actor denied, sensitive view audited |
| `SecureFileArchitectureGuardTest` | Forbidden public-storage patterns |
| `TestDatabaseGuardTest` | Tests cannot target the production database name |
| `ProfilePhotoApiTest` | Photo upload rules |
| `ChildDocumentsApiTest` / `MedicalCertificateTest` | Parent-scoped documents and certificates |
| `PasswordSetupTest` | First-password flow |
| `ClassChatTest` / `ChatApiTest` | Chat membership isolation |

This list is the **current test files**, not a marketing count of assertions. A single total of “N tests / M assertions” is not quoted here because that number changes with every suite run.

---

## 23. Production security verification

**Live checks on 20 September 2026** against `https://aub.owlsolutions.net`:

| Check | Result |
|---|---|
| HTTPS homepage | 200, Nginx 1.24.0, secure session cookie, `X-Frame-Options` / `X-Content-Type-Options` |
| `http://` homepage | 301 → `https://aub.owlsolutions.net/` |
| `GET /api/v1/me` without token | 401 |
| `GET /api/v1/files/{uuid}` without token | 401 |
| `GET /storage/` | 403 (not a public file listing) |
| Private disk vs web root | `aub-private` exists outside `/public` |

**Authenticated file-ACL matrix** (own parent photo 200, unrelated parent 404, student other file 404, teacher sensitive document 404, authenticated mobile photo 200) is encoded in `SecureFilesTest` and related API tests. Those tests are the repeatable proof of the 404-without-existence-leak behaviour.

A separate named “production smoke report” file was **not** present in the documentation tree at the time of this snapshot. The evidence above is live transport/auth probes plus the automated matrix.

---

## 24. Security matrix

| Area | Technology / mechanism | Protects against | Status |
|---|---|---|---|
| Transport | HTTPS/TLS (Let’s Encrypt) | Traffic interception on the network | ✅ Active |
| HTTP → HTTPS | Nginx 301 redirect | Accidental plain-text visits | ✅ Active |
| Mobile auth | Laravel Sanctum Bearer tokens | Unauthorized API use | ✅ Active |
| Web auth | Session + CSRF | Unauthorized staff use / forged staff forms | ✅ Active |
| Actor checks | `EnsureMobileActorIsValid` | Stale tokens after deactivation or unlink | ✅ Active |
| Private files | `aub_private` + `SecureFileService` | Direct file URL exposure | ✅ Active |
| File ACL | `FileAccessService` | Cross-family and cross-role file access | ✅ Active |
| File validation | MIME sniffing, type whitelist, image re-encode | Disguised or dangerous uploads | ✅ Active |
| Mobile secrets | `flutter_secure_storage` | Token sitting in a plain settings file | ✅ Active |
| Password storage | Laravel password hashing | Readable passwords in the database | ✅ Active |
| AI keys | Encrypted application storage | Keys in plaintext columns / mobile app | ✅ Active |
| Rate limits | Login and API throttles | Simple brute-force / automation | ✅ Active |
| Test isolation | `TestDatabaseGuard` | Tests running against production data | ✅ Active |
| Regression guard | Public-storage architecture test | Accidental return to public file URLs | ✅ Active |
| Audit | Activity log | Untraceable sensitive changes | 🟡 Partial (selected events, not every click) |
| Staff field ACL | — | Hiding individual CRM fields from some staff | ⚪ Planned |
| Admin 2FA | — | Stolen staff password reuse | ⚪ Planned |
| Malware scanner | Placeholder only | Malicious content beyond type checks | ⚪ Planned |
| Certificate pinning | — | Extra trust restriction on the mobile TLS channel | ⚪ Optional |
| Fail2ban / SSH 2FA | — | Host login hardening | ⚪ Not documented as present |

---

## 25. User type security summary

| User | Identity isolation | Schedule | Attendance | Files |
|---|---|---|---|---|
| **Administration** | Staff identity; full academy records according to role | Full staff schedule tools | Staff tools / teacher app is separate | Private-file ACL; admin may access person files |
| **Student** | Own profile only | Own timetable | Own history | Own profile photo (view); chat/news/forms as allowed; no sensitive docs |
| **Parent** | Own profile + linked children only | Linked children only | Linked children only | Own + children’s photos; children’s catalog docs and medical certificates; not other families |
| **Teacher** | Own teacher profile | Own lessons | Own lessons only | Own photo + taught students’ photos; sensitive documents denied |

**Boundaries in one sentence each:**

- Administration works in the web panel; it is not the same account as a parent or student app login.
- A student cannot browse other pupils.
- A parent cannot browse other families; a wrong child id looks like “not found”.
- A teacher cannot mark another teacher’s lesson or open identity/medical/consent files.

---

## 26. Defence-in-depth diagram

### General request

```text
Client (browser or app)
    ↓ HTTPS
Nginx
    ↓
Laravel authentication
    ↓
Actor / role / permission validation
    ↓
Business ownership rules
    ↓
Data / API whitelist
    ↓
MySQL / private file storage
    ↓
Audit log
```

### Mobile private file

```text
Flutter app
    ↓ Bearer token + HTTPS
/api/v1/files/{uuid}
    ↓
Sanctum
    ↓
Mobile actor validation
    ↓
FileAccessService
    ↓
aub_private disk
```

---

## 27. Known gaps / next security steps

AUB already has a serious baseline for a school product that holds children’s data. The following items are **not** presented as already done.

| Measure | Status | Note |
|---|---|---|
| Administrator two-factor authentication (2FA) | **Planned** | Extra protection if a staff password is reused or leaked |
| Field-level ACL for ordinary CRM fields | **Planned** | Page/role ACL exists; per-field hiding for staff is not complete |
| Stricter staff action permissions | **Recommended** | Some operational routes are open to any staff member with a role |
| External malware / antivirus scanner | **Planned** | Type and image checks exist; ClamAV-class scanning is not enabled |
| Certificate pinning in the mobile app | **Not yet required** | Optional extra; HTTPS validation is already on |
| Backup encryption and retention verification | **Recommended** | Not verified in this snapshot |
| SSH hardening (key policy, 2FA) | **Recommended** | Deploy SSH exists; extra host hardening not documented here |
| Fail2ban or equivalent | **Recommended** | Not installed on the checked host |
| Security monitoring / alerting | **Recommended** | No dedicated alerting product confirmed |
| Formal consent / GDPR product workflow | **Planned** | File categories exist; a full consent module is not the current product |
| Formal data retention / deletion policy | **Recommended** | Soft-delete exists for files; a published retention schedule was not found |
| Production `APP_DEBUG` / `APP_ENV` | **Recommended** | On 20 September 2026 production still had `APP_ENV=local` and `APP_DEBUG=true`. Debug mode can expose detailed errors. This should be turned off for a public site (`APP_ENV=production`, `APP_DEBUG=false`) as an operations hardening step |

None of the gaps above mean “the current controls are unused”. They mean the academy should treat security as an ongoing programme, not a finished stamp.

---

## 28. GDPR / child data

**The architecture is designed to support GDPR-oriented access minimization and protection of personal data.**

This document does **not** state that AUB is “fully GDPR compliant”. A legal audit, records-of-processing, and school-side policies (lawful basis, retention, processor contracts) are outside what the code alone can certify.

Technical mechanisms that **help** a GDPR-oriented practice:

| Mechanism | How it helps |
|---|---|
| Least data on the mobile API | Phones do not download the whole pupil record |
| Authenticated access only | No anonymous browse of family data |
| Private storage | Photos and documents are not open URLs |
| Relationship checks | Parent sees linked children; teacher sees own academic context |
| 404 instead of 403 on files and foreign children | Avoids confirming that a record exists |
| Audit of sensitive file download/view | Traceability for documents |
| File soft-delete / replace lifecycle | Old objects can be retired in the database |
| Inactive account and token revoke | Access can be stopped when a person leaves |
| Future consent / identity / medical categories | Already registered as private, sensitive types |

---

## 29. What happens on unauthorized access (short)

| Attempt | Typical result |
|---|---|
| Open the API or a file without login | 401 (mobile) or redirect / 404 (web file) |
| Use a deactivated or unlinked mobile account | Token revoked, 401 |
| Parent requests another family’s child | 404 not found |
| Student requests another student’s data/file | 404 |
| Teacher opens another teacher’s attendance or a sensitive document | 404 |
| Guess a file UUID | 404, same as a real UUID the user may not see |
| Upload a disguised executable / SVG / oversized photo | Rejected (validation error) |
| Staff login as a parent/student account | Refused |
| Too many login tries | Temporary throttle / lockout |

---

## Technical appendix

Components **actually used** in AUB today:

- Laravel 13 (PHP 8.3)
- Laravel Sanctum (mobile personal access tokens, 30-day expiry, `mobile` ability)
- MySQL 8 (production `aub`, tests `aub_test`)
- Nginx 1.24 + HTTPS/TLS (Let’s Encrypt / Certbot)
- `flutter_secure_storage` (mobile token)
- Release-build HTTPS gate in the Flutter app
- `SecureFileService` + private disk `aub_private`
- `FileAccessService` (actor × category × action)
- SHA-256 object checksums
- Server MIME detection (`finfo`)
- Image decode / re-encode with EXIF stripping (`ImageNormalizer`)
- UUID opaque object names
- Soft-delete on `SecureFile`
- RBAC: account type, staff role, `can_write` / `can_delete`, last-admin guard
- CSRF on the staff website
- Throttling: `api-login` 5/min, `api-mobile` 120/min, `api-public` 60/min, web login 5-attempt lockout
- `EnsureMobileActorIsValid`
- Activity log (`ActivityLogger`), with secret-field redaction
- `TestDatabaseGuard`
- `PublicStorageArchitectureGuard` architecture test
- Encrypted AI provider keys
- Encrypted admin recovery password field (`admin_visible_password`)
- `.cursor/rules/aub-secure-files.mdc` development rule

---

*End of snapshot — 20 September 2026. This file is a meeting report, not the living product matrix.*
