# AUB — Роли пользователей и доступ

## Обзор

Контроль доступа — это **требование конфиденциальности**, а не только удобство. Академия обрабатывает персональные данные и данные детей. Персонал должен видеть только то, что требуется для его работы.

**Статус этого документа:** описывает **реализованную** модель доступа (Фаза 1, 2026-07-06) и запланированные расширения.

## Терминология

| Term | Meaning |
|------|---------|
| **Role** | Роль доступа персонала/пользователя (Administrator, Secretariat, Teacher…) |
| **Workplace** | Набор экранов, специфичный для роли, с ограниченным меню |
| **Full admin panel** | Полная админка со всеми пунктами меню — только для Administrator |
| **Access channel** | Будущий способ доступа к системе (web workplace, mobile app) |

**Не являются ролями:**

- Costume management — будущая опциональная услуга, не пользовательская роль
- Parent/Student — будущие **каналы доступа**, а не центральная админка

## Запланированные роли доступа

### Superadmin / Administrator

- Полный доступ к админ-панели
- Видны все пункты меню
- Может управлять пользователями, ролями, правами меню
- Перенаправление после входа: `/dashboard`
- Как минимум один активный администратор должен всегда существовать

### Admin

- Может быть эквивалентом Administrator или слегка ограниченным админом (уточнить с заказчиком)
- Полный или почти полный доступ к админ-панели
- Может управлять большинством записей академии

### Secretariat

- Доступ только к выбранным экранам workplace/админки
- Типичный доступ: students, parents, enrollments, schedule view, payments
- Нет доступа к: user management, roles, AI settings, system settings
- Перенаправление: `/workplace` или первый разрешённый маршрут меню

### Teacher

- Только экраны рабочего места преподавателя
- Типичный доступ: assigned groups, schedule, attendance marking, student list (limited fields)
- Нет доступа к: payments, parent contacts (unless approved), admin settings
- Будущее: web workplace + mobile app

### Parent

- **Не** полный доступ к админке
- Будущий канал доступа mobile/web
- Типичный доступ: own children's schedule, attendance, payments, documents, messages

### Student

- **Не** полный доступ к админке
- Будущий канал доступа mobile/web
- Типичный доступ: own schedule, attendance history, announcements

## Полная админ-панель vs рабочие места по ролям

```
Guest
  └── /  (login)

Administrator (is_admin=true)
  └── /dashboard + full menu (all CRM + settings + roles)

Non-admin staff (is_admin=false)
  └── /workplace or first allowed route
  └── AdminLayout with filtered menu (from role_menu_items)
  └── Cannot access routes not in their menu set

Parent / Student (future)
  └── Separate mobile/web channel (not admin panel)
```

## Запланированные функции управления ролями

### Экран управления ролями

- Список всех ролей (name, slug, is_admin, is_active, user count)
- Создание/редактирование роли: name, description, is_admin flag
- Назначение пунктов меню для каждой роли (checkboxes or drag-order)
- Нельзя удалять системные роли (`is_system=true`)
- Нельзя отключить последнюю активную роль администратора

### Создание/редактирование пользователя — выбор роли

- Расширить существующую форму пользователя в `/settings` (`Settings\UserController`)
- Обязательное поле: выбор роли
- При сохранении: назначить `users.role_id`
- Существующий `admin@admin.com` → роль Administrator (migration/seed)

### Генерация динамического меню

- `AdminLayout.jsx` читает разрешённые пункты меню из shared Inertia props (на основе роли пользователя)
- Administrator (`is_admin=true`): показать все пункты меню (текущее поведение)
- Non-admin: показать только пункты из `role_menu_items` для роли пользователя
- Скрыть неавторизованные пункты меню; блокировать прямой доступ по URL через middleware

### Логика перенаправлений

| User state | Redirect |
|------------|----------|
| Guest accessing protected route | `/` (login) |
| Administrator after login | `/dashboard` |
| Non-admin after login | `/workplace` or first allowed `route_name` |
| User with no role | Denied — show error, do not grant access |
| Non-admin accessing admin-only route | 403 Forbidden |

### Защита от блокировки

- Не разрешать удаление последней активной роли администратора
- Не разрешать снятие `is_admin` у единственного активного пользователя-администратора
- Не разрешать отключение всех пользователей с ролью администратора
- Системные роли (`is_system=true`) нельзя удалить

## Экран редактирования роли — настройка меню

