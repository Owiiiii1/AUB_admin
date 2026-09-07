# AUB — Черновик модели данных

Этот документ разделяет **реализованные/универсальные таблицы**, **запланированные таблицы контроля доступа** и **запланированные таблицы академии**.

Условные обозначения статуса:

- **Implemented** — существует в коде и миграциях, используется
- **Generic kit** — из `custom-admin-kit` v0.4.0, универсальное CRM-назначение
- **Planned** — запланировано, не реализовано
- **Legacy (DB only)** — таблица существует в базе данных от предыдущей установки, нет кода приложения

Чувствительность с точки зрения конфиденциальности:

- **Normal** — неперсональные операционные данные
- **Personal data** — имена, email, телефоны, адреса (применяется GDPR)
- **Children's data** — данные о несовершеннолетних (усиленная защита)
- **Special category** — здоровье, медицинские данные, чувствительные заметки (если хранятся)

---

## A. Текущие реализованные / универсальные таблицы kit

### users

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | Personal data |
| email | string unique | Personal data |
| email_verified_at | timestamp nullable | |
| password | string | Hidden |
| remember_token | string nullable | |
| role_id | FK nullable → roles | **Implemented** |
| can_write | boolean default false | **Implemented** |
| can_delete | boolean default false | **Implemented** |
| created_at, updated_at | timestamps | |

**Status:** Implemented  
**Privacy:** Personal data  
**Relationships:** belongsTo Role

---

### customers (расширена как students — interim)

**Status:** Implemented (расширение AUB на таблице kit, 2026-07-06+)  
**Privacy:** Children's data / Personal data

Сохранены поля kit: `name`, `email`, `phone`, `address`, `notes`, `status`.

Дополнительные поля профиля студента — см. англ. версию и миграции `2026_07_06_210000`, `2026_07_06_221000` (личные данные, проживание, курс, документы, отец/мать, заметки).

**Relationships:** hasMany orders (legacy kit); belongsToMany CourseGroup через `course_group_customer`

---

### services (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | |
| description | text nullable | |
| price | decimal(10,2) nullable | |
| duration_minutes | unsigned int nullable | |
| is_active | boolean default true | |
| timestamps | | |

**Status:** Generic kit — **не** курсы академии  
**Privacy:** Normal

---

### staff (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string nullable | |
| phone | string nullable | |
| role | string nullable | Free-text, **not RBAC** |
| is_active | boolean default true | |
| notes | text nullable | |
| timestamps | | |

**Status:** Generic kit — **справочник** персонала, не роли доступа  
**Privacy:** Personal data  
**Note:** Не путать `staff.role` (текст) с запланированной таблицей `roles` (RBAC)

---

### orders (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| customer_id | FK nullable → customers | |
| service_id | FK nullable → services | |
| title | string | |
| description | text nullable | |
| status | string default `new` | |
| scheduled_at | timestamp nullable | |
| completed_at | timestamp nullable | |
| total | decimal(10,2) nullable | |
| notes | text nullable | |
| timestamps | | |

**Status:** Generic kit — **не** enrollments или invoices  
**Privacy:** Normal / may contain personal data in notes

---

### order_staff (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| order_id | FK → orders | |
| staff_id | FK → staff | |
| timestamps | | |
| unique(order_id, staff_id) | | |

**Status:** Generic kit  
**Privacy:** Normal

---

### ai_provider_settings (generic kit)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| provider | string unique | |
| label | string nullable | |
| api_key | text nullable | **Secret — never expose** |
| is_connected | boolean | |
| is_active | boolean | |
| active_model | string nullable | |
| available_models | json nullable | |
| last_checked_at | timestamp nullable | |
| last_error | text nullable | |
| timestamps | | |

**Status:** Generic kit  
**Privacy:** Normal (contains API secrets)

---

### Laravel system tables

| Table | Status |
|-------|--------|
| cache, cache_locks | Implemented |
| jobs, job_batches, failed_jobs | Implemented |
| sessions | Implemented |
| password_reset_tokens | Implemented |
| migrations | Implemented |

