# Cursor Work Report

## Task

Документационная синхронизация AUB на **2026-09-07**: привести `docs/` к фактическому коду, миграциям, маршрутам, аудиту и архитектурному решению Tech Lead (два репозитория, ядро + интерфейсы, API Foundation). Новый функционал не разрабатывался. PHP/JS/миграции/маршруты/конфиг/`.env`/`vendor`/production не менялись. `WEEKLY_SCHEDULE_SERVICE.md` не трогался — фактических расхождений нет.

## Repository state

- **branch:** `main`
- **HEAD до работы:** `7cb15377982802bc6a4c16c0022d385885fcac2f` (`docs: add initial backend technical audit`)
- **working tree до работы:** clean, совпадает с `origin/main`
- **working tree после правок:** только файлы в `docs/` (см. Files changed)
- Production `/var/www/aub` в этой задаче **не изменялся** (docs-only)

## Environment

Без повторного запуска artisan: использованы факты аудита 2026-09-07 (Laravel 13.18.1, PHP 8.3.6, 84 routes, 37 migrations Ran, batches 1–29). Flutter ids проверены в `AUB_app`: package `aub`, `applicationId` / `PRODUCT_BUNDLE_IDENTIFIER` `com.owlsolutions.aub`.

## Current implemented modules

Без изменений кода. Сводка совпадает с аудитом: web CRM ядро (роли, students-on-customers, teachers, courses, lessons in Settings, weekly schedule + hybrid AI) готово частично; API и Flutter-функции отсутствуют.

## Current database/domain model

Без изменений схемы. Документы теперь явно фиксируют: unique `customer_id` на зачислении; нет `Student`/`Parent`; нет `teachers.user_id`; файлы студентов на public disk.

## Current API state

По-прежнему **нет** `routes/api.php`, Sanctum/Passport/JWT, API Resources. Flutter-репозиторий существует как шаблон. Это теперь отражено во всех ключевых docs.

## Authentication and access control

Документы разделяют implemented (session, RBAC, can_write/can_delete, CRUD logs, encrypted AI keys) и gaps (field-level, scoped teacher access, view audit, consent, API auth, private storage).

## Existing tests

Не запускались (docs-only; PHP локально по-прежнему не в PATH).

## Documentation audit

RU и EN для обязательного списка приведены к одной структуре и смыслу. Индекс `docs/README.md` обновлён (репозитории, дата, ссылка на Cursor report).

## Documentation vs code discrepancies

Основные исправленные противоречия:

| Было в docs | Стало |
|-------------|--------|
| 78 routes | 84 (production `route:list`) |
| 29 migrations / 31 ran | 37 файлов, все Ran, batch 1–29 |
| `Lessons/Index.jsx` + пункт меню lessons | Каталог в Settings → Academy; `/lessons` redirect; extra-меню без lessons |
| Файлы `storage/.../customers/` | `students/{id}/documents` на public disk |
| Dashboard как обычный экран | Placeholder |
| Mobile API «фаза 5 / не начинать» | Репозиторий Flutter есть; API нет; параллельная разработка разрешена |
| «Future mobile API» внутри монолита как единственная картина | Два репо: ядро `AUB_admin` + клиент `AUB_app` |
| RBAC/audit «planned» в privacy | RBAC и CRUD log implemented; неполный access audit — gap |
| Teacher/Course «planned» (RU DATA_MODEL) | Implemented в секции C |
| One group per discipline + per student | Unique `customer_id` supersedes discipline unique |
| Production как будто git-проект | Явно: **не** git repository; GitHub — истина для Tech Lead |
| «Не начинать mobile до стабилизации web» | Снято |

## Удалённые архитектурные положения

- AUB как «админка kit + потом приложения»
- Запрет начинать Flutter до «готового web»
- Sanctum как уже выбранный механизм (теперь только кандидат)
- Future-only mobile без упоминания существующего `Owiiiii1/AUB_app`
- Описание kit CRM (orders/services/staff/calendar) как активных модулей
- Production `git pull` как текущий workflow
- `Lessons/Index.jsx` как экран каталога
- Field-level ACL и «учитель видит только своих» как будто уже enforced

