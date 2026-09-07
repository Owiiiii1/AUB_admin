# Cursor Work Report

## Task

DOCUMENTATION-ONLY. Зафиксировать продуктовые и архитектурные решения после предыдущей синхронизации docs; contradiction audit; не выдумывать ответы на OPEN. Функциональный код, миграции, DB, routes, config, vendor и production **не менялись**.

Commit message: `docs: expand roadmap and product open questions`

## Repository state

- **branch:** `main`
- **HEAD до работы:** `78f71b2e8734b7df77dc4a09f3f651286385211c` (`docs: synchronize project architecture and current state`)
- **working tree до работы:** clean vs `origin/main`
- **после правок:** только `docs/` (см. Files changed)
- Production `/var/www/aub` **не изменялся**
- `AUB_app` **не изменялся** (продуктовые docs живут в `AUB_admin`)

## Files changed

### Created

- `docs/en/OPEN_QUESTIONS.md`
- `docs/ru/OPEN_QUESTIONS.md`

### Modified

- `docs/README.md` (index: OPEN_QUESTIONS)
- `docs/en/` + `docs/ru/`: `ARCHITECTURE.md`, `CURRENT_STATE.md`, `DATA_MODEL_DRAFT.md`, `DEVELOPMENT_RULES.md`, `MODULE_ROADMAP.md`, `MVP_SCOPE.md`, `NEXT_STEPS.md`, `PRIVACY_AND_DATA_PROTECTION.md`, `README_PROJECT_OVERVIEW.md`, `USER_ROLES_AND_ACCESS.md`, `WEEKLY_SCHEDULE_SERVICE.md`
- `docs/Development/Cursor_Work_Report.md` (полностью перезаписан)

### Not changed (checked, no remaining “Phase 6 = shows” as product model)

- `docs/en/SERVER_DEPLOYMENT.md` / `docs/ru/SERVER_DEPLOYMENT.md` — deploy facts still accurate
- `docs/ref/*` — UI design sketches, not product roadmap

### Deleted

- none

Non-docs files: **none**.

## Checks

- `git diff --name-only` — только `docs/`
- Локальный PHP/artisan не запускался (docs-only; PHP не в PATH)
- Production SSH не выполнялся
- RU/EN пары OPEN_QUESTIONS, MODULE_ROADMAP, NEXT_STEPS, MVP, DATA_MODEL, ARCHITECTURE, USER_ROLES, PRIVACY, CURRENT_STATE, OVERVIEW, WEEKLY_SCHEDULE, DEVELOPMENT_RULES синхронизированы по смыслу

## Decided (already approved — left as DECIDED)

- `AUB_admin` = central CRM / backend core
- `AUB_app` = Flutter client
- взаимодействие только через HTTPS API
- GitHub repos separate (`Owiiiii1/AUB_admin`, `Owiiiii1/AUB_app`)
- Flutter и backend могут развиваться параллельно; API contract соединяет их
- Parent / Student **не** становятся ролями административного web-RBAC только потому, что им нужен login (identity ≠ administrative RBAC)

## Preliminary decisions (not DECIDED)

1. **Teacher Check-in / Staff Presence:** кнопка «Пришёл» → разовая геолокация (не background tracking) → lat/lng/accuracy/timestamp → backend geofence. Концепт площадки: lat, lng, allowed radius; несколько зданий. Статусы-набросок: `on_time`, `late`, `manual`, `rejected`. Требования: audit, ручная правка админом, GPS accuracy, multi-site. QR = fallback, не основной вариант.
2. **Productions:** отдельная доменная подсистема, не «optional Phase 6 event». `RehearsalGroup` ≠ `CourseGroup`. Ребёнок может быть в обычных CourseGroup **и** rehearsal groups **и** нескольких productions (продуктовый принцип; см. конфликт с unique ниже).
3. **Rehearsal schedule:** обязан попадать в единый календарь ребёнка и в conflict detection с обычными занятиями.

## Open questions

Полный каталог: `docs/en/OPEN_QUESTIONS.md` / `docs/ru/OPEN_QUESTIONS.md`.

