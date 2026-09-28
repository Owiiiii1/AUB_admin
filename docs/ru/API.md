# AUB — Мобильный API

Базовый URL (production): `https://staff.accademiaucraina.it/api/v1`

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
| GET | `/app/version-check` | Нет |
| POST | `/auth/login` | Нет (rate limit) |
| POST | `/auth/set-password` | Нет (rate limit) |
| GET | `/me` | Bearer |
| POST | `/me/photo` | Bearer |
| PUT | `/me/password` | Bearer |
| GET | `/me/devices` | Bearer |
| DELETE | `/me/devices/{id}` | Bearer |
| POST | `/auth/logout` | Bearer |
| POST | `/auth/logout-all` | Bearer |
| GET | `/schedule` | Bearer, только `student` |
| GET | `/children/{student}/schedule` | Bearer, только `parent` |
| GET | `/teacher/schedule` | Bearer, только `teacher` |
| GET | `/rooms` | Bearer, `student` / `teacher` |
| GET | `/rooms/{id}/occupancy` | Bearer, `student` / `teacher` |
| GET | `/rooms/{id}/day` | Bearer, `student` / `teacher` |
| GET | `/attendance` | Bearer, только `student` |
| GET | `/children/{student}/attendance` | Bearer, только `parent` |
| POST | `/teacher/lessons/{scheduledLesson}/check-in` | Bearer, только `teacher` |
| GET | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, только `teacher` |
| PUT | `/teacher/lessons/{scheduledLesson}/attendance` | Bearer, только `teacher` |
| GET | `/files/{uuid}` | Bearer |
| GET | `/chat` | Bearer |
| GET | `/chat/unread` | Bearer |
| POST | `/chat/read` | Bearer |
| POST | `/chat/messages` | Bearer |
| PATCH | `/chat/messages/{id}` | Bearer |
| DELETE | `/chat/messages/{id}` | Bearer |
| POST | `/chat/messages/{id}/comments` | Bearer |
| GET | `/chats` | Bearer |
| GET | `/chats/{id}` | Bearer |
| POST | `/chats/{id}/read` | Bearer |
| POST | `/chats/{id}/messages` | Bearer |
| PATCH | `/chats/{id}/messages/{id}` | Bearer |
| DELETE | `/chats/{id}/messages/{id}` | Bearer |
| POST | `/chats/{id}/messages/{id}/comments` | Bearer |
| GET | `/notifications` | Bearer |
| GET | `/notifications/unread` | Bearer |
| GET | `/notifications/pending-ack` | Bearer |
| POST | `/notifications/{id}/read` | Bearer |
| POST | `/notifications/{id}/ack` | Bearer |
| GET | `/events` | Bearer, только `student` |
| GET | `/events/archive` | Bearer, только `student` |
| GET | `/events/{id}` | Bearer, только `student` |
| GET | `/news` | Bearer |
| GET | `/news/{id}` | Bearer |
| GET | `/documents` | Bearer |
| GET | `/children/{student}/documents` | Bearer, parent |
| POST | `/children/{student}/documents/{slot}` | Bearer, parent |
| GET | `/children/{student}/medical-certificate` | Bearer, parent |
| POST | `/children/{student}/medical-certificate` | Bearer, parent |

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

### GET `/app/version-check`

Публичный. Flutter вызывает **до логина**. Query:

- `platform` = `ios` | `android`
- `version` = имя версии приложения (`1.0.0`)

```json
{
  "success": true,
  "data": {
    "update_required": false,
    "store_url": "https://apps.apple.com/..."
  }
}
```

`update_required` = `true`, если для платформы есть хотя бы одна активная запись и установленная версия среди них нет. Если активных записей нет — вход разрешён. `store_url` из Настройки → Настройки приложения → Контроль версий. Ошибка сети на клиенте не блокирует приложение. Лимит: **60 / минуту / IP**.

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

Если у mobile-актора `users.must_set_password` = true (админ нажал **Azzera password** / обнулить), login **не** выдаёт token. Успех `200`:

```json
{
  "success": true,
  "data": {
    "password_setup_required": true
  }
}
```

Клиент показывает экран создания пароля. Старый пароль больше не действует.

### POST `/auth/set-password`

Публичный, тот же login rate limit. Работает только пока `must_set_password` = true.

```json
{
  "email": "user@example.com",
  "password": "…",
  "password_confirmation": "…",
  "device_name": "Owl iPhone"
}
```

