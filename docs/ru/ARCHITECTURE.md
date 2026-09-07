# AUB — Архитектура

## Концепция продукта

AUB — **централизованная электронная система управления академией**. Это не «админка плюс приложение».

CRM/backend — **ядро**. К ядру подключаются интерфейсы:

| Интерфейс | Аудитория | Статус (2026-09-07) |
|-----------|-----------|---------------------|
| Admin / superadmin web | Администраторы | Реализовано (Inertia web) |
| Web-workplaces персонала | Секретариат, преподаватели, другой персонал | Фаза 1 реализована; логин преподавателя не связан с `teachers` |
| Flutter — студенты | Студенты | Репозиторий есть; API ещё нет |
| Flutter — родители | Родители / опекуны | Тот же app / режим; API ещё нет |
| Flutter — преподаватели | Преподаватели | Тот же app / режим; API ещё нет |

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
- API для внешних клиентов (**пока не реализован**);
- audit / security;
- интеграции.

| Параметр | Значение |
|----------|----------|
| GitHub | [`Owiiiii1/AUB_admin`](https://github.com/Owiiiii1/AUB_admin) |
| Production path | `/var/www/aub` |
| Домен | `https://aub.owlsolutions.net` |
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
| Текущий код | Шаблон Flutter (`lib/main.dart`); HTTP-клиента нет |
| API | **Не существует** (`routes/api.php` нет; нет Sanctum / Passport / JWT) |

Flutter можно разрабатывать параллельно с backend. Мобильная **функциональность** зависит от стабильного API-контракта (следующий этап: **API Foundation**).

```
┌──────────────────────────────────────────────────────────┐
│  Flutter AUB_app                                         │
│  студенты / родители / преподаватели (режимы)            │
│  GitHub: Owiiiii1/AUB_app                                │
└────────────────────────────┬─────────────────────────────┘
                             │ HTTPS API  (ещё не построен)
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
│   ├── Http/Middleware/        # Inertia + role.assigned, role.access, can.write, can.delete, administrator
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
│   └── owl-admin-core.php      # Health (подключает kit ServiceProvider)
│   # routes/api.php            # ОТСУТСТВУЕТ
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

Web-auth: guard Laravel `web`, сессии, CSRF. **Стек API-токенов не установлен.** Laravel Sanctum — **рекомендуемый кандидат** для API Foundation, не утверждённое решение.

## Подход к базе данных

- Основное подключение: **MySQL** (имя БД в `.env`; значения здесь не документируются)
- **37** файлов миграций; на production **все Ran**, batch **1–29**
- Студенты: interim расширенная таблица `customers` (таблицы `students` нет)
- Родители: встроенные колонки на `customers` (таблицы `parents` нет)
- Преподаватели: `teachers` **не** связаны с `users`
- Legacy-таблицы kit (`orders`, `services`, `staff`, `order_staff`) есть; маршруты удалены
- Spatie-подобные таблицы прав удалялись при создании AUB `roles` (2026-07-06). Текущее наличие пустых leftover-таблиц в MySQL не перепроверялось

## Web-аутентификация и доступ

- Session auth; вход на **`/`**
- GET `/login` редиректит на `/`
- Защищённые страницы: `AdminRouteMiddleware::stack()` → `['web', 'auth']` плюс `role.assigned`, `role.access`
- Запись/удаление: `can.write` / `can.delete`
- CRUD ролей только для администратора: middleware `administrator`
- После входа: Administrator → `/dashboard` (заглушка); non-admin → первый разрешённый пункт меню или `/workplace`
- Локали: `it` (по умолчанию), `en`, `ru`, `uk`

**Реализовано:** RBAC, `can_write`, `can_delete`, CRUD activity logging, session auth, encrypted AI keys.

**Не реализовано (не описывать как готовое):** field-level ACL; «преподаватель видит только своих студентов»; access/view audit чувствительных записей; сущности согласий; матрица API-авторизации; private disk для документов детей (загрузки идут на диск **public**).

`config/aub-menu.php` `always_allowed_route_patterns` даёт любому аутентифицированному пользователю **с ролью** доступ к `courses-groups.*`, `lessons.*`, `weekly-schedule.*`, заглушкам, профилю, workplace — шире, чем `role_menu_items`.

## Web-маршрутизация (без API)

| Файл | Содержимое |
|------|------------|
| `routes/web.php` | `GET/POST /` login; includes pages + auth |
| `routes/owl-admin-auth.php` | редирект `/login`, `POST /logout` |
| `routes/owl-admin-pages.php` | Все защищённые Inertia/web CRM-маршруты |
| `routes/owl-admin-core.php` | `GET /owl-admin/health` |
| `bootstrap/app.php` | `web` + `commands` + health `/up` — **без `api`** |

Production `php artisan route:list`: **84** маршрута. Версионированных `/api/*` ресурсов для Flutter нет.

## Куда добавлять работу

| Слой | Расположение | Примечание |
|------|--------------|------------|
| Переиспользуемая оболочка | `vendor/owlsolutions/custom-admin-kit` | Не редактировать |
| Web UI + домен академии | `AUB_admin` app / resources / migrations | Студенты пока на `customers` |
| API для Flutter | `AUB_admin` (`routes/api.php` появится в API Foundation) | Сейчас нет |
| Flutter-клиенты | `Owiiiii1/AUB_app` | Только HTTPS; без прямого доступа к БД |

## API Foundation (следующий технический этап — не построен)

Предполагаемое содержание, **без реализации**:

- маршрутизация и версионирование API
- token authentication (Sanctum — кандидат)
- Flutter login, `/me`, logout/revoke
- авторизация API-акторов
- API Resources / DTO
- формат ошибок, rate limiting
- базовые integration tests

Пока контракта нет, Flutter-репозиторий не может реализовать реальные функции академии против backend.

## Ключевые правила

1. **Никогда не размещать бизнес-логику AUB в `custom-admin-kit`.**
2. **Никогда не обращаться к БД или секретам из Flutter.** Только HTTPS API.
3. **Не считать production `/var/www/aub` git-remote**, пока явно не спроектирован deploy workflow.