Категории: Core Data Model; Enrollment; Identity & Accounts; Roles & Access; Student Attendance; Teacher Check-in; Grades / Report Cards; Scheduling; Productions / Shows; Documents; Communications; Payments; Privacy / Consent; Flutter / Distribution; Infrastructure / Deployment.

**Не отвечены в этой задаче** (намеренно): привязка check-in к смене vs session; radius/accuracy/anti-spoofing; система оценок и учебные периоды; calendar Variant A vs B; Flutter one-app vs flavors; identity graph; платежи; consent UX; git-deploy/staging.

## Remaining contradictions and open product questions

Это **не** копия OPEN_QUESTIONS. Ниже — конфликт docs↔docs, docs↔code, сомнительные assumptions и места, где архитектуру нельзя продолжать без PM / Tech Lead / академии.

### Docs vs code

| Topic | Code fact | Docs after this task |
|-------|-----------|----------------------|
| Enrollment uniqueness | `course_group_customer` unique on `customer_id` only | Documented as **HIGH PRIORITY OPEN**, not as academy rule |
| Teacher login | `teachers` has **no** `user_id` | Teacher Flutter check-in / workplace cannot bind to a login until identity is designed |
| Student / Parent | No models; father/mother columns on `customers` | Identity OPEN; not treated as web RBAC roles |
| Geofence | `academy_buildings` / `academy_rooms` have **no** lat/lng/radius | Conceptual only for check-in |
| Calendar | Only `scheduled_lessons` | Future rehearsals/performances/exams **OPEN** architecture; table not changed |
| `/events` | Placeholder `ComingSoon` | Product Productions is a domain subsystem — **UI still a placeholder** (honest split) |
| Children’s files | **public** disk `students/{id}/documents` | Security Foundation requires private storage before wide mobile |
| API | No `routes/api.php`, no tokens | Flutter features still blocked on API Foundation |
| Access width | `always_allowed_route_patterns` grants courses/schedule/placeholders to any user **with a role** | Still true; intended field ACL / scoped teachers not in code |
| Activity log | CRUD + login/logout, **not** view/access audit | Security Foundation still unmet |
| Kit leftovers | `orders` / `services` / `staff` / `calendar` unrouted | Must not be reused as academy payments/events |

### Docs vs docs (resolved in this task)

- «Events / Shows = optional phase 6» — снято как продуктовая модель; исторические Phase 0–6 помечены historical.
- «Same Flutter app / modes planned» — больше не DECIDED; distribution **OPEN**.
- «One group per student» как бизнес-правило — исправлено: это constraint кода, вопрос академии OPEN.
- Student Attendance смешивался с присутствием преподавателя — разделены домены.

### Remaining tension (cannot pretend resolved)

