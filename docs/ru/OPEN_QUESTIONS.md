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
| Ребёнок одновременно имеет максимум один активный основной `Class` | DECIDED |
| Дополнительные activity groups (production / rehearsal / иное) **не** являются `Class` | DECIDED |
| Один User имеет ровно один основной actor / account type (`student` / `parent` / `teacher`) | DECIDED |
| Если одному физическому человеку нужны две роли — два отдельных аккаунта; multi-profile identity в текущую версию не закладывать | DECIDED |
| `customers` — kit leftover; academy uses `students` + `parents` + `student_parent` | DECIDED (implemented 2026-09-08) |
| Core Data Model refactor должен произойти **до** публикации стабильного mobile API | DECIDED |
| Teacher Check-in относится к **рабочему дню**, не к конкретному lesson/session | DECIDED |
| Текущих оценок в течение периода нет; электронный gradebook за каждый урок не нужен | DECIDED |
| Итоговая оценка принадлежит связке Student + Class + Lesson + AcademicYear (`StudentFinalResult`) | DECIDED |
| Заполнять итоговый результат могут только преподаватели, назначенные на этот урок класса; одна общая запись на предмет | DECIDED |
| Табель включает **все** дисциплины `ClassLesson` класса на AcademicYear | DECIDED |
| PDF табеля формирует Administrator в admin panel; преподаватель PDF не генерирует | DECIDED |
| Productions / Shows — поздний future / discovery-needed этап | DECIDED (приоритет) |

---

## Core Data Model

### Основной класс ученика (`Class`)

- **DECIDED:** ребёнок может состоять только в одном основном учебном классе одновременно. Это обязательное бизнес-правило текущей версии.
- **Терминология (целевая):** `Class` — постоянный основной учебный класс ребёнка. Не смешивать `Class` с временными дополнительными группами.
- **Код сейчас:** unique `student_id` на `academy_class_student`. PHP-модель `AcademyClass` / таблица `academy_classes`.
- **Status:** правило DECIDED; терминология PHP vs продукт зафиксирована (`AcademyClass` = `Class`).

### Студенты / родители vs `customers`

- **DECIDED / implemented:** `customers` не academy Student. Сущности: `students`, `parents`, `student_parent`.
- **Production fact:** тестовые `customers` / schedule / course_groups очищены миграцией 2026-09-08. Admin users сохранены.
- **Status:** реализовано. Identity Layer (привязка User) — **implemented** 2026-09-08.

### Unique vs дополнительные группы

- **DECIDED:** один активный `Class` + одновременно одна или несколько дополнительных групп (события, постановки, репетиции и т.п.).
- Дополнительные группы **не** хранятся как второй `Class`.
- **Status:** продуктовое правило DECIDED. Схема дополнительных групп — не финализировать сейчас.

---

## Enrollment

- **Question:** Статусы зачисления в `Class`, правила перевода между классами, история, даты начала/конца?
- **Why it matters:** Сейчас только pivot без workflow.
- **Current assumption:** Нет. Правило «один активный Class» уже DECIDED; workflow вокруг него — нет.
- **Status:** OPEN

---

## Identity & Accounts

**DECIDED для текущей версии:**

- Один User имеет ровно один основной тип аккаунта.
- Основные mobile actor types: `student`, `parent`, `teacher`.
- Один User **не** может одновременно быть Student и Parent, Teacher и Parent и т.д.
- Если одному физическому человеку нужны две роли, создаются два отдельных аккаунта.
- Multi-profile identity в текущую версию **не** закладывать. Это осознанное упрощение MVP.
- Authentication account type и административный web RBAC — **разные** понятия. Administrative staff продолжает использовать существующий web RBAC.

**Реализовано 2026-09-08:**

- `users.account_type`: `staff` | `student` | `parent` | `teacher` (string, не DB enum).
- `users.is_active` (default true); неактивный User не может войти в web.
- Один User = один actor type. Cross-table связи отклоняет `AccountIdentityService`.
- `staff` не имеет actor profile. `account_type=staff` сам по себе прав не даёт; web-права только из RBAC.
- `student` / `parent`: `role_id` = null; web admin недоступен.
- `teacher`: опциональный web `role_id` для workplace.
- Admin UI: секция Account у Student / Parent внутри студента / Teacher; Settings → Users показывает type/status/linked profile.
- Существующие users получили `staff`. Teachers **не** связывались автоматически по email.

**Остаётся OPEN / technical debt:**

