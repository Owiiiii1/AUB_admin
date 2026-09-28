# AUB Functionality Matrix

Last updated: 2026-09-24
Source of truth: actual codebase and deployed product state.

This document describes what each user type can currently do in AUB.
It must be updated whenever functionality, permissions, routes, APIs, or UI capabilities change.

Actor types on mobile: **Student**, **Parent**, **Teacher**.
**Administration** is staff web access (`account_type = staff`, or a Teacher who also has an assigned web role). A Teacher mobile account is not an administrator.

Status key:

| Mark | Meaning |
|------|---------|
| ✅ Implemented | Usable today through UI and/or the intended API |
| 🟡 Partial | UI or API exists, but the flow is incomplete |
| 🔵 Foundation / backend ready | Data, storage, or API exists; no usable product UI for this actor |
| ⚪ Planned | Agreed or discussed; not delivered |
| 🚫 Not available / intentionally restricted | Out of scope for this actor, or blocked by design |

## Client-ready summary

Administration can sign in on the website, manage students, parents and teachers, build courses and classes, publish the weekly timetable, run academy chat and notices, publish news, keep required documents and medical certificates, and configure staff accounts, halls, the lesson catalog and the mobile app versions.

Students can sign in to the app, see their next lesson and news, open their personal timetable (today first, including rehearsals), search whether a hall is free today, chat with the academy, read notices, open upcoming events (description, role, rehearsals), and manage their profile, password and devices. They cannot change their photo, upload documents, or mark attendance. Tickets, costume, and photo-gallery contents are still stubs.

Parents can sign in to the app, see linked children, open each child’s timetable, this month’s attendance and upcoming events, upload the documents the academy asked for (including the medical certificate), chat with the academy, and read notices. They cannot search halls and cannot see other families’ children.

Teachers can sign in to the app, see the next lesson and news, open their own timetable, search halls, start an assigned lesson with a GPS check-in, record present / absent / excused for a lesson they open, and open Teaching to see their groups, a short student profile, a private note about the student, attendance totals, and their rehearsal slots. They can send a student a personal notice with optional confirmation. They cannot edit the timetable, see family documents, or change another person’s private files. A teacher only uses the website if the academy also gave them a staff role. The report card button does nothing yet.

---

## Executive summary

| Area | Administration | Student | Parent | Teacher |
|---|---|---|---|---|
| Authentication | ✅ | ✅ | ✅ | ✅ |
| Profile / account | ✅ | ✅ | ✅ | ✅ |
| Force-update gate | ✅ | ✅ | ✅ | ✅ |
| Students directory | ✅ | 🚫 | 🟡 own children | 🟡 own roster |
| Teachers directory | ✅ | 🚫 | 🚫 | 🚫 self only |
| Academic structure | ✅ | 🟡 own class | 🟡 child’s class | 🟡 own classes |
| Weekly schedule | ✅ | ✅ | ✅ | ✅ |
| Hall occupancy | 🟡 rooms in settings | ✅ | 🚫 | ✅ |
| Attendance | 🟡 teacher lesson journal | 🔵 | ✅ | ✅ |
| Required documents | ✅ | 🚫 | ✅ | 🚫 |
| Medical certificate | ✅ | 🟡 notices only | ✅ | 🚫 |
| Academy chat | ✅ | ✅ | ✅ | ✅ |
| Class group chats | ✅ | ✅ | 🚫 | ✅ |
| Broadcast notices | ✅ | ✅ | ✅ | ✅ |
| News | ✅ | ✅ | ✅ | ✅ |
| Secure files | ✅ | 🟡 | 🟡 | 🟡 |
| Events / shows | 🟡 | 🟡 | 🟡 | 🚫 |
| Payments | ⚪ | ⚪ | ⚪ | ⚪ |
| Teacher daily check-in | ⚪ | 🚫 | 🚫 | ⚪ |
| Settings / AI / versions | ✅ | 🚫 | 🚫 | 🚫 |
| Activity log | ✅ | 🚫 | 🚫 | 🚫 |

---

