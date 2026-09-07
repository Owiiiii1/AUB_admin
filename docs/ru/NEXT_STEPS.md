# AUB — Следующие шаги

Что построено: [CURRENT_STATE.md](CURRENT_STATE.md). Полный список вопросов: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md). Направления: [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

**Статус (2026-09-07):** Web-ядро в работе. Репозиторий Flutter есть. **HTTPS API нет.** Номера направлений — **не** замороженный приоритет PM.

**DECIDED:** ядро `AUB_admin`; Flutter `AUB_app`; только HTTPS API; раздельные GitHub-репо; параллельный backend/Flutter.

## Ближайший технический трек

1. Честные docs (этот поток).
2. **Security Foundation** до широкого mobile (private storage, field-level ACL, scoped teachers, view audit, матрица API-авторизации, token security, **2FA админов**, consent records) — отдельные задачи.
3. **API Foundation** (Sanctum = кандидат, не зафиксирован).
4. **Flutter Foundation**, когда появится контракт (дистрибуция в Store **OPEN**: одно приложение vs flavors).

Flutter **можно** развивать параллельно (оболочка, навигация). Реальные функции академии ждут API.

## HIGH PRIORITY продуктовый вопрос

**Может ли ребёнок быть сразу в нескольких CourseGroup?** Код: unique `customer_id` на `course_group_customer` ⇒ **одна группа всего**. Если академия допускает несколько дисциплин, ограничение неверное. **Не мигрировать, пока нет ответа.**

## Не смешивать

| Student Attendance | Teacher Check-in |
|--------------------|------------------|
| Ребёнок на **session** (урок/репетиция/…) | Сотрудник **физически в академии** |
| Не построено | PRELIMINARY: кнопка «Пришёл» + разовый GPS + geofence; QR только fallback |
| | OPEN: день/смена vs конкретная session; радиус; accuracy; anti-spoofing |

## Другие крупные модули (не «сделать следующим» без PM)

- **Academic Progress** — оценки, периоды, табель; система оценивания **OPEN** (спросить академию).
- **Productions / репетиции** — RehearsalGroup ≠ CourseGroup (**PRELIMINARY**). Репетиции **обязаны** попадать в единый календарь ребёнка и в conflict detection. **Не** опциональный placeholder фазы 6. Приоритет задаёт PM после discovery.
- **Архитектура единого календаря** — Variant A (`ScheduledSession` + типы) vs Variant B (разные сущности + агрегатор). Tech Lead **не** решил. `scheduled_lessons` пока не менять.
- Identity (User vs профили Teacher/Parent/Student) — **OPEN**.
- Workflow зачислений, документы, коммуникации, платежи.

## Уже сделано (web)

Студенты на `customers`, справочник преподавателей, курсы/группы, уроки в Settings, недельное расписание + гибридный ИИ, CRUD-логи, шаблон Flutter на GitHub.

### Опциональная полировка web-расписания

Исполняемый ИИ, PDF, snap 5 мин в UI, вместимость залов vs учебные окна.

## Не делать в docs-only / без отдельной задачи

- Не удалять legacy-таблицы kit
- Не править `vendor/`
- Не раскрывать секреты
- Не фиксировать Sanctum
- Не «чинить» unique зачисления без ответа академии
- Не реализовывать здесь check-in, оценки или постановки
