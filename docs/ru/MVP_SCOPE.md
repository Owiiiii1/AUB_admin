# AUB — Объём MVP

## Важное различие

Установленный admin kit включает **универсальные CRM-модули** (customers, orders, services, staff, calendar). Это **не** модули, специфичные для академии. MVP AUB добавит модули домена академии поверх основы kit.

**Не** дублировать функциональность kit. **Не** перестраивать auth, управление пользователями, оболочку дашборда или универсальную CRM, если требования академии явно не заменяют их.

## MVP Phase 1 — Основа доступа (первый шаг)

### Staff Roles & Role-Based Workplaces

| Item | Detail |
|------|--------|
| **Purpose** | Контроль того, кто что видит в админке; разделение полной админки и рабочих мест персонала |
| **Status** | **Implemented** (2026-07-06) |
| **Kit overlap** | Нет — должна быть реализация, специфичная для AUB |
| **Phase** | MVP Phase 1 (первый) |
| **Dependency** | Требуется до всех остальных модулей AUB |

**Результаты (deliverables):**

- Экран управления ролями
- Назначение роли при создании/редактировании пользователя
- Динамическое меню по роли
- Разделение доступа admin vs non-admin
- Защита от блокировки последнего администратора
- Перенаправления: guest → `/`, admin → `/dashboard`, non-admin → `/workplace`

---

## Основные модули MVP (после Phase 1)

### Students

| Item | Detail |
|------|--------|
| **Purpose** | Основная сущность академии — зачисленные дети/взрослые |
| **Main entities** | Student |
| **Key fields** | name, date of birth, gender, contact info, enrollment status, group assignment, notes |
| **Screens** | List, create/edit, profile view |
| **Workflows** | Enroll, transfer group, deactivate |
| **Kit overlap** | Универсальный `customers` существует — **не дублировать**; Students — отдельная сущность AUB |
| **Phase** | MVP Phase 2 |
| **Depends on roles** | Да |

### Parents / Guardians

| Item | Detail |
|------|--------|
| **Purpose** | Юридические контакты, связанные со студентами |
| **Main entities** | Parent/Guardian, StudentParent link |
| **Key fields** | name, email, phone, relationship, address, consent status |
| **Screens** | List, create/edit, link to students |
| **Workflows** | Add parent, link/unlink student, update contacts |
| **Kit overlap** | Нет |
| **Phase** | MVP Phase 2 |
| **Depends on roles** | Да — чувствительные персональные данные |

### Teachers

| Item | Detail |
|------|--------|
| **Purpose** | Записи преподавательского состава академии |
| **Main entities** | Teacher |
| **Key fields** | name, email, phone, specializations, employment status, linked user account |
| **Screens** | List, create/edit, profile |
| **Workflows** | Hire, assign to courses/groups |
| **Kit overlap** | Универсальный справочник `staff` существует — **другое назначение**; Teachers специфичен для академии |
| **Phase** | MVP Phase 2 |
| **Depends on roles** | Да |

### Courses / Classes

| Item | Detail |
|------|--------|
| **Purpose** | Программы академии и определения классов |
| **Main entities** | Course, Group/Class |
| **Key fields** | name, description, age range, capacity, schedule template, teacher assignment |
| **Screens** | Course list, group list, create/edit |
| **Workflows** | Create course, open group, assign teacher |
| **Kit overlap** | Универсальный каталог `services` существует — **не путать** с курсами академии |
| **Phase** | MVP Phase 2 |
| **Depends on roles** | Да |

### Groups

| Item | Detail |
|------|--------|
| **Purpose** | Группировки студентов в рамках курса |
| **Main entities** | Group (subset of Course module) |
| **Key fields** | course_id, name, max students, room, schedule |
| **Screens** | Group list, detail, member list |
| **Phase** | MVP Phase 2 |

### Enrollments

| Item | Detail |
|------|--------|
| **Purpose** | Связь студентов с группами/курсами |
| **Main entities** | Enrollment |
| **Key fields** | student_id, group_id, start_date, end_date, status |
| **Screens** | Enrollment list, create/edit |
| **Workflows** | Enroll, transfer, withdraw |
| **Kit overlap** | Универсальный `orders` существует — **другой домен**; не использовать для enrollments |
| **Phase** | MVP Phase 2 |