`password` минимум 8 символов, должен совпасть с `password_confirmation`. Успех — тот же token envelope, что у login; флаг сбрасывается. Неизвестный email, inactive, не mobile-актор или флаг false → тот же `401 invalid_credentials`, что и при неверном логине.

### GET `/me`

Actor-aware whitelist. Никогда не включает password hash, `remember_token`, web `role_id`, `can_write`, `can_delete`, tax code, медицину, notes, документы, AI settings.

**Профиль student:** `id`, `first_name`, `last_name`, `display_name`, `photo_url` (аутентифицированный `GET /api/v1/files/{uuid}` или `null`), `photo` `{file_uuid, url}` или `null`, `phone`, `birth_date` (`YYYY-MM-DD` или `null`), `residence_address`, `residence_city_province`, `residence_postal_code`, `academy_class` `{id,name,course:{id,name}|null}` или `null`, `academic_year` `{id,name}` или `null`. Контактные поля только для чтения. По-прежнему без tax code, медицины, notes и документов. Никогда не public `/storage`.

**Профиль parent:** `id`, `first_name`, `last_name`, `display_name`, `photo_url` (аутентифицированный `GET /api/v1/files/{uuid}` или `null`), `photo` `{file_uuid, url}` или `null`, `children[]` с `id`, `first_name`, `last_name`, `display_name`, `photo_url`, `academy_class`. Только дети из `student_parent`.

**Профиль teacher:** `id`, `first_name`, `last_name`, `display_name`, `photo_url`, `photo`.

### GET `/files/{uuid}`

Аутентифицированный бинарь. Sanctum + `mobile.actor` + `FileAccessService`. Без auth → `401`. Чужой или неизвестный UUID → `404 not_found` (одинаковое тело). Без token в query. См. [Security/Secure_Files.md](Security/Secure_Files.md).

### POST `/me/photo`

Любой mobile-актор parent или teacher. Студент фото только смотрит (его задаёт админка). Multipart-поле `photo` (jpeg/png/webp, до 5 MB). Пишется как `profile_photo` через `SecureFileService`. Успех: `{ photo_url, photo: { file_uuid, url } }`. Неверный файл → `422`. Ученик → `404`. Фото необязательно. В админке фото родителя видно в профиле студента.

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

### Учебный процесс преподавателя

Только `account_type = teacher`.

| Метод | Путь | Назначение |
|---|---|---|
| GET | `/teacher/teaching/groups` | Группы, в которых есть ученики этого преподавателя |
| GET | `/teacher/teaching/groups/{academyClass}/students` | Фото, имя, есть ли аккаунт. Чужая группа — 404 |
| GET | `/teacher/teaching/students/{student}` | Фото, имя, возраст, дата рождения, пол, группа, курс, сводка посещаемости и личная заметка этого преподавателя. Без документов, родителей, адреса и налогового кода |
| PUT | `/teacher/teaching/students/{student}/note` | Сохраняет `body` (до 5000). Пустой текст удаляет заметку. Видит только преподаватель, который её написал |
| GET | `/teacher/teaching/rehearsals` | Слоты репетиций этого преподавателя |
| POST | `/teacher/teaching/students/{student}/messages` | Текст и `requires_ack`. Личное уведомление ученику от преподавателя |

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

`WeeklyScheduleController::publish` ставит `schedule_weeks.status = published`. **Первая** публикация переписывает все уроки недели в `scheduled_lessons.status = published` (чистый статус, без бейджа в приложении). Повторный Publish повышает только новые `draft`/`scheduled` до `published` и **не** сбрасывает `cancelled` / `moved` / `changed`. `locked` есть в схеме как зафиксированная официальная неделя; UI его пока не выставляет. Mobile считает официальными **`published` и `locked`**.

Draft-недели не отдаются.

После первой публикации правки в админке сами ставят негативные статусы:

| Изменение после публикации | Статус урока |
|----------------------------|--------------|
| Слот сдвинут (дата / время / зал) | `moved` |
| Сменён преподаватель | `changed` |
| Отмена в настройках слота (только вручную) | `cancelled` |
| Вернуть исходное в настройках слота | обратно `published` из `published_snapshot` |

Каждое такое изменение шлёт **личное сообщение** (не чат) ученикам группы с аккаунтом приложения. Кампания пишется с `source=schedule` и видна на странице **Рассылки уведомлений** с отметкой «Расписание». Восстановление пишет `source_reason=restored`.

