# AUB — User Roles and Access

## Overview

Access control is a **privacy requirement**, not only a convenience feature. The academy handles personal data and children's data. Staff must see only what their job requires.

**Status of this document:** describes the **implemented** access model (Phase 1, 2026-07-06) plus planned extensions.

## Terminology

| Term | Meaning |
|------|---------|
| **Role** | Staff/user access role (Administrator, Secretariat, Teacher…) |
| **Workplace** | Role-specific screen set with limited menu |
| **Full admin panel** | Complete admin with all menu items — Administrator only |
| **Access channel** | Future way to access the system (web workplace, mobile app) |

**Not roles:**

- Costume management — future optional service, not a user role
- Parent/Student — future **access channels**, not the central admin itself

## Planned access roles

### Superadmin / Administrator

- Full access to admin panel
- All menu items visible
- Can manage users, roles, menu permissions
- Redirect after login: `/dashboard`
- At least one active administrator must always exist

### Admin

- May be equivalent to Administrator or a slightly limited admin (to be confirmed with client)
- Full or near-full admin panel access
- Can manage most academy records

### Secretariat

- Access to selected workplace/admin screens only
- Typical access: students, parents, enrollments, schedule view, payments
- No access to: user management, roles, AI settings, system settings
- Redirect: `/workplace` or first allowed menu route

### Teacher

- Teacher workplace screens only
- Typical access: assigned groups, schedule, attendance marking, student list (limited fields)
- No access to: payments, parent contacts (unless approved), admin settings
- Future: web workplace + mobile app

### Parent

- **Not** full admin access
- Future mobile/web access channel
- Typical access: own children's schedule, attendance, payments, documents, messages

### Student

- **Not** full admin access
- Future mobile/web access channel
- Typical access: own schedule, attendance history, announcements

## Full admin panel vs role-based workplaces

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

## Planned role management features

### Roles management screen

- List all roles (name, slug, is_admin, is_active, user count)
- Create/edit role: name, description, is_admin flag
- Menu item assignment per role (checkboxes or drag-order)
- Cannot delete system roles (`is_system=true`)
- Cannot disable last active administrator role

### User create/edit — role selector

- Extend existing user form in `/settings` (`Settings\UserController`)
- Required field: role selection
- On save: assign `users.role_id`
- Existing `admin@admin.com` → Administrator role (migration/seed)

### Dynamic menu generation

- `AdminLayout.jsx` reads allowed menu items from shared Inertia props (based on user's role)
- Administrator (`is_admin=true`): show all menu items (current behavior)
- Non-admin: show only items from `role_menu_items` for user's role
- Hide unauthorized menu entries; block direct URL access via middleware

### Redirect logic

| User state | Redirect |
|------------|----------|
| Guest accessing protected route | `/` (login) |
| Administrator after login | `/dashboard` |
| Non-admin after login | `/workplace` or first allowed `route_name` |
| User with no role | Denied — show error, do not grant access |
| Non-admin accessing admin-only route | 403 Forbidden |

### Lockout protection

- Do not allow deleting the last active administrator role
- Do not allow removing `is_admin` from the only active administrator user
- Do not allow disabling all users with administrator role
- System roles (`is_system=true`) cannot be deleted

## Role edit screen — menu configuration

Admin selects which screens each role can access:

| menu_key | Example route | Example label |
|----------|---------------|---------------|
| dashboard | dashboard | Home |
| students | customers.index | Students |
| teachers | teachers.index | Teachers |
| settings | settings.index | Settings |
| statistics | statistics.logs | Statistics |

Additional menu items in `AdminLayout` (not in `role_menu_items`, always allowed): courses-groups, lessons, weekly-schedule, placeholder sections.

Legacy kit keys removed from menu: orders, services, staff, calendar, ai-settings (consolidated into settings).

Each role can have a **different menu set**. Roles are flexible — do not hardcode final academy role names too early.

## Example role configurations

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

## Preliminary access matrix

Legend: ✓ = full access, R = read only, W = write, — = no access, F = future channel

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

**Note:** This matrix is preliminary. Final permissions must be confirmed with the academy owner.

## Privacy considerations

- Role-based access limits exposure of children's personal data
- Teachers should not see payment or parent contact data unless explicitly granted
- Health/sensitive notes require highest restriction
- Export permissions must be tightly controlled
- All access decisions should be logged (future audit trail)

## Relationship to generic kit `staff` table

The kit's `staff` table (`app/Models/Staff.php`) is a **CRM staff directory** with a free-text `role` column. It is **not** the planned RBAC system.

| Kit `staff.role` | Planned `roles` table |
|------------------|----------------------|
| Free-text field | Structured access role |
| No menu control | Controls menu and route access |
| CRM directory | User authentication authorization |

Both may coexist: a Teacher record in `teachers` table linked to a `users` record with a Teacher role.

## User write/delete permissions

Beyond role-based menu access, two flags on `users` control destructive and mutating operations:

| Field | Middleware | Effect |
|-------|------------|--------|
| `can_write` | `can.write` | Required for POST/PATCH create/update routes |
| `can_delete` | `can.delete` | Required for DELETE routes (e.g. student delete with password) |

Configured in user create/edit form (Settings → Users tab).

## Implementation status

| Feature | Status |
|---------|--------|
| Roles table | ✅ Implemented |
| RoleMenuItem table | ✅ Implemented |
| users.role_id | ✅ Implemented |
| users.can_write / can_delete | ✅ Implemented |
| Roles management screen | ✅ Settings → Roles tab |
| Role selector in user form | ✅ Implemented |
| Dynamic menu | ✅ Implemented |
| Non-admin redirect | ✅ Implemented |
| Lockout protection | ✅ Implemented |
| Legacy Spatie tables | Replaced with AUB schema (2026-07-06) |

See [CURRENT_STATE.md](CURRENT_STATE.md) and [NEXT_STEPS.md](NEXT_STEPS.md).