Администратор выбирает, к каким экранам каждая роль имеет доступ:

| menu_key | Example route | Example label |
|----------|---------------|---------------|
| dashboard | dashboard | Home |
| students | customers.index | Students |
| teachers | teachers.index | Teachers |
| settings | settings.index | Settings |
| statistics | statistics.logs | Statistics |

Дополнительные пункты в `AdminLayout` (не в `role_menu_items`, всегда разрешены): courses-groups, lessons, weekly-schedule, заглушки разделов.

Legacy kit ключи удалены из меню: orders, services, staff, calendar, ai-settings (объединены в settings).

Каждая роль может иметь **разный набор меню**. Роли гибкие — не фиксировать окончательные названия ролей академии слишком рано.

## Примеры конфигураций ролей

### Administrator

- `is_admin`: true
- Menu: all items
- Access: full admin panel

### Secretariat (example)

- `is_admin`: false
- Menu: dashboard, customers (future: students), calendar, settings (language only)
- Access: workplace screens only

### Teacher (example)

- `is_admin`: false
- Menu: calendar, staff (future: attendance, assigned groups)
- Access: teacher workplace only

## Предварительная матрица доступа

Условные обозначения: ✓ = полный доступ, R = только чтение, W = запись, — = нет доступа, F = будущий канал

| Action | Administrator | Secretariat | Teacher | Parent | Student |
|--------|--------------|-------------|---------|--------|---------|
| View student profile | ✓ | ✓ | R (own groups) | R (own child) F | R (self) F |
| View parent contacts | ✓ | ✓ | — | R (self) F | — |
| View payment data | ✓ | ✓ | — | R (own) F | — |
| View health/sensitive notes | ✓ | R | — | — | — |
| Edit attendance | ✓ | ✓ | W (own lessons) | — | — |
| Edit schedule | ✓ | ✓ | R | R F | R F |
| Export data | ✓ | R | — | — | — |
| Manage users | ✓ | — | — | — | — |
| Manage roles | ✓ | — | — | — | — |
| Manage menu access | ✓ | — | — | — | — |
| AI Settings | ✓ | — | — | — | — |
| App/system settings | ✓ | — | — | — | — |

**Note:** Эта матрица предварительная. Окончательные права должны быть согласованы с владельцем академии.

## Соображения конфиденциальности

- Доступ на основе ролей ограничивает раскрытие персональных данных детей
- Преподаватели не должны видеть платёжные данные или контакты родителей, если это явно не разрешено
- Здоровье/чувствительные заметки требуют максимальных ограничений
- Права на экспорт должны быть строго контролируемыми
- Все решения о доступе должны логироваться (будущий audit trail)

## Связь с универсальной таблицей kit `staff`

Таблица `staff` в kit (`app/Models/Staff.php`) — это **CRM-справочник персонала** со свободным текстовым полем `role`. Это **не** запланированная система RBAC.

| Kit `staff.role` | Planned `roles` table |
|------------------|----------------------|
| Free-text field | Structured access role |
| No menu control | Controls menu and route access |
| CRM directory | User authentication authorization |

Оба могут сосуществовать: запись Teacher в таблице `teachers`, связанная с записью `users` с ролью Teacher.

## Права записи/удаления пользователей

Помимо доступа по меню, два флага на `users` контролируют изменяющие и удаляющие операции:

| Field | Middleware | Effect |
|-------|------------|--------|
| `can_write` | `can.write` | Требуется для POST/PATCH (создание/обновление) |
| `can_delete` | `can.delete` | Требуется для DELETE (например, удаление студента с паролем) |

Настраиваются в форме создания/редактирования пользователя (Settings → Users).

## Статус реализации

| Feature | Status |
|---------|--------|
| Roles table | ✅ Реализовано |
| RoleMenuItem table | ✅ Реализовано |
| users.role_id | ✅ Реализовано |
| users.can_write / can_delete | ✅ Реализовано |
| Roles management screen | ✅ Settings → вкладка Roles |
| Role selector in user form | ✅ Реализовано |
| Dynamic menu | ✅ Реализовано |
| Non-admin redirect | ✅ Реализовано |
| Lockout protection | ✅ Реализовано |
| Legacy Spatie tables | Заменены схемой AUB (2026-07-06) |

См. [CURRENT_STATE.md](CURRENT_STATE.md) и [NEXT_STEPS.md](NEXT_STEPS.md).