## Связь AUB_admin ↔ AUB_app (как описано сейчас)

`AUB_admin` — центральное ядро (CRM, БД, логика, web admin/workplaces, будущий API).  
`AUB_app` — отдельный Flutter-клиент (`aub` / `com.owlsolutions.aub`) для режимов студент / родитель / преподаватель.  
Связь **только HTTPS API**. API ещё нет. Следующий крупный технический этап: **API Foundation**. Flutter можно писать параллельно; функции ждут контракта.

## Open questions (оставлены открытыми)

- Срок миграции `customers` → `students`
- Окончательная матрица field-level доступа
- Логин преподавателя: web / Flutter / оба
- Утверждение Sanctum vs другой token stack
- Git-deploy workflow GitHub → `/var/www/aub`
- Итальянские фискальные правила счетов
- Объём фазы 6 (костюмы/события)
- DPIA: какие поля расписания уходят в LLM

## Technical debt / suspicious areas

Только зафиксированы в docs, не чинились: public disk документов детей; `always_allowed_route_patterns`; неиспользуемый `admin_only` enforcement; leftover kit pages; отсутствие доменных тестов.

## Blockers

Нет блокеров для этой docs-задачи. Production не трогали по ограничению задачи.

## Commands executed

| Command | Result |
|---------|--------|
| `git status` / `git log -1` (до) | **PASS** — clean, `7cb1537` |
| Grep Flutter `applicationId` / bundle id | **PASS** — `com.owlsolutions.aub` |
| `php artisan *` | **NOT RUN** — docs-only + нет локального PHP |
| `npm run build` | **NOT RUN** |
| Production SSH / deploy | **NOT RUN** — задача запрещает менять production |
| `git diff` (перед commit) | только `docs/` |

## Files changed

**Modified**

- `docs/README.md`
- `docs/en/README_PROJECT_OVERVIEW.md`, `docs/ru/README_PROJECT_OVERVIEW.md`
- `docs/en/ARCHITECTURE.md`, `docs/ru/ARCHITECTURE.md`
- `docs/en/CURRENT_STATE.md`, `docs/ru/CURRENT_STATE.md`
- `docs/en/DATA_MODEL_DRAFT.md`, `docs/ru/DATA_MODEL_DRAFT.md`
- `docs/en/DEVELOPMENT_RULES.md`, `docs/ru/DEVELOPMENT_RULES.md`
- `docs/en/MODULE_ROADMAP.md`, `docs/ru/MODULE_ROADMAP.md`
- `docs/en/MVP_SCOPE.md`, `docs/ru/MVP_SCOPE.md`
- `docs/en/NEXT_STEPS.md`, `docs/ru/NEXT_STEPS.md`
- `docs/en/SERVER_DEPLOYMENT.md`, `docs/ru/SERVER_DEPLOYMENT.md`
- `docs/en/USER_ROLES_AND_ACCESS.md`, `docs/ru/USER_ROLES_AND_ACCESS.md`
- `docs/en/PRIVACY_AND_DATA_PROTECTION.md`, `docs/ru/PRIVACY_AND_DATA_PROTECTION.md`
- `docs/Development/Cursor_Work_Report.md` (полная перезапись)

**Unchanged**

- `docs/en/WEEKLY_SCHEDULE_SERVICE.md`, `docs/ru/WEEKLY_SCHEDULE_SERVICE.md`
- весь прикладной код

## Conclusion

Документация ядра приведена к состоянию 2026-09-07: AUB описан как централизованная система с ядром `AUB_admin` и клиентом `AUB_app`; API честно отсутствует; известные ошибки аудита исправлены; RU/EN синхронизированы по смыслу. Следующий логичный этап реализации — **API Foundation**, отдельной задачей.

## Git

- Commit message: `docs: synchronize project architecture and current state`
- Push: `main` (после успешного commit)
