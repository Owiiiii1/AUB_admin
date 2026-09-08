# AUB — Текущее состояние

Документ отражает **проверенное** состояние на **2026-09-08** (Identity Layer + изолированная MySQL test DB). Текст от 2026-07-20 / 2026-09-07 считается устаревшим там, где он противоречит фактам.

См. также [ARCHITECTURE.md](ARCHITECTURE.md) — двухрепозиторная модель.

## Версии

| Компонент | Версия |
|-----------|--------|
| Laravel | 13.18.1 |
| PHP | 8.3.6 (production) |
| Node.js | 20.20.0 (production) |
| `owlsolutions/custom-admin-kit` | v0.4.0 |
| `inertiajs/inertia-laravel` | 3.1.1 |
| `@inertiajs/react` | 2.3.27 |
| `tightenco/ziggy` | 2.6.3 |
| React | 18.3.1 |
| Vite | 8.1.3 |
| Tailwind CSS | 4.3.2 |

Production-сборка есть **на сервере** (`public/build/manifest.json`). Каталог **gitignored**, поэтому клон GitHub не содержит собранных ассетов.

На production есть `storage:link`.

В хост-приложении нет `laravel/sanctum`, Passport, JWT.

## Автотесты

| Пункт | Значение |
|-------|----------|
| Движок | MySQL 8 (не SQLite) |
| Production DB | `aub` |
| Test DB | `aub_test` |
| Guard | `App\Testing\TestDatabaseGuard` — отказ от всего, кроме MySQL `aub_test` |
| Последний suite на production-хосте | **29 passed**, 0 failed, 0 errors (88 assertions) |

`php artisan test` использует phpunit.xml + серверный `.env.testing`. Feature-тесты — `RefreshDatabase` только против `aub_test`.

## Маршруты (web CRM + identity; API нет)

`php artisan route:list` после Identity: **Showing [96] routes**. Файла `routes/api.php` **нет**, API для Flutter **нет**.

### Auth

| Method | URI | Name |
|--------|-----|------|
| GET | `/` | `login` |
| POST | `/` | — |
| GET | `/login` | redirect to `/` |
| POST | `/login` | — |
| POST | `/logout` | `logout` |

Маршрутов password reset (`password.request` и т.п.) **нет**.

### Админка (auth + role middleware)

| URI | Name | Примечания |
|-----|------|------------|
| `/dashboard` | `dashboard` | **Заглушка** Home |
| `/customers`, `/customers/create`, `/customers/{student}` | `customers.*` | Студенты (`Student`); URL пока `/customers`; Account create/link на профиле |
| `/teachers`, `/teachers/create`, `/teachers/{id}` | `teachers.*` | `teachers.user_id` + Account create/link |
| `/courses-groups` | `courses-groups.*` | Курсы + `AcademyClass`; URL пока `/courses-groups` |
| `/lessons` | `lessons.*` | **GET index редиректит** на `/settings?tab=academy&academyTab=lessons` |
| `/schedule-service` | `weekly-schedule.*` | Алиасы `/schedules`, `/weekly-schedule` |
| `/documents` | `placeholder.documents` | Скоро будет |
| `/communication` | `placeholder.communication` | Скоро будет |
| `/events` | `placeholder.events` | Скоро будет |
| `/archive` | `placeholder.archive` | Скоро будет |
| `/costume-service` | `placeholder.costume-service` | Скоро будет |
| `/settings` | `settings.*` | Вкладки: users, roles, app, AI, academy (залы + **каталог уроков**) |
| `/roles` | `roles.*` | Редирект → `/settings?tab=roles` |
| `/ai-settings` | `ai-settings.*` | Редирект → `/settings?tab=ai` |
| `/app-settings` | — | Редирект → `/settings?tab=app` |
| `/statistics/logs` | `statistics.logs` | Журнал CRUD, не view-audit |
| `/profile` | `profile.*`, `password.update` | |
| `/workplace` | `workplace` | Тонкий landing для non-admin |

