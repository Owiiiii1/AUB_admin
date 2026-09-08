# AUB — Правила разработки

Правила для Cursor и всей разработки AUB.

## Репозитории и роли

| Роль | Кто |
|------|-----|
| Project Manager / tester | Пользователь |
| Tech Lead | ChatGPT (для Tech Lead источник истины — GitHub) |
| Programmer | Cursor |

| Репо | GitHub | Ответственность |
|------|--------|-----------------|
| AUB_admin | `Owiiiii1/AUB_admin` | Ядро: CRM, БД, логика, web-workplaces, API |
| AUB_app | `Owiiiii1/AUB_app` | Только Flutter; клиент HTTPS API |

Production `/var/www/aub` **не** git-репозиторий. Деплой — **копирование файлов** на `deploy@178.156.234.23:/var/www/aub` сразу после push. Не `git pull` на сервере.

Во Flutter **нельзя** класть Bitrix/webhook, учётные данные БД или `APP_KEY`. Только API.

## Основные принципы

1. Не реализовывать недокументированные бизнес-модули без обновления docs.
2. Не класть бизнес-логику AUB в `custom-admin-kit`.
3. Модули AUB живут в `AUB_admin` (или во `AUB_app` для Flutter UI), никогда в `vendor/`.
4. Не дублировать уже установленное generic CRM/admin — расширять.
5. AUB — система **ядро + интерфейсы**, не «админ-панель».
6. Mobile можно вести параллельно; **функции** требуют API-контракта. Auth/`/me` контракт: [API.md](API.md). Следующий этап — Flutter Authentication Foundation.
7. Любое изменение `AUB_admin` сразу выкладывается на production. Без исключений.

## Перед добавлением модуля

- [ ] Прочитать [CURRENT_STATE.md](CURRENT_STATE.md), [MVP_SCOPE.md](MVP_SCOPE.md) и [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md)
- [ ] **Не** выдумывать ответы на пункты OPEN / PRELIMINARY
- [ ] `php artisan route:list` (или аналог в `AUB_app`)
- [ ] Просмотреть models/controllers/pages
- [ ] Обновить docs **en + ru** в той же задаче
- [ ] Полностью перезаписать `docs/Development/Cursor_Work_Report.md` (AUB_admin)

## Generic kit — не пересобирать

| Функция | Где / примечание |
|---------|------------------|
| Auth (session login/logout) | `AuthenticatedSessionController`, `Auth/Login.jsx` |
| Dashboard | `Dashboard.jsx` — **заглушка** |
| Пользователи | `Settings\UserController` |
| Профиль | `ProfileController` |
| Студенты | `StudentsController` / `students` (URL `/customers`) |
| Orders / services / staff / calendar | Legacy без маршрутов |
| AI / app settings | вкладки `/settings` |
| Каталог уроков | Настройки → Академия (`LessonsTab.jsx`); `/lessons` редирект |
| Расписание | `/schedule-service` |
| Журнал | `/statistics/logs` |
| Health | `/owl-admin/health` |

## Границы пакетов

```
vendor/owlsolutions/custom-admin-kit/   ← НЕ РЕДАКТИРОВАТЬ
AUB_admin app/, resources/, database/, docs/
AUB_app lib/                            ← только Flutter
```

## Секреты

- Никогда не печатать `DB_PASSWORD`, `APP_KEY`, API keys, tokens
- `.env` и `.env.testing` не коммитятся (см. `.env.testing.example`)
- Честно описывать security: [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md)

## Автотесты (только MySQL)

Имя production DB: **`aub`**. Имя test DB: **`aub_test`**. Тесты никогда не должны использовать `aub`.

- **Не** использовать SQLite / `:memory:`. PHPUnit — MySQL.
- `phpunit.xml` принудительно задаёт `APP_ENV=testing`, `DB_CONNECTION=mysql`, `DB_DATABASE=aub_test` (без credentials).
- Секреты на сервере/локально — в `.env.testing` (не в git). Шаблон: `.env.testing.example`.
- Жёсткий предохранитель: `App\Testing\TestDatabaseGuard` — если `testing` и connection/database не MySQL `aub_test`, abort с `Refusing to run tests against non-test database.` до миграций. Подключён в `AppServiceProvider` и `tests/TestCase`.
- Всегда `php artisan optimize:clear` **перед** `php artisan test`. Не запускать suite при активном `config:cache`: кэш игнорирует phpunit / `.env.testing` и раньше мог указать на production.
- Безопасная проверка test-схемы: `php artisan migrate:status --env=testing` (берёт `.env.testing`; флаг Laravel `--env=testing` загружает этот файл).

## После изменений backend/frontend (AUB_admin)

```bash
php artisan migrate          # только если есть новые миграции
npm run build                # после JS/CSS/Inertia
php artisan optimize:clear
php artisan view:cache       # production
php artisan config:cache     # production
php artisan route:cache      # production
php artisan storage:link     # upload студентов/преподавателей (сейчас public disk)
```

Деплой на production — **копирование файлов сразу после push**, не `git pull`. Путь: `deploy@178.156.234.23:/var/www/aub`.

## Документация

- Всегда обновлять `docs/en/` и `docs/ru/` вместе
- Русская версия — полный перевод
- После каждой задачи Cursor перезаписывать `docs/Development/Cursor_Work_Report.md`
- Словарь статусов: **DECIDED** / **PRELIMINARY** / **OPEN**. Не выдавать предположения за решения. См. [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Рабочий процесс Cursor (обязательно)

После каждой задачи Cursor:

1. Вносит изменения
2. Запускает нужные проверки (без destructive-команд)
3. Обновляет соответствующую документацию (en + ru)
4. **Полностью перезаписывает** `docs/Development/Cursor_Work_Report.md`
5. Commit
6. Push в `main`
7. **Сразу выложить изменённые файлы AUB_admin на production** `deploy@178.156.234.23:/var/www/aub` (без исключений, включая docs)
8. Отвечает пользователю **только**: `готово`

Tech Lead затем проверяет report + GitHub diff.

Задача по `AUB_admin` **не закончена**, пока файлы не на сервере. Не заливать `.env`, `vendor`, `node_modules`, storage runtime, Flutter. В docs-only задаче не менять PHP/JS/миграции/маршруты/конфиг/`.env`/`vendor`, но docs на production копировать обязательно.

## Кастомизации хоста — сохранять

- Вход на `/`
- Маршруты kit CRM сняты
- Консолидация settings, включая каталог уроков
- Локаль по умолчанию `it`
- Студенты на расширенном `customers`, пока нет отдельной задачи Core Data Model refactor (направление `students` / `parents` DECIDED)

## Приватность при разработке

- Проектировать с RBAC с самого начала
- Field-level и scoped access **ещё не реализованы** — не считать их готовыми
- Файлы детей сейчас на диске **public** — известный разрыв

## Стиль кода

- Небольшие проверенные шаги
- Следовать существующим соглашениям
- Минимизировать область
- Перед удалением файлов вне задачи — спрашивать
