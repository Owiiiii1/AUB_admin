# AUB — Открытые вопросы

Продуктовые и архитектурные вопросы, которые **не решены**. Предположения помечены. PRELIMINARY не считать DECIDED.

Статусы: **OPEN** | **PRELIMINARY** | **DECIDED**.

Связанные документы: [MODULE_ROADMAP.md](MODULE_ROADMAP.md), [ARCHITECTURE.md](ARCHITECTURE.md), [DATA_MODEL_DRAFT.md](DATA_MODEL_DRAFT.md).

---

## Уже утверждено (DECIDED)

| Решение | Статус |
|---------|--------|
| `AUB_admin` — центральное CRM/backend-ядро | DECIDED |
| `AUB_app` — Flutter-клиент | DECIDED |
| Клиенты ходят в ядро только по HTTPS API | DECIDED |
| GitHub-репозитории раздельные (`Owiiiii1/AUB_admin`, `Owiiiii1/AUB_app`) | DECIDED |
| Flutter и backend могут развиваться параллельно; их соединяет API-контракт | DECIDED |
| Parent / Student **не** становятся ролями административного web-RBAC только потому, что им нужен login | DECIDED (identity ≠ RBAC) |

---

## Core Data Model

### Unique enrollment: одна CourseGroup на студента

- **Question:** Ребёнок может одновременно состоять в нескольких курсах / дисциплинах / CourseGroup или только в одной?
- **Why it matters:** В `course_group_customer` стоит **unique `customer_id`**. Сейчас в БД студент может быть **только в одной CourseGroup вообще**. Если академия допускает несколько групп сразу, ограничение не соответствует бизнесу.
- **Current assumption:** Продуктового правила нет. Unique — **технический факт кода**, не подтверждённое правило академии.
- **Status:** OPEN — **HIGH PRIORITY**. Миграцию не менять, пока нет ответа PM / академии.

### Отдельные таблицы `students` / `parents` vs `customers`

- **Question:** Когда (и нужно ли) уходить с interim `customers`?
- **Why it matters:** Flutter-идентичности, зачисления, оценки, постановки завязаны на «студента».
- **Current assumption:** `customers` остаётся, пока нет явной задачи миграции.
- **Status:** OPEN

---

## Enrollment

- **Question:** Статусы зачисления, правила перевода, история, даты начала/конца?
- **Why it matters:** Сейчас только pivot без workflow.
- **Current assumption:** Нет.
- **Status:** OPEN

---

## Identity & Accounts

- **Question:** Модель identity. Пример (не выбран): `User` → профиль Teacher / Parent / Student — или другая схема.
- **Why it matters:** `User` есть для **web**-auth; `Teacher` не связан; сущностей Parent/Student нет; Flutter API нет. Authentication identity ≠ административная RBAC-роль.
- **Подвопросы:**
  - Может ли один User иметь несколько actor-профилей?
  - Родитель с несколькими детьми?
  - Преподаватель, который одновременно родитель?
  - Старший student со своим аккаунтом?
  - Кто создаёт аккаунт? Invitation / activation?
- **Current assumption:** Граф identity не выбран. «Режимы» Flutter в старых docs — набросок, не решение.
- **Status:** OPEN

---

## Roles & Access

- **Question:** Итоговая field-level матрица и scoped access преподавателя (только свои группы/дети).
- **Why it matters:** Сейчас роль с `customers.*` видит все поля. `always_allowed_route_patterns` шире меню роли.
- **Current assumption:** Матрица-намерение в [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md) — **не** код.
- **Status:** OPEN

Ниже — **обязательный foundation до широкого mobile rollout** (не реализовано): private storage документов детей; field-level ACL; scoped teacher access; access/view audit чувствительных данных; матрица API-авторизации; mobile token security; **2FA для административного персонала**; consent/privacy records.

---

## Student Attendance

- **Question:** Как отмечается присутствие **ребёнка** на **конкретном занятии / репетиции / session**? Кто ставит? Какие статусы?
- **Why it matters:** Это **не** Teacher Check-in. Смешение доменов даст неверный продукт.
- **Current assumption:** Нет. Модуль не построен.
- **Status:** OPEN

---

## Teacher Check-in / Staff Presence

**Домен:** подтверждение, что преподаватель **физически в академии**. Отдельно от Student Attendance.

### Механизм (PRELIMINARY)

1. Преподаватель открывает Flutter.
2. Нажимает **Пришёл** (сознательное действие).
3. Приложение **один раз** запрашивает геолокацию (не background tracking).
4. Backend получает latitude, longitude, accuracy, timestamp.
5. Backend проверяет **geofence** академии.
6. При успехе создаётся check-in.

Площадки концептуально: latitude, longitude, allowed radius. Несколько зданий.

Предварительные статусы: `on_time`, `late`, `manual`, `rejected`.

Позже спроектировать: audit; ручное подтверждение/исправление администратором; обработка GPS accuracy; несколько площадок.

QR — **возможный fallback/альтернатива**, **не** основной кандидат.

### Открыто (не решено)

