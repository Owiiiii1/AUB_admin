# Cursor Work Report

## Task

Student Profilo: centered identity, read-only contact fields, password/devices APIs, local language and push toggles, demo portraits matching gender and age.

## What changed

- `GET /me` student whitelist adds `phone`, `birth_date`, `residence_address`, `residence_city_province`, `residence_postal_code`. Still no tax code, medical, notes, documents.
- `PUT /me/password`, `GET /me/devices`, `DELETE /me/devices/{id}` for mobile actors. Password change revokes other devices. Current device cannot be revoked from the list.
- `aub:fill-mobile-demo` fills demo student contacts and attaches portraits when public-disk files exist.
- Flutter Student Profilo: centered identity card, read-only personal data, security (password, devices, logout), language + push (local). Push does not send FCM.

No migration.

## Tests

`AccountApiTest` plus updated `/me` key list and demo fill `/me` contact assertion.

## Deploy

Copy PHP/docs to production, `optimize:clear`, upload portraits to `storage/app/public`, run `php artisan aub:fill-mobile-demo --force`, verify `/me` contact + `photo_url`.