| Question | Why it matters | Status |
|----------|----------------|--------|
| Родитель с несколькими детьми — правила связи через `student_parent` | Кардинальность, UX, consent | OPEN (целевая таблица есть; правила нет) |
| Старший student со своим аккаунтом — кто создаёт, с какого момента | Onboarding | OPEN |
| Invitation / activation workflow | Email-приглашение, первый пароль | OPEN (сейчас админ задаёт временный пароль) |
| Отдельный login identifier / alias / username помимо unique `users.email` | Один человек, два аккаунта сейчас требуют два email | OPEN (уникальность email сейчас не менять) |
| Mobile API / token auth | Flutter login | **DECIDED/implemented** — Sanctum `/api/v1`; следующий этап: Flutter Authentication Foundation |
| Долгосрочный вид teacher web workplace vs mobile Teacher account | Check-in и workplace | OPEN (направление: Teacher — actor type; web RBAC отдельно) |

---

## Roles & Access

- **Question:** Итоговая field-level матрица и scoped access преподавателя (только свои классы/дети).
- **Why it matters:** Сейчас роль с `customers.*` видит все поля. `always_allowed_route_patterns` шире меню роли.
- **Current assumption:** Матрица-намерение в [USER_ROLES_AND_ACCESS.md](USER_ROLES_AND_ACCESS.md) — **не** код.
- **Status:** OPEN

Ниже — **обязательный foundation до широкого mobile rollout** (не реализовано): private storage документов детей; field-level ACL; scoped teacher access; access/view audit чувствительных данных; матрица API-авторизации; mobile token security; **2FA для административного персонала**; consent/privacy records.

---

## Student Attendance

Teacher write MVP **реализован** 2026-09-09. Идентичность: **Student + ScheduledLesson** (`attendance_records`). Статусы: `present` / `absent` / `excused`. Unmarked = нет строки. Roster = текущий `academy_class_student`. См. [ATTENDANCE.md](ATTENDANCE.md).

Это **не** Teacher Check-in.

### Осталось OPEN

| Question | Why it matters | Status |
|----------|----------------|--------|
| Финализация attendance / окно редактирования (lock через N дней) | Случайные поздние правки vs рабочие исправления | OPEN |
| Исторический roster / snapshot состава на дату занятия | Текущий состав может отличаться от состава в день урока | OPEN (долг; не строится сейчас) |
| UI истории Student / Parent | Следующий product slice; таблица уже есть | OPEN (не этот этап) |
| Доп. статусы (`late`, sick, remote, комментарии) | Product scope | OPEN (нет в MVP) |

---

## Teacher Check-in / Staff Presence

**Домен:** подтверждение, что преподаватель **физически в академии**. Отдельно от Student Attendance.

**DECIDED semantics:** check-in относится к **рабочему дню**, а **не** к конкретному lesson/session. Не требовать check-in перед каждым уроком.

**DECIDED workflow:**

1. Teacher открывает Flutter app.
2. Нажимает **Пришёл**.
3. Приложение получает **один** snapshot геолокации (не background tracking).
4. Backend проверяет geofence academy location.
5. Создаётся **daily** teacher check-in.

**Концептуальные данные (не финальная схема):** teacher; date; checked_in_at; latitude; longitude; accuracy; academy_location; status; manual correction metadata; audit.

QR — **optional fallback**, не основной механизм.

### Осталось OPEN

| Question | Why it matters | Status |
|----------|----------------|--------|
| Точный geofence radius | Ложные отказы vs читерство | OPEN |
| Minimum acceptable GPS accuracy | В помещении GPS слабый | OPEN |
| Допустимое окно времени check-in | Шум слишком ранних/поздних отметок | OPEN |
| Anti-spoofing | Поддельная геолокация | OPEN |
| Дополнительные сигналы безопасности (Wi-Fi / device attestation / иное) | Безопасность vs трение | OPEN |

Модуль реализуется **после** Teacher identity / app foundation. См. [MODULE_ROADMAP.md](MODULE_ROADMAP.md).

---

## Final Assessment / Report Cards

AUB **не** использует обычную школьную модель постоянных оценок.

**DECIDED:**

- в течение учебного периода текущие оценки не выставляются;
- электронный gradebook с оценками за каждый урок **не нужен**;
- используются только итоговые результаты;
- итоговый табель формируется по окончании учебного периода / учебного года.

**DECIDED структура (концепт, не финальная схема / не миграции):**

```
AcademicYear → Class → ClassLesson → TeacherAssignment
Student + Class + Lesson + AcademicYear → StudentFinalResult
Student + Class + AcademicYear → ReportCard
```

- Набор `ClassLesson` определяет дисциплины табеля. Список дисциплин **не** формируется вручную.
- Итоговая оценка **не** принадлежит преподавателю. Одна общая `StudentFinalResult` на предмет, даже если назначено несколько преподавателей.
- Заполнять / редактировать итог могут **только** преподаватели, назначенные на соответствующий `ClassLesson`.
- Табель включает **все** дисциплины класса на AcademicYear. Если хотя бы по одной обязательной дисциплине результата нет — администратор получает предупреждение перед финализацией / генерацией PDF.
- PDF **обязателен**. PDF формирует **Administrator** через admin panel. Преподаватель PDF не формирует. PDF **не** самостоятельный источник данных.

