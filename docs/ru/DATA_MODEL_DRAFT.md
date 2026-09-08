# AUB — Черновик модели данных

Условные обозначения:

- **Implemented** — есть в коде и миграциях, используется
- **Generic kit** — из `custom-admin-kit` v0.4.0
- **Planned** — не построено
- **Unverified leftover** — исторически упоминалось; Spatie-подобные таблицы дропались в `2026_07_06_120000`; leftover в MySQL **не** перепроверялись 2026-09-07

Миграции: **37 файлов**, на production все **Ran**, batch **1–29**.

Чувствительность: Normal / Personal data / Children’s data / Special category / Secret.

---

## A. Реализованные / kit-таблицы

### users (implemented)

Auth. Дополнительно: `role_id` → `roles`, `can_write`, `can_delete`.  
Связи: `belongsTo Role`.  
Privacy: Personal data.

### customers (implemented — **студенты, interim / не долгосрочная модель**)

Колонки kit: `name`, `email`, `phone`, `address`, `notes`, `status`.

Поля профиля AUB (миграции `2026_07_06_210000`, `2026_07_06_221000`): имя/фамилия, пол, codice fiscale, рождение, проживание, email/телефон студента, флаги курса, пути документов, блоки отца/матери, `student_notes`. Legacy `parent_phone` / `parent_email` сохранены.

Связи в коде: `hasMany Order` (legacy). **Нет** inverse `courseGroups()`; зачисления через `course_group_customer`.

Файлы: диск public `students/{id}/documents` — **разрыв privacy**.

Privacy: Children’s data / Personal data.

### services, staff, orders, order_staff (generic kit)

В UI/маршрутах не используются. `staff.role` — свободный текст, **не** RBAC.

### ai_provider_settings (generic kit, используется)

`provider`, encrypted `api_key`, флаги соединения, модели. Privacy: Secret.

### Системные Laravel

`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations`.

### activity_logs (implemented)

`user_id`, `customer_id`, `action`, `subject_*`, `properties`, `route_name`, `ip_address`, `user_agent`, `created_at` (без `updated_at`). CRUD + login/logout. **Не** audit чтений.

---

## B. Таблицы доступа AUB (implemented)

### roles

`name`, `slug`, `description`, `is_admin`, `is_system`, `is_active`.  
hasMany `RoleMenuItem`, hasMany `User`.

### role_menu_items

`role_id`, `menu_key`, `label`, `route_name`, `url`, `icon`, `sort_order`, `is_active`.

---

## C. Реализованные таблицы академии (2026-07-08+)

### teachers

`type`, `first_name`, `last_name`, `name`, `email`, `phone`, `tax_code`, `description`, `photo_path`.  
`belongsToMany Lesson` (`lesson_teacher`). **Нет `user_id`.**  
Фото: public disk `teachers/{id}/photos`.

### courses / course_groups / course_group_customer

`courses`: `discipline`, `name`, `sort_order`, `study_starts_at`, `study_ends_at`.  
`course_groups`: `course_id`, `name`, `color`, `sort_order`.  
`course_group_customer`: `course_group_id`, `customer_id`, `discipline` (колонка осталась).

**Ограничение зачисления в коде:** unique `customer_id` — не больше одной CourseGroup на студента в БД. **DECIDED:** это концептуально соответствует правилу «один основной `Class`». Целевая терминология: `Class` = постоянный основной учебный класс; не смешивать с дополнительными activity groups. Имена таблиц (`course_groups` vs `Class`) ещё нормализовать. Миграции в этой задаче не менять. См. [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

Ранее unique `(customer_id, discipline)` заменён миграцией `2026_07_08_170000`.

### lessons + pivots

`lessons`: `discipline`, `name`, `description`, `duration_minutes`, `sort_order`.  
`lesson_teacher`, `lesson_course`.  
`course_group_lesson`: `teacher_id`, `hours`; unique `(course_group_id, lesson_id, teacher_id)`.

### Недельное расписание (2026-07-20)

`academy_buildings`, `academy_rooms` (`capacity`, `room_type`). **Колонок lat/lng/radius сейчас нет.** Поля geofence — **концептуальное** требование Teacher Check-in, не реализовано.  
`schedule_weeks`: понедельник `week_start_date`, пятница `week_end_date`, `work_starts_at` / `work_ends_at`, `draft`/`published`/`locked`.  
`scheduled_lessons`: неделя, здание, зал, группа, преподаватель, урок, дата/время, цвет, статус.  
`schedule_ai_runs`: промпт, preferences JSON, метрики, отчёт, предупреждения.

UI: визуал 30 мин, планирование 5 мин. См. [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).  
`locked` есть в схеме; UI его не выставляет.

---

## D. Planned / концепт (нет финальной схемы, нет миграций)

Не считать список утверждённой БД. Миграции **не** создавать в docs-only задаче. Core Data Model refactor — **до** стабильного mobile API.

### Student / Parent (направление DECIDED)

`customers` — interim / legacy, **не** долгосрочная модель Student. В production ценных пользовательских данных нет (тест). Целевые сущности: `students`, `parents`, `student_parent`. Реализацию миграций не начинать сейчас.

### `Class` vs дополнительные группы (DECIDED)

- `Class` — постоянный основной учебный класс; максимум один активный на ребёнка.
- Дополнительные группы (события, постановки, репетиции) **не** являются `Class`. Ребёнок может быть в одном `Class` и в нескольких activity groups.
- Предварительные будущие типы (не схема): `ProductionGroup`, `RehearsalGroup`, возможно другие. Финальную схему не создавать сейчас.

### Identity (DECIDED)

Один User — ровно один основной actor type: `student` | `parent` | `teacher`. Multi-profile не закладывать. Web RBAC персонала остаётся отдельно. `teachers.user_id` в коде нет.

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
| Таблицы `students` / `parents` / `student_parent` | Направление DECIDED; сейчас `customers` | DECIDED direction / не реализовано |
| Workflow зачислений в `Class` | Статусы, история, переводы | OPEN |
| **Student Attendance** | Присутствие на **session**. Не GPS преподавателя | OPEN |
| **Teacher Check-in** | Daily geofence check-in. Детали radius/accuracy OPEN | DECIDED semantics / не реализовано |
| Final Assessment | `StudentFinalResult` / `ReportCard`. Шкала OPEN | DECIDED core / детали OPEN |
| Единый календарь | Variant A vs B. Tech Lead не решил. **`scheduled_lessons` сейчас не менять** | OPEN |
| Additional groups / Productions | ≠ `Class`; поздний future; схему не финализировать | DECIDED split / workflow OPEN |
| Сущность Document + **private** storage | Сейчас: пути на public disk | OPEN (нужно до широкого mobile) |
| ConsentType / ConsentDocumentVersion / ConsentRecord | Возраст не хардкодить | DECIDED direction / не реализовано |
| Teacher.user_id + mobile Teacher account | Один actor type на User | DECIDED identity / не реализовано |
| Payment / Invoice | Не начато | OPEN |

Не использовать kit `orders` как зачисления или счета.

---

## Kit vs имена AUB

| Kit | AUB сейчас | Целевое направление |
|-----|------------|---------------------|
| customers | Студенты (interim) | `students` / `parents` / `student_parent` — отдельная задача refactor, не сейчас |
| course_groups | Учебные группы в коде | Продуктовый `Class` (нормализация терминологии OPEN) |
| services | courses | Отдельная таблица есть |
| staff | teachers + roles | Не путать |
| orders | enrollments / invoices | Новый домен, не kit |
| страница calendar | `/schedule-service` | Реализовано |
