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
| Студенты | Частично | Сущность `Customer` / таблица `customers` |
| Родители | Частично | Встроенные колонки; модели Parent нет |
| Преподаватели | Справочник готов | Нет `user_id` |
| Курсы / группы | Готово | `/courses-groups` |
| Каталог уроков | Готово | Настройки → Академия; нет `Lessons/Index.jsx` |
| Зачисления | Частично | `course_group_customer`; unique `customer_id` в **коде**. Может ли студент быть в нескольких CourseGroup — **HIGH PRIORITY OPEN**. Unique index не считать бизнес-правилом |

## Операции (фаза 3)

| Модуль | Статус |
|--------|--------|
| Недельное расписание | Реализовано (`/schedule-service`) — только обычные `scheduled_lessons` |
| **Student Attendance** | Не начато — ребёнок на **session**; отдельно от присутствия преподавателя |
| **Teacher Check-in / Staff Presence** | Не начато — **PRELIMINARY**: кнопка «Пришёл» + разовый GPS + geofence на backend |
| Документы | Upload в профиле на диске **public**; `/documents` — заглушка |
| Заметки / коммуникации | Заглушка |

## Учёт (фаза 4)

Платежи / счета / отчёты — не начаты. `orders.total` kit — не бухгалтерия академии. `/statistics/logs` — журнал активности, не дашборд отчётов.

## Крупные модули (не «опциональные остатки»)

| Модуль | Примечание |
|--------|------------|
| Academic Progress / оценки / табель | Крупный будущий модуль. Система оценок **OPEN**. Финальной БД нет. |
| Productions / Shows / Rehearsals | Доменная подсистема, связанная с core schedule — **не** «опциональный event фазы 6». RehearsalGroup ≠ CourseGroup (**PRELIMINARY**). Репетиции обязаны попадать в единый календарь ребёнка и в conflict detection (**PRELIMINARY**). Приоритет задаёт PM после product discovery. Costume Service может остаться отдельным интегрированным сервисом. |
| Единый календарь | Будущее: уроки + репетиции + спектакли (+ возможно экзамены/события). Архитектура Variant A vs B **OPEN**. `scheduled_lessons` сейчас не менять. |

## API и Flutter

| Пункт | Статус |
|-------|--------|
| Репозиторий Flutter `Owiiiii1/AUB_app` | Есть (`aub`, `com.owlsolutions.aub`) |
| HTTPS API | **Нет** |
| Token auth | **Нет** (Sanctum = кандидат) |
| Дистрибуция Store | **OPEN** — одно приложение с режимами vs несколько Store-приложений / flavors |

Flutter **можно развивать параллельно**. MVP-функции мобильного клиента блокируются API Foundation, а не требованием «web должен быть завершён на 100%». Backend и Flutter развиваются параллельно; их соединяет API-контракт.

## Граф зависимостей (исторический web + следующий технический)

```
Фаза 0 kit ✅
    → Фаза 1 роли ✅
    → Фаза 2 записи (частично)
    → Фаза 3 расписание ✅ / student attendance ❌ / teacher check-in ❌
    → Фаза 4 учёт ❌
    → Security Foundation (до широкого mobile)
    → API Foundation (следующий технический)
    → Flutter (параллельно; дистрибуция OPEN)
    → Identity / Student+Parent MVP / workplace преподавателя
    → Academic Progress, workflow секретариата, документы, коммуникации, платежи
    → Эволюция расписания + Productions (приоритет: PM после discovery)
```
