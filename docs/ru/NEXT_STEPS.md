# AUB — Следующие шаги

Что построено: [CURRENT_STATE.md](CURRENT_STATE.md). Полный список вопросов: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md). Направления: [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

**Статус (2026-09-08):** Web-ядро в работе. Репозиторий Flutter есть. **HTTPS API нет.** Core Data Model refactor должен предшествовать стабильному API-контракту.

**DECIDED:** ядро `AUB_admin`; Flutter `AUB_app`; только HTTPS API; раздельные GitHub-репо; параллельный backend/Flutter; один активный `Class`; один User = один actor type; `customers` — interim.

## Ближайший технический трек

1. Честные docs (этот поток).
2. **Security Foundation** до широкого mobile (private storage, field-level ACL, scoped teachers, view audit, матрица API-авторизации, token security, **2FA админов**, consent records) — отдельные задачи.
3. **Core Data Model refactor** (`students` / `parents` / `student_parent`; один `Class`) — **до** стабильного API. Миграции не в этой задаче.
4. **Identity model implementation** (один User = один actor type; identity ≠ web RBAC).
5. **API Foundation** (Sanctum = кандидат, не зафиксирован) — только после п. 3–4.
6. **Flutter Foundation**, когда появится контракт (дистрибуция в Store **OPEN**: одно приложение vs flavors).

Flutter **можно** развивать параллельно (оболочка, навигация). Реальные функции академии ждут API. Стабильный контракт не публиковать поверх `customers`.

## Зафиксированные продуктовые правила (не OPEN)

- Ребёнок: максимум один активный основной `Class`; плюс отдельно дополнительные группы ≠ `Class`.
- Unique `customer_id` на `course_group_customer` концептуально соответствует «один основной класс»; терминологию ещё нормализовать.
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

Студенты на `customers` (interim), справочник преподавателей, курсы/группы, уроки в Settings, недельное расписание + гибридный ИИ, CRUD-логи, шаблон Flutter на GitHub.

### Опциональная полировка web-расписания

Исполняемый ИИ, PDF, snap 5 мин в UI, вместимость залов vs учебные окна.

## Не делать в docs-only / без отдельной задачи

- Не удалять legacy-таблицы kit
- Не править `vendor/`
- Не раскрывать секреты
- Не фиксировать Sanctum
- Не реализовывать миграции `students` / `parents`
- Не публиковать стабильный mobile API до Core Data Model refactor
- Не реализовывать здесь check-in, табели или постановки
- Не хардкодить возрастной порог consent