Статусы уроков на официальной неделе:

| Статус урока | Mobile |
|--------------|--------|
| `draft` | Скрыт |
| `scheduled` | Скрыт (черновик размещения; в том числе уроки, добавленные после Publish без повторной публикации) |
| `published` | Показывается без бейджа |
| `cancelled` | Показывается со `status: cancelled` |
| `moved` | Показывается с актуальными датой/временем и `status: moved`; исходное размещение в `published_snapshot` |
| `changed` | Показывается со `status: changed` (преподаватель сменён после публикации) |

#### Пустые состояния (всегда HTTP 200)

| Случай | `academy_class` | `week.published` | `days` | `empty_reason` |
|--------|-----------------|------------------|--------|----------------|
| Нет класса | `null` | `false` | `[]` | `no_class` |
| Нет официальной недели и нет репетиций | объект класса | `false` | 7 пустых дней | `unpublished` |
| Нет официальной недели, у ученика есть репетиции | объект класса | `false` | 7 дней, только карточки репетиций | `unpublished` |
| Официальная неделя, нет видимых уроков | объект класса | `true` | 7 пустых дней | `no_lessons` |

`week.starts_on` — понедельник; `week.ends_on` — воскресенье (календарная неделя для приложения). Admin `week_end_date` по-прежнему пятница для доски Пн–Пт.

Whitelist: student `{id, display_name, academy_class {id,name,course:{id,name}|null}}`, урок `{id, kind, event_id, starts_at, ends_at, title, lesson{id,name}, teacher{id,display_name}, location.building/room {id,name}, status}`. Без notes, AI metadata, conflicts, email/phone, tax codes, medical/document.

`kind` — `lesson` или `rehearsal`. У уроков `event_id: null`. Репетиции — видимые слоты ученика из `academy_event_rehearsals`, только в `GET /schedule`. Расписание родителя и преподавателя остаётся уроками (`kind: lesson`). Строки по дням, сортировка по времени; урок идёт раньше репетиции в то же время.

### GET `/rooms`

Только student и teacher. Parent → `403 forbidden`. Активные залы (`academy_rooms` плюс активное здание): `id`, `name`, `building {id,name}`. Сортировка: здание, затем `sort_order` зала.

### GET `/rooms/{id}/occupancy`

Те же роли. Дата всегда **сегодня** в `Europe/Rome`. Опционально `?at=HH:MM`; без параметра = сейчас. Неверный `at` → `422 validation_error`. Неактивный или неизвестный зал → `404`.

Занимающие уроки — только официальная published/locked неделя. Статусы `published` и `changed` занимают зал, если `starts_at <= at < ends_at`. `cancelled` и `moved` зал **не** блокируют.

```json
{
  "room": {"id": 4, "name": "Sala 2", "building": {"id": 1, "name": "SEDE ACCADEMIA"}},
  "date": "2026-09-20",
  "at": "14:30",
  "week_published": true,
  "free": true,
  "occupying": []
}
```

`occupying` — те же поля урока, что в schedule (`starts_at`, `ends_at`, `title`, `lesson`, `teacher`, `academy_class`, `status`, `location`).

### GET `/rooms/{id}/day`

Те же роли. Официальные видимые статусы (`published` / `cancelled` / `moved` / `changed`) этого зала на сегодня. Черновик недели → `lessons: []` и `week_published: false`.

В уроках расписания преподавателя есть `teacher_checked_in` (`true` после успешной отметки). В расписании ученика и родителя этого поля нет.

### POST `/teacher/lessons/{scheduledLesson}/check-in`

Только teacher. Занятие должно принадлежать этому преподавателю и лежать на неделе `published` или `locked`. Статусы: `published`, `moved`, `changed`. Отменённый урок — **422** `lesson: cancelled`. Чужой урок — **404**.

Сервер принимает отметку за `app_settings.teacher_check_in_minutes_before` минут до `starts_at` и до `ends_at` (Europe/Rome). По умолчанию 15. Значение 0 разрешает отметку в любое время. У зала должны быть координаты. Расстояние считается по Haversine и сравнивается с `app_settings.teacher_check_in_radius_meters` (по умолчанию 50 м, в админке 10–500). Если `accuracy_meters` больше радиуса, отметка отклоняется. Повторное нажатие возвращает уже созданную строку и не перезаписывает её.

