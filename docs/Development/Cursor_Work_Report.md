# Cursor Work Report

## Task

Public privacy policy, public deletion requests, and in-app account deletion.

## Delivered

- `/privacy` and `/data-deletion` are public. Italian is the reference text
- A public form stores a pending request and does not delete the account
- The app path is Profile → Privacy e dati → Elimina account for student, parent, and teacher
- An administrator completes the request. Parent completion does not delete the child. Student and teacher history that has no defined retention period stays

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Change the mobile application id to it.accademiaucraina.aub.

## Delivered

- Android applicationId, iOS and macOS bundle id, and the Linux application id are `it.accademiaucraina.aub`
- The previous id `com.owlsolutions.aub` is no longer the app id

FUNCTIONALITY_MATRIX updated: no. The identifier changed. What each role can do did not.

## Previous task

Move production to staff.accademiaucraina.it.

## Delivered

- The live staff site and API are on the new host. The previous host is in maintenance and was not deleted
- The app’s default API address is the new host. The app was not published

FUNCTIONALITY_MATRIX updated: yes. The staff website address changed. Capabilities did not.

## Previous task

Clear Martina Barbieri’s check-in for today and ship app 1.1.6.

## Delivered

- Today’s “On lesson” mark for Martina Barbieri was removed so the lesson can be started again
- App version name is 1.1.6, build 9. Android and iOS 1.1.6 are active. Older versions stay active

FUNCTIONALITY_MATRIX updated: no. The capability did not change; one check-in row was removed and the installed version number moved to 1.1.6.

## Previous task

Let a teacher keep a private note on a student.

## Delivered

- The opened student in Teaching has a note field
- The note is stored per teacher and is not shown to another teacher of the same student

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Show the same lesson status on the schedule and on the opened roster.

## Delivered

- A checked-in lesson shows “On lesson” on the teacher schedule card
- “Lesson not started” and “Lesson missed” stay off once the teacher has checked in
- Closing the roster reloads the schedule

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Center the hall map on the editor and draw the check-in radius.

## Delivered

- The map picker starts at the person’s current location when the hall has no point yet
- A circle around the chosen point uses the academy check-in radius

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Make hall rows open the editor and show lesson time badges.

## Delivered

- Hall rows open the editor. Delete sits in that dialog and requires typing DELETE
- Hall location is one field: coordinates or a Google Maps link
- A teacher schedule card shows “Lesson not started” or a red “Lesson missed” badge at the top right

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Stretch the teacher lesson info card and mark lessons that have not started.

## Delivered

- The lesson info card on the opened roster fills the screen width
- A teacher schedule card says the lesson has not started while its start time is still ahead

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Add a map picker for hall coordinates in academy settings.

## Delivered

- The hall form keeps manual latitude and longitude and adds “Choose on map”
- A click or drag on the OpenStreetMap map fills the coordinate fields

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Fix the teacher directory header and make the check-in lead time an academy setting.

## Delivered

- The completed-lesson columns sit under one header: total, attended, missed
- Settings → Academy → General stores how many minutes before the start a teacher may check in. 0 allows a check-in at any time

FUNCTIONALITY_MATRIX updated: yes

## Previous task

Raise the app version to 1.1.5 and stop the teacher group name from overflowing the field.

## Delivered

- App version name is 1.1.5, build 8. About and the update check use that name. Release date is 24 Sep 2026
- The group field on Teaching ellipsizes a long “group · course” label instead of painting past the field

FUNCTIONALITY_MATRIX updated: no. Existing screens only; no new capability.

## Previous task

Teacher GPS lesson check-in: hall coordinates, academy radius, start-lesson button, and the admin attendance journal.

## Delivered

- Halls store latitude and longitude. Settings → Academy → General stores one check-in radius (default 50 m)
- `POST /api/v1/teacher/lessons/{lesson}/check-in` accepts a fix only for the assigned teacher, inside the 15-minute window, inside the saved radius
- The teacher app shows Start lesson on the roster and On lesson on the schedule card after reload
- The teacher directory shows completed / attended / missed. The profile opens a lesson journal. Cancelled lessons stay out of the totals

FUNCTIONALITY_MATRIX updated: yes
