# AUB — User Roles and Access

Access control is a **privacy requirement**. This document splits **implemented** behaviour from **intended** product rules.

## Terminology

| Term | Meaning |
|------|---------|
| **Role** | Staff web role (`roles` table) |
| **Workplace** | Limited web screen set |
| **Admin / superadmin interface** | Full web CRM for `is_admin` |
| **Access channel** | How a person reaches the core: web workplace, admin UI, or Flutter |

Parent and Student are **not** admin roles. They are planned **Flutter (and possibly web) channels** against `AUB_admin` API. The API does not exist yet.

**DECIDED:** Parent/Student must not become administrative web RBAC roles only because they need login. Authentication account type ≠ administrative web RBAC. Administrative staff continues to use the existing web RBAC.

**DECIDED (MVP identity):** one User = one primary actor type (`student` / `parent` / `teacher`). Two roles for one person = two accounts. Do not design multi-profile identity into the current version. Invitation / activation remain OPEN ([OPEN_QUESTIONS.md](OPEN_QUESTIONS.md)).

Costume management is a future **service**, not a role. Flutter Store packaging (one app with modes vs several apps/flavors) is **OPEN**.

## Implemented (web)

| Feature | Status |
|---------|--------|
| `roles` / `role_menu_items` | Yes |
| `users.role_id` | Yes |
| `users.can_write` / `can_delete` | Yes — middleware `can.write` / `can.delete` |
| Roles UI in Settings | Yes |
| Role selector on users | Yes |
| Dynamic `adminMenu` | Yes |
| `role.assigned` / `role.access` / `administrator` | Yes |
| Post-login redirect | Admin → `/dashboard` (placeholder); others → first menu route or `/workplace` |
| Last-admin lockout | Yes (`AdministratorLockoutGuard`) |
| Session authentication | Yes |
| Activity CRUD logging | Yes (create/update/delete + login/logout) |

### Menu keys stored in `role_menu_items`

`dashboard`, `students` (`customers.*`), `teachers`, `settings`, `statistics`.

### Always allowed for any authenticated user **with a role**

From `config/aub-menu.php`: `profile.*`, `password.update`, `logout`, `workplace`, `courses-groups.*`, `lessons.*`, `placeholder.*`, `weekly-schedule.*`.

`AdminLayout` always lists courses/groups, schedule, and placeholder sections. **Lessons is not an extra sidebar item** (catalog is Settings → Academy).

This is **wider** than role-menu permissions. Do not document “non-admin cannot open schedule” as a fact — the code allows it.

`settings.admin_only` is metadata; `MenuRegistry::isAdminOnlyMenuKey()` is unused for enforcement.

### Flags

| Field | Middleware | Effect |
|-------|------------|--------|
| `can_write` | `can.write` | POST/PATCH domain writes |
| `can_delete` | `can.delete` | DELETE (student delete also requires current password) |

Language change (`settings.language.update`) is **not** behind `can.write`.

## Planned product roles (not fully enforced)

These are **intent**. Code does not yet hide parent contacts from teachers or limit teachers to assigned groups.

| Role / channel | Intent |
|----------------|--------|
| Administrator | Full web admin |
| Admin | Possibly slightly reduced admin — unconfirmed |
| Secretariat | Students, enrollments, schedule, later payments; no system settings |
| Teacher | Own groups, schedule, **student attendance**, later **own check-in**; no payments/parent contacts unless granted |
| Parent | Flutter channel: own children only — **not** a web admin role |
| Student | Flutter channel: self only — **not** a web admin role |

## Full admin vs workplaces vs Flutter

```
Guest → /  (web login)

Administrator (is_admin)
  → /dashboard + registry menu + extra always-allowed items

Non-admin staff
  → /workplace or first role_menu_items route
  → Extra AdminLayout items still shown (courses, schedule, placeholders)

Parent / Student / Teacher (mobile)
  → AUB_app via HTTPS API  [API not built]
```

## Preliminary access matrix (intent, not code)

✓ full, R read, W write, — none, F future Flutter

| Action | Administrator | Secretariat | Teacher | Parent | Student |
|--------|---------------|-------------|---------|--------|---------|
| Student profile | ✓ | ✓ (intent) | R own groups (intent) | R own child F | R self F |
| Parent contacts | ✓ | ✓ (intent) | — (intent) | R self F | — |
| Payments | ✓ | ✓ (intent) | — | R own F | — |
| Medical / sensitive | ✓ | R (intent) | — | — | — |
| Student Attendance (child on a session) | ✓ | ✓ | W own (intent) | R own child F (OPEN) | — |
| Teacher Check-in / presence | ✓ / manual fix | ✓ (intent) | W own daily F | — | — |
| Final results / report cards | ✓ + PDF in admin panel | ? OPEN (who closes the card) | W `StudentFinalResult` only for assigned `ClassLesson`; one shared record; does not generate PDF | R F OPEN | R F OPEN |
| Schedule edit | ✓ | ✓ (intent) | R (intent) | R F | R F |
| Users / roles / AI | ✓ | — | — | — | — |

**In code today:** anyone who can open `customers.*` sees all student fields including parents and medical expiry. Teachers have no `user_id`, so “own groups” cannot be applied to a login.

## Kit `staff` vs AUB roles

Kit `staff.role` is free text. AUB RBAC is `roles` + `users.role_id`. Teachers are `teachers`, not `staff`.

## Requires follow-up (not implemented)

- Field-level access (**Security Foundation** before wide mobile)
- Teacher → only assigned students/groups
- API authorization matrix
- Mobile token security
- View/access audit of sensitive records
- Consent entities (`ConsentType` / `ConsentDocumentVersion` / `ConsentRecord`)
- 2FA for administrative staff
- Identity: User ↔ Teacher as a mobile actor; Parent/Student accounts. **Not** multi-profile on one User

See [PRIVACY_AND_DATA_PROTECTION.md](PRIVACY_AND_DATA_PROTECTION.md), [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md), [CURRENT_STATE.md](CURRENT_STATE.md).