```json
{
  "latitude": 45.464211,
  "longitude": 9.191383,
  "accuracy_meters": 12,
  "device_name": "Owl phone"
}
```

Успех:

```json
{
  "success": true,
  "data": {
    "checked_in": true,
    "already_recorded": false,
    "checked_in_at": "2026-09-07T16:05:00+02:00",
    "distance_meters": 4.2,
    "radius_meters": 50
  }
}
```

**422** `validation_error`, коды полей: `window` = `too_early` | `too_late` | `closed`; `room` = `missing_coordinates`; `accuracy` = `low_accuracy`; `location` = `outside_radius`.

В GET посещаемости у урока также есть `teacher_checked_in` и `teacher_checked_in_at`.

### GET `/teacher/lessons/{scheduledLesson}/attendance`

Только teacher. См. [ATTENDANCE.md](ATTENDANCE.md).

Занятие должно принадлежать `request.user.teacherProfile` и быть mobile-visible (`schedule_weeks.status` в `published`/`locked`, урок `published`/`moved`/`changed`/`cancelled`). Иначе **404** `not_found` (включая занятие другого преподавателя).

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

Учитываются только занятия официальной недели `published`/`locked` со статусом `published`/`moved`/`changed`. Cancelled / draft / scheduled исключаются, даже если строка осталась.

Сортировка: `lesson_date DESC`, `starts_at DESC`, `attendance_record.id DESC`.

`summary.marked = present + absent + excused`. Только абсолютные числа.

Whitelist: student `{id, display_name}`; records `{id, date, starts_at, ends_at, status, lesson{id,name}, title, teacher{id,display_name}, location.building/room}`. Без `marked_by`, `marked_at`, контактов, tax_code, medical, notes, documents, parent data, AI metadata.

### GET `/children/{student}/attendance`

Только Parent. Тот же payload, что у student history. Ownership через `parentProfile.students`. Чужой ребёнок → **404** `not_found` (не 403). Student / teacher → **403**.

### Chat

Два типа тредов на одних таблицах `chats` / `chat_messages` (фото — `chat_photo` через `SecureFileService`, `GET /files/{uuid}`). Править/удалять можно только свои сообщения (иначе 404).

**Академия** (`type=academy`): один тред на мобильного пользователя с секретариатом. Все staff видят его. `POST /chat/read` пишет `actor_last_read_at`. Общее прочтение staff (`staff_last_read_at`) **не** снимает бейдж в приложении. Старый `GET /chat` по-прежнему отдаёт этот тред.

**Класс** (`type=class`): один тред на `AcademyClass`. Участники — ученики группы и преподаватели программы (`class_lesson_teacher`) с app-аккаунтом. Staff не члены, но любой web-админ пишет от своего `users.name` (`kind=staff`). Родители не входят (403). Unread персональный в `chat_reads` (свои сообщения не считаются). Классовый unread **не** входит в бейдж пункта Communication.

`GET /chat/unread` для student/teacher — сумма академии и классов. Для parent — только академия.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/chat` | Тред академии + вложенные комментарии + `unread_count` |
| GET | `/chat/unread` | `{ count }` — parent: академия; student/teacher: академия + классы |
| POST | `/chat/read` | Сброс unread актора на треде академии |
| POST | `/chat/messages` | Академия: `body` и/или `photo` |
| PATCH | `/chat/messages/{id}` | Своё тело (академия) |
| DELETE | `/chat/messages/{id}` | Своё, soft-delete (академия) |
| POST | `/chat/messages/{id}/comments` | Комментарий к корневому сообщению академии |
| GET | `/chats` | `{ items: [{ id, type, title, course, unread_count }] }` — всегда академия; class только для member |
| GET | `/chats/{id}` | То же тело, что `/chat`, плюс `type` / `class` |
| POST | `/chats/{id}/read` | Академия: `actor_last_read_at`; класс: `chat_reads` этого пользователя |
| POST | `/chats/{id}/messages` | Как post академии |
| PATCH | `/chats/{id}/messages/{id}` | Своё тело |
| DELETE | `/chats/{id}/messages/{id}` | Своё, soft-delete |
| POST | `/chats/{id}/messages/{id}/comments` | Комментарий к корневому сообщению |

Не member: **404**. Parent на class-чате: **403**.

### Уведомления (колокольчик)

Односторонние рассылки академии. Получатели — app-аккаунты `student` / `parent` / `teacher` с профилем. Polling, не FCM. Inbox `read` снимает бейдж. `ack` только если у кампании `requires_ack` — ставит галочку Letto в админке.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/notifications` | `{ items: [{ id, body, audience, requires_ack, created_at, read_at, acknowledged_at }] }` |
| GET | `/notifications/unread` | `{ count }` — доставки без `read_at` |
| GET | `/notifications/pending-ack` | `{ item }` — самая старая недоподтверждённая с `requires_ack`, или `null` |
| POST | `/notifications/{id}/read` | Прочтение в inbox; **не** подтверждает |
| POST | `/notifications/{id}/ack` | Пишет `acknowledged_at` + `read_at`; 404 если не требуется или чужое |

