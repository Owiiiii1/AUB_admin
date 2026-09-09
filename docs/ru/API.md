# AUB — Мобильный API

Базовый URL (production): `https://aub.owlsolutions.net/api/v1`

Версия в пути (`v1`). Не смешивать с web/Inertia session-маршрутами.

Flutter (`AUB_app`) обязан использовать **только HTTPS + Bearer**. Не класть в приложение учётные данные БД или `APP_KEY`.

## Схема auth

Personal access tokens Laravel Sanctum **v4.3.3**.

```http
Authorization: Bearer <token>
```

Токены хранятся в hashed-виде. Plaintext возвращается **только** при login. Ability выдаваемых токенов: `mobile`. Тип актора **никогда** не берётся из token; на каждом запросе читается `users.account_type`.

Срок по умолчанию: **30 дней** (`43200` минут), задаётся `AUB_API_TOKEN_EXPIRATION_MINUTES` / `config/aub.php`. Refresh-token в этой версии нет; после expiry нужен повторный login. Несколько устройств разрешены.

## Кто может пользоваться mobile API

Допустимые типы: `student`, `parent`, `teacher`.

Пользователь должен быть `is_active` и иметь matching linked profile (`students.user_id` / `parents.user_id` / `teachers.user_id`).

`staff` работает через web session CRM. Login staff на этом API отклоняется с тем же публичным ответом, что и неверный пароль.

## Эндпоинты

| Method | Path | Auth |
|--------|------|------|
| GET | `/health` | Нет |
| POST | `/auth/login` | Нет (rate limit) |
| GET | `/me` | Bearer |
| POST | `/auth/logout` | Bearer |
| POST | `/auth/logout-all` | Bearer |
| GET | `/schedule` | Bearer, только `student` |
| GET | `/children/{student}/schedule` | Bearer, только `parent` |

### GET `/health`

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "api_version": "v1"
  }
}
```

Не отдаёт credentials БД, версии пакетов, пути и секреты.

### POST `/auth/login`

Запрос:

```json
{
  "email": "user@example.com",
  "password": "…",
  "device_name": "Owl iPhone"
}
```

`device_name` обязателен (max 120). Это имя Sanctum-токена.

Успех `200`:

```json
{
  "success": true,
  "data": {
    "token": "<plaintext один раз>",
    "token_type": "Bearer",
    "expires_at": "2026-10-08T20:00:00+00:00"
  }
}
```

Ошибка auth `401` (неизвестный email, неверный пароль, inactive, staff, нет профиля — одно тело):

```json
{
  "success": false,
  "error": {
    "code": "invalid_credentials",
    "message": "Invalid credentials."
  }
}
```

Нет `device_name` / невалидный email: `422` `validation_error`.

Лимит login: **5 / минуту** на нормализованный email + IP → `429` `too_many_requests`.

### GET `/me`

Actor-aware whitelist. Никогда не включает password hash, `remember_token`, web `role_id`, `can_write`, `can_delete`, tax code, медицину, notes, документы, AI settings.

**Профиль student:** `id`, `first_name`, `last_name`, `display_name`, `photo_url` (URL public disk или `null`), `academy_class` `{id,name}` или `null`, `academic_year` `{id,name}` или `null`.

**Профиль parent:** `id`, `first_name`, `last_name`, `display_name`, `children[]` с `id`, `first_name`, `last_name`, `display_name`, `academy_class`. Только дети из `student_parent`.

**Профиль teacher:** `id`, `first_name`, `last_name`, `display_name`.

### GET `/schedule`

Только student (`account_type = student`). Студент берётся из `request.user.studentProfile`. Никакого `student_id` в query/body.

### GET `/children/{student}/schedule`

Только parent. Ребёнок должен быть связан через `student_parent`. Чужой или неизвестный id → `404 not_found` (одинаковое тело), без раскрытия существования student.

Teacher и staff эти эндпоинты не используют (`403` для валидного teacher token; staff не получает mobile-сессию).

Query:

```text
?week=YYYY-MM-DD
```

Дата может быть любым днём недели. Backend приводит её к Monday–Sunday через Laravel `now()` / `config('app.timezone')`. **Сейчас в `config/app.php` timezone = `UTC` (не читается из `APP_TIMEZONE`).** Flutter не является source of truth для недели.

Без `week` — текущая неделя в этой timezone. Невалидный `week` → `422 validation_error`.

#### Правила публикации (как admin Publish)

`WeeklyScheduleController::publish` ставит `schedule_weeks.status = published` и переписывает все уроки недели в `scheduled_lessons.status = published`. `locked` есть в схеме как зафиксированная официальная неделя; UI его пока не выставляет. Mobile считает официальными **`published` и `locked`**.

Draft-недели не отдаются.

Статусы уроков на официальной неделе:

| Статус урока | Mobile |
|--------------|--------|
| `draft` | Скрыт |
| `scheduled` | Скрыт (черновик размещения; в том числе уроки, добавленные после Publish без повторной публикации) |
| `published` | Показывается |
| `cancelled` | Показывается со `status: cancelled` |
| `moved` | Показывается с актуальными датой/временем и `status: moved` (истории old/new в модели нет) |

#### Пустые состояния (всегда HTTP 200)

| Случай | `academy_class` | `week.published` | `days` | `empty_reason` |
|--------|-----------------|------------------|--------|----------------|
| Нет класса | `null` | `false` | `[]` | `no_class` |
| Нет официальной недели | объект класса | `false` | 7 пустых дней | `unpublished` |
| Официальная неделя, нет видимых уроков | объект класса | `true` | 7 пустых дней | `no_lessons` |

`week.starts_on` — понедельник; `week.ends_on` — воскресенье (календарная неделя для приложения). Admin `week_end_date` по-прежнему пятница для доски Пн–Пт.

Whitelist: student `{id, display_name, academy_class}`, урок `{id, starts_at, ends_at, title, lesson{id,name}, teacher{id,display_name}, location.building/room {id,name}, status}`. Без notes, AI metadata, conflicts, email/phone, tax codes, medical/document.

Уроки группируются по дню и сортируются по `starts_at`.

### POST `/auth/logout`

Отзывает **только** текущий token.

### POST `/auth/logout-all`

Отзывает **все** Sanctum-токены пользователя.

## Контракт ошибок

```json
{
  "success": false,
  "error": {
    "code": "…",
    "message": "…",
    "fields": { "email": ["…"] }
  }
}
```

`fields` только для `validation_error`.

| HTTP | code |
|------|------|
| 401 | `unauthenticated` или `invalid_credentials` |
| 403 | `forbidden` |
| 404 | `not_found` |
| 422 | `validation_error` |
| 429 | `too_many_requests` |
| 500 | `server_error` (без stack/SQL/путей на production) |

`/api/*` всегда JSON. HTML-ошибки web не менялись.

## Runtime identity

Защищённые маршруты: `auth:sanctum` + `EnsureMobileActorIsValid`. На каждом запросе User перечитывается из БД:

- inactive → токены отзываются, `401`
- `staff` или нет matching profile → текущий token отзывается, `401`

Админский `is_active = false` также удаляет все токены (модель User). Unlink профиля удаляет токены этого User.

Лимит authenticated API: **120 / минуту / user**.

## CORS

Нативный Flutter не использует browser CORS. Allowed origins пустые. Не ставить `*`, пока отдельно не согласован Flutter Web.

## Вне scope (нет в этом контракте)

Регистрация, forgot/reset password, email verification, refresh tokens, push tokens, **расписание преподавателя**, attendance, check-in, оценки, документы, сообщения, платежи, постановки, редактирование сетки с телефона.