---

### Legacy tables (DB only, no app code)

| Table | Status | Notes |
|-------|--------|-------|
| permissions | Legacy (DB only) | Пустая; Spatie-style, не используется |
| model_has_roles | Legacy (DB only) | Пустая |
| model_has_permissions | Legacy (DB only) | Пустая |
| role_has_permissions | Legacy (DB only) | Пустая |
| media | Legacy (DB only) | Пустая |
| settings | Legacy (DB only) | Пустая |
| personal_access_tokens | Legacy (DB only) | Пустая |

**Note:** Таблицы AUB `roles` и `role_menu_items` **реализованы** (Фаза 1). Legacy Spatie `roles` заменена.

### activity_logs (реализовано)

Журнал CRUD-действий — `ActivityLogController`, `/statistics/logs`. Поля: user_id, customer_id, action, subject_type/id, properties, route_name, ip_address, user_agent, created_at.

---

## B. Таблицы контроля доступа AUB (реализовано)

### Role

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | Display name, e.g. "Administrator" |
| slug | string unique | e.g. `administrator`, `secretariat` |
| description | text nullable | |
| is_admin | boolean default false | Full admin panel access |
| is_system | boolean default false | Cannot delete system roles |
| is_active | boolean default true | |
| created_at, updated_at | timestamps | |

**Status:** Implemented (Фаза 1, 2026-07-06)  
**Privacy:** Normal  
**Relationships:** hasMany RoleMenuItem, hasMany User

**Business rules:**

- Роль по умолчанию `Administrator` с `is_admin=true`, `is_system=true`
- Нельзя удалить/отключить последнюю активную роль администратора
- Нельзя снять admin-доступ у единственного активного администратора

---

### RoleMenuItem

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| role_id | FK → roles | |
| menu_key | string | Internal key, e.g. `customers`, `dashboard` |
| label | string nullable | Display override |
| route_name | string nullable | Laravel route name |
| url | string nullable | Fallback URL |
| icon | string nullable | Icon identifier |
| sort_order | unsigned int default 0 | |
| is_active | boolean default true | |
| created_at, updated_at | timestamps | |

**Status:** Implemented  
**Privacy:** Normal  
**Relationships:** belongsTo Role

**Purpose:** Определяет, какие пункты меню видит каждая роль. Роль Administrator получает все пункты (или `is_admin` обходит фильтрацию).

---

### users.role_id (реализовано)

| Field | Type | Notes |
|-------|------|-------|
| role_id | FK nullable → roles | Назначается при создании/редактировании |
| can_write | boolean | Флаг права записи |
| can_delete | boolean | Флаг права удаления |

**Status:** Implemented  
**Privacy:** Normal

---

## C. Запланированные таблицы академии AUB

### Student

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| first_name, last_name | string | |
| date_of_birth | date nullable | |
| gender | string nullable | |
| email, phone | string nullable | If applicable |
| address | text nullable | |
| status | enum/string | active, inactive, graduated |
| enrollment_date | date nullable | |
| notes | text nullable | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Children's data / Personal data

---

### Parent / Guardian

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| first_name, last_name | string | |
| email, phone | string | |
| address | text nullable | |
| relationship | string nullable | mother, father, guardian |
| user_id | FK nullable → users | Future mobile access |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Personal data

---

### student_parent (pivot, planned)

| Field | Type |
|-------|------|
| student_id | FK → students |
| parent_id | FK → parents |
| is_primary | boolean |
| timestamps | |

---

### Teacher

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| first_name, last_name | string | |
| email, phone | string nullable | |
| specializations | json/text nullable | |
| employment_status | string | |
| user_id | FK nullable → users | Link to login account |
| staff_id | FK nullable → staff | Optional link to kit staff record |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Personal data

---

### Course

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| name | string | |
| description | text nullable | |
| age_min, age_max | int nullable | |
| is_active | boolean | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Normal

---

