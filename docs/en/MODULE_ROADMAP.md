# AUB — Module Roadmap

Numbers below are **major product directions**, not a frozen business priority. The PM may reorder after discovery (especially **Productions**). Backend and Flutter evolve **in parallel**; the **API contract** joins them.

Implemented web snapshot: [CURRENT_STATE.md](CURRENT_STATE.md). Open items: [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md).

## Already in production (web core)

- Laravel 13 + kit v0.4.0, session auth, RBAC Phase 1
- Students on `customers` (partial); parents embedded; teachers directory (no `user_id`)
- Courses / groups; lessons catalog in Settings → Academy
- Weekly schedule + hybrid AI (`scheduled_lessons` only)
- CRUD activity log; settings tabs
- Flutter repo exists; **no API**

---

## Directions (0–22+)

| # | Direction | Notes |
|---|-----------|--------|
| 0 | Baseline / GitHub / documentation | In progress as living docs |
| 1 | **Security Foundation** | **Required before wide mobile rollout.** Private storage for children’s files; field-level ACL; scoped teacher access; access/view audit; API authorization matrix; mobile token security; **2FA for admin staff**; consent/privacy records. **Not implemented.** |
| 2 | Core Data Model | `customers` vs `students`; **HIGH PRIORITY:** unique one-group-per-student vs multi-group; identity tables |
| 3 | API Foundation | Routing, versioning, token auth (Sanctum = candidate), `/me`, resources, errors, rate limits, tests |
| 4 | Flutter Foundation | App shell, env, HTTPS client, auth against API — distribution model OPEN |
| 5 | User Identity / Student / Parent / Teacher access | Identity ≠ admin RBAC. Teacher↔User missing |
| 6 | Student + Parent mobile MVP | Depends on 3–5 |
| 7 | Teacher App / Teacher Workplace | Flutter check-in + web workplace; `teachers.user_id` |
| 8 | **Student Attendance** | Child present on a **specific lesson / rehearsal / session**. **Not** teacher geofence |
| 9 | **Teacher Check-in / Staff Presence** | Physical presence at academy. PRELIMINARY: button «Пришёл» + one-shot GPS + geofence. QR = fallback only |
| 10 | Academic Progress / Grades / Report Cards | Large module; grading system OPEN (academy discovery) |
| 11 | Secretariat / Enrollment Workflow | Statuses, transfers, history |
| 12 | Documents | Standalone module; move off public disk |
| 13 | Communications + Push | Placeholder today |
| 14 | Payments / Accounting | Fiscal rules OPEN |
| 15 | Schedule evolution | Unified calendar; Variant A vs B OPEN; do not change `scheduled_lessons` until chosen |
| 16 | **Productions / Shows / Rehearsal Scheduling** | **Not** “optional event placeholder”. RehearsalGroup ≠ CourseGroup. Rehearsals on child’s calendar + conflicts. Priority: **PM after discovery** (may move up). Costume Service may stay separate |
| 17 | Dashboard / Reporting | Current dashboard is a placeholder; logs ≠ reports |
| 18 | Consent / Privacy | Entities missing; legal texts missing |
| 19 | Infrastructure / Backup / Monitoring | OPEN |
| 20 | CI/CD / Staging / Deployment | Production is not a git repo |
| 21 | App Store / Google Play release | After privacy + API + distribution choice |
| 22+ | Costume Service / Archive / extra services | Costume may integrate with productions; still a separate service |

Legacy numeric “Phase 0–6” from earlier docs is **historical**. Do not treat “Phase 6 = shows” as the product model.

## Domain splits (do not mix)

| Domain | Meaning |
|--------|---------|
| Student Attendance | Child on a session |
| Teacher Presence | Staff physically at the academy |
| CourseGroup | Regular study group |
| RehearsalGroup | Production cast group (PRELIMINARY: not CourseGroup) |

## Parallelism

```
AUB_admin  ── API contract ──  AUB_app
   │                              │
   web workplaces / admin         student / parent / teacher
                                  (distribution OPEN)
```
