# Cursor Work Report

## Task

Полный технический аудит фактического состояния `AUB_admin` после переноса проекта с сервера в GitHub. Новый функционал не разрабатывался. Код, миграции, БД, маршруты и конфигурация не изменялись. Цель — зафиксировать реализованное состояние для Tech Lead и расхождения документации с кодом.

Репозиторий аудита: `Owiiiii1/AUB_admin` (локальный путь `C:\Users\owlni\PhpstormProjects\AUB`, production `/var/www/aub`). Flutter-репозиторий `Owiiiii1/AUB_app` учтён только как внешний канал; его код не менялся.

## Repository state

- **branch:** `main`
- **HEAD до работы:** `e53971ad41e3840a924251d3c6a7c40e8b905c68` (`Initial AUB backend baseline`)
- **remote:** `https://github.com/Owiiiii1/AUB_admin.git`
- **working tree до работы:** clean, `main` совпадает с `origin/main`
- **working tree после работы:** добавлен только этот отчёт (`docs/Development/Cursor_Work_Report.md`)

Production `/var/www/aub` **не является git-репозиторием**. GitHub пока не является runtime-источником сервера: деплой по-прежнему файловый.

## Environment

Источник версий: `composer.lock` / `package-lock.json` + production SSH (`deploy@178.156.234.23:/var/www/aub`). Секреты `.env` не читались и не включались.

| Компонент | GitHub lock / код | Production (проверено) | Локальная Windows-машина аудита |
|-----------|-------------------|------------------------|----------------------------------|
| PHP | `^8.3` | **8.3.6** | PHP не в PATH — `php artisan` локально NOT RUN |
| Laravel | **v13.18.1** | **v13.18.1** | — |
| Composer | lock присутствует | **2.9.4** | Composer не в PATH |
| `owlsolutions/custom-admin-kit` | **v0.4.0** (`2ec18792…`) | **v0.4.0** | vendor есть локально |
| `inertiajs/inertia-laravel` | **v3.1.1** | **v3.1.1** | |
| `@inertiajs/react` | **2.3.27** | lock тот же | |
| `tightenco/ziggy` | **v2.6.3** | **v2.6.3** | |
| React / react-dom | **18.3.1** | lock тот же | |
| Vite | **8.1.3** | lock тот же | локальный `npm run build` FAIL (см. Commands) |
| Tailwind CSS | **4.3.2** | lock тот же | |
| Radix UI | **1.6.1** (`package.json`) | | |
| lucide-react | **1.23.0** | | |
| Node.js | docs: 20.20.0 | **v20.20.0** | **v24.14.0** |
| npm | | **10.8.2** | **11.9.0** |
| MySQL | docs: 8.0.46 | **8.0.46** (Ubuntu 24.04) | локальный `.env` не разбирался |
| Nginx | docs: 1.24.0 | **1.24.0** | |
| Frontend build | `public/build` gitignored | `public/build/manifest.json` есть | локальный `manifest.json` есть |
| `storage:link` | требуется кодом | symlink есть | локальный `public/storage` есть |

Дополнительно (Composer require, не third-party API): `laravel/tinker ^3.0`. Dev: phpunit **^12.5.12**, pint, pail, pao, faker, mockery, collision.

`laravel/sanctum`, Passport, JWT **не установлены**. В `composer.lock` Sanctum встречается только как `suggest` пакета `custom-admin-kit`.

`.env.example` — Laravel-скелет (`APP_LOCALE=en`, `DB_CONNECTION=sqlite`). Фактический default локали в `config/app.php`: `env('APP_LOCALE', 'it')`. Production `.env` не читался.

## Current implemented modules

