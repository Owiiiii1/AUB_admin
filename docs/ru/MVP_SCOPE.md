# AUB — Объём MVP

## Различие

`custom-admin-kit` поставил **универсальные заготовки CRM**. Это не модули академии. Маршруты kit orders/services/staff/calendar **сняты**. Студенты пока живут в `customers`.

MVP AUB — **ядро академии** плюс **интерфейсы** (web admin, workplaces персонала, Flutter). Не пересобирать session auth и CRUD пользователей. Не проектировать всё вокруг одной админки.

Числовые метки «фаза 0–6» ниже — **исторические web-вехи**, не roadmap продукта. Живые направления: [MODULE_ROADMAP.md](MODULE_ROADMAP.md). Открытые вопросы: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Фаза 1 — Доступ (сделана)

Роли и workplaces: реализованы 2026-07-06. См. [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md).

Пробелы: нет field-level ACL; extra-маршруты всегда доступны любой роли. **Security Foundation** (private documents, field ACL, scoped teachers, view audit, API auth matrix, mobile tokens, 2FA админов, записи согласий) обязателен до **широкого** mobile rollout — не реализован.

## Основные записи (фаза 2)

| Модуль | Статус | Примечание |
|--------|--------|------------|
| Студенты | Частично | Сущность `Customer` / таблица `customers` (interim; целевое `students`) |
| Родители | Частично | Встроенные колонки; модели Parent нет |
| Преподаватели | Справочник готов | Нет `user_id` |
| Курсы / группы | Готово | `/courses-groups` |
| Каталог уроков | Готово | Настройки → Академия; нет `Lessons/Index.jsx` |
| Зачисления | Частично | unique `academy_class_student.student_id` = **DECIDED** «один активный `Class`». Workflow статусов — OPEN |

## Операции (фаза 3)

| Модуль | Статус |
|--------|--------|
| Недельное расписание | Реализовано (`/schedule-service`) — только обычные `scheduled_lessons` |
| **Student Attendance** | Не начато — ребёнок на **session**; отдельно от присутствия преподавателя |
| **Teacher Check-in / Staff Presence** | Не начато — **DECIDED**: daily presence (кнопка «Пришёл» + разовый GPS + geofence); не per lesson |
| Документы | Upload в профиле на диске **public**; `/documents` — заглушка |
| Заметки / коммуникации | Заглушка |

## Учёт (фаза 4)

Платежи / счета / отчёты — не начаты. `orders.total` kit — не бухгалтерия академии. `/statistics/logs` — журнал активности, не дашборд отчётов.

## Крупные модули (не «опциональные остатки»)

| Модуль | Примечание |
|--------|------------|
| Final Assessment / Report Cards | **Отдельный продуктовый модуль.** Текущих оценок нет; нет per-lesson gradebook. Итог: `StudentFinalResult` (Student + Class + Lesson + AcademicYear). Табель = все `ClassLesson`. PDF формирует Administrator. Шкала / шаблон PDF — OPEN. |
| Productions / Shows | **Поздний future** / discovery-needed. Activity groups ≠ `Class`. Расписание будущих групп — в единый календарь. Workflow не детализировать сейчас. Costume Service может остаться отдельным сервисом. |
| Единый календарь | Будущее: уроки + activity groups (+ возможно экзамены/события). Архитектура Variant A vs B **OPEN**. `scheduled_lessons` сейчас не менять. |

## API и Flutter

| Пункт | Статус |
|-------|--------|
| Репозиторий Flutter `Owiiiii1/AUB_app` | Есть (`aub`, `com.owlsolutions.aub`) |
| HTTPS API | **Нет** |
| Token auth | **Sanctum `/api/v1`** (Flutter-клиент ещё не подключён) |
| Дистрибуция Store | **OPEN** — одно приложение с режимами vs несколько Store-приложений / flavors |

Flutter **можно развивать параллельно**. MVP-функции мобильного клиента блокируются API Foundation, а не требованием «web должен быть завершён на 100%». Backend и Flutter развиваются параллельно; их соединяет API-контракт. Стабильный контракт **после** Core Data Model refactor + Identity.

## Граф зависимостей (исторический web + следующий технический)

```
Фаза 0 kit ✅
    → Фаза 1 роли ✅
    → Фаза 2 записи (частично; customers = interim)
    → Фаза 3 расписание ✅ / student attendance ❌ / teacher check-in ❌
    → Фаза 4 учёт ❌
    → Security Foundation (до широкого mobile)
    → Core Data Model refactor (до стабильного API)
    → Identity model (один User = один actor type)
    → API Foundation
    → Flutter Foundation (параллельно; дистрибуция OPEN)
    → Student+Parent MVP / Teacher workplace
    → Teacher Check-in (после Teacher identity/app)
    → Final Assessment / Report Cards (отдельный модуль)
    → Workflow секретариата, документы, коммуникации, платежи
    → Эволюция расписания
    → Productions / Shows (поздний future)
```
