# AUB — Архитектура

## Обзор

AUB — монолит на Laravel 13 с фронтендом Inertia.js + React. Бизнес-логика находится в проекте AUB (`/var/www/aub`). Переиспользуемая оболочка админки поставляется пакетом `owlsolutions/custom-admin-kit` v0.4.0, но **модули, специфичные для AUB, не должны размещаться внутри пакета**.

## Структура проекта Laravel

```
/var/www/aub/
├── app/
│   ├── Http/Controllers/       # Auth, CRM, Settings, Profile
│   ├── Http/Middleware/        # HandleInertiaRequests
│   ├── Http/Requests/          # LoginRequest
│   ├── Models/                 # User, Customer, Order, Service, Staff, AiProviderSetting
│   ├── Providers/
│   └── Services/Ai/            # AI provider clients
├── config/
│   └── owl-admin.php           # Branding, locales, middleware config
├── database/migrations/        # Laravel + kit CRM migrations
├── resources/
│   ├── js/
│   │   ├── Pages/              # Inertia pages (Dashboard, CRM, Auth, Settings…)
│   │   ├── Layouts/            # AdminLayout, AuthLayout
│   │   └── Components/ui/      # shadcn-style UI components
│   ├── css/                    # app.css, owl-admin.css
│   └── views/app.blade.php     # Inertia root template
├── routes/
│   ├── web.php                 # Root login + includes kit routes
│   ├── owl-admin-pages.php     # Protected admin/CRM pages
│   ├── owl-admin-auth.php      # Logout + /login redirect
│   └── owl-admin-core.php      # Health route
└── public/build/               # Vite production assets
```

## Установленная основа админки

Пакет: **`owlsolutions/custom-admin-kit` v0.4.0**, пресет **`admin`**.

Предоставляет:

- Оболочку аутентификации (login, logout, profile)
- Админ-layout с боковым меню
- Настройки (язык, CRUD пользователей)
- AI Settings (OpenAI, Anthropic, Gemini)
- Универсальную CRM: customers, orders, services, staff, calendar
- Health endpoint: `/owl-admin/health`
- Artisan-команды: `owl-admin:install`, `owl-admin:frontend-setup`, `owl-admin:doctor`, `owl-admin:smoke`, `owl-admin:make-admin`

## Фронтенд-стек (установлен)

| Технология | Версия | Роль |
|------------|--------|------|
| Inertia.js (Laravel) | 3.1.1 | Мост server-driven SPA |
| Inertia.js (React) | 2.3.27 | React-адаптер |
| React | 18.3.1 | UI-фреймворк |
| Vite | 8.1.3 | Сборщик |
| Tailwind CSS | 4.3.2 | Стилизация |
| Ziggy | 2.6.3 | Именованные маршруты Laravel в JS |
| Radix UI | 1.6.1 | Доступные компоненты |
| lucide-react | 1.23.0 | Иконки |

Точка входа: `resources/js/app.jsx`  
Layouts: `resources/js/Layouts/AdminLayout.jsx`, `AuthLayout.jsx`

## Бэкенд-стек

| Технология | Версия |
|------------|--------|
| PHP | 8.3.6 |
| Laravel | 13.18.1 |
| MySQL | 8.0.46 |
| Node.js | 20.20.0 |

## Подход к базе данных

- Основное подключение: **MySQL** (`DB_DATABASE=aub`)
- Миграции в `database/migrations/` (31 выполнено на 2026-07-20)
- Таблицы академии AUB: customers (расширена), teachers, courses, lessons, schedule, activity_logs
- Legacy kit (orders, services, staff) — таблицы есть, маршруты удалены
- Legacy Spatie permission tables заменены схемой ролей AUB (2026-07-06)

## Аутентификация и структура админки

- Аутентификация на основе сессий через guard Laravel `web`
- Вход по адресу **`/`** (кастомизация; по умолчанию в kit было `/login`)
- Защищённые маршруты используют `AdminRouteMiddleware::stack()` → `['web', 'auth']`
- Перенаправление гостя: неаутентифицированные пользователи → login
- Перенаправление после входа: `route('dashboard')`
- CRUD пользователей: `Settings\UserController` по адресу `/settings`
- **Контроль доступа на основе ролей реализован** (Фаза 1) — middleware `role.assigned`, `role.access`, `can.write`, `can.delete`
- Перенаправление после входа: admin → `/dashboard`, non-admin → `/workplace` или первый разрешённый маршрут