1. **HIGH PRIORITY — one student = one CourseGroup.** PRELIMINARY Productions говорит, что ребёнок может быть в нескольких обычных группах **и** rehearsal groups. Текущий unique `customer_id` **запрещает даже две CourseGroup**. Если академия разрешает несколько дисциплин, схема неверна. Если запрещает — формулировку Productions надо будет сузить. **Миграцию не трогать**, пока нет ответа. Без ответа нельзя проектировать enrollment, календарь конфликтов и rehearsal membership.
2. **Identity vs RBAC vs Teacher.** Check-in в Flutter предполагает аутентифицированного преподавателя. `Teacher` не связан с `User`. Нельзя проектировать API `/check-in` как «web role Teacher» и нельзя добавлять Parent/Student в `roles`. Нужна actor/identity модель (OPEN), включая: несколько профилей на User; родитель с несколькими детьми; преподаватель-родитель; старший student; кто создаёт account.
3. **Calendar Variant A vs B.** Требование «репетиции в едином календаре + conflicts» PRELIMINARY, но Tech Lead **не** выбрал сущность. Продолжать проектирование productions schema поверх `scheduled_lessons` или новой `ScheduledSession` без выбора — риск переделки. **`scheduled_lessons` не менять.**
4. **Check-in bind.** Geofence-механизм PRELIMINARY, но late/on_time бессмысленны, пока не ясно: смена/рабочий день vs конкретный session. Нельзя финализировать статусы и FK.
5. **Attendance semantics.** Student Attendance = ребёнок на session (урок **или** репетиция). Teacher Presence ≠ это. Пока нет модели session, attendance API проектировать рано. Rehearsal attendance — тот же домен Student Attendance, другой тип session.
6. **Grades.** Крупный модуль без системы оценивания, периодов, права коррекции, PDF/подписи, видимости student vs parent. Не проектировать финальную БД.
7. **Payments scope.** Kit `orders` существует и не academy billing. Связь tuition / production fees / tickets OPEN. Нельзя обещать accounting MVP.
8. **Documents privacy.** Public disk vs обязательный private storage до широкого mobile — разрыв; Security Foundation поднят в roadmap, не реализован.
9. **Mobile roles / distribution.** Один Store-app с modes vs flavors OPEN. Нельзя фиксировать bundle id strategy и App Store listings.
10. **Course / group terminology.** В коде: `courses`, `course_groups`, `lessons`, `scheduled_lessons`. В продукте ещё `RehearsalGroup` ≠ `CourseGroup`. Риск путаницы «группа» в UI и в API. Нужен glossary после ответа по multi-enrollment.
11. **Weekday board vs shows.** Weekly schedule UI — только пн–пт. Performances часто бывают в выходные. Не зафиксировано, расширяется ли доска или performances живут отдельно (зависит от calendar A/B).
12. **`always_allowed` vs Security Foundation.** Любая роль открывает schedule/courses. Scoped teacher access нельзя считать «почти готовым».
13. **Dashboard / logs.** `/dashboard` placeholder; `/statistics/logs` ≠ reporting. Roadmap item 17 не начат.

### Assumptions that look doubtful (not turned into answers)

- Unique one-group-per-student могло быть удобством UI, а не правилом академии.
- «Teacher» в web RBAC и «Teacher» directory — разные вещи; intent-матрица в USER_ROLES выглядит как продукт, но это **intent**, не код.
- Sanctum как token stack — **кандидат**, не решение.
- Mon–Fri schedule sufficient for academy — сомнительно при productions.
- Costume Service «отдельный сервис» может оказаться tightly coupled с casting; оставлено как возможность, не решение.
- QR «только fallback» — preliminary; anti-spoofing может вернуть QR/Wi-Fi/attestation.

### Do not continue architecture without PM / Tech Lead / academy

- Multi CourseGroup vs unique `customer_id`
- Identity graph (User ↔ profiles)
- Calendar Variant A vs B
- Check-in: day/shift vs session; radius; accuracy; anti-spoofing
- Grading system and academic periods
- Flutter distribution A vs B
- Productions priority vs other modules (PM after discovery)
- Whether Parent/Student ever get **any** web UI (channel OPEN; RBAC-admin **DECIDED no**)

## Current implemented modules / API / auth

Без изменений кода. Web CRM ядро как в аудите 2026-09-07. API отсутствует. Flutter — шаблон.

## Documentation vs code discrepancies

Сознательно оставлены как **факты + OPEN**, не «исправлены кодом»: unique enrollment; public disk; no teacher user_id; no API; placeholder `/events`; always_allowed routes.

## Git diff

До commit: `23 files changed` в `docs/` plus 2 untracked OPEN_QUESTIONS plus this report.

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
git commit -m "docs: expand roadmap and product open questions"
git status
git push origin main
```

Artisan / npm / SSH / migrations: **не запускались**.

## Commit SHA

`83d2f0d08eedf2a5390a0acae815a075e350d123` — `docs: expand roadmap and product open questions` on `main`. If this file was amended after the hash was written, trust `git rev-parse HEAD`.

## Conclusion

Документация больше не описывает Productions как optional Phase 6, не смешивает Student Attendance с Teacher Presence, не выдаёт unique enrollment и Flutter modes за утверждённые правила. Security Foundation поднят в обязательный foundation до широкого mobile rollout. Открытые вопросы собраны в OPEN_QUESTIONS (RU/EN) со статусами OPEN / PRELIMINARY / DECIDED. Код и production не менялись. Архитектуру enrollments, календаря, identity, check-in и оценок нельзя финализировать без ответов PM / Tech Lead / академии.
