# Cursor Work Report

## Task

Fill production academy data so Student / Parent / Teacher mobile screens are not empty (no published current week, zero `attendance_records`).

## What changed

Artisan `php artisan aub:fill-mobile-demo --force`:

- Publishes previous / current / next Monday–Friday weeks in `Europe/Rome`.
- Places demo lessons on the existing **Mobile Test** class for the linked teacher (`teacher@admin.com` → Martina Barbieri).
- Marks past official published/moved lessons for the class roster (present / absent / excused).
- Adds five classmates (no extra logins) and attaches them to the existing parent so Parent children is not a single row.
- Friday of the current week includes `22:00` so Student Home still has Oggi + Prossima lezione after evening local time.
- One cancelled + one moved lesson on the current week for Orario status chips.
- Demo rows tagged `notes=__mobile_demo__` (not exposed on mobile). Re-run deletes and recreates only those rows.
- Isolated week `2026-12-28` is not touched. Existing account passwords are not changed.
- `--force` required outside `testing`.

No migration. No API contract change. Flutter not deployed.

## Tests

`MobileDemoDataTest` on `aub_test`: command fills schedule + attendance; Student/Parent/Teacher GET payloads are non-empty; idempotent re-run; foreign week untouched.

Full suite on production host: **98 passed**, 0 failed, 0 errors (759 assertions).

## Deploy

Production smoke: weeks `2026-08-31`, `2026-09-07`, `2026-09-14` published; isolated `2026-12-28` kept. Student GET `/schedule` 200, Friday 4 lessons including `22:00`. Student GET `/attendance?month=2026-09` marked 26 (present 17, absent 5, excused 4).