## Administration

Staff website at `https://staff.accademiaucraina.it`. Session login at `/`. No self-serve password reset. No admin 2FA.

Write actions need `can_write`. Deletes need `can_delete`. Role create/update/delete also needs the administrator role. Page access follows the assigned role’s menu plus a shared extra list (courses, schedule, chat, notices, news, documents, placeholders).

A Teacher who also has a web role can open the CRM. Student and Parent accounts cannot.

### Access / Accounts

| Function | Status | Channel | Notes |
|---|---|---|---|
| Staff login / logout | ✅ | Web | `/`, `POST /logout` |
| Self-serve forgot password | 🚫 | Web | Intentionally absent; staff reset actor passwords instead |
| Admin 2FA | ⚪ | Web | Not built |
| Roles and page permissions | ✅ | Web | Settings → Roles; menu keys in `role_menu_items` |
| Staff user management | ✅ | Web | Settings → Users; staff/admin accounts only |
| Student / parent / teacher app accounts | ✅ | Web | Create, link, unlink, enable, disable on the person card |
| Change / reveal / reset actor password | ✅ | Web | Reset forces create-password on next app login |
| Active / inactive accounts | ✅ | Web | Inactive cannot use web or mobile |
| Own staff profile and password | ✅ | Web | `/profile` |
| Workplace landing | 🟡 | Web | Thin non-admin landing; not an operational desk |

### Students

| Function | Status | Channel | Notes |
|---|---|---|---|
| List, prefix search, pagination | ✅ | Web | `/customers`; 20 per page |
| Create / edit | ✅ | Web | Questionnaire plus per-block modals |
| Delete | ✅ | Web | Password confirm; files removed with the card |
| Archive / enrollment history | ⚪ | Web | One active class only; no transfer history UI |
| Parents (father / mother) | ✅ | Web | Stored as parent records with relation type |
| Class assignment | ✅ | Web | Courses & groups; one class per student |
| App account linking | ✅ | Web | On the student card |
| Profile photo | ✅ | Web | Private file; served via `/secure-files/{uuid}` |
| Required document slots | ✅ | Web | Catalog types enabled per student with `+` |
| Medical certificate review | ✅ | Web | File-only upload; AI fills expiry; accept / reject |
| Identity / consent PDF cabinet | 🔵 | Backend foundation | Categories exist; no dedicated consent product UI |
| Field-level hiding of parent contacts | 🚫 | Web | Any role that can open students sees parent contacts and medical block |

### Teachers

| Function | Status | Channel | Notes |
|---|---|---|---|
| List, prefix search, create / edit / delete | ✅ | Web | `/teachers` |
| App account linking | ✅ | Web | Same account actions as students |
| Profile photo | ✅ | Web | Private file |
| Lesson / class assignment | ✅ | Web | Catalog eligibility and class-lesson teachers |
| Teacher document cabinet | 🔵 | Backend foundation | Category registered; no teacher-docs UI |

### Academic structure

| Function | Status | Channel | Notes |
|---|---|---|---|
| Courses and classes | ✅ | Web | `/courses-groups`; school tabs |
| Attach / detach students and lessons | ✅ | Web | Current academic year |
| Lesson catalog | ✅ | Web | Settings → Academy → Lessons |
| Buildings and rooms | ✅ | Web | Settings → Academy → Halls |
| Academic year admin | 🟡 | Web | Year is created/used automatically; no dedicated year screen |
| Academy “general” settings | 🟡 | Web | Placeholder copy on the general tab |

### Schedule

| Function | Status | Channel | Notes |
|---|---|---|---|
| Weekly board (day / week, fullscreen) | ✅ | Web | `/schedule-service` |
| Create / edit / duplicate / delete slots | ✅ | Web | After first publish, move / teacher change / cancel set mobile statuses |
| Restore original slot | ✅ | Web | From the published snapshot |
| Copy previous week / clear timeline | ✅ | Web | |
| Publish | ✅ | Web | Official week for mobile |
| Lock week | 🔵 | Backend foundation | `locked` is treated as official on mobile; admin UI does not set it |
| Conflict check | ✅ | Web | `/schedule-service/{week}/conflicts` |
| AI-assisted placement | ✅ | Web | Hybrid planner; not a one-click “fix everything” |
| PDF export | ⚪ | Web | Button present, disabled (“coming soon”) |
| Personal notice on timetable change | ✅ | Web + Mobile | Students of the class who have an app account; body is structured (lesson, group, when, room, teacher) and the app shows line breaks |

