# AUB — Open questions

Product and architecture questions that are **not decided**. Assumptions are labelled. Do not treat PRELIMINARY as DECIDED.

Status values: **OPEN** | **PRELIMINARY** | **DECIDED**.

Companion: [MODULE_ROADMAP.md](MODULE_ROADMAP.md), [ARCHITECTURE.md](ARCHITECTURE.md), [DATA_MODEL_DRAFT.md](DATA_MODEL_DRAFT.md).

---

## Decided (already approved)

| Decision | Status |
|----------|--------|
| `AUB_admin` is the central CRM / backend core | DECIDED |
| `AUB_app` is the Flutter client | DECIDED |
| Clients talk to the core only over HTTPS API | DECIDED |
| GitHub repositories are separate (`Owiiiii1/AUB_admin`, `Owiiiii1/AUB_app`) | DECIDED |
| Flutter and backend may evolve in parallel; the API contract joins them | DECIDED |
| Parent / Student must **not** become administrative web RBAC roles merely because they need login | DECIDED (identity vs RBAC) |

---

## Core Data Model

### Unique enrollment: one CourseGroup per student

- **Question:** May a child belong to several courses / disciplines / CourseGroups at once, or only one?
- **Why it matters:** Table `course_group_customer` has **unique `customer_id`**. The database currently allows **only one CourseGroup per student in total**. If the academy allows multiple concurrent groups, the constraint is wrong for the business.
- **Current assumption:** None as a product rule. The unique index is an **implemented technical constraint**, not a confirmed academy rule.
- **Status:** OPEN — **HIGH PRIORITY**. Do not change the migration until PM / academy answers.

### Dedicated `students` / `parents` tables vs `customers`

- **Question:** When (if ever) to migrate off interim `customers`?
- **Why it matters:** Flutter identities, enrollments, grades, productions all hang off “student”.
- **Current assumption:** Interim `customers` remains until an explicit migration task.
- **Status:** OPEN

---

## Enrollment

- **Question:** Enrollment statuses, transfer rules, history, start/end dates?
- **Why it matters:** Current pivot has no workflow.
- **Current assumption:** None.
- **Status:** OPEN

---

## Identity & Accounts

- **Question:** Actor/identity model. Example (not chosen): `User` → Teacher profile / Parent profile / Student profile — or another design.
- **Why it matters:** `User` exists for **web** auth; `Teacher` is not linked; Parent/Student entities do not exist; Flutter API does not exist. Authentication identity ≠ administrative RBAC role.
- **Sub-questions:**
  - Can one User have several actor profiles?
  - Parent with several children?
  - Teacher who is also a parent?
  - Older student with their own account?
  - Who creates the account? Invitation / activation workflow?
- **Current assumption:** None for the identity graph. Flutter “modes” in older docs were a sketch, not a decision.
- **Status:** OPEN

---

## Roles & Access

- **Question:** Final field-level matrix (admin / secretariat / teacher / parent / student) and scoped teacher access (only assigned groups/children).
- **Why it matters:** Today any role that can open `customers.*` sees all student fields. `always_allowed_route_patterns` is wider than role menu.
- **Current assumption:** Intent matrix in [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md) is **not** code.
- **Status:** OPEN

Security items below are **mandatory foundation before a wide mobile rollout** (not implemented): private storage for children’s files; field-level ACL; scoped teacher access; access/view audit of sensitive data; API authorization matrix; mobile token security; **2FA for administrative staff**; consent/privacy records.

---

## Student Attendance

- **Question:** How is a child’s presence marked on a **specific lesson / rehearsal / session**? Who marks it? Which statuses (present / absent / late / excused)?
- **Why it matters:** This is **not** Teacher Check-in. Mixing the two domains will produce the wrong product.
- **Current assumption:** None. Module not built.
- **Status:** OPEN

---

## Teacher Check-in / Staff Presence

**Domain:** proof that a teacher is **physically at the academy**. Distinct from Student Attendance.

### Mechanism (PRELIMINARY)

1. Teacher opens Flutter.
2. Taps **Пришёл** / Check-in (conscious action).
3. App requests geolocation **once** (not background tracking).
4. Backend receives latitude, longitude, accuracy, timestamp.
5. Backend checks academy **geofence**.
6. On success, a check-in record is created.

Academy locations conceptually: latitude, longitude, allowed radius. Several buildings/sites.

Preliminary statuses: `on_time`, `late`, `manual`, `rejected`.

Requirements to design later: audit; admin manual confirm/correct; GPS accuracy handling; multi-site.

QR is a **possible fallback/alternative**, **not** the primary candidate.

### Open (not decided)

