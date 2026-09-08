# Cursor Work Report

## Task

DOCUMENTATION-ONLY. Зафиксировать решения PM / Tech Lead: один `Class`; дополнительные группы ≠ `Class`; один User = один actor type; `customers` → `students`/`parents`; Teacher Check-in = daily presence; Final Assessment без текущих оценок; consent без возрастного порога; Productions — поздний future. Код, миграции, DB, routes, config, frontend, vendor и production **не менялись**.

Commit message: `docs: finalize core product decisions`

## Repository state

- **branch:** `main`
- **working tree до работы:** предыдущий docs-коммит на `main`
- **после правок:** только `docs/`
- Production `/var/www/aub` **не изменялся**
- `AUB_app` **не изменялся** (продуктовые docs живут в `AUB_admin`)

## Decisions moved to DECIDED

- Ребёнок одновременно имеет максимум один активный основной `Class`
- Unique `course_group_customer.customer_id` концептуально соответствует «один основной класс»; терминология `course_groups` vs `Class` ещё нормализовать
- Дополнительные activity groups (production / rehearsal / иное) **не** являются `Class`; ребёнок может быть в одном `Class` и в нескольких таких группах
- Один User имеет ровно один основной actor/account type (`student` / `parent` / `teacher`); multi-profile identity в текущую версию не закладывать; две роли одного человека = два аккаунта
- Authentication account type ≠ административный web RBAC
- `customers` — interim/legacy; целевое направление `students` + `parents` + `student_parent`; production без ценных пользовательских данных
- Core Data Model refactor **до** публикации стабильного mobile API
- Teacher Check-in = daily presence, не per lesson; workflow «Пришёл» + один GPS snapshot + geofence
- Текущих оценок нет; per-lesson gradebook не нужен
- Итоговая оценка принадлежит Student + Class + Lesson + AcademicYear (`StudentFinalResult`)
- Заполнять итог могут только преподаватели, назначенные на этот `ClassLesson`; одна общая запись
- Табель включает все дисциплины `ClassLesson` класса на AcademicYear
- PDF табеля формирует Administrator в admin panel; PDF не источник данных
- Productions / Shows — поздний future / discovery-needed
- Расписание дополнительных групп в будущем попадает в единый календарь ребёнка
- Consent: целевые `ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`; возрастной порог не хардкодить

## Remaining OPEN

- Нормализация терминологии `Class` vs текущие `course_groups` / API-имена
- Enrollment workflow (статусы, переводы, история, даты)
- Правила `student_parent` (несколько детей), invitation/activation, старший student-аккаунт, `teachers.user_id`
- Field-level ACL / scoped teacher access
- Student Attendance (ребёнок на session)
- Check-in: geofence radius, min GPS accuracy, окно времени, anti-spoofing, доп. сигналы
- Final Assessment детали: шкала, формат result, обязательность comment, кто закрывает табель, правка после `finalized`, approval workflow, digital signature, шаблон PDF, snapshot после печати, версии, видимость student vs parent, экзамены, периоды
- Calendar Variant A vs B
- Flutter Store: одно приложение vs flavors
- Documents / Communications / Payments / Infrastructure
- Consent: какие types обязательны, UX, legal review возраста

## Files changed

### Created

- none

### Modified

- `docs/README.md`
- `docs/en/` + `docs/ru/`: `OPEN_QUESTIONS.md`, `MODULE_ROADMAP.md`, `NEXT_STEPS.md`, `DATA_MODEL_DRAFT.md`, `ARCHITECTURE.md`, `MVP_SCOPE.md`, `USER_ROLES_AND_ACCESS.md`, `PRIVACY_AND_DATA_PROTECTION.md`, `README_PROJECT_OVERVIEW.md`, `CURRENT_STATE.md`, `DEVELOPMENT_RULES.md`, `WEEKLY_SCHEDULE_SERVICE.md`
- `docs/Development/Cursor_Work_Report.md` (полностью перезаписан)

### Not changed (checked, still accurate)

- `docs/en/SERVER_DEPLOYMENT.md` / `docs/ru/SERVER_DEPLOYMENT.md` — факты деплоя
- `docs/ref/*` — UI design sketches, не product roadmap

### Deleted

- none

Non-docs files: **none**.

## Remaining contradictions

Это **не** копия OPEN_QUESTIONS. Ниже — docs↔code и терминологические разрывы, которые **нельзя** закрыть docs-only.

### Docs vs code (честно оставлены)

