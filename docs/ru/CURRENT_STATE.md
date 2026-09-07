# AUB — Текущее состояние

Документ отражает фактическое состояние на **2026-07-20**.

## Версии

| Компонент | Версия |
|-----------|--------|
| Laravel | 13.18.1 |
| PHP | 8.3.6 |
| Node.js | 20.20.0 |
| `owlsolutions/custom-admin-kit` | v0.4.0 |
| `inertiajs/inertia-laravel` | 3.1.1 |
| `tightenco/ziggy` | 2.6.3 |

Production-сборка существует: `public/build/manifest.json`

## Маршруты (78 зарегистрированных)

### Auth

| Method | URI | Name |
|--------|-----|------|
| GET | `/` | `login` |
| POST | `/` | — |
| GET | `/login` | redirect to `/` |
| POST | `/login` | — |
| POST | `/logout` | `logout` |

### Админка (требуется auth + role middleware)

| URI | Name | Примечания |
|-----|------|------------|
| `/dashboard` | `dashboard` | |
| `/customers`, `/customers/create`, `/customers/{id}` | `customers.*` | Список студентов + полноэкранный профиль |
| `/teachers`, `/teachers/create`, `/teachers/{id}` | `teachers.*` | Список преподавателей + профиль |
| `/courses-groups` | `courses-groups.*` | Курсы, группы, привязка студентов/уроков |
| `/lessons` | `lessons.*` | Каталог уроков по дисциплинам |
| `/schedule-service` | `weekly-schedule.*` | Доска недельного расписания (алиасы `/schedules`, `/weekly-schedule`) |
| `/documents` | `placeholder.documents` | Скоро будет |
| `/communication` | `placeholder.communication` | Скоро будет |
| `/events` | `placeholder.events` | Скоро будет |
| `/archive` | `placeholder.archive` | Скоро будет |
| `/costume-service` | `placeholder.costume-service` | Скоро будет |
| `/settings` | `settings.*` | Вкладки: пользователи, роли, приложение, ИИ, академия |
| `/roles` | `roles.*` | Редирект → `/settings?tab=roles` |
| `/ai-settings` | `ai-settings.*` | Редирект → `/settings?tab=ai` |
| `/app-settings` | — | Редирект → `/settings?tab=app` |
| `/statistics/logs` | `statistics.logs` | Просмотр журнала активности |
| `/profile` | `profile.*`, `password.update` | |
| `/workplace` | `workplace` | Landing для non-admin |

Маршруты записи/удаления дополнительно защищены middleware `can.write` / `can.delete`.

### Удалены из активных маршрутов (generic kit)

У этих модулей kit таблицы и контроллеры остались, но **маршруты и пункты меню удалены**:

- `/orders`, `/services`, `/staff`, `/calendar`

### System

| URI | Name |
|-----|------|
| `/owl-admin/health` | `owl-admin.health` |
| `/up` | Laravel health |
| `/storage/{path}` | Публичный доступ к файлам (нужен `php artisan storage:link`) |

---

## Миграции (29 batch-записей выполнено)

| Migration | Назначение |
|-----------|------------|
| Laravel core (users, cache, jobs) | Система |
| Kit CRM (customers, services, staff, orders, order_staff, ai_provider_settings) | Legacy-схема kit — частично заменена |
| `2026_07_06_120000` … `120100` | Роли + seed Administrator |
| `2026_07_06_130000` … `143000` | Очистка меню: переименование students, удаление orders/calendar/staff/services/ai-settings |
| `2026_07_06_140000` | Консолидация settings |
| `2026_07_06_150000` | Таблица `activity_logs` |
| `2026_07_06_210000` | Поля профиля студента в `customers` |
| `2026_07_06_221000` | `gender` в `customers` |
| `2026_07_08_120000` | `courses`, `course_groups`, `course_group_customer` |
| `2026_07_08_140000` … `140100` | `teachers` + seed меню |
| `2026_07_08_150000` | Одна группа курса на дисциплину |
| `2026_07_08_160000` | `users.can_delete` |
| `2026_07_08_170000` | Одна группа курса на студента |
| `2026_07_08_180000` … `182000` | `lessons`, pivot-таблицы, `course_group_lesson` |
| `2026_07_08_181000` | Связи уроков; удаление `duration_minutes` |
| `2026_07_08_190000` | `users.can_write` |
| `2026_07_20_100000` | Таблицы недельного расписания |
| `2026_07_20_153500` | Снятие unique на `course_group_lesson.lesson_id` |
| `2026_07_20_174500` | Несколько преподавателей на группу+урок |
| `2026_07_20_175000` | `lessons.duration_minutes` |
| `2026_07_20_183000` | `course_groups.color` |
| `2026_07_20_203000` | `schedule_weeks.work_starts_at` / `work_ends_at` |
| `2026_07_20_221000` | Таблица `schedule_ai_runs` |
| `2026_07_20_224500` | `courses.study_starts_at` / `study_ends_at` + seed смен |

---

## Таблицы базы данных (используются)

