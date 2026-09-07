# AUB — Обзор проекта

## Что такое AUB?

**AUB** — централизованная электронная система управления и учёта академии. Это не одно мобильное приложение и не «админка + приложение». Цель — автоматизировать процессы: учёт студентов, рабочие места персонала, расписание, посещаемость, платежи, документы, отчётность.

**CRM/backend (`AUB_admin`) — ядро.** Web-workplaces, админ-интерфейс и Flutter-клиенты — интерфейсы к этому ядру.

## Бизнес-цель

Одна цифровая платформа, которая:

- заменяет разрозненные таблицы и ручные процессы;
- даёт администраторам полноценную CRM / админ-панель;
- даёт персоналу workplaces **по ролям** в web;
- даёт студентам, родителям и преподавателям **мобильный доступ** через Flutter, когда появится HTTPS API.

## Каналы доступа

| Канал | Аудитория | Код | Статус (2026-09-07) |
|-------|-----------|-----|---------------------|
| Admin / superadmin web | Администраторы | `AUB_admin` Inertia | Реализовано; dashboard — заглушка |
| Web-workplaces персонала | Секретариат, преподаватели, другой персонал | `AUB_admin` | RBAC фазы 1 есть; запись преподавателя не связана с логином |
| Flutter — студенты | Студенты | `AUB_app` | Репозиторий есть; шаблон; API нет |
| Flutter — родители | Родители / опекуны | `AUB_app` (режим) | Планируется в том же app; API нет |
| Flutter — преподаватели | Преподаватели | `AUB_app` (режим) | Планируется в том же app; API нет |

## Репозитории

| Сущность | GitHub | Роль |
|----------|--------|------|
| **AUB_admin** | [`Owiiiii1/AUB_admin`](https://github.com/Owiiiii1/AUB_admin) | CRM, БД, бизнес-логика, web admin/workplaces, будущий API |
| **AUB_app** | [`Owiiiii1/AUB_app`](https://github.com/Owiiiii1/AUB_app) | Flutter app `aub`, bundle id `com.owlsolutions.aub` |

Flutter обязан использовать **только HTTPS API**. Файла `routes/api.php` нет. Sanctum / Passport / JWT не установлены.

Production ядра: `/var/www/aub`, `https://aub.owlsolutions.net`. Папка **не** git-репозиторий. Для Tech Lead источник истины — GitHub.

## Установленное ядро (web)

Чистый Laravel 13 хост **2026-07-06**, затем модули академии до **2026-07-20**. Стек проверен **2026-09-07**: Laravel **13.18.1**, kit **v0.4.0**, PHP **8.3.6**, MySQL **8.0.46**.

Вход на **`/`**. `/login` редиректит на `/`.

| Маршрут | Назначение | Примечание |
|---------|------------|------------|
| `/` | Вход | Гость |
| `/dashboard` | Home | **Заглушка** |
| `/customers` | Список и профиль студентов | Таблица `customers` |
| `/teachers` | Преподаватели | Нет связи с `users` |
| `/courses-groups` | Курсы и группы | |
| `/lessons` | Каталог уроков | **Редирект** в Настройки → Академия → Уроки |
| `/schedule-service` | Недельное расписание + гибридный ИИ | |
| `/settings` | Пользователи, роли, app, AI, академия | Каталог уроков здесь |
| `/statistics/logs` | Журнал CRUD | Не полный access audit |
| `/profile` | Текущий пользователь | |
| `/workplace` | Landing non-admin | Тонкая страница |
| `/documents`, `/communication`, `/events`, `/archive`, `/costume-service` | Скоро будет | Заглушки |
| `/owl-admin/health` | Health kit | |

**Снято с маршрутов и меню** (таблицы kit остались): `/orders`, `/services`, `/staff`, `/calendar`.

Production `route:list`: **84** web-маршрута. **Ноль** API-endpoint’ов для Flutter.

## Универсальный kit vs модули академии

| Generic kit | Смысл в AUB | Статус |
|-------------|-------------|--------|
| `customers` | Студенты (interim) | Частично |
| `services` | Не курсы — отдельная `courses` | Курсы реализованы |
| справочник `staff` | Не RBAC — отдельные `teachers` + `roles` | Teachers + RBAC реализованы |
| `orders` | Не зачисления — pivot `course_group_customer` | Частично |
| страница `calendar` | Недельное расписание `/schedule-service` | Реализовано |

Поле kit `staff.role` — свободный текст, **не** контроль доступа.

## Снимок MVP

**Готово или в работе:** роли фазы 1; преподаватели; курсы/группы; каталог уроков; недельное расписание + гибридный ИИ; пользователи/`can_write`/`can_delete`; CRUD-журнал.

**Частично:** студенты на `customers`; родители как встроенные поля; зачисления без статусов; документы только upload в профиле.

**Нет:** посещаемость; платежи; отдельные модули документов/коммуникаций; mobile API; token auth; field-level ACL.

**Следующий крупный технический этап:** [API Foundation](NEXT_STEPS.md) — Flutter можно развивать параллельно, но функции требуют API-контракта.

Костюмы/шоу/билеты остаются опциональной фазой 6.

## Сервер

| Параметр | Значение |
|----------|----------|
| GitHub ядра | `Owiiiii1/AUB_admin` |
| Production path | `/var/www/aub` (не git-репо) |
| Web root | `/var/www/aub/public` |
| Домен | `https://aub.owlsolutions.net` |
| IP | `178.156.234.23` |
| Linux user | `deploy` |
| Admin kit | `owlsolutions/custom-admin-kit` v0.4.0 |

Подробности: [ARCHITECTURE.md](ARCHITECTURE.md), [CURRENT_STATE.md](CURRENT_STATE.md), [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md).
