# AUB — Teacher Attendance

Teacher Attendance MVP: преподаватель открывает официальное занятие из Teacher Schedule, видит roster класса, отмечает каждого ученика, сохраняет и при повторном открытии видит те же отметки.

История посещаемости для Student / Parent в этот этап **не** входит.

Подробности контракта также в [API.md](API.md).

## Идентичность записи

Посещаемость принадлежит **Student + ScheduledLesson**, не Student+date, не Student+Class и не Student+ClassLesson.

`ScheduledLesson` — фактический экземпляр занятия в конкретную дату/время.

Таблица: `attendance_records`. PHP: `AttendanceRecord`.

## Схема

| Колонка | Примечание |
|---------|------------|
| `id` | PK |
| `scheduled_lesson_id` | FK `scheduled_lessons`, `cascadeOnDelete` |
| `student_id` | FK `students`, `cascadeOnDelete` |
| `status` | `present` / `absent` / `excused` |
| `marked_by` | FK `users`, **nullable**, `nullOnDelete` |
| `marked_at` | datetime |
| `created_at` / `updated_at` | timestamps |

**UNIQUE (`scheduled_lesson_id`, `student_id`)**. Одна текущая отметка на ученика на занятие.

`marked_by` nullable, чтобы audit-строка пережила удаление staff-аккаунта (`nullOnDelete`). Teacher mobile save всегда ставит текущего User.

## Статусы (MVP)

```text
present | absent | excused
```

Не в этой версии: `late`, `left_early`, `sick`, `remote`, custom.

Отдельного хранимого статуса `unmarked` **нет**. Если строки нет, ученик не отмечен (`attendance: null` в API). Backend **не** создаёт заранее по одной строке на каждого ученика каждого занятия.

`status = null` в PUT **удаляет** строку.

## Источник roster

Roster `ScheduledLesson` — **текущий** состав класса:

```text
ScheduledLesson.academy_class_id → AcademyClass → academy_class_student → students
```

Сортировка: `last_name`, `first_name`, `id`.

### Технический долг

Если позже понадобится «кто состоял в классе именно в дату занятия», нужна enrollment history или roster snapshot. **Сейчас не строится.**

## Ownership

Преподаватель может читать/писать attendance только если:

```text
scheduled_lesson.teacher_id == current teacher profile id
```

Чужое занятие → **404** `not_found` (GET и PUT). Student / parent → **403**. Staff не получает mobile-сессию → **401**.

## Публикация

Та же официальная видимость, что у Teacher Schedule, плюс GET для cancelled:

| Неделя | Занятие | GET | PUT |
|--------|---------|-----|-----|
| draft | любое | 404 | 404 |
| published / locked | draft / scheduled | 404 | 404 |
| published / locked | published / moved | roster, `attendance_editable=true` | разрешён |
| published / locked | cancelled | roster, `attendance_editable=false`, `reason=cancelled` | **409** `attendance_not_editable` |

Черновики admin (`draft` / `scheduled`) на mobile не видны.

## Окно редактирования по времени

MVP **не** ограничивает отметку минутами занятия. Преподаватель может поставить или исправить отметку после урока.

Чужие и неофициальные занятия по-прежнему недоступны.

**OPEN:** финализация attendance / окно редактирования (lock через N дней). См. [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Эндпоинты

Только teacher (`auth:sanctum` + `mobile.actor` + `account_type=teacher`):

```text
GET /api/v1/teacher/lessons/{scheduledLesson}/attendance
PUT /api/v1/teacher/lessons/{scheduledLesson}/attendance
```

PUT — **bulk partial upsert**: каждая переданная строка применяется; ученики вне payload не меняются. Flutter может слать весь roster или только изменённые строки.

Если любой `student_id` не в roster класса, весь запрос отклоняется (**422**, атомарно, без частичной записи).

Успешный PUT возвращает тот же контракт, что GET (актуальный roster + отметки).

Audit: при реальной смене статуса обновляются `marked_by` / `marked_at` (`now()` в `Europe/Rome`). Activity log `attendance.updated` хранит только `scheduled_lesson_id` + `changed_count` (без списка детей).

## Вне этого этапа

История Student/Parent, проценты, уведомления об отсутствии, justification upload, medical reason, late, комментарии, admin dashboard, teacher check-in/geolocation, завершение занятия, roster snapshots, finalization lock.