### Attendance

| Function | Status | Channel | Notes |
|---|---|---|---|
| View / edit attendance in admin | 🔵 | Backend foundation | Rows exist; no admin attendance screen |
| Teacher marking | ✅ | Mobile | See Teacher |
| Parent history | ✅ | Mobile | See Parent |

### Communications

| Function | Status | Channel | Notes |
|---|---|---|---|
| Academy chat (student / parent / teacher) | ✅ | Web + Mobile | Shared staff unread |
| Class group chats | ✅ | Web + Mobile | Admin + student + teacher; parent 🚫; per-staff unread; not in Communication menu badge |
| Broadcast notices | ✅ | Web + Mobile | Personal or all; optional “I have read”; app shows paragraphs and line breaks |
| News per audience | ✅ | Web + Mobile | TipTap + cover image |
| Home attention inbox | ✅ | Web | Open medical review / renewal cards |
| Tasks / Problems | ⚪ | Web | Not built |

### Files / Security

| Function | Status | Channel | Notes |
|---|---|---|---|
| Private person files | ✅ | Web + Mobile | `aub_private`; never a public `/storage` URL |
| Category ACL | ✅ | Backend foundation | Unknown category = deny |
| Activity log | ✅ | Web | CRUD + login/logout + file events; not a full view-audit of every GET |
| View-audit of sensitive records | 🟡 | Backend foundation | Sensitive categories can log view/download; no dedicated audit UI |

### Settings

| Function | Status | Channel | Notes |
|---|---|---|---|
| Staff users, roles | ✅ | Web | Roles: administrator only for mutations |
| AI providers | ✅ | Web | Encrypted keys; activate / check |
| Mobile store links and allowed versions | ✅ | Web | Settings → App |
| UI locales it / uk / en / ru | ✅ | Web | |

### Events

| Function | Status | Channel | Notes |
|---|---|---|---|
| Event cards and Generale | 🟡 | Web | Course + academic year, one date/time, cover and extra photos, visibility toggle |
| Rehearsals | ✅ | Web | Date, time, one hall, one teacher. Not written into `scheduled_lessons` |
| Students and role | 🟡 | Web | Future roster follows the course; past participation is frozen. Role image and text. Costume block is a stub |

### Placeholders (menu visible, product not delivered)

| Function | Status | Channel | Notes |
|---|---|---|---|
| Archive | ⚪ | Web | Coming soon page |
| Costume service | ⚪ | Web | Coming soon page; may stay a separate service |

### Administration restrictions

- 🚫 Cannot use the mobile app login (`staff` is not a mobile actor)
- 🚫 No field-level privacy: opening Students shows parent contacts and medical data
- 🚫 No self-serve password reset, no 2FA
- 🚫 Kit CRM (orders / services / staff / calendar) is unrouted leftover, not product

---

## Student

Mobile app. Bottom navigation: **Home / Orario / Eventi / Profilo**.

### Account

| Function | Status | Channel | Notes |
|---|---|---|---|
| Login, session restore, logout | ✅ | Mobile | Sanctum; force-update check first; login copy is Italian only; footer OwlSolutions opens https://owlsolutions.net |
| Create password after admin reset | ✅ | Mobile | Screen copy is Italian only |
| Profile (name, class, phone, birth date, address) | ✅ | Mobile | Contact fields read-only |
| Change password | ✅ | Mobile | Other devices signed out |
| Devices (list / revoke) | ✅ | Mobile | Current device uses logout |
| Language | ✅ | Mobile | Local only (it / uk / en / ru); login / create-password stay Italian |
| Informazioni | ✅ | Mobile | Logo, large centered version + release date, OwlSolutions link |
| Push preference | 🟡 | Mobile | Local toggle only; FCM not delivered |
| Upload own photo | 🚫 | Mobile | Photo is set in admin; student may only view it |
| Face ID / forgot-password form | 🚫 | Mobile | Secretariat copy on login |

