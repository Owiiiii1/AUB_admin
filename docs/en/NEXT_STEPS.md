# AUB — Next Steps

Practical development steps ordered by priority.

## Current focus: complete Phase 2 + start Phase 3 operations

**Status:** Phase 1 complete (2026-07-06). Phase 2 partially complete. Weekly schedule + AI scheduling usable (2026-07-20).

See [CURRENT_STATE.md](CURRENT_STATE.md) and [WEEKLY_SCHEDULE_SERVICE.md](WEEKLY_SCHEDULE_SERVICE.md).

### Recently completed (2026-07-06 — 2026-07-20)

- [x] Student profile on extended `customers` table (full-page UI, documents, parents modals)
- [x] Teachers module (list + profile)
- [x] Courses & groups module (`/courses-groups`)
- [x] Lessons catalog (`/lessons`)
- [x] Weekly schedule service (`/schedule-service`): board, conflicts, publish/copy/clear
- [x] Hybrid AI scheduling (preferences → `DeterministicSchedulePlanner`), `schedule_ai_runs` log
- [x] Course study window (shifts 08–13 / 13–18), multiple teachers per group+lesson
- [x] Planning grid 5 min with visual 30-min rows; teacher daily limit 8h
- [x] General / Group views; AI assistant instructions for supported levers
- [x] Activity logs (statistics page)
- [x] Settings consolidation (users, roles, app, AI, academy tabs)
- [x] UI/branding: login redesign, AUB colors, Singo Sans, Italian default locale
- [x] Placeholder menu sections (documents, communication, events, archive, costume service)

### Near-term schedule improvements (optional)

- [ ] Actionable AI recommendations (auto add/split teachers, not only re-prompt)
- [ ] PDF export
- [ ] Manual UI snap at 5 min (drag/resize still 30 min)
- [ ] Room capacity vs narrow study windows for large courses

---

## Recommended next steps (priority order)

### 1. Attendance tracking (Phase 3)

- Mark presence per scheduled lesson / group
- Teacher workplace integration
- Depends on: weekly schedule + course groups (done)

### 2. Enrollment workflow (Phase 2 completion)

- Statuses (active, withdrawn, completed)
- Transfer between groups with history
- Decide: keep `customers` or migrate to dedicated `students` table

### 3. Parents as separate entity (Phase 2)

- `parents` table + `student_parent` pivot
- Replace embedded father/mother columns when ready
- Future: parent mobile access channel

### 4. Documents module (Phase 3)

- Standalone `/documents` page (currently placeholder)
- Central document list, types, retention rules
- Reuse existing upload paths from student profile

### 5. Communication module (Phase 3)

- `/communication` placeholder → messaging/notifications
- Message icon in student list (currently non-functional)

### 6. Teacher ↔ user account linking (Phase 2)

- `teachers.user_id` for teacher workplace login
- Role-based field visibility on student profile

---

## Later steps

| Step | Module | Phase |
|------|--------|-------|
| Events, archive, costume service | Placeholder → full modules | Phase 6 (optional) |
| Payments/invoices | Accounting | Phase 4 |
| Privacy/consent records + App Store preparation | Compliance + mobile | Phase 4–5 |
| PDF export for schedule | Operations | Phase 3 |
| Mobile API | External access | Phase 5 |

---

## Client clarification questions (still open)

### Academy data

- Confirm interim `customers`-as-students approach vs dedicated `students` table migration timing
- Enrollment statuses and transfer rules
- Teacher login requirements (web only vs mobile)
- Payment structure and Italian fiscal invoice requirements

### Privacy and localization

- Field-level access matrix per role (teacher vs secretariat vs admin)
- Consent workflow for photos and data processing
- Import from existing spreadsheets?

### Optional services (Phase 6 — not MVP)

- Costume service scope and billing integration
- Events vs shows participation

---

## Do not do now

- Do not delete legacy kit tables (`orders`, `services`, `staff`) without explicit decision
- Do not modify `vendor/owlsolutions/custom-admin-kit/`
- Do not expose secrets in documentation
- Do not implement mobile apps before core web workflows are stable