### События

Только student. Остальные акторы получают `403`. Скрытое событие и событие без активного участия — `404`.

Будущие события (сегодня и позже) следуют текущему составу курса. После даты события участие сохраняется, даже если ученика перевели. Билеты, костюм и содержимое галереи в ответ не входят.

| Метод | Путь | Примечание |
|--------|------|-------|
| GET | `/events` | `{ items: [{ id, title, occurs_at, venue_name, cover_url, course }] }` — видимые, сегодня или позже, активное участие |
| GET | `/events/archive` | `{ items: [{ id, title, occurs_at }] }` — видимые прошедшие события, где ученик всё ещё участник |
| GET | `/events/{id}` | Описание, фото, своя роль `{ description, image_url }`, репетиции. Картинка чужой роли не отдаётся |
| GET | `/children/{student}/events` | Родитель этого ребёнка. Тот же список, что у ученика |
| GET | `/children/{student}/events/archive` | Родитель этого ребёнка. Тот же архив, что у ученика |
| GET | `/children/{student}/events/{id}` | Родитель этого ребёнка. Та же карточка, включая роль ребёнка. Чужой ребёнок → 404 |

### Новости

Контент-лента по аудитории (не колокольчик). У каждой записи `student`, `parent` или `teacher`. В ответе только `is_active` своей `account_type`, сортировка `priority` desc затем `id` desc. Обложка — `news_image` через `SecureFileService` (`GET /files/{uuid}`). Чужая аудитория или inactive → **404**.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/news` | `{ items: [{ id, title, short_body, long_body, photo_url }] }` |
| GET | `/news/{id}` | Те же поля одной записи |

### Документы

PDF-шаблоны из типов каталога, у которых включён чекбокс «есть файл для загрузки» и загружен файл (`/documents` → Catalogo documenti). Файл — `document_form_template` на `document_type` через `SecureFileService` (`GET /files/{uuid}`). Один список для ученика, родителя и преподавателя. Типы без флага или без файла не попадают в выдачу.

Родитель также видит слоты из анкеты ребёнка в админке (`student_document_slots`). `has_download_file` = true только если у типа есть файл-шаблон. Загрузка — multipart `file` (pdf/jpeg/png, 10 МБ) в слот как `catalog_document`. Чужой родитель → **404**. Ученик/преподаватель → **403**.

Медицинская справка — не слот каталога. Таблица `student_medical_certificates` + категория `medical_certificate`. Родитель загружает только файл. AI заполняет срок; статус `pending_review`, пока staff не примет. `GET /children/{student}/documents` также отдаёт `medical_certificate`. Ежедневный `medical-certificates:watch` ставит `needs_renewal` за 30 и 7 дней.

| Method | Path | Notes |
|--------|------|-------|
| GET | `/documents` | `{ items: [{ id, document_type_id, type_name, file_name, file_url }] }` |
| GET | `/children/{student}/documents` | Родитель этого ребёнка. `{ items: [...], medical_certificate: { id, status, uploaded_at, expires_on, renewal_stage, has_file, file_name, file_url, ai_is_certificate, ai_notes } }` |
| POST | `/children/{student}/documents/{slot}` | Родитель этого ребёнка. Multipart `file`. `{ item }` — та же форма, что у строки списка. |
| GET | `/children/{student}/medical-certificate` | Родитель этого ребёнка. `{ item }` — та же форма, что `medical_certificate`. |
| POST | `/children/{student}/medical-certificate` | Родитель этого ребёнка. Только multipart `file`. `{ item }`. |

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

Регистрация, forgot/reset password, email verification, refresh tokens, push tokens, check-in, оценки, кабинеты документов ученика/преподавателя, платежи, постановки, редактирование сетки с телефона.
