# AUB — Module Roadmap

Numbers below are **major product directions**. The recommended **starting sequence** (1–5) is mandatory: Core Data Model refactor must **precede** a stable API contract. Other modules may be reordered by the PM, except Productions (late future). Backend and Flutter evolve **in parallel**; the **API contract** joins them.

Implemented web snapshot: [CURRENT_STATE.md](CURRENT_STATE.md). Open items: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Already in production (web core)

- Laravel 13 + kit v0.4.0, session auth, RBAC Phase 1
- Students on `customers` (**interim**; target direction `students` / `parents` / `student_parent`)
- Parents embedded; teachers directory (no `user_id`)
- Courses / groups; lessons catalog in Settings → Academy
- Weekly schedule + hybrid AI (`scheduled_lessons` only)
- CRUD activity log; settings tabs
- Flutter repo exists; **no API**

---

## Recommended starting sequence

| # | Direction | Notes |
|---|-----------|--------|
| 0 | Baseline / GitHub / documentation | In progress as living docs |
| 1 | **Security Foundation** | **Required before wide mobile rollout.** Private storage for children’s files; field-level ACL; scoped teacher access; access/view audit; API authorization matrix; mobile token security; **2FA for admin staff**; consent/privacy records. **Not implemented.** |
| 2 | **Core Data Model refactor** | Target `students`, `parents`, `student_parent`. `customers` is not the long-term model. One active `Class` per child. **Before** a stable mobile API. Migrations are a separate task, not now. |
| 3 | **Identity model implementation** | One User = one actor type (`student` / `parent` / `teacher`). Two roles for one person = two accounts. Identity ≠ admin web RBAC. |
| 4 | **API Foundation** | Routing, versioning, token auth (Sanctum = candidate), `/me`, resources, errors, rate limits, tests. Only after 2–3. |
| 5 | **Flutter Foundation** | App shell, env, HTTPS client, auth against API — Store distribution model OPEN |

## Product modules (after foundation)

| # | Direction | Notes |
|---|-----------|--------|
| 6 | Student + Parent mobile MVP | Depends on 3–5 |
| 7 | Teacher App / Teacher Workplace | Flutter + web workplace; Teacher↔User link. Identity/app foundation **before** check-in |
| 8 | **Student Attendance** | Child present on a **specific lesson / rehearsal / session**. **Not** teacher geofence |
| 9 | **Teacher Check-in / Staff Presence** | Separate module **after** Teacher identity/app foundation. Daily presence, not per-lesson. Button «Пришёл» + one-shot GPS + geofence. QR = optional fallback |
| 10 | **Final Assessment / Report Cards** | **Separate product module.** No running grades; only `StudentFinalResult` + `ReportCard`; Administrator generates the PDF |
| 11 | Secretariat / Enrollment Workflow | Statuses, transfers, history around one `Class` |
| 12 | Documents | Standalone module; move off public disk |
| 13 | Communications + Push | Placeholder today |
| 14 | Payments / Accounting | Fiscal rules OPEN |
| 15 | Schedule evolution | Unified calendar; Variant A vs B OPEN; do not change `scheduled_lessons` until chosen |
| 16 | Dashboard / Reporting | Current dashboard is a placeholder; logs ≠ reports |
| 17 | Consent / Privacy | Target `ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`; do not hardcode an age threshold |
| 18 | Infrastructure / Backup / Monitoring | OPEN |
| 19 | CI/CD / Staging / Deployment | Production is not a git repo |
| 20 | App Store / Google Play release | After privacy + API + distribution choice |

Legacy numeric “Phase 0–6” from earlier docs is **historical**. Do not treat “Phase 6 = shows” as the product model.

---

## Late future (discovery-needed)

| Direction | Notes |
|-----------|--------|
| **Productions / Shows** | Late stage. Do not detail workflow now. Activity groups ≠ `Class`. Future group schedules join the child’s unified calendar. Costume Service may stay a separate service |
| Costume Service / Archive / extra services | May integrate with productions later; still separate services |

---

## Domain splits (do not mix)

| Domain | Meaning |
|--------|---------|
| `Class` | Permanent primary academic class; at most one active per child |
| Additional activity groups | Production / rehearsal / other; **not** a `Class`; a child may join several |
| Student Attendance | Child on a session |
| Teacher Presence | Staff physically at the academy (daily check-in) |
| Final Assessment | End-of-period result: `StudentFinalResult` / `ReportCard`; not running grades |

## Parallelism

```
AUB_admin  ── API contract ──  AUB_app
   │                              │
   web workplaces / admin         student / parent / teacher
                                  (one User = one actor type;
                                   Store distribution OPEN)
```

Do **not** publish a stable API contract before Core Data Model refactor + Identity model.