Запись/удаление: `can.write` / `can.delete`. CRUD ролей дополнительно `administrator`.

### Удалены из маршрутов (generic kit)

Контроллеры и таблицы остались; **маршруты и пункты меню сняты**: `/orders`, `/services`, `/staff`, `/calendar`.

### System

| URI | Name |
|-----|------|
| `/owl-admin/health` | `owl-admin.health` |
| `/up` | Laravel health |
| `GET/PUT storage/{path}` | `storage.local` / `storage.local.upload` |
| `/storage/{path}` через symlink | Файлы public disk (нужен `storage:link`) |

Разница со старым числом «84» — 12 POST маршрутов actor-account (student / parent / teacher). **Измерено: 96.** API нет.

## Миграции

**39 файлов** в `database/migrations/`. Identity Layer: `2026_09_08_220000_add_account_identity_layer`.

Ядро Laravel: users (включая sessions / password_reset_tokens), cache, jobs.

Kit: `customers` (legacy, academy больше не использует), `services`, `staff`, `orders`, `order_staff`, `ai_provider_settings`.

AUB: роли, seed меню, activity_logs, `students` / `parents` / `student_parent`, `academic_years`, `academy_classes`, `class_lessons`, `class_lesson_teacher`, teachers (`user_id`), Identity (`users.account_type`, `users.is_active`, `students.user_id`, `parents.user_id`), courses, lessons/pivots, `can_write` / `can_delete`, недельное расписание (`academy_class_id`), AI-прогоны, учебные окна.

## Таблицы

### Используются

| Таблица | Назначение |
|---------|------------|
| `users` | Auth; `account_type` (`staff` \| `student` \| `parent` \| `teacher`), `is_active`, `role_id`, `can_write`, `can_delete`. Email unique |
| `roles`, `role_menu_items` | RBAC (только web permissions; не actor type) |
| `academic_years` | Учебный год; один `is_active` как текущий |
| `students` | Профиль ученика; nullable unique `user_id` |
| `parents` | Родители/опекуны; PHP-модель `AcademyParent`; nullable unique `user_id` |
| `student_parent` | M2M + `relation_type` (`father` / `mother` / `guardian` / `other`) |
| `teachers` | Преподаватели; nullable unique `user_id` |
| `courses` | Направление/курс; учебное окно |
| `academy_classes` | Продуктовый `Class`; PHP-модель `AcademyClass` |
| `academy_class_student` | Зачисление; **unique `student_id`** = максимум один активный Class |
| `class_lessons` | Программа класса на AcademicYear; unique (year, class, lesson) |
| `class_lesson_teacher` | Несколько преподавателей на ClassLesson (`hours` на pivot) |
| `lessons` | Каталог дисциплин (`duration_minutes`) |
| `lesson_teacher`, `lesson_course` | Каталог: кто может вести урок / связь с курсом |
| `academy_buildings`, `academy_rooms` | Локации (колонок geofence lat/lng/radius нет) |
| `schedule_weeks`, `scheduled_lessons` | Недельное расписание; `scheduled_lessons.academy_class_id` |
| `schedule_ai_runs` | Журнал ИИ |
| `activity_logs` | CRUD (+ login/logout); `student_id` + legacy `customer_id` |
| `ai_provider_settings` | Encrypted ключи провайдеров |
| `customers` | **Legacy kit**; academy-логика больше не использует |

### Legacy kit (нет активных маршрутов)

`services`, `staff`, `orders`, `order_staff`.

## Модели

