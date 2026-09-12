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
| PUT | `/me/password` | Bearer |
| GET | `/me/devices` | Bearer |
| DELETE | `/me/devices/{id}` | Bearer |
| POST | `/auth/logout` | Bearer |
| POST | `/auth/logout-all` | Bearer |
| GET | `/schedule` | Bearer, только `student` |
| GET | `/children/{student}/schedule` | Bearer, только `parent` |
| GET | `/teacher/schedule` | Bearer, только `teacher` |
| GET | `/attendance` | Bearer, только `student` |
| GET | `/children/{student}/attendance` | Bearer, только `parent` |
| GET | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, только `teacher` |
| PUT | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, только `teacher` |

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

**Профиль student:** `id`, `first_name`, `last_name`, `display_name`, `photo_url` (аутентифицированный `GET /api/v1/files/{uuid}` или `null`), `photo` `{file_uuid, url}` или `null`, `phone`, `birth_date` (`YYYY-MM-DD` или `null`), `residence_address`, `residence_city_province`, `residence_postal_code`, `academy_class` `{id,name}` или `null`, `academic_year` `{id,name}` или `null`. Контактные поля только для чтения. По-прежнему без tax code, медицины, notes и документов. Никогда не public `/storage`.

**Профиль parent:** `id`, `first_name`, `last_name`, `display_name`, `children[]` с `id`, `first_name`, `last_name`, `display_name`, `photo_url`, `academy_class`. Только дети из `student_parent`.

**Профиль teacher:** `id`, `first_name`, `last_name`, `display_name`, `photo_url`, `photo`.

### GET `/files/{uuid}`

Аутентифицированный бинарь. Sanctum + `mobile.actor` + `FileAccessService`. Без auth → `401`. Чужой или неизвестный UUID → `404 not_found` (одинаковое тело). Без token в query. См. [Security/Secure_Files.md](Security/Secure_Files.md).

### PUT `/me/password`

Любой mobile-актор (`student`, `parent`, `teacher`). Тело: `current_password`, `password`, `password_confirmation`. Минимум 8 символов. Неверный текущий пароль — `422` `validation_error` с `fields.current_password`. При успехе токены других устройств отзываются; текущий токен остаётся.

### GET `/me/devices`

Любой mobile-актор. Ответ `{ devices: [{ id, name, last_used_at, created_at, current }] }`. `name` — это `device_name` с login. Хеши токенов не возвращаются. `current` — токен этого запроса.

### DELETE `/me/devices/{id}`

Отзывает Sanctum-токен, если он принадлежит вызывающему. Текущее устройство здесь отозвать нельзя (`422`); для этого `POST /auth/logout`. Чужой или неизвестный id → `404 not_found` (то же тело).

### GET `/schedule`

Только student (`account_type = student`). Студент берётся из `request.user.studentProfile`. Никакого `student_id` в query/body.

### GET `/children/{student}/schedule`

Только parent. Ребёнок должен быть связан через `student_parent`. Чужой или неизвестный id → `404 not_found` (одинаковое тело), без раскрытия существования student.

Teacher и staff эти эндпоинты не используют (`403` для валидного teacher token; staff не получает mobile-сессию).

### GET `/teacher/schedule`

Только teacher (`account_type = teacher`). Преподаватель берётся из `request.user.teacherProfile`. Никакого `teacher_id` в query/body. Лишние ключи вроде `teacher_id` игнорируются.

Student и parent получают `403`. Staff не получает mobile-сессию (`401`).

Тот же контракт `?week=YYYY-MM-DD`, те же правила публикации, текущая неделя `Europe/Rome`, HTTP 200 для пустых состояний.

Отдаются все официальные `ScheduledLesson` этого преподавателя (`teacher_id`), по всем классам. Сортировка: `lesson_date`, `starts_at`, `ends_at`, `id`.

В каждом уроке есть `academy_class {id,name}`. Нет объекта teacher (вызывающий и есть этот преподаватель), нет roster класса, имён/ID учеников, данных родителей, email/phone, tax_code, notes, AI metadata, conflicts.

Пустые состояния:

| Случай | `week.published` | `days` | `empty_reason` |
|--------|------------------|--------|----------------|
| Нет официальной недели | `false` | 7 пустых дней | `unpublished` |
| Официальная неделя, нет уроков этого преподавателя | `true` | 7 пустых дней | `no_lessons` |