---

## Операционные модули MVP

### Schedule / Lessons Calendar

| Item | Detail |
|------|--------|
| **Purpose** | Расписание занятий академии |
| **Main entities** | `ScheduleWeek`, `ScheduledLesson`, `AcademyBuilding`, `AcademyRoom`; интеграция с `CourseGroup`, `Lesson`, `Teacher` |
| **Key fields** | week_start_date, building, room, group_id, teacher_id, lesson_date, starts_at, ends_at, status |
| **Screens** | `/schedule-service` — недельная сетка, незапланированные группы, инспектор |
| **Kit overlap** | Универсальная страница `calendar` — **только заглушка**; недельное расписание реализовано как модуль AUB (`weekly-schedule.*`) |
| **Phase** | MVP Phase 3 — **сервис недельного расписания реализован (2026-07-20)**; посещаемость ещё нет |
| **Docs** | `docs/en/WEEKLY_SCHEDULE_SERVICE.md`, `docs/ru/WEEKLY_SCHEDULE_SERVICE.md` |

### Attendance

| Item | Detail |
|------|--------|
| **Purpose** | Учёт присутствия студентов на занятиях |
| **Main entities** | AttendanceRecord |
| **Key fields** | lesson_id, student_id, status (present/absent/late), notes |
| **Screens** | Attendance sheet per lesson/group |
| **Phase** | MVP Phase 3 |
| **Depends on roles** | Да — преподаватели отмечают посещаемость, администраторы видят всё |

### Documents / Student Files

| Item | Detail |
|------|--------|
| **Purpose** | Хранение договоров, медицинских записей, форм согласия |
| **Main entities** | Document |
| **Key fields** | student_id, type, file path, uploaded_by, date |
| **Screens** | Document list per student, upload |
| **Phase** | MVP Phase 3 |
| **Privacy** | Высокая — персональные данные/данные детей |

### Internal Notes / Communication History

| Item | Detail |
|------|--------|
| **Purpose** | Заметки персонала о студентах/родителях |
| **Main entities** | Note |
| **Key fields** | entity_type, entity_id, author_id, content, visibility |
| **Screens** | Notes tab on student/parent profile |
| **Phase** | MVP Phase 3 |

---

## Учётные модули MVP

### Payments / Invoices / Accounting Basics

| Item | Detail |
|------|--------|
| **Purpose** | Учёт взносов, платежей, задолженностей |
| **Main entities** | Payment, Invoice |
| **Key fields** | student_id, amount, due_date, paid_date, status, payment_method |
| **Screens** | Invoice list, payment recording, debt report |
| **Kit overlap** | Поле `orders.total` существует — **не** учёт академии |
| **Phase** | MVP Phase 4 |

### Basic Reports / Statistics

| Item | Detail |
|------|--------|
| **Purpose** | Количество зачислений, показатели посещаемости, сводки по платежам |
| **Screens** | Report dashboard |
| **Kit overlap** | Универсальная страница `statistics/logs` существует — **заглушка** |
| **Phase** | MVP Phase 4 |

---

## Админ-пользователи и права доступа

| Item | Detail |
|------|--------|
| **Purpose** | Управление пользователями системы и их ролями доступа |
| **Kit overlap** | User CRUD в `/settings` существует — **расширить выбором роли**, не перестраивать |
| **Phase** | MVP Phase 1 (модуль Staff Roles) |

---

## Явно НЕ входит в MVP Phase 1

| Module | Notes |
|--------|-------|
| Costume management (`servizio costumi`) | Будущая опциональная интегрированная услуга |
| Show/event participation (`servizio spettacoli`) | Будущая опциональная интегрированная услуга |
| Tickets | Будущее |
| Costume rental | Будущее |
| Participation fees (as separate service) | Будущее |
| Mobile apps (student/parent/teacher) | Phase 5 |
| Push notifications | Phase 5 |

Позже они могут стать отдельными интегрированными услугами, а не частью основной CRM академии.

## Граф зависимостей модулей

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
