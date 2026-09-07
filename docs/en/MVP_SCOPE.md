# AUB — MVP Scope

## Important distinction

The installed admin kit includes **generic CRM modules** (customers, orders, services, staff, calendar). These are **not** academy-specific modules. AUB MVP will add academy domain modules on top of the kit foundation.

Do **not** duplicate kit functionality. Do **not** rebuild auth, user management, dashboard shell, or generic CRM unless academy requirements explicitly replace them.

## MVP Phase 1 — Access foundation (first step)

### Staff Roles & Role-Based Workplaces

| Item | Detail |
|------|--------|
| **Purpose** | Control who sees what in the admin; separate full admin from staff workplaces |
| **Status** | **Implemented** (2026-07-06) |
| **Kit overlap** | None — must be AUB-specific implementation |
| **Phase** | MVP Phase 1 (first) |
| **Dependency** | Required before all other AUB modules |

**Deliverables:**

- Roles management screen
- Role assignment in user create/edit
- Dynamic menu by role
- Admin vs non-admin access separation
- Lockout protection for last administrator
- Redirect: guest → `/`, admin → `/dashboard`, non-admin → `/workplace`

---

## MVP core modules (after Phase 1)

### Students

| Item | Detail |
|------|--------|
| **Purpose** | Core academy entity — enrolled children/adults |
| **Main entities** | Student (interim: extended `Customer` model) |
| **Key fields** | first/last name, gender, tax code, birth data, residence, contacts, course info, parent fields, documents, notes |
| **Screens** | List (`/customers`), full-page profile create/edit |
| **Workflows** | Create profile, upload documents, delete with password confirmation; enrollment via course groups (partial) |
| **Kit overlap** | Uses `customers` table — **interim**; dedicated `students` table planned |
| **Phase** | MVP Phase 2 — **partial (2026-07-06+)** |
| **Depends on roles** | Yes |

### Parents / Guardians

| Item | Detail |
|------|--------|
| **Purpose** | Legal contacts linked to students |
| **Main entities** | Embedded father/mother fields on student profile (modals) |
| **Key fields** | name, email, phone, notes per parent |
| **Screens** | Modals inside student profile |
| **Workflows** | Edit via profile only |
| **Kit overlap** | None |
| **Phase** | MVP Phase 2 — **partial** |
| **Depends on roles** | Yes — sensitive personal data |

### Teachers

| Item | Detail |
|------|--------|
| **Purpose** | Academy teaching staff records |
| **Main entities** | Teacher (`teachers` table) |
| **Key fields** | type, first/last name, email, phone, tax code, description, photo |
| **Screens** | List, full-page profile create/edit |
| **Workflows** | CRUD; link to lessons; schedule assignment |
| **Kit overlap** | Generic `staff` directory exists — **different purpose** |
| **Phase** | MVP Phase 2 — **implemented** |
| **Depends on roles** | Yes |
| **Not yet** | Link to `users` account for teacher login |

### Courses / Classes

| Item | Detail |
|------|--------|
| **Purpose** | Academy programs and class definitions |
| **Main entities** | Course, CourseGroup |
| **Key fields** | discipline, name, sort_order; groups with student/lesson attachments |
| **Screens** | `/courses-groups` — unified UI |
| **Workflows** | Create course/group, attach students and lessons |
| **Kit overlap** | Generic `services` exists — not used |
| **Phase** | MVP Phase 2 — **implemented** |
| **Depends on roles** | Yes |

### Groups

| Item | Detail |
|------|--------|
| **Purpose** | Student groupings within a course |
| **Main entities** | Group (subset of Course module) |
| **Key fields** | course_id, name, max students, room, schedule |
| **Screens** | Group list, detail, member list |
| **Phase** | MVP Phase 2 |

### Enrollments

| Item | Detail |
|------|--------|
| **Purpose** | Link students to groups/courses |
| **Main entities** | `course_group_customer` pivot |
| **Key fields** | course_group_id, customer_id |
| **Screens** | Attach/detach in `/courses-groups` |
| **Workflows** | Attach student to group; single group per student rule enforced |
| **Kit overlap** | Generic `orders` exists — not used |
| **Phase** | MVP Phase 2 — **partial** |

---

## MVP operations modules

### Schedule / Lessons Calendar

