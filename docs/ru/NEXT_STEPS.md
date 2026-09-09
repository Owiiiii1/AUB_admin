# AUB — Следующие шаги

Что построено: [CURRENT_STATE.md](CURRENT_STATE.md). Полный список вопросов: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md). Направления: [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

**Статус (2026-09-09):** Web-ядро в работе. **API Foundation `/api/v1` сделан** (Sanctum). **Student/parent/teacher schedule API сделан**. **Teacher Attendance API сделан**. Часовой пояс приложения — **`Europe/Rome`**. В Flutter есть auth + student/parent schedule. Core Data Model **сделан**. Identity Layer **сделан**. PHPUnit на `aub_test` **зелёный**.

**DECIDED:** ядро `AUB_admin`; Flutter `AUB_app`; только HTTPS API; раздельные GitHub-репо; параллельный backend/Flutter; один активный `Class`; один User = один actor type; `customers` — kit leftover.

## Ближайший технический трек

1. Честные docs (этот поток).
2. **Security Foundation** до широкого mobile (private storage, field-level ACL, scoped teachers, view audit, матрица API-авторизации, token security, **2FA админов**, consent records) — отдельные задачи.
3. **Core Data Model refactor** — **сделан** (`students` / `parents` / `AcademyClass` / `ClassLesson`).
4. **Identity model implementation** — **сделан** (`account_type`, profile `user_id`, `AccountIdentityService`, admin UI).
5. **Инфраструктура тестов** — **сделана** (MySQL `aub_test`, hard guard против production `aub`; без SQLite).
6. **API Foundation** — **сделан** (`/api/v1`, Sanctum v4.3.3, login/logout/me/health). Контракт: [API.md](API.md).
7. **Flutter Authentication Foundation** — **сделан** в `AUB_app`.
8. **Student / Parent schedule API** — **сделан** (`GET /schedule`, `GET /children/{student}/schedule`).
9. **Часовой пояс академии** — **сделан** (`APP_TIMEZONE=Europe/Rome`).
10. **Teacher schedule API** — **сделан** (`GET /teacher/schedule`).
11. **Teacher Attendance API** — **сделан** (`GET`/`PUT /teacher/lessons/{scheduledLesson}/attendance`). Контракт: [ATTENDANCE.md](ATTENDANCE.md).

Следующий клиентский slice: **история посещаемости Student/Parent** (read-only). Teacher check-in — отдельный модуль.

## Зафиксированные продуктовые правила (не OPEN)

- Ребёнок: максимум один активный основной `Class` (`unique academy_class_student.student_id`); плюс отдельно дополнительные группы ≠ `Class`.
- PHP-модель Class = `AcademyClass` / `academy_classes`.
- Teacher Check-in = **daily presence**, не per lesson.
- Текущих оценок нет. Итог: `StudentFinalResult` (Student + Class + Lesson + AcademicYear). PDF табеля формирует Administrator.

## Не смешивать

| Student Attendance | Teacher Check-in |
|--------------------|------------------|
| Ребёнок на **session** (урок/репетиция/…) | Сотрудник **физически в академии** |
| Не построено | DECIDED: кнопка «Пришёл» + разовый GPS + geofence → daily check-in; QR optional fallback |
| | OPEN: radius; accuracy; окно времени; anti-spoofing |

## Другие крупные модули (не «сделать следующим» без PM)

- **Final Assessment / Report Cards** — отдельный продуктовый модуль после foundation. Шкала / шаблон PDF / кто закрывает табель — **OPEN**.
- **Teacher Check-in** — отдельный модуль после Teacher identity/app foundation.
- **Productions / Shows** — поздний future / discovery-needed. Activity groups ≠ `Class`. Расписание будущих групп — в единый календарь. Workflow не детализировать сейчас.
- **Архитектура единого календаря** — Variant A vs B. Tech Lead **не** решил. `scheduled_lessons` пока не менять.
- Workflow зачислений в `Class`, документы, коммуникации, платежи.

## Уже сделано (web)

Студенты на `students` + родители на `parents`, `AcademyClass`, `ClassLesson`, справочник преподавателей (`user_id` foundation), курсы, уроки в Settings, недельное расписание на `academy_class_id` + гибридный ИИ, CRUD-логи, шаблон Flutter на GitHub.

### Опциональная полировка web-расписания

Исполняемый ИИ, PDF, snap 5 мин в UI, вместимость залов vs учебные окна.

## Не делать в docs-only / без отдельной задачи

- Не удалять legacy-таблицы kit
- Не править `vendor/`
- Не раскрывать секреты
- Не фиксировать refresh-token / JWT
- Не реализовывать Flutter login в этой задаче (API Foundation уже сделан)
- Не реализовывать check-in, табели и постановки здесь
- Не хардкодить возрастной порог consent
