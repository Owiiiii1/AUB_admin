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

Production `/var/www/aub` **не** git-репозиторий (2026-09-07). Не выдумывать git-pull деплой, пока Tech Lead его не задаст.

Во Flutter **нельзя** класть Bitrix/webhook, учётные данные БД или `APP_KEY`. Только API.

## Основные принципы

1. Не реализовывать недокументированные бизнес-модули без обновления docs.
2. Не класть бизнес-логику AUB в `custom-admin-kit`.
3. Модули AUB живут в `AUB_admin` (или во `AUB_app` для Flutter UI), никогда в `vendor/`.
4. Не дублировать уже установленное generic CRM/admin — расширять.
5. AUB — система **ядро + интерфейсы**, не «админ-панель».
6. Mobile можно вести параллельно; **функции** требуют API-контракта.

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
| Студенты | `CustomersController` / `customers` (interim) |
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
- `.env` не коммитится
- Честно описывать security: [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md)

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

Деплой на production сейчас — **копирование файлов**, не `git pull`.

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
7. Отвечает пользователю **только**: `готово`

Tech Lead затем проверяет report + GitHub diff.

В docs-only задаче не менять PHP/JS/миграции/маршруты/конфиг/`.env`/`vendor`.

## Кастомизации хоста — сохранять

- Вход на `/`
- Маршруты kit CRM сняты
- Консолидация settings, включая каталог уроков
- Локаль по умолчанию `it`
- Студенты на расширенном `customers`, пока нет отдельной задачи миграции

## Приватность при разработке

- Проектировать с RBAC с самого начала
- Field-level и scoped access **ещё не реализованы** — не считать их готовыми
- Файлы детей сейчас на диске **public** — известный разрыв

## Стиль кода

- Небольшие проверенные шаги
- Следовать существующим соглашениям
- Минимизировать область
- Перед удалением файлов вне задачи — спрашивать
