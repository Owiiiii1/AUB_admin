# AUB — Следующие шаги

Что построено: [CURRENT_STATE.md](CURRENT_STATE.md). Полный список вопросов: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md). Направления: [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

**Статус (2026-09-08):** Web-ядро в работе. Репозиторий Flutter есть. **HTTPS API нет.** Core Data Model **сделан**. Identity Layer **сделан**. Следующий этап — **API Foundation**.

**DECIDED:** ядро `AUB_admin`; Flutter `AUB_app`; только HTTPS API; раздельные GitHub-репо; параллельный backend/Flutter; один активный `Class`; один User = один actor type; `customers` — kit leftover.

## Ближайший технический трек

1. Честные docs (этот поток).
2. **Security Foundation** до широкого mobile (private storage, field-level ACL, scoped teachers, view audit, матрица API-авторизации, token security, **2FA админов**, consent records) — отдельные задачи.
3. **Core Data Model refactor** — **сделан** (`students` / `parents` / `AcademyClass` / `ClassLesson`).
4. **Identity model implementation** — **сделан** (`account_type`, profile `user_id`, `AccountIdentityService`, admin UI).
5. **API Foundation** (Sanctum = кандидат, не зафиксирован) — **следующий этап**.
6. **Flutter Foundation**, когда появится контракт (дистрибуция в Store **OPEN**: одно приложение vs flavors).

Flutter **можно** развивать параллельно (оболочка, навигация). Реальные функции академии ждут API. Стабильный контракт не публиковать до Identity.

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
- Не фиксировать Sanctum
- Не реализовывать API / Flutter login в этой задаче (Identity уже сделан)
- Не публиковать стабильный mobile API до Identity
- Не реализовывать здесь check-in, табели или постановки
- Не хардкодить возрастной порог consent
