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

### customers (implemented — **студенты, interim**)

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

**Правило зачисления сейчас:** unique `customer_id` — **одна группа на студента**. Unique `(customer_id, discipline)` заменён миграцией `2026_07_08_170000`.

### lessons + pivots

`lessons`: `discipline`, `name`, `description`, `duration_minutes`, `sort_order`.  
`lesson_teacher`, `lesson_course`.  
`course_group_lesson`: `teacher_id`, `hours`; unique `(course_group_id, lesson_id, teacher_id)`.

### Недельное расписание (2026-07-20)

`academy_buildings`, `academy_rooms` (`capacity`, `room_type`).  
`schedule_weeks`: понедельник `week_start_date`, пятница `week_end_date`, `work_starts_at` / `work_ends_at`, `draft`/`published`/`locked`.  
`scheduled_lessons`: неделя, здание, зал, группа, преподаватель, урок, дата/время, цвет, статус.  
`schedule_ai_runs`: промпт, preferences JSON, метрики, отчёт, предупреждения.

UI: визуал 30 мин, планирование 5 мин. См. [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).  
`locked` есть в схеме; UI его не выставляет.

---

## D. Planned (отдельных сущностей пока нет)

| Сущность | Примечание |
|----------|------------|
| Student | Сейчас: `customers`. Отдельная таблица — опционально |
| Parent / `student_parent` | Сейчас: колонки отца/матери |
| Workflow зачислений | Статусы, даты, история — сейчас только pivot |
| AttendanceRecord | Не начато |
| Payment / Invoice | Не начато |
| Document (таблица) | Сейчас: path-колонки + public files |
| ConsentRecord / PrivacyPolicyVersion | Не начато |
| Teacher.user_id | Нужен для login преподавателя / режима Flutter |

Не использовать kit `orders` как зачисления или счета.

---

## Kit vs имена AUB

| Kit | AUB сейчас | Действие |
|-----|------------|----------|
| customers | Студенты (interim) | Держать, пока нет задачи миграции |
| services | courses | Отдельная таблица есть |
| staff | teachers + roles | Не путать |
| orders | enrollments / invoices | Новый домен, не kit |
| страница calendar | `/schedule-service` | Реализовано |