| Question | Why it matters | Assumption | Status |
|----------|----------------|------------|--------|
| Check-in к **рабочему дню/смене** или к **конкретному scheduled lesson/session**? | Модель данных, UX, смысл late/on_time | Нет | OPEN |
| Временное окно check-in | Шум слишком ранних/поздних отметок | Нет | OPEN |
| Допустимый geofence radius | Ложные отказы vs читерство | Нет | OPEN |
| Допустимая GPS accuracy | В помещении GPS слабый | Нет | OPEN |
| Anti-spoofing | Поддельная геолокация | Нет | OPEN |
| Доп. сигналы (Wi-Fi / QR / device attestation)? | Безопасность vs трение | QR только fallback (PRELIMINARY) | OPEN, кроме «QR не основной» |

---

## Grades / Report Cards (Academic Progress)

Крупный модуль, **без финальной БД**. Предварительный scope: оценки; комментарии преподавателя; по предметам/дисциплинам; по учебным периодам; промежуточные и итоговые; табель; история изменений; кто выставил/изменил и когда; просмотр студентом и родителем; преподаватель только в разрешённых предметах/группах.

| Question | Why it matters | Status |
|----------|----------------|--------|
| Система оценивания (числа / буквы / уровни / текст) и диапазон | Схема и UI | OPEN — discovery с академией |
| Есть ли экзамены? | Календарь + оценки | OPEN |
| Периоды: семестр / триместр / квадриместр / иное? | Табель | OPEN |
| Разные системы у разных курсов? | Гибкость vs сложность | OPEN |
| Кто может править выставленную оценку? | Audit | OPEN |
| Workflow утверждения итогов? | Официальные записи | OPEN |
| Официальный PDF / report card / подпись? | Юридика/операции | OPEN |
| Что видит student vs parent | Privacy + Flutter | OPEN |
| История за несколько лет? | Хранение + UX | OPEN |

---

## Scheduling

Сейчас реализовано: `scheduled_lessons` (обычные уроки, доска пн–пт). **Таблицу в этой задаче не менять.**

Будущий календарь должен показывать как минимум: обычные уроки; репетиции; спектакли; возможно экзамены; возможно события академии.

**Архитектура (Tech Lead не выбрал):**

- **Variant A:** одна сущность `ScheduledSession` с типами (`lesson`, `rehearsal`, `exam`, `performance`, …).
- **Variant B:** разные доменные сущности (`ScheduledLesson`, `Rehearsal`, `Performance`, …) плюс агрегирующий слой календаря.

| Question | Status |
|----------|--------|
| Variant A vs B | OPEN |
| Как репетиции попадают в **единый календарь ребёнка** и в **conflict detection** с обычными занятиями | Требование PRELIMINARY (обязательно); реализация OPEN |

---

## Productions / Shows

Это **не** «опциональный placeholder фазы 6». Постановка — **доменная подсистема**, тесно связанная с ядром расписания. **Приоритет после product discovery задаёт PM** — направление можно поднять в roadmap.

Предварительная модель (не финальная схема):

`Production / Show` → свои участники → роли/cast → **rehearsal groups** → репетиции → спектакли.

**PRELIMINARY:** Rehearsal Group **не** является CourseGroup. В неё могут входить дети разных возрастов, курсов и обычных учебных групп. Ребёнок может быть в обычных CourseGroup **и** в одной или нескольких rehearsal groups **и** в нескольких productions.

**PRELIMINARY:** у репетиций своё расписание; они **обязаны** попадать в единый календарь ребёнка и в проверку конфликтов с обычными занятиями.

Возможные будущие сущности (не утверждены как финал): Production, ProductionParticipant, CastRole / ProductionRole, RehearsalGroup, Rehearsal, Performance. ProductionStaff — возможна, **не** утверждена.

Costume Service может остаться **отдельным интегрированным сервисом**. Планирование постановок ≠ аренда костюмов.

Нужен discovery: workflow создания; casting; несколько ролей у ребёнка; кто собирает rehearsal groups; обязательность репетиций; attendance **на репетициях** (домен Student Attendance); преподаватели/хореографы/режиссёры; площадки; отмена/перенос; даты спектаклей; костюмы; взносы; билеты; согласие родителей; уведомления; связь costume↔production.

**Status:** PRELIMINARY (разделение домена + правило календаря); большая часть workflow — OPEN.

---

## Documents

- **Question:** Private vs public storage; типы документов; срок хранения; кто загружает.
- **Why it matters:** Файлы сейчас на диске **public**.
- **Status:** OPEN (private storage обязателен до широкого mobile rollout — см. Security)

---

## Communications

- **Question:** Каналы (in-app, email, push), кто кому пишет, шаблоны.
- **Status:** OPEN (`/communication` — заглушка; иконка в списке студентов не работает)

---

## Payments

- **Question:** Итальянские счета, способы оплаты, долги, связь с постановками/взносами/билетами.
- **Status:** OPEN

---

## Privacy / Consent

- **Question:** Типы согласий, workflow до 14 лет, версии политики, согласие на фото.
- **Status:** OPEN (сущностей нет). Обязательно до App Store / широкого mobile.

---

## Flutter / Distribution

- **Variant A:** одно Store-приложение с **режимами** student / parent / teacher.
- **Variant B:** несколько Store-приложений на общей Flutter codebase / flavors.

Не решено. Старый текст архитектуры про «одно приложение / режимы» — набросок, **не** DECIDED.

- **Status:** OPEN

---

## Infrastructure / Deployment

- **Question:** Git-deploy GitHub → `/var/www/aub`; staging; бэкапы; мониторинг; CI/CD.
- **Current fact:** production **не** git-репозиторий.
- **Status:** OPEN