Конфигурация: `config/owl-admin.php`, `config/aub-menu.php`  
Поддерживаемые локали UI: `it` (по умолчанию), `en`, `ru`, `uk`

## Структура маршрутизации

| Файл | Содержимое |
|------|------------|
| `routes/web.php` | Корневой login (`GET/POST /`), подключает файлы маршрутов kit |
| `routes/owl-admin-auth.php` | `GET /login` → redirect `/`, `POST /login`, `POST /logout` |
| `routes/owl-admin-pages.php` | Все защищённые CRM/админ-страницы |
| `routes/owl-admin-core.php` | `GET /owl-admin/health` |

Кэш маршрутов включён в production (`bootstrap/cache/routes-v7.php`).

## Существующие CRM-модули (универсальный kit)

Это **стартовые CRM-модули**, а не специфичные для академии:

- **Customers** — универсальные записи клиентов
- **Orders** — универсальные заказы, связанные с customers/services/staff
- **Services** — универсальный каталог услуг с ценой/длительностью
- **Staff** — универсальный справочник персонала (name, email, phone, свободный текст `role`)
- **Calendar** — простая страница календаря
- **AI Settings** — API-ключи AI-провайдеров и активация

## Куда добавлять модули, специфичные для AUB

Вся бизнес-логика AUB размещается внутри `/var/www/aub`:

```
app/Models/Student.php
app/Http/Controllers/StudentsController.php
resources/js/Pages/Students/Index.jsx
database/migrations/xxxx_create_students_table.php
routes/aub-*.php  (или расширение owl-admin-pages.php секциями AUB)
```

**Не изменять** `vendor/owlsolutions/custom-admin-kit/`.

## Разделение слоёв доступа

```
┌─────────────────────────────────────────────────────────┐
│                    Future mobile API                     │
│         (students / parents / teachers apps)             │
└────────────────────────┬────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────┐
│              Role-based staff workplaces                 │
│    (Secretariat, Teacher — limited menu/screens)         │
│                   [ЗАПЛАНИРОВАНО]                      │
└────────────────────────┬────────────────────────────────┘
                         │
┌────────────────────────▼────────────────────────────────┐
│              Full admin panel (Administrator)            │
│   dashboard, CRM kit modules, settings, users, roles     │
│                   [УСТАНОВЛЕНО]                          │
└─────────────────────────────────────────────────────────┘
```

## Архитектура ролей/меню (реализовано, Фаза 1)

**Статус: реализовано (2026-07-06).**

```
roles
  └── role_menu_items (menu_key, route_name, sort_order…)
users
  └── role_id → roles
```

Поведение:

- Роль Administrator → полное админ-меню, перенаправление на `/dashboard`
- Неадминистративная роль → динамическое меню из `role_menu_items`, перенаправление на `/workplace` или первый разрешённый маршрут
- Гость → `/` (login)
- Защита от блокировки последнего активного администратора

Реализация расширит:

- `app/Models/Role.php`, `RoleMenuItem.php`
- `resources/js/Layouts/AdminLayout.jsx` — генерация динамического меню
- `Settings\UserController` — выбор роли при создании/редактировании
- Новый `RolesController` + `resources/js/Pages/Roles/`

## Рекомендуемые границы модулей

| Слой | Расположение | Примеры |
|------|--------------|---------|
| Переиспользуемая оболочка админки | `vendor/owlsolutions/custom-admin-kit` | Auth layout, UI components, doctor/smoke |
| Универсальный CRM-стартер | Опубликованные заготовки в проекте AUB | customers, orders, services, staff |
| Контроль доступа AUB | Только проект AUB | roles, role_menu_items, middleware |
| Домен академии AUB | Только проект AUB | students, parents, teachers, courses |
| Будущий mobile API | Проект AUB (`routes/api.php`) | Parent/student/teacher endpoints |

## Ключевое правило

**Никогда не размещать бизнес-логику, специфичную для AUB, в `custom-admin-kit`.** Пакет — только базовая основа админки/CRM.
