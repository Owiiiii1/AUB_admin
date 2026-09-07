# AUB — Next Steps

Practical order of work. Source of truth for what is already built: [CURRENT_STATE.md](CURRENT_STATE.md).

**Status (2026-09-07):** Phase 1 complete. Phase 2 partial. Weekly schedule + hybrid AI in use. Flutter repository exists. **HTTPS API does not exist.**

## Current technical priority: API Foundation

Flutter development **may run in parallel** with web/backend. Mobile **features** require a stable API contract.

The old rule “do not start mobile until web is stable” is **withdrawn**.

### API Foundation (next major stage — not implemented)

Candidate contents (Tech Lead has **not** locked the token library):

- `routes/api.php` (or equivalent) and API versioning
- Token authentication — **Laravel Sanctum is a recommended candidate**, not an approved decision
- Flutter login, `/me`, secure logout / token revoke
- Authorization for student / parent / teacher / staff API actors
- API Resources / DTO
- Error format
- Rate limiting
- Basic integration tests

Until this lands in `AUB_admin`, `AUB_app` stays a template (`com.owlsolutions.aub`).

---

## Already done (2026-07-06 — 2026-07-20), still true

- [x] Student profile on extended `customers`
- [x] Teachers directory
- [x] Courses & groups
- [x] Lessons catalog (Settings → Academy, not `Lessons/Index.jsx`)
- [x] Weekly schedule board + hybrid AI
- [x] Activity CRUD log
- [x] Settings tabs (users, roles, app, AI, academy)
- [x] Flutter GitHub repository `Owiiiii1/AUB_app` (2026-09, template only)

### Optional schedule polish (web)

- [ ] Actionable AI recommendations (not only re-prompt)
- [ ] PDF export
- [ ] Manual 5-minute snap in UI
- [ ] Room capacity vs narrow course windows

---

## Recommended product steps (after or alongside API Foundation)

Order can be adjusted by Tech Lead; API Foundation unblocks Flutter.

### 1. Enrollment workflow (Phase 2)

- Statuses (active, withdrawn, completed)
- Transfers with history
- Decide: keep `customers` or migrate to `students`

### 2. Parents as a real entity (Phase 2)

- `parents` + `student_parent`
- Needed for a parent Flutter mode

### 3. Teacher ↔ User (Phase 2)

- `teachers.user_id`
- Required for teacher workplace login and teacher Flutter mode

### 4. Attendance (Phase 3)

- Mark presence per scheduled lesson / group
- Depends on schedule + groups (done) and on teacher identity (partial)

### 5. Documents module (Phase 3)

- Standalone `/documents` (now placeholder)
- Move children’s files off the **public** disk (privacy gap)

### 6. Communication (Phase 3)

- `/communication` placeholder → messaging
- Student-list message icon is non-functional

### 7. Field-level and scoped access

- Teacher sees only assigned students
- Hide parent contacts / medical fields by role
- View/access audit for sensitive records (current log is CRUD, not reads)

---

## Later

| Step | Phase |
|------|-------|
| Payments / invoices | 4 |
| Consent records, public Privacy Policy, App Store prep | 4–5 / compliance |
| Events, archive, costumes | 6 optional |
| Git-based deploy GitHub → `/var/www/aub` | ops (production is not a git repo today) |

---

## Open client questions

- Timing of `customers` → `students` migration
- Enrollment statuses and transfer rules
- Teacher login: web, Flutter, or both
- Italian fiscal invoice rules
- Final field-level matrix (teacher vs secretariat vs admin)
- Consent workflow for photos and processing
- Costume/events scope (Phase 6)

---

## Do not do now (unless a dedicated task says otherwise)

- Do not delete legacy kit tables without an explicit decision
- Do not edit `vendor/owlsolutions/custom-admin-kit/`
- Do not expose secrets
- Do not treat Sanctum as “the” API choice until Tech Lead confirms
- Do not implement API or Flutter features in a docs-only task