### Home

| Function | Status | Channel | Notes |
|---|---|---|---|
| Greeting | ✅ | Mobile | From `/me` |
| Next lesson | ✅ | Mobile | Current published week |
| News carousel | ✅ | Mobile | Student audience only |
| Today’s full list on Home | 🚫 | Mobile | Intentionally not on Home; use Orario |
| Attendance summary on Home | 🚫 | Mobile | Not on Home |

### Schedule

| Function | Status | Channel | Notes |
|---|---|---|---|
| Own class week | ✅ | Mobile | Published / locked weeks only |
| Today selected by default | ✅ | Mobile | Full week via “Questa settimana” |
| Previous / next week | ✅ | Mobile | |
| Moved / cancelled / changed badges | ✅ | Mobile | |
| Teacher and location | ✅ | Mobile | |
| Hall info (free / busy today) | ✅ | Mobile | Bottom of Orario |
| Edit timetable | 🚫 | Mobile | |

### Attendance

| Function | Status | Channel | Notes |
|---|---|---|---|
| Own monthly history | 🔵 | API | `GET /attendance` works; no Presenze tab in the student app |
| Mark own presence | 🚫 | Mobile | Teacher-only |

### Communications and media

| Function | Status | Channel | Notes |
|---|---|---|---|
| Academy chat | ✅ | Mobile | Header picker: academy + own class |
| Class group chat | ✅ | Mobile | Members of the enrolled class with an app account |
| Broadcast inbox + required ack | ✅ | Mobile | Bell; blocking “Ho letto” when required; line breaks / paragraphs |
| Authenticated profile / news / chat images | ✅ | Mobile | `GET /files/{uuid}` with the session |
| Required documents | 🚫 | Mobile | Parent uploads; student has no Documenti screen |
| Medical certificate | 🟡 | Mobile | Can receive renewal notices; cannot upload the file |

### Events

| Function | Status | Channel | Notes |
|---|---|---|---|
| Upcoming events | 🟡 | Mobile | Cards for today and later; description, photos, own role, rehearsals |
| Fotogalleria | 🟡 | Mobile | Buttons for past events the student still belongs to; gallery content is a stub |
| Tickets and costume | 🟡 | Mobile | Honest “in development” blocks, no payment data |
| Rehearsals in Orario | ✅ | Mobile | Merged into the student week only; parent and teacher schedules stay lessons |

### Student restrictions

- 🚫 Cannot open another student’s schedule or files
- 🚫 Cannot upload identity, medical, or catalog documents
- 🚫 Cannot search halls as a parent would — hall search **is** allowed (student + teacher only)
- 🚫 Cannot use the staff website
- 🚫 Cannot see unpublished / draft timetable work

---

## Parent

Mobile app. Bottom navigation: **Home / Figli / Orario / Profilo**.

### Account

| Function | Status | Channel | Notes |
|---|---|---|---|
| Login, session restore, logout | ✅ | Mobile | Login copy is Italian only |
| Profile, password, devices, language | ✅ | Mobile | Same pattern as student; login stays Italian |
| Own photo upload | ✅ | Mobile | Optional |
| Push preference | 🟡 | Mobile | Local only |

### Children

| Function | Status | Channel | Notes |
|---|---|---|---|
| Linked children | ✅ | Mobile | Via `student_parent` |
| Switch child | ✅ | Mobile | Reloads that child’s schedule / documents |
| Child summary (name, class, photo) | ✅ | Mobile | Figli |

### Home

