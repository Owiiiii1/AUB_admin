# AUB — Архитектура

## Концепция продукта

AUB — **централизованная электронная система управления академией**. Это не «админка плюс приложение».

CRM/backend — **ядро**. К ядру подключаются интерфейсы:

| Интерфейс | Аудитория | Статус (2026-09-07) |
|-----------|-----------|---------------------|
| Admin / superadmin web | Администраторы | Реализовано (Inertia web) |
| Web-workplaces персонала | Секретариат, преподаватели, другой персонал | Фаза 1 реализована; логин преподавателя не связан с `teachers` |
| Flutter — студенты / родители / преподаватели | Не-админ акторы | Репозиторий есть; **API Foundation + student/parent/teacher schedule API**; в Flutter есть auth + student/parent schedule |

Не проектировать продукт вокруг одной admin panel.

## Две кодовые сущности

### AUB_admin — ядро

Центральное ядро академии:

- CRM;
- база данных;
- бизнес-логика;
- административные web-интерфейсы;
- workplaces персонала;
- аутентификация / авторизация;
- API для внешних клиентов (`/api/v1`, Sanctum Bearer);
- audit / security;
- интеграции.

| Параметр | Значение |
|----------|----------|
| GitHub | [`Owiiiii1/AUB_admin`](https://github.com/Owiiiii1/AUB_admin) |
| Production path | `/var/www/aub` |
| Домен | `https://aub.owlsolutions.net` |
| Часовой пояс приложения | `Europe/Rome` через `APP_TIMEZONE` (`config/app.php`) |
| Git на production | **Нет** `.git` на 2026-09-07 — в docs-only задачах это не менять |

Для Tech Lead источник истины — **GitHub**. Production — развёрнутое файловое дерево; оно может расходиться с GitHub, пока нет git-deploy.

Модули академии живут в этом репозитории, **не** в `vendor/owlsolutions/custom-admin-kit/`.

### AUB_app — Flutter-клиент

Отдельное мобильное приложение. С `AUB_admin` взаимодействует **только по HTTPS API**.

| Параметр | Значение |
|----------|----------|
| GitHub | [`Owiiiii1/AUB_app`](https://github.com/Owiiiii1/AUB_app) |
| Dart package | `aub` |
| Bundle / application id | `com.owlsolutions.aub` |
| Текущий код | Auth foundation в `AUB_app`; student/parent/teacher schedule API на `/api/v1` |
| API | **`/api/v1`** (Sanctum PAT; чтение расписания student/parent/teacher) |

Flutter можно разрабатывать параллельно с backend. Контракт: [API.md](API.md).

```
┌──────────────────────────────────────────────────────────┐
│  Flutter AUB_app                                         │
│  student / parent / teacher (дистрибуция OPEN)           │
│  GitHub: Owiiiii1/AUB_app                                │
└────────────────────────────┬─────────────────────────────┘
                             │ HTTPS API  /api/v1 (Sanctum)
                             ▼
┌──────────────────────────────────────────────────────────┐
│  AUB_admin  (ядро)                                       │
│  CRM · БД · бизнес-логика · authz · audit · API          │
│  GitHub: Owiiiii1/AUB_admin                              │
│  Production: /var/www/aub                                │
│                                                          │
│   ┌────────────────────┐  ┌──────────────────────────┐   │
│   │ Admin / superadmin │  │ Web-workplaces персонала │   │
│   │ Inertia + React    │  │ меню по роли             │   │
│   └────────────────────┘  └──────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
```

## Структура Laravel AUB_admin

```
AUB_admin / /var/www/aub/
├── app/
│   ├── Http/Controllers/       # Auth, CRM академии, Settings, расписание
│   ├── Http/Controllers/Api/V1/  # Health, Auth, Me
│   ├── Http/Resources/Api/       # Whitelist JSON
│   ├── Http/Middleware/        # Inertia + RBAC + EnsureMobileActorIsValid
│   ├── Models/                 # User, Role, Customer, Teacher, Course, Lesson, Schedule…
│   ├── Services/               # ActivityLogger, WeeklySchedule/*, Ai/
│   └── Support/                # RoleAccess, MenuRegistry, AdministratorLockoutGuard
├── config/
│   ├── owl-admin.php           # Брендинг, локали
│   └── aub-menu.php            # Реестр меню + always_allowed_route_patterns
├── database/migrations/        # 37 файлов (Laravel + kit + AUB)
├── resources/js/
│   ├── Pages/                  # Inertia-страницы
│   ├── Layouts/                # AdminLayout, AuthLayout
│   └── Components/ui/
├── routes/
│   ├── web.php                 # Корневой login + includes
│   ├── owl-admin-pages.php     # Защищённые web-страницы
│   ├── owl-admin-auth.php      # Logout + редирект /login
│   ├── owl-admin-core.php      # Health (подключает kit ServiceProvider)
│   └── api.php                 # /api/v1 health, auth, me
└── public/build/               # Vite-ассеты (gitignored; на production есть)
```

## Установленная основа админки

Пакет: **`owlsolutions/custom-admin-kit` v0.4.0**, пресет **`admin`**.

Даёт переиспользуемую оболочку (auth, layout, doctor/smoke, заготовки CRM). Кастомизации хоста: вход на `/`, маршруты kit CRM сняты, настройки сведены во вкладки.

**Не размещать бизнес-логику академии в kit.**

Универсальный kit CRM (`orders`, `services`, `staff`, `calendar`) остаётся как неиспользуемые контроллеры, Inertia-страницы и таблицы. Активный UI их не использует.

## Стек (проверено 2026-09-07)

| Технология | Версия | Роль |
|------------|--------|------|
| PHP | 8.3.6 | Runtime (production) |
| Laravel | 13.18.1 | Ядро |
| MySQL | 8.0.46 | Основная БД |
| Node.js | 20.20.0 | Сборка фронтенда на production |
| `owlsolutions/custom-admin-kit` | v0.4.0 | Оболочка админки |
| Inertia.js (Laravel) | 3.1.1 | Server-driven SPA |
| Inertia.js (React) | 2.3.27 | React-адаптер |
| React | 18.3.1 | Web UI |
| Vite | 8.1.3 | Сборщик |
| Tailwind CSS | 4.3.2 | Стили |
| Ziggy | 2.6.3 | Именованные маршруты в JS |
| Radix UI | 1.6.1 | UI-примитивы |
| lucide-react | 1.23.0 | Иконки |

Web-auth: guard Laravel `web`, сессии, CSRF. Mobile API: Laravel Sanctum **v4.3.3**, Bearer PAT, `/api/v1`. Passport / JWT нет. Контракт: [API.md](API.md).

## Подход к базе данных

- Основное подключение: **MySQL** (имя БД в `.env`; значения здесь не документируются)
- **40** файлов миграций, включая Identity Layer и `personal_access_tokens`
- Студенты: таблица `students` (не `customers`). **DECIDED:** `students` / `parents` / `student_parent`; `customers` — kit leftover
- Родители: `parents` + `student_parent` (`AcademyParent`)
- Identity Layer **implemented**: `users.account_type` (`staff` | `student` | `parent` | `teacher`), `users.is_active`; `students.user_id` / `parents.user_id` / `teachers.user_id` nullable unique FK. Один User = один actor type. `account_type` ≠ web `role_id`
- Зачисления: unique `academy_class_student.student_id` = один активный `Class` (`AcademyClass`)
- Legacy-таблицы kit (`orders`, `services`, `staff`, `order_staff`) есть; маршруты удалены
- Spatie-подобные таблицы прав удалялись при создании AUB `roles` (2026-07-06). Текущее наличие пустых leftover-таблиц в MySQL не перепроверялось

## Web-аутентификация и доступ

- Session auth; вход на **`/`**
- GET `/login` редиректит на `/`
- Неактивный User (`is_active=false`) и Parent/Student actor accounts не получают web admin (logout на login-форме или 403)
- Защищённые страницы: `AdminRouteMiddleware::stack()` → `['web', 'auth']` плюс `role.assigned`, `role.access`
- Запись/удаление: `can.write` / `can.delete`
- CRUD ролей только для администратора: middleware `administrator`
- После входа: Administrator → `/dashboard` (заглушка); non-admin → первый разрешённый пункт меню или `/workplace`
- Локали: `it` (по умолчанию), `en`, `ru`, `uk`

**Реализовано:** RBAC, `can_write`, `can_delete`, CRUD activity logging, session auth, encrypted AI keys.

**Не реализовано (не описывать как готовое):** field-level ACL; «преподаватель видит только своих студентов»; access/view audit чувствительных записей; сущности согласий; матрица API-авторизации; private disk для документов детей (загрузки на диск **public**); **2FA** админов. Это **Security Foundation** до широкого mobile rollout.

`config/aub-menu.php` `always_allowed_route_patterns` даёт любому аутентифицированному пользователю **с ролью** доступ к `courses-groups.*`, `lessons.*`, `weekly-schedule.*`, заглушкам, профилю, workplace — шире, чем `role_menu_items`.

## Web-маршрутизация и API

| Файл | Содержимое |
|------|------------|
| `routes/web.php` | `GET/POST /` login; includes pages + auth |
| `routes/owl-admin-auth.php` | редирект `/login`, `POST /logout` |
| `routes/owl-admin-pages.php` | Все защищённые Inertia/web CRM-маршруты |
| `routes/owl-admin-core.php` | `GET /owl-admin/health` |
| `routes/api.php` | `/api/v1` health, login, logout, logout-all, me, student/parent/teacher schedule, teacher attendance, student/parent attendance history |
| `bootstrap/app.php` | `web` + `api` + `commands` + health `/up` |

Production `php artisan route:list --path=api`: **10** маршрутов. Web CRM без изменений. Подробности: [API.md](API.md).

## Куда добавлять работу

| Слой | Расположение | Примечание |
|------|--------------|------------|
| Переиспользуемая оболочка | `vendor/owlsolutions/custom-admin-kit` | Не редактировать |
| Web UI + домен академии | `AUB_admin` app / resources / migrations | Студенты пока на `customers` |
| API для Flutter | `AUB_admin` `routes/api.php` (`/api/v1`) | Sanctum Bearer; см. [API.md](API.md) |
| Flutter-клиенты | `Owiiiii1/AUB_app` | Только HTTPS; без прямого доступа к БД |

## API Foundation (implemented 2026-09-08)

Сделано: маршруты `/api/v1`, Sanctum PAT, login / logout / logout-all / `/me` / health, JSON envelope, rate limits, `EnsureMobileActorIsValid`, API Resources (whitelist), тесты на `aub_test`.

`staff` через mobile login не допускается. Student/parent/teacher schedule и Teacher Attendance реализованы. Прочие feature endpoints вне этого foundation. Контракт: [API.md](API.md), [ATTENDANCE.md](ATTENDANCE.md).

## Identity vs административный RBAC (implemented 2026-09-08)

Identity Layer — **implemented**.

```
User.account_type: staff | student | parent | teacher
one User = one actor type
Student.user_id / Parent.user_id / Teacher.user_id
account_type != web RBAC
```

- `staff` — существующие web users (administrator / secretariat / другие). `account_type=staff` **сам по себе прав не даёт**; web-права только из `role_id` + `can_write` / `can_delete`.
- `student` / `parent` — actor accounts; `role_id` всегда `null`; web CRM недоступен.
- `teacher` — actor type; `role_id` может быть `null` (нет CRM) или web-role, если нужен workplace. Это одна identity `teacher` + permission set, не два actor type.
- Привязка только через `AccountIdentityService`. Staff не имеет Student/Parent/Teacher profile.
- Один физический человек с двумя функциями = **два User**. Email unique; разные login identifier. Invitation/activation **нет**. Mobile API: Sanctum `/api/v1` для student/parent/teacher.

**DECIDED:** Parent и Student **не** добавлять как RBAC-роли админки только потому, что им нужен вход. Authentication account type и административный web RBAC — разные понятия.

## `Class` vs дополнительные группы (DECIDED)

`Class` — постоянный основной учебный класс; максимум один активный на ребёнка. Дополнительные activity groups (production / rehearsal / иное) **не** являются `Class`. Ребёнок может быть в одном `Class` и в нескольких дополнительных группах.

## Student Attendance vs Teacher Presence

Два домена:

- **Student Attendance** — отметка ребёнка на конкретном занятии / репетиции / session.
- **Teacher Check-in / Staff Presence** — преподаватель физически в академии.

**DECIDED check-in:** относится к **рабочему дню**, не к уроку. Кнопка Flutter «Пришёл» → один GPS snapshot → geofence → daily teacher check-in. Не требовать check-in перед каждым уроком. QR — optional fallback. OPEN: radius, GPS accuracy, окно времени, anti-spoofing.

## Final Assessment (ядро DECIDED)

Текущих оценок и per-lesson gradebook нет. Итог: `StudentFinalResult` (Student + Class + Lesson + AcademicYear). Табель включает все `ClassLesson`. PDF формирует Administrator. Отдельный продуктовый модуль.

## Единый календарь (OPEN)

Реализовано: только `scheduled_lessons`. Не заменять, пока Tech Lead не выберет:

- **A** — одна `ScheduledSession` с типами, или
- **B** — разные сущности + агрегирующий слой календаря.

**DECIDED для будущего:** расписание дополнительных групп должно попадать в единый календарь ребёнка. Реализация OPEN.

## Productions (поздний future)

Поздний discovery-needed этап. Activity groups ≠ `Class`. Workflow не детализировать сейчас. Costume Service может остаться отдельным сервисом.

## Ключевые правила

1. **Никогда не размещать бизнес-логику AUB в `custom-admin-kit`.**
2. **Никогда не обращаться к БД или секретам из Flutter.** Только HTTPS API.
3. **Не считать production `/var/www/aub` git-remote.** Деплой — копирование файлов сразу после push.
4. **Не выдумывать ответы** на пункты [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).