| Item | Detail |
|------|--------|
| **Purpose** | Academy lesson schedule |
| **Main entities** | `ScheduleWeek`, `ScheduledLesson`, `AcademyBuilding`, `AcademyRoom`; integrates with `CourseGroup`, `Lesson`, `Teacher` |
| **Key fields** | week_start_date, building, room, group_id, teacher_id, lesson_date, starts_at, ends_at, status |
| **Screens** | `/schedule-service` — weekly timeline board, unscheduled groups, inspector |
| **Kit overlap** | Generic `calendar` page exists — **placeholder only**; weekly schedule implemented as AUB module (`weekly-schedule.*`) |
| **Phase** | MVP Phase 3 — **weekly schedule service implemented (2026-07-20)**; attendance still pending |
| **Docs** | `docs/en/WEEKLY_SCHEDULE_SERVICE.md`, `docs/ru/WEEKLY_SCHEDULE_SERVICE.md` |

### Attendance

| Item | Detail |
|------|--------|
| **Purpose** | Track student presence at lessons |
| **Main entities** | AttendanceRecord |
| **Key fields** | lesson_id, student_id, status (present/absent/late), notes |
| **Screens** | Attendance sheet per lesson/group |
| **Phase** | MVP Phase 3 |
| **Depends on roles** | Yes — teachers mark attendance, admins see all |

### Documents / Student Files

| Item | Detail |
|------|--------|
| **Purpose** | Store contracts, medical notes, consent forms |
| **Main entities** | File paths on `customers` + future `Document` table |
| **Key fields** | medical certificate, parent ID, regulation forms, photo |
| **Screens** | Upload fields in student profile; `/documents` menu — placeholder |
| **Phase** | MVP Phase 3 — **partial (profile uploads only)** |
| **Privacy** | High — personal/children's data |

### Internal Notes / Communication History

| Item | Detail |
|------|--------|
| **Purpose** | Staff notes about students/parents |
| **Main entities** | Note |
| **Key fields** | entity_type, entity_id, author_id, content, visibility |
| **Screens** | Notes tab on student/parent profile |
| **Phase** | MVP Phase 3 |

---

## MVP accounting modules

### Payments / Invoices / Accounting Basics

| Item | Detail |
|------|--------|
| **Purpose** | Track fees, payments, debts |
| **Main entities** | Payment, Invoice |
| **Key fields** | student_id, amount, due_date, paid_date, status, payment_method |
| **Screens** | Invoice list, payment recording, debt report |
| **Kit overlap** | Generic `orders.total` field exists — **not** academy accounting |
| **Phase** | MVP Phase 4 |

### Basic Reports / Statistics

| Item | Detail |
|------|--------|
| **Purpose** | Enrollment counts, attendance rates, payment summaries |
| **Screens** | Report dashboard |
| **Kit overlap** | Generic `statistics/logs` page exists — **placeholder** |
| **Phase** | MVP Phase 4 |

---

## Admin users and permissions

| Item | Detail |
|------|--------|
| **Purpose** | Manage system users and their access roles |
| **Kit overlap** | User CRUD in `/settings` — extended with role selector, `can_write`, `can_delete` |
| **Phase** | MVP Phase 1 — **implemented** |

---

## Explicitly OUT of MVP Phase 1

| Module | Notes |
|--------|-------|
| Costume management (`servizio costumi`) | Future optional integrated service |
| Show/event participation (`servizio spettacoli`) | Future optional integrated service |
| Tickets | Future |
| Costume rental | Future |
| Participation fees (as separate service) | Future |
| Mobile apps (student/parent/teacher) | Phase 5 |
| Push notifications | Phase 5 |

These may become separate integrated services later, not part of the core academy CRM.

## Module dependency graph

```
Phase 0: Admin kit (installed)
    │
    ▼
Phase 1: Staff Roles & Role-Based Workplaces
    │
    ▼
Phase 2: Students, Parents, Teachers, Courses, Groups, Enrollments
    │
    ▼
Phase 3: Schedule, Attendance, Documents, Notes
    │
    ▼
Phase 4: Payments, Invoices, Reports
    │
    ▼
Phase 5: Mobile API, Notifications
    │
    ▼
Phase 6: Optional services (costumes, shows, tickets)
```