| Model | Статус |
|-------|--------|
| User | Реализована — `account_type`, `is_active`, role, `can_write`, `can_delete`; `studentProfile` / `parentProfile` / `teacherProfile` |
| Role, RoleMenuItem | Реализованы (фаза 1) |
| Customer | Legacy kit — **не** academy Student |
| Student | Профиль ученика; `user()` |
| AcademyParent | Родитель (`parents`); `user()` |
| Teacher | Реализована; `user()` |
| Course | Направление |
| AcademyClass | Продуктовый `Class` |
| AcademicYear | Учебный год |
| ClassLesson | Урок класса на год |
| Lesson | Каталог дисциплин |
| AcademyBuilding, AcademyRoom | Реализованы |
| ScheduleWeek, ScheduledLesson, ScheduleAiRun | Реализованы; `ScheduledLesson` → `AcademyClass` |
| ActivityLog | Реализована (`student_id`) |
| AiProviderSetting | Реализована (`api_key` encrypted) |
| Service, Staff, Order | Legacy kit — не в маршрутах |

## Контроллеры / сервисы

| Область | Расположение |
|---------|--------------|
| Студенты | `StudentsController` (Inertia `Customers/*`, URL `/customers`) |
| Преподаватели | `TeachersController` |
| Курсы/группы | `CoursesGroupsController` |
| Каталог уроков | `LessonsController` (UI через Settings) |
| Недельное расписание | `WeeklyScheduleController` |
| ИИ-планировщик | `Services/WeeklySchedule/*`, `Services/Ai/*` |
| Журнал | `ActivityLogController`, `ActivityLogger` |
| Роли / пользователи / AI / академия | `RolesController`, `Settings/*` |
| Auth / профиль | `AuthenticatedSessionController`, `ProfileController` |
| Workplace | `WorkplaceController` |

Контроллеры kit `OrdersController`, `ServicesController`, `StaffController`, `CalendarController` **не подключены к маршрутам**.

## Inertia / React

| Страница | Path | Статус |
|----------|------|--------|
| Login | `Auth/Login.jsx` | Реализовано |
| Dashboard | `Dashboard.jsx` | **Заглушка** |
| Студенты | `Customers/Index.jsx`, `Customers/Profile.jsx` | Реализовано |
| Преподаватели | `Teachers/Index.jsx`, `Teachers/Profile.jsx` | Реализовано |
| Курсы и группы | `CoursesGroups/Index.jsx` | Реализовано |
| Каталог уроков | `Settings/Tabs/LessonsTab.jsx` | Реализовано; **нет** `Lessons/Index.jsx` |
| Недельное расписание | `WeeklySchedule/Index.jsx` | Реализовано |
| Настройки | `Settings/Index.jsx` + `Tabs/*` | Реализовано; вкладка academy «general» — текст-заглушка |
| Статистика | `Statistics/Logs.jsx` | Реализовано |
| Профиль | `Profile/Edit.jsx` | Реализовано |
| Workplace | `Workplace/Index.jsx` | Тонкий landing |
| Скоро будет | `Placeholder/ComingSoon.jsx` | Заглушки |
| Остатки kit | `Orders`, `Services`, `Staff`, `Calendar`, `Roles/Index`, `AiSettings/Index`, `AppSettings/Index` | Файлы есть; не активный UI |

Layouts: `AdminLayout.jsx`, `AuthLayout.jsx`.

## Меню

Динамические пункты из `role_menu_items`: `dashboard`, `students` → `customers.index`, `teachers`, `settings` (флаг admin_only в конфиге), `statistics`.

**Всегда в extra-списке `AdminLayout`** (и в `always_allowed_route_patterns` для любого пользователя с ролью):

| Key | Route | Статус |
|-----|-------|--------|
| coursesAndGroups | `courses-groups.index` | Реализовано |
| scheduleService | `weekly-schedule.index` | Реализовано |
| documents, communication, events, costumeService, archive | `placeholder.*` | UI-заглушки. Productions — **поздний future**; `/events` остаётся заглушкой. Costume Service может остаться отдельным сервисом. |

Отдельного пункта `lessons` в боковом extra-меню **нет**. Каталог — Настройки → Академия.

Конфиг: `config/aub-menu.php`. Флаг `admin_only` **не** enforced в `RoleAccess::syncRoleMenuItems`.

## Локализация