| Topic | Code fact | Docs after this task |
|-------|-----------|----------------------|
| Primary class | `course_groups` + unique `course_group_customer.customer_id` | Product `Class` DECIDED; unique концептуально совпадает; имена таблиц не нормализованы |
| Student / Parent | `customers` + father/mother columns | Направление `students`/`parents`/`student_parent` DECIDED; миграций нет |
| Teacher login | `teachers` **без** `user_id` | Teacher — mobile actor type DECIDED; связь с User не реализована |
| Geofence | `academy_buildings` / `academy_rooms` без lat/lng/radius | Концепт check-in; колонок нет |
| Calendar | Только `scheduled_lessons` | Будущий единый календарь DECIDED как требование; Variant A vs B OPEN |
| `/events` | Placeholder `ComingSoon` | Productions — поздний future; UI-заглушка |
| Children’s files | **public** disk | Security Foundation по-прежнему требует private storage |
| API | Нет `routes/api.php` | Стабильный контракт **после** Core Data Model + Identity |
| Access width | `always_allowed_route_patterns` шире меню роли | Intent ≠ код |
| Activity log | CRUD + login/logout, не view audit | Security Foundation unmet |
| Kit leftovers | `orders` / `services` / `staff` / `calendar` unrouted | Не переиспользовать как academy payments/events |

### Docs vs docs (resolved in this task)

- «Один CourseGroup — HIGH PRIORITY OPEN» — снято: правило одного `Class` DECIDED
- Identity graph / multi-profile OPEN — снято: один User = один actor type
- `customers` «держать, пока нет задачи» без направления — исправлено на DECIDED target tables
- Check-in «день vs session OPEN» — исправлено: daily presence DECIDED
- Academic Progress с текущими/промежуточными оценками — исправлено: только итоговые результаты
- «до 14 лет» как продуктное правило — удалено
- Productions как поднимаемый приоритет после discovery — перенесено в поздний future
- API Foundation как ближайший этап до Core Data Model — исправлено: Security → Core Data Model → Identity → API → Flutter

### Remaining tension (не притворяться решённым)

1. **Терминология Class vs course_groups.** Продуктовое правило есть, схема ещё kit-имена. Нормализация — отдельная задача Core Data Model, не сейчас.
2. **Teacher web RBAC vs Teacher mobile actor.** Один физический преподаватель с web-логином персонала и mobile Teacher-аккаунтом формально два account type, если следовать правилу «один User = один type». Как связать directory `teachers` с web User и mobile Teacher — OPEN.
3. **Calendar A vs B.** Требование «activity groups в календаре ребёнка» DECIDED для будущего; сущность не выбрана. `scheduled_lessons` не менять.
4. **ReportCard lifecycle.** Ядро модели DECIDED; кто закрывает табель, правка после `finalized`, approval, PDF-шаблон — OPEN. Не проектировать финальную БД модуля сейчас.
5. **Consent legal age.** Модель-направление есть; порог нельзя хардкодить до legal review.
6. **Store packaging vs identity.** Один User = один type DECIDED; одно Store-приложение vs flavors всё ещё OPEN.

## Checks

- `git diff --name-only` — только `docs/`
- Поиск по docs: нет оставшихся «HIGH PRIORITY» unique-enrollment, «до 14 лет» как правило, «текущие оценки в периоде», «multi-profile в текущую версию»
- RU/EN пары синхронизированы по смыслу: OPEN_QUESTIONS, MODULE_ROADMAP, NEXT_STEPS, DATA_MODEL_DRAFT, ARCHITECTURE, MVP_SCOPE, USER_ROLES, PRIVACY, README_PROJECT_OVERVIEW, CURRENT_STATE, DEVELOPMENT_RULES, WEEKLY_SCHEDULE
- Локальный PHP/artisan не запускался (docs-only)
- Production SSH не выполнялся

## Git diff

Ожидаемый набор (только `docs/`):

```
docs/README.md
docs/en/* (ARCHITECTURE, CURRENT_STATE, DATA_MODEL_DRAFT, DEVELOPMENT_RULES,
  MODULE_ROADMAP, MVP_SCOPE, NEXT_STEPS, OPEN_QUESTIONS,
  PRIVACY_AND_DATA_PROTECTION, README_PROJECT_OVERVIEW,
  USER_ROLES_AND_ACCESS, WEEKLY_SCHEDULE_SERVICE)
docs/ru/* (тот же набор)
docs/Development/Cursor_Work_Report.md
```

Non-docs: none.

## Commands executed

```
git status
git diff --stat
git log -5 --oneline
git add docs/
git commit -m "docs: finalize core product decisions"
git status
git push origin main
```

Artisan / npm / SSH / migrations: **не запускались**.

## Commit SHA

SHA этого docs-коммита на `main` — `git log -1 --format=%H`.

## Conclusion

Документация фиксирует обязательные правила текущей версии: один `Class`, отдельные activity groups, один actor type на User, interim `customers`, daily teacher check-in, только итоговые результаты табеля, PDF от Administrator, Productions как поздний future. Core Data Model явно стоит перед стабильным API. Код и production не менялись.
