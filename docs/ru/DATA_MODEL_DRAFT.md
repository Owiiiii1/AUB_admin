# AUB — Черновик модели данных

Условные обозначения:

- **Implemented** — есть в коде и миграциях, используется
- **Generic kit** — из `custom-admin-kit` v0.4.0
- **Planned** — не построено
- **Unverified leftover** — исторически упоминалось; Spatie-подобные таблицы дропались в `2026_07_06_120000`; leftover в MySQL **не** перепроверялись 2026-09-07

Миграции: **40 файлов**, включая Identity Layer `2026_09_08_220000_add_account_identity_layer` и Sanctum `personal_access_tokens`.

Чувствительность: Normal / Personal data / Children’s data / Special category / Secret.

---

## A. Реализованные / kit-таблицы

### users (implemented)

Auth. Дополнительно: `account_type` (`staff` | `student` | `parent` | `teacher`), `is_active` (default true), `role_id` → `roles`, `can_write`, `can_delete`. Email unique.
Связи: `belongsTo Role`, `hasOne Student` (`studentProfile`), `hasOne AcademyParent` (`parentProfile`), `hasOne Teacher` (`teacherProfile`).
`account_type` ≠ web RBAC. Privacy: Personal data.

### customers (generic kit — **legacy, not academy Student**)

Kit columns remain. Academy student profiles live in `students`. Production test `customers` rows were cleared by `2026_09_08_200000`. Table kept for kit compatibility.

Relations in code: `hasMany Order` (legacy).

Files for students: public disk `students/{id}/documents` — **privacy gap**.

Privacy: Children’s data / Personal data.

### services, staff, orders, order_staff (generic kit)

В UI/маршрутах не используются. `staff.role` — свободный текст, **не** RBAC.

### ai_provider_settings (generic kit, используется)

`provider`, encrypted `api_key`, флаги соединения, модели. Privacy: Secret.

### Системные Laravel

`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations`, `personal_access_tokens` (Sanctum hashed PAT; `name` = device_name, `abilities` includes `mobile`, `expires_at`). Privacy: Secret (token hash).

### activity_logs (implemented)

`user_id`, `customer_id` (legacy), `student_id`, `action`, `subject_*`, `properties`, `route_name`, `ip_address`, `user_agent`, `created_at` (без `updated_at`). CRUD + login/logout. **Не** audit чтений.

---

## B. Таблицы доступа AUB (implemented)

### roles

`name`, `slug`, `description`, `is_admin`, `is_system`, `is_active`.  
hasMany `RoleMenuItem`, hasMany `User`.

### role_menu_items

`role_id`, `menu_key`, `label`, `route_name`, `url`, `icon`, `sort_order`, `is_active`.

---

## C. Реализованные таблицы академии

### academic_years

`name`, `starts_at`, `ends_at`, `is_active`. Текущий год = `is_active` (без auto-switch). PHP: `AcademicYear::current()`. Админ-UI годов нет.

### students

Профиль ученика: `name`, `first_name`, `last_name`, `gender`, `tax_code`, `birth_date`, `birth_place`, residence fields, `email`, `phone`, course flags, `medical_certificate_expiry`, document/photo paths, `notes`, `status`. **Нет** father_*/mother_* колонок. Nullable unique `user_id` → `users.id` (`nullOnDelete`). `belongsTo User`.

`belongsToMany AcademyParent` (`student_parent`), `belongsToMany AcademyClass` (`academy_class_student`).

### parents (`AcademyParent`)

`first_name`, `last_name`, `email`, `phone`, `tax_code`, `notes`, nullable unique `user_id` → `users.id` (`nullOnDelete`). Таблица `parents`. PHP-класс не `Parent` (зарезервировано). `belongsTo User`.

### student_parent

`student_id`, `parent_id`, `relation_type` (nullable string: `father` / `mother` / `guardian` / `other`). Unique `(student_id, parent_id)`.

### teachers

`type`, `first_name`, `last_name`, `name`, `email`, `phone`, `tax_code`, `description`, `photo_path`, nullable unique `user_id`.  
`belongsToMany Lesson` (`lesson_teacher`), `belongsToMany ClassLesson` (`class_lesson_teacher`). `belongsTo User`. Identity Layer implemented (create/link from teacher profile).  
Фото: public disk `teachers/{id}/photos`.

### courses / academy_classes / academy_class_student

`courses`: `discipline`, `name`, `sort_order`, `study_starts_at`, `study_ends_at`. Направление обучения.

`academy_classes`: `course_id`, `name`, `color`, `sort_order`. Продуктовый **Class**. PHP-модель `AcademyClass` (`Class` зарезервировано).

`academy_class_student`: unique `student_id` — максимум один активный основной Class.

Таблицы `course_groups`, `course_group_customer`, `course_group_lesson` **удалены**.

### lessons + ClassLesson

`lessons`: `discipline`, `name`, `description`, `duration_minutes`, `sort_order`.  
`lesson_teacher`, `lesson_course`.  
`class_lessons`: `academic_year_id`, `academy_class_id`, `lesson_id`, `hours`, `sort_order`; unique `(academic_year_id, academy_class_id, lesson_id)`.  
`class_lesson_teacher`: несколько преподавателей; `hours` на pivot.

