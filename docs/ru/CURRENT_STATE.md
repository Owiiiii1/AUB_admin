# AUB — Текущее состояние

Документ отражает **проверенное** состояние на **2026-09-07** (код + миграции + production `route:list` / `migrate:status`). Текст от 2026-07-20 считается устаревшим там, где он противоречит фактам.

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

## Маршруты (84 на production)

`php artisan route:list` 2026-09-07: **Showing [84] routes**. Файла `routes/api.php` **нет**, API для Flutter **нет**.

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
| `/customers`, `/customers/create`, `/customers/{id}` | `customers.*` | Студенты на `customers` |
| `/teachers`, `/teachers/create`, `/teachers/{id}` | `teachers.*` | Нет `user_id` |
| `/courses-groups` | `courses-groups.*` | Курсы, группы, привязка студентов/уроков |
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

Разница со старым числом «78» — serve-маршруты filesystem, `/up` и полный набор schedule/academy. **Измерено: 84.**

## Миграции

**37 файлов** в `database/migrations/`. Production: **все Ran**, batch **1–29**.

Ядро Laravel: users (включая sessions / password_reset_tokens), cache, jobs.

Kit: `customers`, `services`, `staff`, `orders`, `order_staff`, `ai_provider_settings`.

AUB: роли, seed меню, activity_logs, поля профиля студента, teachers, courses/groups, lessons/pivots, `can_write` / `can_delete`, недельное расписание, AI-прогоны, учебные окна, несколько преподавателей на группу+урок.

Не путать «29 batch» с «29 файлами».

## Таблицы

### Используются

| Таблица | Назначение |
|---------|------------|
| `users` | Auth; `role_id`, `can_write`, `can_delete` |
| `roles`, `role_menu_items` | RBAC |
| `customers` | **Студенты** (interim-таблица kit) |
| `teachers` | Преподаватели; **не** связаны с `users` |
| `courses`, `course_groups` | Курсы/группы; учебное окно курса |
| `course_group_customer` | Зачисление; unique `customer_id` (одна группа на студента) |
| `lessons` | Каталог (`duration_minutes`) |
| `lesson_teacher`, `lesson_course`, `course_group_lesson` | Связи уроков; несколько преподавателей |
| `academy_buildings`, `academy_rooms` | Локации |
| `schedule_weeks`, `scheduled_lessons` | Недельное расписание |
| `schedule_ai_runs` | Журнал ИИ |
| `activity_logs` | CRUD (+ login/logout) |
| `ai_provider_settings` | Encrypted ключи провайдеров |

### Legacy kit (нет активных маршрутов)

`services`, `staff`, `orders`, `order_staff`.

## Модели

| Model | Статус |
|-------|--------|
| User | Реализована — role, `can_write`, `can_delete` |
| Role, RoleMenuItem | Реализованы (фаза 1) |
| Customer | **Профиль студента** (нет inverse `courseGroups()`) |
| Teacher | Реализована; нет `user_id` |
| Course, CourseGroup | Реализованы |
| Lesson | Реализована |
| AcademyBuilding, AcademyRoom | Реализованы |
| ScheduleWeek, ScheduledLesson, ScheduleAiRun | Реализованы |
| ActivityLog | Реализована |
| AiProviderSetting | Реализована (`api_key` encrypted) |
| Service, Staff, Order | Legacy kit — не в маршрутах |

Моделей `Student` и `Parent` нет. Поля отца/матери на `customers`.

## Контроллеры / сервисы

| Область | Расположение |
|---------|--------------|
| Студенты | `CustomersController` |
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
| documents, communication, events, costumeService, archive | `placeholder.*` | Заглушки |

Отдельного пункта `lessons` в боковом extra-меню **нет**. Каталог — Настройки → Академия.

Конфиг: `config/aub-menu.php`. Флаг `admin_only` **не** enforced в `RoleAccess::syncRoleMenuItems`.

## Локализация

Локали UI: **it** (по умолчанию), en, ru, uk. Есть `lang/*/validation.php`. Экраны студентов также используют inline `translations`.

`.env.example` — скелет Laravel `APP_LOCALE=en`; default в `config/app.php` — `it`.

## Студенты (interim)

Реализовано на **`customers`**. Файлы: `storage/app/public/students/{id}/documents` (диск public) — **не** `customers/`. Фото преподавателей: `teachers/{id}/photos`.

Field-level ограничений нет: роль с доступом к `customers.*` видит контакты родителей и срок медсправки.

## Сводка по фазам

| Фаза | Модуль | Статус |
|------|--------|--------|
| 0 | Основа admin kit | Завершена |
| 1 | Роли и workplaces | Завершена (2026-07-06) |
| 2 | Студенты | Частично — `customers` |
| 2 | Родители | Частично — встроенные поля |
| 2 | Преподаватели | Справочник готов; нет связи с user |
| 2 | Курсы / группы | Готово |
| 2 | Зачисления | Частично — только pivot |
| 2 | Каталог уроков | Готово (Настройки → Академия) |
| 3 | Недельное расписание | Готово + гибридный ИИ (2026-07-20) |
| 3 | Загрузка файлов студента | Частично — public disk, нет модуля документов |
| 3 | Посещаемость | Не начато |
| 3 | Документы / коммуникации в меню | Заглушки |
| 4+ | Платежи | Не начато |
| API / Flutter | API + token auth | **Не начато**; репозиторий Flutter есть |

## Что НЕ реализовано

- Отдельные таблицы `students` / `parents`
- Полный workflow зачислений (статусы, переводы, история)
- Посещаемость
- Платежи / счета
- Отдельные модули документов и коммуникаций
- События, архив, костюмы (заглушки меню)
- **`routes/api.php`, API resources, Sanctum/Passport/JWT`**
- Функции Flutter сверх шаблона
- PDF-экспорт расписания (кнопка-заглушка)
- Исполняемые рекомендации ИИ (только re-prompt)
- Field-level visibility; ограничение преподавателя своими студентами
- Сущности согласий / privacy policy
- Access/view audit чувствительных записей
- Private storage документов детей
- Git-deploy с GitHub в `/var/www/aub`

## Кастомизации хоста (сохранять)

- Вход на `/`
- Маршруты kit CRM сняты
- Settings / roles / AI / academy (включая уроки) под `/settings`
- Итальянский UI по умолчанию
- Брендинг AUB (login, sidebar `#1A2B44`, Singo Sans)

## После изменений frontend/backend

См. [DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md) и [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md). Production — **не** git checkout.