| Module | Backend | UI | DB | Status | Notes |
|--------|---------|----|----|--------|-------|
| Admin kit foundation | custom-admin-kit v0.4.0 + host overrides | Inertia/React, AdminLayout/AuthLayout | users, sessions, cache, jobs | готово | Login на `/`, не `/login` |
| Auth (session) | `AuthenticatedSessionController`, guard `web` | `Auth/Login.jsx` | `users`, `sessions` | готово | Password reset маршрутов нет |
| Roles / workplaces | Role, RoleMenuItem, middleware, `RoleAccess` | Settings → Roles; `Workplace/Index.jsx` | `roles`, `role_menu_items`, `users.role_id` | готово | Workplace — тонкий landing |
| Users admin | `Settings\UserController` | Settings → Users | `users` + `can_write`/`can_delete` | готово | |
| Students | `CustomersController` на модели `Customer` | `Customers/Index.jsx`, `Customers/Profile.jsx` | расширенная `customers` | частично | Нет модели/таблицы `students` |
| Parents | поля отца/матери в `customers` | модалки в профиле студента | колонки `father_*` / `mother_*` + legacy `parent_*` | частично | Нет сущности Parent |
| Teachers | `TeachersController` | `Teachers/Index.jsx`, `Teachers/Profile.jsx` | `teachers` | готово | Нет `user_id`, нет связи с login |
| Courses / groups | `CoursesGroupsController` | `CoursesGroups/Index.jsx` | `courses`, `course_groups` | готово | Учебное окно смены на курсе |
| Enrollments | attach/detach студента к группе | UI внутри courses-groups | pivot `course_group_customer` | частично | Нет статусов/истории/переводов как отдельного workflow |
| Lessons catalog | `LessonsController` | **Settings → Academy → Lessons** (`Settings/Tabs/LessonsTab.jsx`) | `lessons` + pivots | готово | `/lessons` редиректит в settings; страницы `Lessons/Index.jsx` нет |
| Academy locations | `AcademySettingsController` | Settings → Academy → halls | `academy_buildings`, `academy_rooms` | готово | Вкладка «general» — placeholder |
| Weekly schedule | `WeeklyScheduleController` + planners | `WeeklySchedule/Index.jsx` + palette/block | `schedule_weeks`, `scheduled_lessons` | готово | Пн–пт; publish/copy/clear/conflicts |
| AI schedule planning | `WeeklyScheduleAiService`, `DeterministicSchedulePlanner`, AI clients | модалка в weekly schedule | `schedule_ai_runs`, `ai_provider_settings` | готово как гибрид | Кнопка рекомендаций только re-prompt |
| AI provider settings | `AiSettingsController` | Settings → AI | `ai_provider_settings` | готово | Ключи encrypted cast |
| Activity / audit log | `ActivityLogger`, `ActivityLogController` | `Statistics/Logs.jsx` | `activity_logs` | частично | CRUD + login/logout; нет access-log просмотра профиля |
| App settings / locale | `SettingsController@updateLanguage` | Settings → App | session locale | готово | Локали it/uk/en/ru |
| Dashboard | closure Inertia | `Dashboard.jsx` | — | placeholder | Текст «страница-заглушка» |
| Documents module | нет отдельного модуля | `Placeholder/ComingSoon` + upload в профиле | path-колонки на `customers` | placeholder + частичные файлы | |
| Communication | нет | placeholder; иконка в списке студентов не работает | — | нет | |
| Events / archive / costumes | нет | placeholders | — | нет | |
| Attendance | нет | нет | нет | нет | |
| Payments / invoices | нет | нет | нет (kit `orders.total` не используется) | нет | |
| Mobile API | нет `routes/api.php` | Flutter-репо отдельно | — | нет | Flutter — дефолтный шаблон |
| Kit CRM (orders/services/staff/calendar) | контроллеры/модели есть | страницы Inertia есть | таблицы есть | legacy, не в маршрутах | |

## Current database/domain model

Миграции: **37 файлов** в `database/migrations/`. На production все **Ran**. Номера batch: 1…29 (это и есть «29 batch-записей» в `CURRENT_STATE.md`, не 29 файлов). `ARCHITECTURE.md` («31 ran») не подтверждается.

### AUB-сущности (используются)