Whitelist корня: `teacher {id, display_name}`, `week`, `days`, `empty_reason`.

Query:

```text
?week=YYYY-MM-DD
```

Дата может быть любым днём недели. Backend приводит её к Monday–Sunday через Laravel `now()` / `config('app.timezone')` (`APP_TIMEZONE`, по умолчанию `Europe/Rome`). Flutter не является source of truth для недели.

Без `week` — текущая неделя в `Europe/Rome`. Невалидный `week` → `422 validation_error`.

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

### GET `/teacher/lessons/{scheduledLesson}/attendance`

Только teacher. См. [ATTENDANCE.md](ATTENDANCE.md).

Занятие должно принадлежать `request.user.teacherProfile` и быть mobile-visible (`schedule_weeks.status` в `published`/`locked`, урок `published`/`moved`/`cancelled`). Иначе **404** `not_found` (включая занятие другого преподавателя).

Roster — **текущий** состав `academy_class_student` класса `scheduled_lesson.academy_class_id`, сортировка `last_name`, `first_name`, `id`. Нет `AttendanceRecord` → `attendance: null` (не отмечен). Строки заранее не создаются.

Whitelist ученика: `id`, `display_name`, `photo_url` (аутентифицированный `/api/v1/files/{uuid}` или `null`). Без email, phone, address, parents, medical, tax_code, notes, documents.

Cancelled: HTTP 200, roster показан, `attendance_editable: false`, `reason: cancelled`.

### PUT `/teacher/lessons/{scheduledLesson}/attendance`

Те же visibility/ownership, что у GET. Тело:

```json
{
  "attendance": [
    { "student_id": 100, "status": "present" },
    { "student_id": 101, "status": "absent" },
    { "student_id": 102, "status": "excused" },
    { "student_id": 103, "status": null }
  ]
}
```

Статусы: `present`, `absent`, `excused`. `null` удаляет запись. Семантика: **bulk partial upsert** — переданные строки меняются; ученики вне payload не трогаются.

Каждый `student_id` должен быть в roster класса этого занятия; иначе **422** `validation_error` и **никаких** записей (атомарно). Дубликат `student_id` в payload → 422.

Cancelled (или иначе нередактируемое занятие) → **409**:

```json
{
  "success": false,
  "error": {
    "code": "attendance_not_editable",
    "message": "Attendance cannot be edited for this lesson."
  }
}
```

Успех **200** возвращает ту же форму `data`, что GET. `marked_by` — текущий User; `marked_at` — `now()` в `Europe/Rome` при реальной смене статуса.

### GET `/attendance`

Только Student. См. [ATTENDANCE.md](ATTENDANCE.md).

Student берётся из `request.user.studentProfile`. Клиентский `student_id` не принимается. Teacher / parent → **403**. Staff mobile → **401**.

Query `month=YYYY-MM` (опционально). Если нет — текущий месяц в `Europe/Rome`. Backend возвращает `period.month`, `period.starts_on`, `period.ends_on`. Невалидный month → **422** `validation_error`.

Возвращаются только существующие `AttendanceRecord`. **Нет `AttendanceRecord` ≠ `absent`** — неотмеченные занятия не история и не считаются.

Учитываются только занятия официальной недели `published`/`locked` со статусом `published`/`moved`. Cancelled / draft / scheduled исключаются, даже если строка осталась.

Сортировка: `lesson_date DESC`, `starts_at DESC`, `attendance_record.id DESC`.

`summary.marked = present + absent + excused`. Только абсолютные числа.

Whitelist: student `{id, display_name}`; records `{id, date, starts_at, ends_at, status, lesson{id,name}, title, teacher{id,display_name}, location.building/room}`. Без `marked_by`, `marked_at`, контактов, tax_code, medical, notes, documents, parent data, AI metadata.

### GET `/children/{student}/attendance`

Только Parent. Тот же payload, что у student history. Ownership через `parentProfile.students`. Чужой ребёнок → **404** `not_found` (не 403). Student / teacher → **403**.

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
| 409 | `attendance_not_editable` |
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

Регистрация, forgot/reset password, email verification, refresh tokens, push tokens, check-in, оценки, документы, сообщения, платежи, постановки, редактирование сетки с телефона.
