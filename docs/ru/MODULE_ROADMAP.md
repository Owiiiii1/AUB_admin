# AUB — Дорожная карта модулей

Номера ниже — **крупные продуктовые направления**. Рекомендуемая **последовательность начала** (1–5) обязательна: Core Data Model refactor явно **предшествует** стабильному API-контракту. Остальные модули PM может переставлять, кроме Productions (поздний future). Backend и Flutter развиваются **параллельно**; их соединяет **API-контракт**.

Снимок реализованного web: [CURRENT_STATE.md](CURRENT_STATE.md). Открытые пункты: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Уже в production (web-ядро)

- Laravel 13 + kit v0.4.0, session auth, RBAC фазы 1
- Студенты на `students` + родители на `parents` / `student_parent`
- Справочник преподавателей (`user_id` + create/link account)
- Identity Layer: `account_type` staff/student/parent/teacher; один User = один actor type
- Курсы / `AcademyClass`; каталог уроков в Настройки → Академия
- `ClassLesson` + назначения преподавателей; недельное расписание на `academy_class_id`
- CRUD-журнал; вкладки settings
- Репозиторий Flutter есть; **API Foundation `/api/v1` сделан**; **student/parent schedule API сделан**; в клиенте есть auth foundation

---

## Рекомендуемая последовательность начала

| # | Направление | Примечание |
|---|-------------|------------|
| 0 | Baseline / GitHub / documentation | Живые docs |
| 1 | **Security Foundation** | **Обязательно до широкого mobile rollout.** Private storage файлов детей; field-level ACL; scoped teacher access; access/view audit; матрица API-авторизации; mobile token security; **2FA для админ-персонала**; consent/privacy records. **Не реализовано.** |
| 2 | **Core Data Model refactor** | **Сделан** 2026-09-08. `students`, `parents`, `AcademyClass`, `ClassLesson`. `customers` leftover. |
| 3 | **Identity model implementation** | **Сделан** 2026-09-08. Один User = один actor type (`staff` / `student` / `parent` / `teacher`). Identity ≠ admin web RBAC. |
| 4 | **API Foundation** | **Сделан** 2026-09-08. `/api/v1`, Sanctum v4.3.3, login/logout/me/health. См. [API.md](API.md). |
| 5 | **Flutter Foundation** | Auth foundation в `AUB_app`. Модель дистрибуции Store OPEN |
| 6 | Student + Parent mobile MVP | **Schedule API сделан** 2026-09-09 (`GET /schedule`, `GET /children/{student}/schedule`). Дальше Flutter UI расписания. |
| 7 | Teacher App / Teacher Workplace | Flutter + web workplace; связь Teacher↔User. Identity/app foundation **до** check-in |
| 8 | **Student Attendance** | Присутствие ребёнка на **конкретном занятии / репетиции / session**. **Не** geofence преподавателя |
| 9 | **Teacher Check-in / Staff Presence** | Отдельный модуль **после** Teacher identity/app foundation. Daily presence, не per-lesson. Кнопка «Пришёл» + разовый GPS + geofence. QR = optional fallback |
| 10 | **Final Assessment / Report Cards** | **Отдельный продуктовый модуль.** Текущих оценок нет; только `StudentFinalResult` + `ReportCard`; PDF генерирует Administrator |
| 11 | Secretariat / Enrollment Workflow | Статусы, переводы, история вокруг одного `Class` |
| 12 | Documents | Отдельный модуль; убрать с public disk |
| 13 | Communications + Push | Сейчас заглушка |
| 14 | Payments / Accounting | Фискальные правила OPEN |
| 15 | Schedule evolution | Единый календарь; Variant A vs B OPEN; `scheduled_lessons` не менять, пока нет выбора |
| 16 | Dashboard / Reporting | Dashboard сейчас заглушка; логи ≠ отчёты |
| 17 | Consent / Privacy | Целевые `ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`; возрастной порог не хардкодить |
| 18 | Infrastructure / Backup / Monitoring | OPEN |
| 19 | CI/CD / Staging / Deployment | Production не git-репо |
| 20 | App Store / Google Play | После privacy + API + выбора дистрибуции |

Старые номера «фаза 0–6» — **исторические**. Не считать «фаза 6 = спектакли» продуктовой моделью.

---

## Поздний future (discovery-needed)

| Направление | Примечание |
|-------------|------------|
| **Productions / Shows** | Поздний этап. Не детализировать workflow сейчас. Activity groups ≠ `Class`. Расписание будущих групп — в единый календарь ребёнка. Costume Service может остаться отдельным сервисом |
| Costume Service / Archive / доп. сервисы | Могут стыковаться с постановками позже; всё же отдельные сервисы |

---

## Разделение доменов (не смешивать)

| Домен | Смысл |
|-------|-------|
| `Class` | Постоянный основной учебный класс; максимум один активный на ребёнка |
| Additional activity groups | Production / rehearsal / иное; **не** `Class`; ребёнок может быть в нескольких |
| Student Attendance | Ребёнок на session |
| Teacher Presence | Сотрудник физически в академии (daily check-in) |
| Final Assessment | Итог периода: `StudentFinalResult` / `ReportCard`; не текущие оценки |

## Параллельность

```
AUB_admin  ── API-контракт ──  AUB_app
   │                              │
   web workplaces / admin         student / parent / teacher
                                  (один User = один actor type;
                                   дистрибуция Store OPEN)
```

Стабильный API-контракт **не** публиковать до Core Data Model refactor + Identity model.