| Function | Status | Channel | Notes |
|---|---|---|---|
| Greeting and enrolled-child count | ✅ | Mobile | |
| News carousel | ✅ | Mobile | Parent audience |
| Medical-certificate alert | ✅ | Mobile | Missing / in review / renewal |
| Missing catalog-document warning | ✅ | Mobile | **Vai** opens that child’s Documenti |
| Child cards / aggregated upcoming on Home | 🚫 | Mobile | Intentionally not on Home; use Figli / Orario |

### Schedule

| Function | Status | Channel | Notes |
|---|---|---|---|
| Per-child week | ✅ | Mobile | Today first; prev / next; statuses |
| Hall occupancy | 🚫 | Mobile | API and button are student/teacher only |

### Attendance

| Function | Status | Channel | Notes |
|---|---|---|---|
| Child month summary and recent records | ✅ | Mobile | Figli; `present` / `absent` / `excused`; no row ≠ absent |
| Justify an absence | 🚫 | Mobile | Not built |

### Documents

| Function | Status | Channel | Notes |
|---|---|---|---|
| Required slots for the selected child | ✅ | Mobile | Status, file name, form download, upload |
| Medical certificate upload | ✅ | Mobile | Dedicated block; AI review happens in admin |
| Payments / consents product | ⚪ | Mobile | Not built (file categories exist only as storage rules) |

### Events

| Function | Status | Channel | Notes |
|---|---|---|---|
| Child events | 🟡 | Mobile | Figli: upcoming cards and detail for the selected child. Same stubs as the student (tickets, costume, gallery contents) |

### Communications and media

| Function | Status | Channel | Notes |
|---|---|---|---|
| Academy chat | ✅ | Mobile | Header opens the academy thread |
| Class group chats | 🚫 | Mobile | Not a member |
| Broadcasts + required ack | ✅ | Mobile | Line breaks / paragraphs |
| Child photo view | ✅ | Mobile | Linked children only |
| Other families’ data | 🚫 | Mobile | Unknown child id → not found |

### Parent restrictions

- 🚫 Cannot open a student who is not linked
- 🚫 Cannot search halls
- 🚫 Cannot mark attendance
- 🚫 Cannot edit the timetable
- 🚫 Cannot join class group chats
- 🚫 Cannot use the staff website
- 🚫 Cannot see identity-document / generic medical_document / consent cabinets unless a later task opens them (medical **certificate** is allowed)

---

## Teacher

Mobile app. Bottom navigation: **Oggi / Orario / Processo didattico / Profilo**.

A teacher app account is **not** staff. Website access exists only if the same person also has a staff role.

### Account

| Function | Status | Channel | Notes |
|---|---|---|---|
| Login, session restore, logout | ✅ | Mobile | Login copy is Italian only |
| Profile, password, devices, language | ✅ | Mobile | Role shown as Docente; login stays Italian |
| Own photo upload | ✅ | Mobile | |
| Push preference | 🟡 | Mobile | Local only |

### Today

| Function | Status | Channel | Notes |
|---|---|---|---|
| Greeting and next lesson | ✅ | Mobile | Today’s lesson list is not on Home. Cancelled lessons are not “next” |
| News carousel | ✅ | Mobile | Teacher audience |
| Fake “attendance complete” flags | 🚫 | Mobile | Intentionally absent |

### Schedule

| Function | Status | Channel | Notes |
|---|---|---|---|
| Own lessons across classes | ✅ | Mobile | Today first; week navigation; class, room, statuses. A lesson that has not started shows a “Lesson not started” badge; a lesson whose end time has passed without check-in shows a red “Lesson missed” badge. A checked-in lesson shows “On lesson” on the schedule card as well, and the schedule reloads when the roster closes |
| Hall info | ✅ | Mobile | Same as student |
| Create / move / cancel lessons | 🚫 | Mobile | Admin schedule board only |

### Teaching and attendance

