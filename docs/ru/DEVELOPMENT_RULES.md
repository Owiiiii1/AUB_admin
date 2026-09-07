# AUB — Правила разработки

Правила для Cursor AI и всей будущей разработки проекта AUB.

## Основные принципы

1. **Не реализовывать недокументированные бизнес-модули** без предварительного обновления документации.
2. **Не смешивать бизнес-логику AUB в `custom-admin-kit`.** Пакет — только базовая административная/CRM основа.
3. **Модули AUB должны находиться в проекте AUB** (`/var/www/aub`), а не в `vendor/`.
4. **Не дублировать уже установленную общую CRM/административную функциональность.**
5. **Staff Roles & Role-Based Workplaces — первый AUB-специфичный шаг**, если владелец проекта не изменит это вручную.

## Перед добавлением любого модуля

- [ ] Прочитать [CURRENT_STATE.md](CURRENT_STATE.md) и [MVP_SCOPE.md](MVP_SCOPE.md)
- [ ] Выполнить `php artisan route:list` — проверить существующие маршруты
- [ ] Изучить `app/Models/`, `app/Http/Controllers/`, `resources/js/Pages/` — избегать дублирования
- [ ] Убедиться, что модуль ещё не предоставляется admin kit
- [ ] Обновить документацию (en + ru) до или параллельно с реализацией

## Generic kit — не дублировать

Это **уже установлено и работает**. Не пересоздавать:

| Функция | Расположение |
|---------|----------|
| Auth (login, logout) | `AuthenticatedSessionController`, `Auth/Login.jsx` |
| Dashboard | `Dashboard.jsx`, route `dashboard` |
| User management | `Settings\UserController`, `/settings` |
| Profile | `ProfileController`, `/profile` |
| Generic customers | `CustomersController`, `/customers` — **расширен как модуль Students** |
| Generic orders | Удалён из маршрутов (legacy-таблица осталась) |
| Generic services | Удалён из маршрутов (legacy-таблица осталась) |
| Generic staff directory | Удалён из маршрутов; использовать `TeachersController` |
| Calendar placeholder | Удалён; использовать `/schedule-service` |
| AI Settings | Объединено в `/settings?tab=ai` |
| App settings page | Объединено в `/settings?tab=app` |
| Statistics/logs page | `/statistics/logs` — просмотр журнала активности |
| Health check | `/owl-admin/health` |
| Admin layout + UI components | `AdminLayout.jsx`, `Components/ui/` |
| Doctor/smoke commands | `owl-admin:doctor`, `owl-admin:smoke` |

**Расширять, а не пересоздавать.** Пример: добавить выбор роли в существующую форму пользователя, а не создавать второй модуль управления пользователями.

Если в меню уже есть placeholder (например **Сервис расписаний** на `/schedule-service`), реализуй модуль AUB на этом маршруте — не добавляй дублирующий пункт меню. Документируй в `WEEKLY_SCHEDULE_SERVICE.md`.

## Границы пакетов

```
vendor/owlsolutions/custom-admin-kit/   ← DO NOT EDIT (base foundation)
/var/www/aub/app/                       ← AUB business logic here
/var/www/aub/resources/js/Pages/        ← AUB pages here
/var/www/aub/database/migrations/       ← AUB migrations here
/var/www/aub/docs/                      ← Documentation here
```

Если файлы kit требуют кастомизации, редактировать **опубликованные копии** в проекте AUB, а не stubs в vendor.

## Правила области MVP

- **Не** добавлять модули costume/show/ticket/rental в ядро MVP преждевременно
- Отмечать опциональные сервисы как Фаза 6 в [MODULE_ROADMAP.md](MODULE_ROADMAP.md)
- Контроль доступа (Фаза 1) должен предшествовать доменным модулям академии (Фаза 2+)

## Безопасность и секреты

- **Никогда не раскрывать секреты** в документации, логах, чате или коммитах
- Не выводить: `DB_PASSWORD`, `APP_KEY`, API keys, tokens, credentials
- Хранить секреты только в `.env` (не коммитить)
- Тестовые учётные данные администратора (`admin@admin.com`) только для разработки

## После изменений backend/frontend

Выполнить соответствующие команды:

```bash
php artisan migrate          # after new migrations
npm run build                # after every frontend change (required for UI updates)
php artisan optimize:clear   # clear caches after build/config changes
php artisan view:cache       # production
php artisan config:cache     # production
php artisan route:cache      # production
php artisan storage:link     # required for student/teacher photo uploads
php artisan owl-admin:smoke --preset=admin   # verify install
```

**Правило:** после любого изменения `resources/js/`, `resources/css/` или Inertia-страниц запускать `npm run build` и очищать кеш перед проверкой в браузере.

## Правила документации

- **Всегда обновлять обе** версии: `docs/en/` и `docs/ru/` в одной задаче
- Русская версия должна быть **полным точным переводом**, а не кратким изложением
- Обновлять [CURRENT_STATE.md](CURRENT_STATE.md) после каждого реализованного модуля
- Обновлять [DATA_MODEL_DRAFT.md](DATA_MODEL_DRAFT.md) при добавлении/изменении сущностей
- Обновлять [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md) при изменении прав доступа
- При изменениях, затрагивающих данные детей, обновлять [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md)

## Стиль кода

- Использовать чёткие границы модулей (Model → Controller → Page → Route)
- Предпочитать небольшие проверенные шаги крупным непроверенным изменениям
- Следовать существующим соглашениям кода (Inertia pages, AdminLayout, UI components)
- Минимизировать область изменений — менять только то, что требует задача
- Не переусложнять (без преждевременных абстракций)

## Удаление файлов/папок

- **Перед удалением файлов или папок вне области текущей задачи запрашивать подтверждение**
- Не удалять базы данных без явного запроса
- Не создавать ручные резервные копии без явного запроса

## Git и развёртывание

- Не коммитить `.env` или секреты
- Не делать force-push в main без явного одобрения
- Следовать существующему стилю сообщений коммитов

## Кастомизации хоста, уже применённые

Они отличаются от значений kit по умолчанию — сохранять, если не меняются намеренно:

- Вход на `/` вместо `/login` (`routes/web.php`)
- `/login` перенаправляет на `/` (`routes/owl-admin-auth.php`)
- Брендинг AUB: редизайн login, sidebar `#1A2B44`, карточки `#EBF1FF`, шрифт Singo Sans
- Итальянский (`it`) — локаль UI по умолчанию
- Generic kit CRM-маршруты удалены; студенты на расширенном `customers`
- Settings/roles/AI объединены во вкладках `/settings`

Документировать любые новые кастомизации в [CURRENT_STATE.md](CURRENT_STATE.md).

## Разработка с учётом приватности

- Проектировать все модули с role-based access с самого начала
- Минимизировать поля, показываемые каждой роли
- Логировать доступ к чувствительным данным (будущий audit trail)
- См. [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md)

## Рабочий процесс Cursor

1. Прочитать соответствующую документацию перед кодированием
2. Изучить текущее состояние проекта (routes, models, pages)
3. Реализовать минимально корректное изменение
4. Выполнить migrations/build/smoke
5. Обновить docs (en + ru)
6. Сообщить, что изменилось и что было проверено