### Group / Class

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| course_id | FK → courses | |
| name | string | |
| max_students | int nullable | |
| room | string nullable | |
| teacher_id | FK nullable → teachers | |
| schedule_template | json nullable | |
| is_active | boolean | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Normal

---

### Enrollment

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| group_id | FK → groups | |
| start_date | date | |
| end_date | date nullable | |
| status | string | active, completed, withdrawn |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Children's data

---

### Lesson

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| group_id | FK → groups | |
| teacher_id | FK nullable → teachers | |
| date | date | |
| start_time, end_time | time | |
| room | string nullable | |
| status | string | scheduled, completed, cancelled |
| notes | text nullable | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Normal

---

### AttendanceRecord

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| lesson_id | FK → lessons | |
| student_id | FK → students | |
| status | string | present, absent, late, excused |
| notes | text nullable | |
| marked_by | FK → users | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Children's data

---

### Payment / Invoice

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| amount | decimal(10,2) | |
| due_date | date | |
| paid_date | date nullable | |
| status | string | pending, paid, overdue, cancelled |
| payment_method | string nullable | |
| description | text nullable | |
| invoice_number | string nullable | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Personal data / financial

---

### Document

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| type | string | contract, medical, consent, other |
| file_path | string | |
| uploaded_by | FK → users | |
| description | text nullable | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Personal data / Children's data / Special category (medical)

---

### Note / ActivityLog

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| notable_type, notable_id | morph | student, parent, etc. |
| author_id | FK → users | |
| content | text | |
| visibility | string | internal, restricted |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Personal data / may be special category

---

### ConsentRecord

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| student_id | FK → students | |
| parent_id | FK nullable → parents | |
| consent_type | string | photo, data processing, etc. |
| granted | boolean | |
| granted_at | timestamp nullable | |
| privacy_policy_version_id | FK | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Children's data

---

### PrivacyPolicyVersion

| Field | Type | Notes |
|-------|------|-------|
| id | bigint PK | |
| version | string | |
| content | text | |
| effective_from | date | |
| timestamps | | |

**Status:** Запланировано, не реализовано  
**Privacy:** Normal (legal document metadata)

---

## Обзор связей сущностей (запланировано)

```
Role ──< RoleMenuItem
  │
  └──< User (role_id)
         │
Student ──< Enrollment >── Group ──< Course
   │                          │
   └── student_parent ── Parent    └── Teacher
   │
   ├──< AttendanceRecord >── Lesson
   ├──< Document
   ├──< Payment
   └──< ConsentRecord
```

## Универсальный kit vs именование AUB

| Generic kit table | AUB equivalent (planned) | Action |
|-------------------|--------------------------|--------|
| customers | students | Create new `students` table |
| services | courses | Create new `courses` table |
| staff (directory) | teachers + staff roles | Keep directory; add `teachers` + RBAC |
| orders | enrollments + invoices | Create new tables |
| calendar (page) | weekly schedule service | Реализовано на `/schedule-service` (`weekly-schedule.*`) |

### Недельное расписание (реализовано 2026-07-20)

| Таблица / поле | Назначение |
|---------------|------------|
| `academy_buildings` | Локации (Accademia, Carcano, Danzadanza, Arcimboldi) |
| `academy_rooms` | Залы по зданиям |
| `schedule_weeks` | Понедельник `week_start_date`; конец недели — пятница; `work_starts_at` / `work_ends_at`; draft/published/locked |
| `scheduled_lessons` | Блоки: неделя, здание, зал, группа, преподаватель, предмет, дата/время |
| `schedule_ai_runs` | Журнал ИИ-прогонов (промпт, preferences, метрики, отчёт) |
| `courses.study_starts_at` / `study_ends_at` | Учебное окно (смена) курса |
| `course_group_lesson` | Несколько преподавателей на группу+урок (`hours` на каждого) |

Сетка: визуал 30 мин, планирование/ИИ 5 мин. См. [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).

Не переименовывать и не переиспользовать универсальные таблицы kit, пока модули академии не спроектированы и не утверждены.