| Таблица | Назначение |
|---------|------------|
| `users` | Auth; `role_id`, `can_write`, `can_delete` |
| `roles`, `role_menu_items` | RBAC |
| `customers` | **Студенты** (расширенный профиль — interim на таблице kit) |
| `teachers` | Преподаватели академии |
| `courses`, `course_groups` | Курсы и группы; у курса — учебное окно `study_starts_at` / `study_ends_at` |
| `course_group_customer` | Студент ↔ группа (pivot) |
| `lessons` | Каталог уроков (`duration_minutes`) |
| `lesson_teacher`, `lesson_course`, `course_group_lesson` | Связи уроков; несколько преподавателей на группу+урок |
| `academy_buildings`, `academy_rooms` | Локации для расписания |
| `schedule_weeks`, `scheduled_lessons` | Недельное расписание (рабочие часы недели) |
| `schedule_ai_runs` | Журнал ИИ-распределений |
| `activity_logs` | Аудит CRUD-действий |
| `ai_provider_settings` | Ключи AI-провайдеров |
| Kit legacy (UI не использует): `services`, `staff`, `orders`, `order_staff` | Таблицы есть; маршруты удалены |

---

## Модели

| Model | Статус |
|-------|--------|
| User | Реализована — role, `can_write`, `can_delete` |
| Role, RoleMenuItem | Реализованы (Фаза 1) |
| Customer | **Расширена как профиль студента** |
| Teacher | Реализована |
| Course, CourseGroup | Реализованы (у Course — учебное окно смены) |
| Lesson | Реализована |
| AcademyBuilding, AcademyRoom | Реализованы |
| ScheduleWeek, ScheduledLesson, ScheduleAiRun | Реализованы |
| ActivityLog | Реализована |
| Service, Staff, Order | Legacy kit — нет активных маршрутов |

Отдельных моделей `Student` и `Parent` пока нет — данные хранятся в `customers` (студент + встроенные поля отца/матери).

---

## Контроллеры

| Controller | Назначение |
|------------|------------|
| `CustomersController` | Список студентов, полноэкранный профиль CRUD, загрузка файлов, удаление с паролем |
| `TeachersController` | Список + профиль преподавателя |
| `CoursesGroupsController` | Курсы, группы, привязка студентов/уроков |
| `LessonsController` | CRUD каталога уроков |
| `WeeklyScheduleController` | Доска недельного расписания |
| `ActivityLogController` | Статистика / журнал активности |
| `RolesController` | CRUD ролей (вкладка settings) |
| `WorkplaceController` | Landing для non-admin |
| `Settings/*` | Пользователи, язык, AI, здания/залы академии |
| `Auth/AuthenticatedSessionController` | Login, logout |
| `ProfileController` | Профиль пользователя |

Контроллеры kit (`OrdersController`, `ServicesController`, `StaffController`, `CalendarController`) существуют, но не подключены к маршрутам.

---

## Inertia/React-страницы

| Page | Path |
|------|------|
| Login (редизайн) | `Auth/Login.jsx` |
| Dashboard | `Dashboard.jsx` |
| Список студентов | `Customers/Index.jsx` |
| Профиль студента (создание/редактирование) | `Customers/Profile.jsx` |
| Список преподавателей | `Teachers/Index.jsx` |
| Профиль преподавателя | `Teachers/Profile.jsx` |
| Курсы и группы | `CoursesGroups/Index.jsx` |
| Уроки | `Lessons/Index.jsx` |
| Недельное расписание | `WeeklySchedule/Index.jsx` |
| Заглушки разделов | `Placeholder/ComingSoon.jsx` |
| Настройки (вкладки) | `Settings/Index.jsx` + `Tabs/*` |
| Статистика / логи | `Statistics/Logs.jsx` |
| Профиль | `Profile/Edit.jsx` |
| Workplace | `Workplace/Index.jsx` |

Layouts: `AdminLayout.jsx`, `AuthLayout.jsx`

---

## Структура меню админки

Динамические пункты из `role_menu_items` (через prop `adminMenu`):

| menu_key | Подпись (IT) | Route |
|----------|--------------|-------|
| dashboard | Home | `dashboard` |
| students | Studenti | `customers.index` |
| teachers | Insegnanti | `teachers.index` |
| settings | Impostazioni | `settings.index` |
| statistics | Statistiche | `statistics.logs` |

Дополнительные пункты в `AdminLayout` (всегда доступны авторизованным):

| Key | Подпись (IT) | Route | Статус |
|-----|--------------|-------|--------|
| coursesAndGroups | Corsi e gruppi | `courses-groups.index` | Реализовано |
| lessons | Lezioni | `lessons.index` | Реализовано |
| scheduleService | Servizio orari | `weekly-schedule.index` | Реализовано |
| documents | Documenti | `placeholder.documents` | Заглушка |
| communication | Comunicazioni | `placeholder.communication` | Заглушка |
| events | Eventi | `placeholder.events` | Заглушка |
| costumeService | Servizio costumi | `placeholder.costume-service` | Заглушка |
| archive | Archivio | `placeholder.archive` | Заглушка (разделитель сверху) |

Конфиг: `config/aub-menu.php`