| Модель | Таблица | Основные relations | Роль |
|--------|---------|--------------------|------|
| `User` | `users` | `belongsTo Role` | Session-auth, `role_id`, `can_write`, `can_delete` |
| `Role` | `roles` | `hasMany RoleMenuItem`, `hasMany User` | RBAC; `is_admin` / `is_system` / `is_active` |
| `RoleMenuItem` | `role_menu_items` | `belongsTo Role` | Пункты меню роли (`dashboard`, `students`, `teachers`, `settings`, `statistics`) |
| `Customer` | `customers` | `hasMany Order` (legacy); **нет** inverse на группы | **Студент** (interim). Личные поля, отец/мать, документы, фото |
| `Teacher` | `teachers` | `belongsToMany Lesson` (`lesson_teacher`) | Преподаватель академии. Нет `user_id` |
| `Course` | `courses` | `hasMany CourseGroup`; `belongsToMany Lesson` (`lesson_course`) | Дисциплина/курс + `study_starts_at` / `study_ends_at` |
| `CourseGroup` | `course_groups` | `belongsTo Course`; `belongsToMany Customer`; `belongsToMany Lesson` (pivot `teacher_id`, `hours`) | Группа курса, `color` |
| `Lesson` | `lessons` | teachers, courses, groups | Каталог предметов (`discipline`, `duration_minutes`) |
| — | `course_group_customer` | unique `customer_id` (один студент → одна группа после `2026_07_08_170000`) | Зачисление |
| — | `lesson_teacher`, `lesson_course`, `course_group_lesson` | unique `(course_group_id, lesson_id, teacher_id)` | Назначения уроков; несколько преподавателей на группу+урок |
| `AcademyBuilding` | `academy_buildings` | `hasMany AcademyRoom` | Площадки |
| `AcademyRoom` | `academy_rooms` | `belongsTo Building`; `hasMany ScheduledLesson` | Залы (`capacity`, `room_type`) |
| `ScheduleWeek` | `schedule_weeks` | `hasMany ScheduledLesson`, `hasMany ScheduleAiRun`; `belongsTo User` (publisher) | Неделя пн–пт; `draft`/`published`/`locked`; рабочие часы |
| `ScheduledLesson` | `scheduled_lessons` | week, building, room, group, teacher, lesson | Блок на доске |
| `ScheduleAiRun` | `schedule_ai_runs` | `belongsTo ScheduleWeek`, `belongsTo User` | Журнал ИИ-прогонов |
| `ActivityLog` | `activity_logs` | `belongsTo User`, `belongsTo Customer` | Аудит действий (без `updated_at`) |
| `AiProviderSetting` | `ai_provider_settings` | — | Ключи провайдеров (`api_key` encrypted) |

Правила enrollments по миграциям: сначала unique `(customer_id, discipline)`, затем заменено unique `customer_id` — фактически **один активный group-assignment на студента**, не «по дисциплине».

### Legacy kit

| Модель | Таблица | Роль |
|--------|---------|------|
| `Service` | `services` | Универсальный каталог услуг kit; маршрутов нет |
| `Staff` | `staff` | Справочник персонала kit; `staff.role` — свободный текст, не RBAC |
| `Order` | `orders` + `order_staff` | Универсальные заказы kit; маршрутов нет |

`Customer::orders()` живёт, но UI/routes заказов отключены.

### Системные Laravel-таблицы