| Function | Status | Channel | Notes |
|---|---|---|---|
| Teaching tab: own groups and students | ✅ | Mobile | Only groups of students assigned to this teacher. Row opens a short profile: photo, name, age, group, attendance totals, and a private note only that teacher can read and edit. Report card button is present and does nothing |
| Message a student | ✅ | Mobile | Personal notice with optional read confirmation. Lands in the admin messages table and in the student’s inbox, with the teacher as sender |
| Own rehearsal slots | ✅ | Mobile | Rehearsals where this teacher is assigned |
| Report card | 🟡 | Mobile | Button is visible and does not open anything |
| Current-week worklist | 🚫 | Mobile | Presenze tab removed. Marking stays on the opened lesson |
| Roster: present / absent / excused / unmarked | ✅ | Mobile | Unmarked = no row |
| Mark all present, local draft, explicit save | ✅ | Mobile | |
| Unsaved-back warning | ✅ | Mobile | |
| Cancelled lesson read-only | ✅ | Mobile | |
| Start lesson GPS check-in | ✅ | Mobile | Button on the opened lesson. Server checks the assigned teacher, the academy “minutes before start” window until the lesson ends (0 means any time), hall coordinates, GPS accuracy, and the academy radius. Student marks stay independent. “On lesson” remains on the schedule card after reload |

### Communications and media

| Function | Status | Channel | Notes |
|---|---|---|---|
| Academy chat | ✅ | Mobile | Header picker: academy + program groups |
| Class group chats | ✅ | Mobile | `class_lesson_teacher` with an app account |
| Broadcasts + required ack | ✅ | Mobile | Line breaks / paragraphs |
| Own photo | ✅ | Mobile | |
| Assigned students’ profile photos | ✅ | Mobile | Roster / classes they teach |
| Student documents, medical certificate, parent data | 🚫 | Mobile | Denied by file ACL |

### Teacher lesson check-in

| Function | Status | Channel | Notes |
|---|---|---|---|
| Lesson GPS check-in | ✅ | Mobile + admin | Hall location is one field: coordinates or a Google Maps link, or a point picked on a map. The map opens at the editor’s location and draws the academy check-in radius around the point. One academy radius and minutes-before-start (0 = any time) are in Settings → Academy → General. A hall row opens the editor; deletion asks for DELETE. Teacher directory shows completed / attended / missed. Profile opens the lesson journal. Cancelled lessons stay out of the totals |

### Teacher restrictions

- 🚫 Cannot access student identity documents, medical certificates, or parent private files
- 🚫 Cannot see another teacher’s lessons or mark another teacher’s roster
- 🚫 Cannot modify schedules from the app
- 🚫 Cannot use hall occupancy as a parent would — hall search **is** allowed
- 🚫 Staff-only website tools stay unavailable unless a web role is assigned separately

---

## Permissions / Restrictions (cross-cutting)

| Rule | Status |
|---|---|
| Mobile login is `student` / `parent` / `teacher` only | ✅ |
| Staff cannot obtain a mobile session | ✅ |
| Student / parent cannot open the CRM | ✅ |
| Teacher CRM access requires a separate web role | ✅ |
| Unpublished timetable drafts are hidden from mobile | ✅ |
| Unknown file category is denied | ✅ |
| Unauthorized file UUID looks like 404, not 403 | ✅ |
| Parent 404 for unrelated children (no existence leak) | ✅ |
| No field-level staff privacy on student cards | 🚫 not implemented |
| FCM / calendar sync / offline cache / certificate pinning | 🚫 not implemented |

---

## Planned / next major capabilities

These are **not** implemented. Do not present them to the client as available.

- Secretariat operational improvements (full dashboard, tasks, richer Home)
- Tasks / Problems
- Final Assessment / Pagelle / report cards
- Payments / invoices
- Consent / GDPR product (signed versions, records) — storage categories exist only as foundation
- Event tickets, costume payments, and photo-gallery contents (admin and student Eventi are otherwise usable)
- Costume service
- Teacher daily check-in (GPS snapshot + geofence; decided, not built)
- Student Presenze tab (history API exists)
- Admin attendance screen
- Schedule PDF export
- Week lock control in admin
- Academic-year management UI
- Enrollment transfers / history
- Admin 2FA and self-serve password reset
- Push notification delivery (FCM)
- Reporting beyond the activity log
