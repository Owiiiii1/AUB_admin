# AUB — Дорожная карта модулей

Номера ниже — **крупные продуктовые направления**, не замороженный бизнес-приоритет. PM может переставить их после discovery (особенно **Productions**). Backend и Flutter развиваются **параллельно**; их соединяет **API-контракт**.

Снимок реализованного web: [CURRENT_STATE.md](CURRENT_STATE.md). Открытые пункты: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Уже в production (web-ядро)

- Laravel 13 + kit v0.4.0, session auth, RBAC фазы 1
- Студенты на `customers` (частично); родители встроены; справочник преподавателей (нет `user_id`)
- Курсы / группы; каталог уроков в Настройки → Академия
- Недельное расписание + гибридный ИИ (только `scheduled_lessons`)
- CRUD-журнал; вкладки settings
- Репозиторий Flutter есть; **API нет**

---

## Направления (0–22+)

| # | Направление | Примечание |
|---|-------------|------------|
| 0 | Baseline / GitHub / documentation | Живые docs |
| 1 | **Security Foundation** | **Обязательно до широкого mobile rollout.** Private storage файлов детей; field-level ACL; scoped teacher access; access/view audit; матрица API-авторизации; mobile token security; **2FA для админ-персонала**; consent/privacy records. **Не реализовано.** |
| 2 | Core Data Model | `customers` vs `students`; **HIGH PRIORITY:** unique «одна группа на студента» vs несколько групп; identity |
| 3 | API Foundation | Маршруты, версии, token auth (Sanctum = кандидат), `/me`, resources, ошибки, rate limits, тесты |
| 4 | Flutter Foundation | Оболочка app, env, HTTPS-клиент, auth к API — модель дистрибуции OPEN |
| 5 | User Identity / доступ Student / Parent / Teacher | Identity ≠ admin RBAC. Teacher↔User нет |
| 6 | Student + Parent mobile MVP | Зависит от 3–5 |
| 7 | Teacher App / Teacher Workplace | Flutter check-in + web workplace; `teachers.user_id` |
| 8 | **Student Attendance** | Присутствие ребёнка на **конкретном занятии / репетиции / session**. **Не** geofence преподавателя |
| 9 | **Teacher Check-in / Staff Presence** | Физическое присутствие в академии. PRELIMINARY: кнопка «Пришёл» + разовый GPS + geofence. QR только fallback |
| 10 | Academic Progress / оценки / табель | Крупный модуль; система оценок OPEN (discovery с академией) |
| 11 | Secretariat / Enrollment Workflow | Статусы, переводы, история |
| 12 | Documents | Отдельный модуль; убрать с public disk |
| 13 | Communications + Push | Сейчас заглушка |
| 14 | Payments / Accounting | Фискальные правила OPEN |
| 15 | Schedule evolution | Единый календарь; Variant A vs B OPEN; `scheduled_lessons` не менять, пока нет выбора |
| 16 | **Productions / Shows / Rehearsal Scheduling** | **Не** «опциональный event placeholder». RehearsalGroup ≠ CourseGroup. Репетиции в календаре ребёнка + конфликты. Приоритет: **PM после discovery** (можно поднять). Costume Service может остаться отдельным |
| 17 | Dashboard / Reporting | Dashboard сейчас заглушка; логи ≠ отчёты |
| 18 | Consent / Privacy | Сущностей нет; юридических текстов нет |
| 19 | Infrastructure / Backup / Monitoring | OPEN |
| 20 | CI/CD / Staging / Deployment | Production не git-репо |
| 21 | App Store / Google Play | После privacy + API + выбора дистрибуции |
| 22+ | Costume Service / Archive / доп. сервисы | Костюмы могут стыковаться с постановками; всё же отдельный сервис |

Старые номера «фаза 0–6» — **исторические**. Не считать «фаза 6 = спектакли» продуктовой моделью.

## Разделение доменов (не смешивать)

| Домен | Смысл |
|-------|-------|
| Student Attendance | Ребёнок на session |
| Teacher Presence | Сотрудник физически в академии |
| CourseGroup | Обычная учебная группа |
| RehearsalGroup | Группа постановки (PRELIMINARY: не CourseGroup) |

## Параллельность

```
AUB_admin  ── API-контракт ──  AUB_app
   │                              │
   web workplaces / admin         student / parent / teacher
                                  (дистрибуция OPEN)
```