---

## UI и брендинг (2026-07-06 — 2026-07-20)

| Область | Изменение |
|---------|-----------|
| Страница login | Split-layout, фоновые изображения, лого AUB, заголовки Singo Sans, кнопка `#1A2B44` |
| AuthLayout | Desktop/mobile фоны (`login-chatgpt-reference.png`, `login-mobile-girl.png`) |
| Боковое меню | Фон `#1A2B44`, белое лого AUB, локализованный заголовок панели |
| Переключатель языка | Dropdown с иконкой Globe (login + admin), локали: **it** (по умолчанию), uk, en, ru |
| Основные кнопки | `#1A2B44` / hover `#132033` |
| Карточки виджетов | `.app-widget` фон `#EBF1FF` |
| Таблица студентов | Колонки фото + возраст; кликабельные строки; иконка сообщения (не работает) |
| Профиль студента | Полноэкранная форма; фото на аватаре; модалки родителей; загрузка документов; удаление с паролем |
| Настройки | Вкладки; роли и AI перенесены из отдельных пунктов меню |
| Шрифт | Singo Sans — `public/fonts/singo-sans/singo-sans-regular.ttf`, `.font-singo` в `app.css` |

Референсы: `docs/ref/login/`, `docs/ref/loginMobile/`, `docs/ref/STprofile/`

---

## Локализация

| Локаль | UI | Laravel validation |
|--------|-----|-------------------|
| it | UI админки по умолчанию | `lang/it/validation.php` |
| en | Поддерживается | `lang/en/validation.php` |
| ru | Поддерживается | `lang/ru/validation.php` |
| uk | Поддерживается | `lang/uk/validation.php` |

Страницы студентов используют inline-объекты `translations` по локали.

---

## Статус модуля студентов (interim-архитектура)

**Реализовано поверх таблицы `customers`** — отдельной таблицы `students` пока нет.

### Поля профиля

- Личные: имя/фамилия (обязательны при создании), пол, codice fiscale, дата/место рождения, адрес, email, телефон, заметки
- Родители: блоки отца и матери (имя, телефон, email, заметки) через модалки; legacy `parent_phone`/`parent_email` сохранены
- Курс: `course_aa_2026_27`, `other_courses`, `is_existing_student`, `form_filled_at`
- Документы: срок медсправки, документ родителя, формы регламента, фото (загрузка через аватар в шапке)
- Файлы: `storage/app/public/customers/` — **нужен** `php artisan storage:link`

### Поведение UI

- Список `/customers` — фото, имя, возраст, курс; строка открывает профиль
- Создание `/customers/create`, редактирование `/customers/{id}`
- Подтверждение сохранения; удаление — подтверждение + пароль текущего пользователя

---

## Сводка по фазам

| Фаза | Модуль | Статус |
|------|--------|--------|
| 0 | Основа admin kit | ✅ Завершена |
| 1 | Роли и workplaces | ✅ Завершена (2026-07-06) |
| 2 | Студенты | 🟡 Частично — расширенный `customers`, без отдельной сущности |
| 2 | Родители | 🟡 Частично — только встроенные поля отца/матери |
| 2 | Преподаватели | ✅ Завершено |
| 2 | Курсы / группы | ✅ Завершено |
| 2 | Зачисления | 🟡 Частично — pivot `course_group_customer` |
| 2 | Каталог уроков | ✅ Завершено |
| 3 | Недельное расписание | ✅ Завершено + ИИ-гибрид, окна курсов, сетка 5/30 (2026-07-20) |
| 3 | Загрузка документов студента | 🟡 Частично — в профиле, без отдельного модуля |
| 3 | Посещаемость | ❌ Не начато |
| 3 | Модуль документов (меню) | ❌ Только заглушка |
| 3 | Коммуникации | ❌ Только заглушка |
| 4+ | Платежи, mobile, опциональные сервисы | ❌ Не начато |

---

## Что НЕ реализовано

- Отдельные таблицы/модели `students` / `parents`
- Полный workflow зачислений (статусы, переводы, история)
- Учёт посещаемости
- Платежи / счета / бухгалтерия
- Отдельная страница управления документами
- Коммуникации / сообщения
- События, архив, сервис костюмов (только заглушки в меню)
- Mobile API и приложения для родителей/студентов
- PDF-экспорт расписания
- Полностью «исполняемые» ИИ-рекомендации (кнопка только дописывает промпт и перезапускает create)
- Ограничение видимости полей профиля по ролям

Подробности модуля расписания: [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).

---

## Кастомизации хоста (сохранять)

- Login на `/` вместо `/login`
- `/login` редиректит на `/`
- Generic kit CRM-маршруты удалены из меню и routing
- Settings, roles, AI объединены во вкладках `/settings`
- Итальянский — локаль UI по умолчанию

---

## После изменений frontend/backend

```bash
npm run build
php artisan optimize:clear
php artisan view:cache
# после новых миграций:
php artisan migrate
# для фото студентов:
php artisan storage:link
```

См. [DEVELOPMENT_RULES.md](DEVELOPMENT_RULES.md).