Локали UI: **it** (по умолчанию), en, ru, uk. Есть `lang/*/validation.php`. Экраны студентов также используют inline `translations`.

`.env.example` — скелет Laravel `APP_LOCALE=en`; default в `config/app.php` — `it`.

## Студенты

Реализовано на **`students`** + **`parents`** + **`student_parent`**. UI Father/Mother сохранён; backend пишет `AcademyParent` + `relation_type`. URL `/customers` и Inertia `Customers/*` оставлены без redesign. Файлы: `storage/app/public/students/{id}/documents` (диск **public**) — security debt, private storage отдельным этапом. Фото преподавателей: `teachers/{id}/photos`.

Field-level ограничений нет: роль с доступом к `customers.*` видит контакты родителей и срок медсправки.

## Сводка по фазам

| Фаза | Модуль | Статус |
|------|--------|--------|
| 0 | Основа admin kit | Завершена |
| 1 | Роли и workplaces | Завершена (2026-07-06) |
| 2 | Студенты | Готово — `students` |
| 2 | Родители | Готово — `parents` / `student_parent` |
| 2 | Преподаватели | Справочник + Identity: create/link account; web role опционален |
| 2 | Курсы / Class | Готово — `Course` + `AcademyClass` |
| 2 | Зачисления | Частично — unique один Class; workflow статусов OPEN |
| 2 | Каталог уроков | Готово (Настройки → Академия) |
| 3 | Недельное расписание | Готово + гибридный ИИ (2026-07-20) |
| 3 | Загрузка файлов студента | Частично — public disk, нет модуля документов |
| 3 | **Student Attendance** | Не начато (ребёнок на session) |
| 3 | **Teacher Check-in** | Не начато (**DECIDED**: daily GPS snapshot + geofence; не per lesson) |
| 3 | Документы / коммуникации в меню | Заглушки |
| 4+ | Платежи | Не начато |
| future | Final Assessment / Report Cards | Отдельный модуль; текущих оценок нет; не начато |
| late future | Productions / Shows | Discovery-needed; activity groups ≠ `Class`; web `/events` — заглушка |
| API / Flutter | API + token auth | **Не начато**; репозиторий Flutter есть; упаковка Store **OPEN**. Стабильный API **после** Identity (сделан) |

## Что НЕ реализовано

- **API Foundation** (login `/api`, Sanctum/JWT, `/me`) — следующий этап
- Invitation / activation workflow
- Отдельный login identifier помимо unique email
- Полноценный UI AcademicYear
- Полный workflow зачислений (статусы, переводы, история). Правило «один активный `Class`» — DECIDED
- **Student Attendance** (уровень session) и **Teacher Check-in** (daily presence — семантика DECIDED, код нет)
- Final Assessment / `StudentFinalResult` / `ReportCard` (ядро DECIDED; не реализовано; текущих оценок нет)
- Productions / activity groups / репетиции / спектакли (поздний future; web `/events` — только заглушка)
- Платежи / счета
- Отдельные модули документов и коммуникаций
- Архив, костюмы (заглушки меню; костюмы могут остаться отдельным сервисом)
- **`routes/api.php`, API resources, Sanctum/Passport/JWT`**
- Функции Flutter сверх шаблона; одно приложение vs flavors **OPEN**
- PDF-экспорт расписания (кнопка-заглушка)
- Исполняемые рекомендации ИИ (только re-prompt)
- Field-level visibility; ограничение преподавателя своими студентами
- Сущности согласий (`ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`)
- Access/view audit чувствительных записей
- Private storage документов детей
- 2FA администраторов
- Git-deploy с GitHub в `/var/www/aub`

## Кастомизации хоста (сохранять)

- Вход на `/`
- Маршруты kit CRM сняты
- Settings / roles / AI / academy (включая уроки) под `/settings`
- Итальянский UI по умолчанию
- Брендинг AUB (login, sidebar `#1A2B44`, Singo Sans)

## После изменений frontend/backend

См. [DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md) и [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md). Production — **не** git checkout.
