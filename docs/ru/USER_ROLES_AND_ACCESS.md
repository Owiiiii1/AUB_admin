# AUB — Роли пользователей и доступ

Контроль доступа — **требование конфиденциальности**. Здесь разделены **реализованное** поведение и **задуманные** продуктовые правила.

## Термины

| Термин | Смысл |
|--------|-------|
| **Role** | Web-роль персонала (таблица `roles`) |
| **Workplace** | Ограниченный набор web-экранов |
| **Admin / superadmin interface** | Полная web-CRM при `is_admin` |
| **Канал доступа** | Как человек входит в ядро: web workplace, админка или Flutter |

Parent и Student — **доменные сущности** и каналы Flutter. Они **не** роли админки. Identity Layer **implemented**. Mobile API `/api/v1` **implemented** (Sanctum); Flutter-клиент ещё не подключён.

**DECIDED:** Parent/Student не должны становиться RBAC-ролями админки только потому, что им нужен login. Authentication account type ≠ административный web RBAC. Administrative staff продолжает существующий web RBAC.

**DECIDED (MVP identity):** один User = один основной actor type (`student` / `parent` / `teacher`). Две роли одного человека = два аккаунта. Multi-profile identity в текущую версию не закладывать. Invitation / activation — OPEN ([OPEN_QUESTIONS.md](OPEN_QUESTIONS.md)).

Управление костюмами — будущий **сервис**, не роль. Упаковка Flutter Store (одно приложение с режимами vs несколько apps/flavors) — **OPEN**.

## Реализовано (web)

| Возможность | Статус |
|-------------|--------|
| `roles` / `role_menu_items` | Да |
| `users.account_type` / `users.is_active` | Да — identity; не web-права |
| `users.role_id` | Да |
| `users.can_write` / `can_delete` | Да — middleware `can.write` / `can.delete` |
| UI ролей в Settings | Да |
| Выбор роли у пользователя | Да |
| Динамический `adminMenu` | Да |
| `role.assigned` / `role.access` / `administrator` | Да |
| Редирект после входа | Admin → `/dashboard` (заглушка); остальные → первый пункт меню или `/workplace` |
| Lockout последнего админа | Да (`AdministratorLockoutGuard`) |
| Session authentication | Да — inactive / parent / student actor accounts не входят в web admin |
| Activity CRUD logging | Да (create/update/delete + login/logout) |

### Ключи меню в `role_menu_items`

`dashboard`, `students` (`customers.*`), `teachers`, `settings`, `statistics`.

### Всегда разрешено любому аутентифицированному пользователю **с ролью**

Из `config/aub-menu.php`: `profile.*`, `password.update`, `logout`, `workplace`, `courses-groups.*`, `lessons.*`, `placeholder.*`, `weekly-schedule.*`.

`AdminLayout` всегда показывает курсы/группы, расписание и заглушки. **Lessons нет в extra-меню** (каталог — Настройки → Академия).

Это **шире**, чем права меню роли. Нельзя описывать как факт «non-admin не откроет расписание» — код разрешает.

`settings.admin_only` — метаданные; `MenuRegistry::isAdminOnlyMenuKey()` не используется для enforcement.

### Флаги

| Поле | Middleware | Эффект |
|------|------------|--------|
| `can_write` | `can.write` | POST/PATCH |
| `can_delete` | `can.delete` | DELETE (удаление студента ещё требует текущий пароль) |

Смена языка (`settings.language.update`) **не** за `can.write`.

## Планируемые продуктовые роли (не полностью enforced)

Это **намерение**. Код пока не скрывает контакты родителей от преподавателей и не ограничивает преподавателя своими группами.

| Роль / канал | Намерение |
|--------------|-----------|
| Administrator | Полная web-админка |
| Admin | Возможно урезанный админ — не подтверждено |
| Secretariat | Студенты, зачисления, расписание, позже платежи; без системных настроек |
| Teacher | Свои группы, расписание, **посещаемость студентов**, позже **свой check-in**; без платежей/контактов родителей без разрешения |
| Parent | Канал Flutter: только свои дети — **не** web-роль админки |
| Student | Канал Flutter: только себя — **не** web-роль админки |

## Админка vs workplaces vs Flutter

```
Гость → /  (web login)

Administrator (is_admin)
  → /dashboard + меню реестра + extra always-allowed

Non-admin персонал
  → /workplace или первый route из role_menu_items
  → Extra-пункты AdminLayout всё равно показываются (курсы, расписание, заглушки)

Parent / Student / Teacher (mobile)
  → AUB_app через HTTPS API `/api/v1`  [клиент ещё не подключён; контракт: API.md]
```

## Предварительная матрица (намерение, не код)

✓ полный, R чтение, W запись, — нет, F будущий Flutter

| Действие | Administrator | Secretariat | Teacher | Parent | Student |
|----------|---------------|-------------|---------|--------|---------|
| Профиль студента | ✓ | ✓ (намерение) | R свои группы (намерение) | R свой ребёнок F | R себя F |
| Контакты родителей | ✓ | ✓ (намерение) | — (намерение) | R себя F | — |
| Платежи | ✓ | ✓ (намерение) | — | R свои F | — |
| Медицина / sensitive | ✓ | R (намерение) | — | — | — |
| Student Attendance (ребёнок на session) | ✓ | ✓ | W свои (намерение) | R свой ребёнок F (OPEN) | — |
| Teacher Check-in / присутствие | ✓ / ручная правка | ✓ (намерение) | W своё daily F | — | — |
| Итоговые результаты / табель | ✓ + PDF в admin panel | ? OPEN (кто закрывает табель) | W `StudentFinalResult` только по назначенным `ClassLesson`; одна общая запись; PDF не генерирует | R F OPEN | R F OPEN |
| Правка расписания | ✓ | ✓ (намерение) | R (намерение) | R F | R F |
| Users / roles / AI | ✓ | — | — | — | — |

**В коде сейчас:** кто открывает `customers.*`, видит все поля студента, включая родителей и срок медсправки. Teacher account можно связать с User; scoped «свои группы» по login ещё не enforced.

## Kit `staff` vs роли AUB

`staff.role` kit — свободный текст. RBAC AUB — `roles` + `users.role_id`. Преподаватели — `teachers`, не `staff`.

## Требует доработки (не реализовано)

- Field-level access (**Security Foundation** до широкого mobile)
- Преподаватель → только назначенные студенты/группы
- Матрица API-авторизации
- Безопасность мобильных токенов
- Access/view audit чувствительных записей
- Сущности согласий (`ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`)
- 2FA для административного персонала
- Identity Layer **implemented**: User ↔ Student / Parent / Teacher; Parent/Student без `role_id` не входят в web CRM. Invitation **OPEN**. Mobile API Foundation **implemented**. **Не** multi-profile на одном User

См. [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md), [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md), [CURRENT_STATE.md](CURRENT_STATE.md).