### Недельное расписание (2026-07-20, FK 2026-09-08)

`academy_buildings`, `academy_rooms` (`capacity`, `room_type`). **Колонок lat/lng/radius сейчас нет.**  
`schedule_weeks`: понедельник `week_start_date`, пятница `week_end_date`, `work_starts_at` / `work_ends_at`, `draft`/`published`/`locked`.  
`scheduled_lessons`: неделя, здание, зал, **`academy_class_id`**, преподаватель, урок, дата/время, цвет, статус.  
`schedule_ai_runs`: промпт, preferences JSON, метрики, отчёт, предупреждения.

UI: визуал 30 мин, планирование 5 мин. См. [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).  
`locked` есть в схеме; UI его не выставляет.

---

## D. Planned / концепт (нет финальной схемы, нет миграций)

Не считать список утверждённой БД для ещё не построенных модулей. Core Data Model, Identity Layer и API Foundation **сделаны**. Следующий клиентский этап — Flutter Authentication Foundation.

### Student / Parent (**implemented**)

`students`, `parents`, `student_parent` реализованы. `customers` — unused kit leftover. PHP: `Student`, `AcademyParent`.

### `Class` vs дополнительные группы (DECIDED)

- `Class` — постоянный основной учебный класс; максимум один активный на ребёнка (**unique `academy_class_student.student_id`**).
- PHP-модель: `AcademyClass` / таблица `academy_classes`.
- Дополнительные группы (события, постановки, репетиции) **не** являются `Class`. Ребёнок может быть в одном `Class` и в нескольких activity groups (схема доп. групп не строится сейчас).

### Identity (implemented 2026-09-08)

```
User.account_type: staff | student | parent | teacher
one User = one actor type
Student.user_id / Parent.user_id / Teacher.user_id
account_type != web RBAC
```

Привязка только через `AccountIdentityService`. Один физический человек с двумя функциями = два User (email unique). Invitation/activation нет. API нет.

### Teacher Check-in (семантика DECIDED)

Daily presence, не per lesson. Концептуальные поля: teacher; date; checked_in_at; latitude; longitude; accuracy; academy_location; status; manual correction metadata; audit. Geofence radius / accuracy / окно / anti-spoofing — OPEN. QR = optional fallback. `academy_buildings` / `academy_rooms` сейчас **без** lat/lng/radius.

### Final Assessment (ядро DECIDED, детали OPEN)

Концепт:

```
AcademicYear → Class → ClassLesson → TeacherAssignment
Student + Class + Lesson + AcademicYear → StudentFinalResult
Student + Class + AcademicYear → ReportCard
```

`StudentFinalResult` (пример полей): student_id; class_id; lesson_id; academic_year_id; result; teacher_comment; finalized_at; updated_at; audit metadata.

`ReportCard` (предварительно): student_id; class_id; academic_year_id; status (`draft` / `finalized` / `printed`); finalized_at; generated_at; generated_by.

Текущих оценок / per-lesson gradebook **нет**. PDF собирает Administrator из доменных сущностей; PDF не источник данных. Шкала, шаблон PDF, кто закрывает табель — OPEN.

### Consent (направление, порог возраста не фиксировать)

Целевые сущности: `ConsentType`, `ConsentDocumentVersion`, `ConsentRecord`. Возможные types: privacy; data processing; photo/video; marketing; special activity. Для несовершеннолетнего — связь с parent/guardian там, где требует политика/закон. Возрастной порог не хардкодить до legal review.

| Тема | Примечание | Status |
|------|------------|--------|
| `students` / `parents` / `student_parent` | Реализовано 2026-09-08 | DECIDED / implemented |
| Enrollment workflow вокруг `Class` | Статусы, история, переводы | OPEN |
| **Student Attendance** | Присутствие на **session**. Не GPS преподавателя | OPEN |
| **Teacher Check-in** | Daily geofence check-in. Детали radius/accuracy OPEN | DECIDED semantics / не реализовано |
| Final Assessment | `StudentFinalResult` / `ReportCard`. Шкала OPEN | DECIDED core / детали OPEN |
| Единый календарь | Variant A vs B. Tech Lead не решил. **`scheduled_lessons` сейчас не менять** | OPEN |
| Additional groups / Productions | ≠ `Class`; поздний future; схему не финализировать | DECIDED split / workflow OPEN |
| Сущность Document + **private** storage | Сейчас: пути на public disk | OPEN (нужно до широкого mobile) |
| ConsentType / ConsentDocumentVersion / ConsentRecord | Возраст не хардкодить | DECIDED direction / не реализовано |
| Teacher.user_id + mobile Teacher account | Связь и web create/link есть; mobile API нет | Identity implemented / API OPEN |
| Payment / Invoice | Не начато | OPEN |

Не использовать kit `orders` как зачисления или счета.

---

## Kit vs имена AUB

| Kit | AUB сейчас | Целевое направление |
|-----|------------|---------------------|
| customers | Legacy kit leftover | Academy использует `students` / `parents` |
| course_groups | Удалены | `academy_classes` / `AcademyClass` = продукт `Class` |
| services | courses | Отдельная таблица есть |
| staff | teachers + roles | Не путать |
| orders | enrollments / invoices | Новый домен, не kit |
| страница calendar | `/schedule-service` | Реализовано |