`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `migrations`.

Документ `DATA_MODEL_DRAFT` упоминает legacy Spatie-таблицы (`permissions`, `model_has_*`, `media`, `settings`, `personal_access_tokens`) как «DB only». Миграция `2026_07_06_120000` **дропает** Spatie-подобные таблицы при up. Текущее наличие этих таблиц в MySQL **не подтверждалось** (не выполнялся `SHOW TABLES`).

### Не существует в коде

Отдельных моделей/таблиц `Student`, `Parent`, `Enrollment`, `AttendanceRecord`, `Document`, `Payment`, `Invoice`, `ConsentRecord`, `PrivacyPolicyVersion` нет.

## Current API state

**Мобильного/REST API фактически нет.**

Проверено по коду и production `route:list` (84 маршрута):

- файла `routes/api.php` **нет**;
- `bootstrap/app.php` регистрирует только `web`, `commands`, `health: /up` — **без** `api:`;
- guard только `web` / session (`config/auth.php`);
- пакетов Sanctum / Passport / JWT нет;
- каталога `app/Http/Resources/` нет;
- API controllers / token auth / `/api/*` endpoints нет;
- `withExceptions()->shouldRenderJsonWhen(fn => $request->is('api/*'))` — заготовка Laravel, мёртвый путь;
- kit **suggests** Sanctum для `/api/user`, хост это не использует.

Все рабочие endpoints — Inertia web-формы (cookie session + CSRF). Для Flutter сейчас **нет пригодного API**.

Отдельный репозиторий `Owiiiii1/AUB_app` существует (`pubspec.yaml` name `aub`, SDK `^3.10.7`), фактически дефолтный Flutter-проект: один `lib/main.dart`, зависимости только `flutter` + `cupertino_icons`. HTTP-клиента, auth и вызовов backend нет.

## Authentication and access control

Фактически реализовано:

- Session auth, login `GET/POST /`, logout `POST /logout`.
- `/login` GET редиректит на `/`; POST `/login` дублирует store.
- После логина: admin → `dashboard`, non-admin → первый `role_menu_items` route или `workplace` (`RoleAccess::postLoginRedirectUrl`).
- Middleware aliases: `role.assigned`, `role.access`, `administrator`, `can.write`, `can.delete`.
- Защищённые страницы: `AdminRouteMiddleware::stack()` (`web`+`auth`) + `role.assigned` + `role.access`.
- `can.write` на POST/PATCH доменных записей; `can.delete` на DELETE.
- `administrator` на CRUD ролей.
- Lockout последнего администратора: `AdministratorLockoutGuard`.
- Activity log: login/logout и CRUD через `ActivityLogger` (пароли/`api_key` редактируются).
- Uploads: диск `public`, пути `students/{id}/documents` и `teachers/{id}/photos`. Нужен `storage:link` (на production есть). Файлы отдаются как публичные storage-URL.
- Password-protected delete студента: `current_password` в `CustomersController@destroy`.
- Локали UI: `it` (default config), `uk`, `en`, `ru`.
- Password reset: `Route::has('password.request')` = false; ссылка в login скрыта.

Ограничения (факты кода, не pentest):

- Нет field-level ACL на профиле студента: роль с `customers.*` видит родителей, контакты, медсправку, файлы.
- `config/aub-menu.php` `always_allowed_route_patterns` даёт **любому** пользователю с ролью доступ к `courses-groups.*`, `lessons.*`, `weekly-schedule.*`, `placeholder.*`, `profile.*`, `workplace` — даже если пункты не в `role_menu_items`. Меню в `AdminLayout` действительно дописывает courses/schedule/placeholders всем auth-пользователям.
- `MenuRegistry::isAdminOnlyMenuKey()` **нигде не вызывается** для enforcement. `settings.admin_only` — метаданные для UI; `syncRoleMenuItems` не фильтрует admin-only ключи.
- `EnsureRouteAllowedForRole`: если у маршрута нет `name`, проверка пропускается.
- `PUT/GET storage/{path}` (`storage.local` / `storage.local.upload`) присутствуют в route list (Laravel filesystem `serve`). Не тестировалось.
- Нет ограничения «учитель видит только своих студентов».
- Нет consent/privacy-policy сущностей.

## Existing tests

Найдено только Laravel skeleton:

- `tests/Unit/ExampleTest.php` — `assertTrue(true)`
- `tests/Feature/ExampleTest.php` — `GET /` ожидает 200
- `phpunit.xml` — sqlite `:memory:`, `SESSION_DRIVER=array` (не бьёт production MySQL)

Доменных тестов (students, schedule, roles, AI) нет. Factories: только `UserFactory`. `DatabaseSeeder` всё ещё создаёт `test@example.com` (скелет; на production, судя по docs, админ создавался через `owl-admin:make-admin` — не перепроверялось запросом к БД).

Запуск: локально PHP нет. На production `php artisan test` — **2 passed (2 assertions)**. Для Feature-теста phpunit переключает БД на sqlite memory, production-данные не мигрировались и не чистились.

## Documentation audit

Прочитаны все запрошенные RU-документы и соответствующие EN. Индекс `docs/README.md` корректен по списку файлов.

### RU / EN синхронизация

**Не полностью синхронизированы.** Правило «каждая правка — обе локали» в индексе есть, но содержимое разошлось.

| Пара | Оценка |
|------|--------|
| `CURRENT_STATE.md` | Структура совпадает; объёмный перевод. Общие фактические ошибки (см. таблицу расхождений) в обеих. |
| `NEXT_STEPS.md` | Структурно синхронны. |
| `WEEKLY_SCHEDULE_SERVICE.md` | Структурно синхронны; близки к коду расписания. |
| `ARCHITECTURE.md` | **Рассинхрон RU vs EN.** EN описывает AUB middleware/models/services; RU всё ещё kit-era (Models: User, Customer, Order, Service, Staff). |
| `DATA_MODEL_DRAFT.md` | **Рассинхрон.** EN: секция C = implemented teachers/courses/lessons. RU: секция C = «запланированные» Student/Teacher/Course. |
| `MODULE_ROADMAP.md` | EN-диаграмма содержит Phase 1; RU-диаграмма дублирует Phase 2 и пропускает Phase 1. |
| `MVP_SCOPE`, `DEVELOPMENT_RULES`, `SERVER_DEPLOYMENT`, `USER_ROLES_AND_ACCESS`, `README_PROJECT_OVERVIEW`, `PRIVACY_AND_DATA_PROTECTION` | В целом парные переводы; оба PRIVACY помечают RBAC как «planned» в блоке technical measures, хотя RBAC реализован. |

Документы, которые **нужно будет обновить** под двухрепозиторную архитектуру (пока не переписывались):

- `docs/README.md`
- `docs/{en,ru}/README_PROJECT_OVERVIEW.md`
- `docs/{en,ru}/ARCHITECTURE.md`
- `docs/{en,ru}/CURRENT_STATE.md`
- `docs/{en,ru}/NEXT_STEPS.md`
- `docs/{en,ru}/MODULE_ROADMAP.md`
- `docs/{en,ru}/MVP_SCOPE.md`
- `docs/{en,ru}/DEVELOPMENT_RULES.md`
- `docs/{en,ru}/SERVER_DEPLOYMENT.md` (добавить GitHub как источник и отсутствие `.git` на сервере)
- `docs/{en,ru}/USER_ROLES_AND_ACCESS.md` (parent/student как Flutter-канал через API)
- `docs/{en,ru}/PRIVACY_AND_DATA_PROTECTION.md` (mobile apps уже заведены как отдельный репозиторий)
- `docs/{en,ru}/DATA_MODEL_DRAFT.md` (особенно RU)

`WEEKLY_SCHEDULE_SERVICE.md` ближе всего к истине среди модульных docs.

## Documentation vs code discrepancies

| Документ | Что утверждает | Что фактически в коде | Статус |
| -------- | -------------- | --------------------- | ------ |
| `CURRENT_STATE.md` | 78 зарегистрированных маршрутов | Production `route:list`: **84** (в т.ч. `storage.local`, `storage.local.upload`, `/up`) | частично устарело |
| `CURRENT_STATE.md` | 29 batch-записей миграций | 37 migration-файлов, все Ran; batch 1–29 | частично устарело (путаница batch vs files) |
| `ARCHITECTURE.md` | 31 migration ran | 37 Ran | устарело |
| `CURRENT_STATE.md` | страница уроков `Lessons/Index.jsx`; пункт меню lessons в AdminLayout | Файла нет; `LessonsController@index` редирект на `settings?tab=academy&academyTab=lessons`; в extra-меню AdminLayout уроков нет | устарело |
| `CURRENT_STATE.md` | файлы студентов `storage/app/public/customers/` | `store(..., 'public')` → `students/{id}/documents` | устарело |
| `CURRENT_STATE.md` | Dashboard как обычный экран админки | `Dashboard.jsx` — явная заглушка | частично устарело |
| `CURRENT_STATE.md` / overview | production build существует | В git `public/build` ignored; на сервере manifest есть | актуально для сервера; невозможно подтвердить из GitHub tree |
| `CURRENT_STATE.md` | Mobile API не начат | Подтверждено: API нет. Но Flutter-репозиторий **уже создан** (docs этого не знают) | частично устарело |
| `NEXT_STEPS.md` | «Не реализовывать mobile-приложения до стабилизации web» | Архитектурное решение от 2026-09-07: два репо, Flutter уже есть | устарело относительно нового решения |
| `ARCHITECTURE.md` RU | Models: User, Customer, Order, Service, Staff | Есть Teacher, Course, Lesson, Schedule*, Role, ActivityLog, … | устарело (RU) |
| `ARCHITECTURE.md` | Future mobile API в том же монолите `routes/api.php` | API файла нет; Flutter вынесен в `AUB_app` | частично устарело |
| `ARCHITECTURE.md` | «существующие CRM-модули kit» (orders/services/staff/calendar) как рабочие | Маршруты и меню удалены; остались файлы | частично устарело |
| `DATA_MODEL_DRAFT.md` RU | Teacher/Course/Group/Lesson — planned | Реализованы миграциями 2026-07-08…07-20 | устарело (RU) |
| `DATA_MODEL_DRAFT.md` EN | one group per discipline + one group per student | Unique `customer_id` supersedes discipline unique | частично устарело |
| `PRIVACY_AND_DATA_PROTECTION.md` | RBAC и access audit — planned | RBAC есть; CRUD activity log есть; field-level и view-audit нет | частично устарело |
| `MODULE_ROADMAP.md` Phase 0 | generic CRM orders/services/staff/calendar работают | UI/routes сняты | частично устарело |
| `MODULE_ROADMAP.md` Phase 5 | REST/API и mobile apps как будущая фаза | Flutter-репо создан; API всё ещё нет | частично устарело |
| `MODULE_ROADMAP.md` RU diagram | Phase 2 дважды, Phase 1 отсутствует | Phase 1 реализована 2026-07-06 | устарело (RU diagram) |
| `MVP_SCOPE.md` | Students — отдельная сущность AUB, не дублировать customers | Реализация **на** `customers` | частично устарело vs исходный MVP intent (код interim, это же отражено в CURRENT_STATE) |
| `USER_ROLES_AND_ACCESS.md` | Extra AdminLayout items включают lessons | lessons не в `PLACEHOLDER_MENU_ITEMS` | частично устарело |
| `USER_ROLES_AND_ACCESS.md` | секции «planned features» рядом со status ✅ | Реализация Phase 1 есть; матрица доступа всё ещё preliminary и не enforced в коде | частично устарело |
| `DEVELOPMENT_RULES.md` | путь `/var/www/aub` как единственный locus | Теперь ещё GitHub `AUB_admin` + `AUB_app` | частично устарело |
| `SERVER_DEPLOYMENT.md` | стандартный git/composer workflow на сервере | На сервере **нет** `.git` | частично устарело |
| `README_PROJECT_OVERVIEW.md` | mobile apps — будущее | Репозиторий приложения создан | частично устарело |
| `docs/README.md` | Last updated 2026-07-20; current work Phase 2–3 | Верно для web-модулей; не отражает GitHub split и audit 2026-09-07 | частично устарело |
| `WEEKLY_SCHEDULE_SERVICE.md` | маршруты, гибридный ИИ, 5/30 grid, ограничения | Совпадает с controller/services/UI | актуально |
| `CURRENT_STATE.md` версии Laravel/kit/Inertia/Ziggy | 13.18.1 / v0.4.0 / 3.1.1 / 2.6.3 | Совпадает с lock и production | актуально |
| Тестовый `admin@admin.com` в overview | существует | Не проверялось запросом к БД | невозможно подтвердить |

## Technical debt / suspicious areas

Только наблюдения, без исправлений:

1. **Нет API** при уже существующем Flutter-репозитории — главный архитектурный gap.
2. Студенты живут в kit-таблице `customers`; модель не имеет `courseGroups()`.
3. Преподаватель не связан с `users` — teacher workplace/login невозможен без доработки.
4. Extra-меню и `always_allowed_route_patterns` шире, чем RBAC-меню роли.
5. `admin_only` не enforced на backend.
6. Kit-контроллеры и Inertia-страницы `Orders`, `Services`, `Staff`, `Calendar`, плюс неиспользуемые `Roles/Index.jsx`, `AiSettings/Index.jsx`, `AppSettings/Index.jsx` остались в дереве.
7. Документы студента на **public** disk.
8. `locked` статус недели в схеме есть, UI его не выставляет (уже в weekly-schedule docs).
9. Dashboard и academy «general» — placeholders.
10. Локальный `node_modules` на Windows, судя по ошибке rolldown native binding, похож на Linux-копию с сервера; `npm run build` на машине аудита не работает без переустановки deps (не делалась).
11. Production без git: расхождение GitHub ↔ `/var/www/aub` может накапливаться незаметно.
12. Тестов на домен нет.
13. `.env.example` не отражает academy defaults (locale it, MySQL).
14. `DatabaseSeeder` скелетный.

## Blockers

- Локально нет `php`/`composer` в PATH → `php artisan route:list` / `migrate:status` / `test` выполнялись **на production**, не на Windows-копии.
- `.env` не читался (политика секретов) → фактические `APP_DEBUG`, DB name, mail driver с машины аудита не подтверждались, кроме того что artisan на сервере работает.
- `SHOW TABLES` / содержимое БД не снималось — legacy Spatie-таблицы «DB only» не верифицированы.
- Наличие пользователя `admin@admin.com` не проверялось SQL-запросом.
- Локальный `npm run build` не прошёл из-за отсутствующего `@rolldown/binding-win32-x64-msvc`; `npm install` / `audit fix` не запускались по задаче.
- Production не git-репозиторий — нельзя сравнить HEAD сервера с GitHub без файлового diff.
- Penetration testing не проводился.

## Commands executed

| Command | Where | Result |
|---------|-------|--------|
| `git status` / `git log -1` / `git remote -v` | local AUB_admin | **PASS** — `main`, clean, `e53971a`, origin GitHub |
| `php -v` / `php artisan --version` | local Windows | **NOT RUN** — php не в PATH |
| `composer --version` | local | **NOT RUN** — composer не в PATH |
| `node -v` / `npm -v` | local | **PASS** — Node 24.14.0, npm 11.9.0 |
| `npm run build` | local | **FAIL** — `vite` не в PATH скрипта; повтор через `node node_modules/vite/bin/vite.js build` FAIL (`@rolldown/binding-win32-x64-msvc` missing) |
| `ssh … php -v` и stack versions | production | **PASS** — PHP 8.3.6, Node 20.20.0, MySQL 8.0.46, Nginx 1.24.0, Composer 2.9.4 |
| `php artisan --version` | production `/var/www/aub` | **PASS** — Laravel 13.18.1 |
| `php artisan migrate:status` | production | **PASS** — все 37 миграций Ran, destructive migrate не запускался |
| `php artisan route:list` | production | **PASS** — 84 routes, api нет |
| `php artisan route:list --columns=… --json` | production | **FAIL** — опции `--columns` нет в этой версии |
| `composer show owlsolutions/custom-admin-kit` (+ inertia, ziggy, framework) | production | **PASS** |
| `php artisan test` | production | **PASS** — 2 passed; sqlite memory, без migrate --force |
| `git rev-parse` на сервере | production | **PASS** (диагностика) — not a git repository |
| `test -f public/build/manifest.json` / `test -L public/storage` | production | **PASS** — оба yes |
| Чтение `.env` / dump credentials | — | **NOT RUN** |
| `php artisan migrate` | — | **NOT RUN** |
| `npm install` / `npm audit fix` | — | **NOT RUN** |

## Files changed

Создано:

- `docs/Development/Cursor_Work_Report.md`

Функциональный код, миграции, маршруты, конфиги, `vendor/`, `.env` — без изменений.

## Conclusion

Для Tech Lead, фактический статус на 2026-09-07:

**Реально готово (web CRM ядро):** session-auth; роли и workplaces Phase 1; пользователи/`can_write`/`can_delete`; преподаватели; курсы/группы; каталог уроков (внутри Settings → Academy); площадки/залы; недельное расписание с конфликтами, publish/copy/clear и гибридным ИИ; журнал `schedule_ai_runs`; activity log CRUD; итальянская UI-локаль и брендинг AUB. Production отвечает, миграции применены, Vite-сборки на сервере есть.

**Частично готово:** студенты как расширенный `customers`; родители как встроенные поля; зачисления как pivot без статусов; документы как upload в профиле; RBAC без field-level и с широким `always_allowed`; statistics = логи, не отчёты; dashboard/workplace — заглушки-landing.

**Чего нет:** `routes/api.php` и любой Flutter-пригодный API; Sanctum/Passport/JWT; отдельные `students`/`parents`; attendance; payments; модули documents/communication/events/archive/costumes; teacher↔user; consent/privacy-policy сущности; доменные тесты; git на production.

**Логичные следующие шаги (без выполнения в этой задаче):**

1. Синхронизировать docs (особенно RU `ARCHITECTURE` / `DATA_MODEL_DRAFT`, `CURRENT_STATE` 78→84, lessons UI, storage paths, двухрепозиторная модель).
2. Спроектировать и реализовать API-слой в `AUB_admin` (auth tokens + ресурсы для Flutter) — сейчас это блокер мобильного канала.
3. Решить, становится ли GitHub источником деплоя (на сервере нет `.git`).
4. По продукту web: attendance и enrollment workflow, либо teacher `user_id`, в порядке `NEXT_STEPS.md` — после явного решения Tech Lead, учитывая что mobile больше не «не начинать».
)