Предварительная `ReportCard`: student_id; class_id; academic_year_id; status (`draft` / `finalized` / `printed`); finalized_at; generated_at; generated_by. Точный lifecycle может быть уточнён позже.

Это **отдельный продуктовый модуль**, не часть API Foundation.

### Осталось OPEN

| Question | Why it matters | Status |
|----------|----------------|--------|
| Шкала итоговой оценки | Схема и UI | OPEN |
| Формат результата | Числа / буквы / уровни / текст | OPEN |
| Обязательность `teacher_comment` | UX + PDF | OPEN |
| Кто имеет право финально закрыть табель | Workflow | OPEN |
| Можно ли редактировать результат после `finalized` | Audit + ops | OPEN |
| Нужен ли отдельный approval workflow | Официальные записи | OPEN |
| Нужен ли цифровой signature | Юридика/операции | OPEN |
| Формат PDF и официальный шаблон | Печать | OPEN |
| Хранить ли snapshot PDF после печати | Архив | OPEN |
| Правила повторной генерации / версии табеля | Ops | OPEN |
| Что видит student vs parent | Privacy + Flutter | OPEN |
| Есть ли экзамены как отдельный тип session | Календарь; **не** текущие оценки | OPEN |
| Периоды: семестр / триместр / год / иное | Когда формируется табель | OPEN |

---

## Scheduling

Сейчас реализовано: `scheduled_lessons` (обычные уроки, доска пн–пт). **Таблицу в docs-only задаче не менять.**

Будущий календарь должен показывать как минимум: обычные уроки; в будущем — репетиции / activity groups; возможно экзамены; возможно события академии.

**Архитектура (Tech Lead не выбрал):**

- **Variant A:** одна сущность `ScheduledSession` с типами (`lesson`, `rehearsal`, `exam`, `performance`, …).
- **Variant B:** разные доменные сущности (`ScheduledLesson`, `Rehearsal`, `Performance`, …) плюс агрегирующий слой календаря.

| Question | Status |
|----------|--------|
| Variant A vs B | OPEN |
| Как расписание дополнительных групп попадёт в **единый календарь ребёнка** | Требование DECIDED для будущего; реализация OPEN |

---

## Productions / Shows / дополнительные группы

**DECIDED:**

- Productions / Shows — **поздний** этап roadmap (future / discovery-needed). Не поднимать приоритет без отдельного решения PM.
- Production / rehearsal / activity groups **≠** `Class`.
- Ребёнок может состоять в одном основном `Class` **и** в одной или нескольких дополнительных группах.
- Расписание этих групп в будущем должно попадать в единый календарь ребёнка.

**Не делать сейчас:** финальную схему; детальный Production workflow.

Предварительные **будущие** типы (не финальная схема): `ProductionGroup`; `RehearsalGroup`; возможно другие activity groups.

Costume Service может остаться **отдельным интегрированным сервисом**. Планирование постановок ≠ аренда костюмов.

**Status:** приоритет и правило «≠ Class» — DECIDED; workflow / casting / билеты / взносы — OPEN / discovery-needed.

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

**Не фиксировать** конкретный возрастной порог в domain model. Неподтверждённые формулировки вроде «до 14 лет» **не использовать** как правило продукта.

**Целевое направление (не реализовано):**

- `ConsentType`
- `ConsentDocumentVersion`
- `ConsentRecord`

Возможные consent types: privacy; data processing; photo/video; marketing; special activity.

Для несовершеннолетнего согласие связывается с parent/guardian **там, где это требуется** политикой академии и применимым законодательством. Конкретные возрастные юридические правила **не хардкодить** до legal / compliance review.

| Question | Status |
|----------|--------|
| Какие типы согласий обязательны | OPEN |
| UX / workflow сбора | OPEN |
| Legal / compliance review возрастных правил | OPEN (до review не кодировать порог) |
| Сущности в БД | OPEN (модель-направление есть; реализации нет) |

Обязательно до App Store / широкого mobile.

---

## Flutter / Distribution

- **Variant A:** одно Store-приложение с **режимами** student / parent / teacher.
- **Variant B:** несколько Store-приложений на общей Flutter codebase / flavors.

Не путать с identity: один User = один actor type — **DECIDED**. Упаковка Store (одно приложение vs flavors) — **OPEN**.

- **Status:** OPEN (только дистрибуция)

---

## Infrastructure / Deployment

- **Question:** Git-deploy GitHub → `/var/www/aub`; staging; бэкапы; мониторинг; CI/CD.
- **Current fact:** production **не** git-репозиторий.
- **Status:** OPEN