| Question | Why it matters | Assumption | Status |
|----------|----------------|------------|--------|
| Check-in bound to **work day/shift** or to a **specific scheduled lesson/session**? | Data model, UX, late/on_time meaning | None | OPEN |
| Check-in time window | Prevents early/late noise | None | OPEN |
| Allowed geofence radius | False rejects vs cheating | None | OPEN |
| Allowed GPS accuracy | Indoor GPS is poor | None | OPEN |
| Anti-spoofing | Fake location apps | None | OPEN |
| Extra signals (Wi-Fi / QR / device attestation)? | Security vs friction | QR = fallback only (PRELIMINARY) | OPEN except QR-not-primary |

---

## Grades / Report Cards (Academic Progress)

Large module, **no final DB**. Preliminary scope: scores; teacher comments; by subject/discipline; by academic period; interim vs final; report card; change history; who set/changed and when; student view; parent view; teacher limited to allowed subjects/groups.

| Question | Why it matters | Status |
|----------|----------------|--------|
| Grading system (numbers / letters / levels / text) and range | Schema and UI | OPEN — academy discovery |
| Exams exist? | Calendar + grades | OPEN |
| Periods: semester / trimester / quadrimester / other? | Report cards | OPEN |
| Different systems per course? | Flexibility vs complexity | OPEN |
| Who may correct a posted grade? | Audit | OPEN |
| Approval workflow for finals? | Official records | OPEN |
| Official PDF / report card / signature? | Legal/ops | OPEN |
| What student vs parent may see | Privacy + Flutter | OPEN |
| Multi-year academic history? | Retention + UX | OPEN |

---

## Scheduling

Implemented today: `scheduled_lessons` (regular lessons, Mon–Fri board). **Do not change that table in this task.**

Future calendar should show at least: regular lessons; rehearsals; performances; possibly exams; possibly academy events.

**Architecture (Tech Lead has not chosen):**

- **Variant A:** one `ScheduledSession` with types (`lesson`, `rehearsal`, `exam`, `performance`, …).
- **Variant B:** separate domain entities (`ScheduledLesson`, `Rehearsal`, `Performance`, …) plus an aggregating calendar/scheduling layer.

| Question | Status |
|----------|--------|
| Variant A vs B | OPEN |
| How rehearsals join **child unified calendar** and **conflict detection** with regular lessons | PRELIMINARY requirement (must); implementation OPEN |

---

## Productions / Shows

**Not** “optional Phase 6 event placeholder”. A production is a **domain subsystem** tightly integrated with core scheduling. **Priority after product discovery is for the PM to set** — it may move up the roadmap.

Preliminary model (not a final schema):

`Production / Show` → own participants → roles/cast → **Rehearsal groups** → rehearsals → performances.

**PRELIMINARY:** a Rehearsal Group is **not** a CourseGroup. It may mix ages, courses, and regular study groups. A child may be in regular CourseGroups **and** one or more rehearsal groups **and** several productions.

**PRELIMINARY:** rehearsals have their own schedule and **must** appear on the child’s unified calendar and in conflict checks vs regular lessons.

Possible future entities (not approved as final): Production, ProductionParticipant, CastRole / ProductionRole, RehearsalGroup, Rehearsal, Performance. ProductionStaff — possible, **not** approved.

Costume Service may stay a **separate integrated service**. Production scheduling is not the same as costume rental.

Discovery still needed: creation workflow; casting; multiple roles per child; who builds rehearsal groups; mandatory rehearsals; attendance **on rehearsals** (student attendance domain); teachers/choreographers/directors; venues; cancel/move; performance dates; costumes; participation fees; tickets; parent consent; notifications; costume↔production link.

**Status:** PRELIMINARY (domain split + calendar rule); most workflow details OPEN.

---

## Documents

- **Question:** Private vs public storage; document types; retention; who uploads.
- **Why it matters:** Files today sit on the **public** disk.
- **Status:** OPEN (private storage is required before wide mobile rollout — see Security)

---

## Communications

- **Question:** Channels (in-app, email, push), who may message whom, templates.
- **Status:** OPEN (`/communication` is a placeholder; student-list icon is dead)

---

## Payments

- **Question:** Italian fiscal invoices, methods, debt, link to productions/fees/tickets.
- **Status:** OPEN

---

## Privacy / Consent

- **Question:** Consent types, under-14 workflow, policy versions, photo consent.
- **Status:** OPEN (no entities). Mandatory before App Store / wide mobile.

---

## Flutter / Distribution

- **Variant A:** one Store app with student / parent / teacher **modes**.
- **Variant B:** several Store apps sharing a Flutter codebase / flavors.

Not decided. Older architecture text that said “same app / modes” was a sketch, **not** DECIDED.

- **Status:** OPEN

---

## Infrastructure / Deployment

- **Question:** Git deploy GitHub → `/var/www/aub`; staging; backups; monitoring; CI/CD.
- **Current fact:** production is **not** a git repository.
- **Status:** OPEN
