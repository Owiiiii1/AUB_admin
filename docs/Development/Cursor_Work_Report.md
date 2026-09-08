# Cursor Work Report

## Task

Core Data Model refactor in `AUB_admin`: replace the interim academy model (`customers` as students, `course_groups` as classes) with the target domain (`AcademicYear`, `Student`, `AcademyParent`/`parents`, `AcademyClass`, `ClassLesson`, teacher assignments). Weekly schedule adapted to `AcademyClass`. Identity / API / Flutter not implemented.

## Before

- Child profile lived on kit `customers` (including father_*/mother_* columns).
- Product Class was `CourseGroup` / `course_groups`.
- Enrollment: `course_group_customer` with unique `customer_id`.
- Class program: `course_group_lesson` (group + lesson + teacher + hours).
- `scheduled_lessons.course_group_id`.
- PHP model `CourseGroup`; no `Student` / `Parent` / `AcademicYear` / `ClassLesson`.
- Students UI: `CustomersController`.

## After

| Product | PHP model | Table |
|---------|-----------|--------|
| Academic year | `AcademicYear` | `academic_years` |
| Student | `Student` | `students` |
| Parent | `AcademyParent` (`Parent` is reserved) | `parents` |
| Student ↔ Parent | pivot | `student_parent` (`relation_type`) |
| Course / direction | `Course` | `courses` (kept) |
| Class | `AcademyClass` (`Class` is reserved) | `academy_classes` |
| Enrollment | unique `student_id` | `academy_class_student` |
| Class program | `ClassLesson` | `class_lessons` |
| Teachers on a class lesson | pivot | `class_lesson_teacher` |
| Teacher | `Teacher` | `teachers.user_id` nullable unique |
| Weekly slot | `ScheduledLesson` | `scheduled_lessons.academy_class_id` |

Kit `Customer` / `customers` remain unused leftovers. URLs `/customers` and `/courses-groups` and Inertia `Customers/*` kept for UX.

## Database changes

### Created

- `academic_years`
- `students`
- `parents`
- `student_parent`
- `academy_classes`
- `academy_class_student` (unique `student_id`)
- `class_lessons` (unique year+class+lesson)
- `class_lesson_teacher`
- `teachers.user_id` (nullable unique FK)
- `activity_logs.student_id`
- `scheduled_lessons.academy_class_id`

### Dropped

- `course_group_lesson`
- `course_group_customer`
- `course_groups`
- `scheduled_lessons.course_group_id`

### Left as legacy

- `customers` (rows deleted; table kept for kit)
- `orders`, `services`, `staff`, `order_staff`
- `activity_logs.customer_id` (nulled)

### Kept / unchanged system

- `users`, `roles`, `role_menu_items`, sessions
- `teachers` (directory), `courses`, `lessons`, `lesson_teacher`, `lesson_course`
- `academy_buildings`, `academy_rooms`, `schedule_weeks`

## Migration strategy

Production had **test academy data only**. No backward-compatible copy of `customers` → `students`.

Destructive steps in `2026_09_08_200000_introduce_core_academy_data_model`:

- delete all `scheduled_lessons` rows
- drop `course_group_*` tables
- delete all `customers` rows (table remains)
- null `activity_logs.customer_id` and `orders.customer_id`
- insert AcademicYear `2026/2027` as active

**Not deleted:** admin/system `users`, roles, teachers catalog, courses, lessons, buildings/rooms.

`CoreAcademySeeder` exists for local smoke data. **Not** called from `DatabaseSeeder`. **Not** run on production.

## Models and relations

- `AcademicYear` hasMany `ClassLesson`; `current()` = `is_active` or latest `starts_at`
- `Student` belongsToMany `AcademyParent`, belongsToMany `AcademyClass`; `academyClass()` returns the single class
- `AcademyParent` belongsToMany `Student`
- `Course` hasMany `AcademyClass`
- `AcademyClass` belongsTo `Course`; belongsToMany `Student`; hasMany `ClassLesson`, `ScheduledLesson`
- `ClassLesson` belongsTo `AcademicYear`, `AcademyClass`, `Lesson`; belongsToMany `Teacher` (pivot `hours`)
- `Teacher` belongsTo `User` (nullable); belongsToMany `ClassLesson`, `Lesson`
- `ScheduledLesson` belongsTo `AcademyClass`
- `ActivityLog` belongsTo `Student` (and leftover `Customer`)

## UI impact

- Students list/profile/create/edit/photo/documents/parents: `StudentsController`; Father/Mother sections still in UI; backend writes `Parent` records + `relation_type`
- Courses/groups (`/courses-groups`): groups are `AcademyClass`; attach students via `student_ids`; lessons via `ClassLesson`
- Weekly schedule: cards and lessons use `academy_class_id`
- Activity log filters students instead of customers (prop shape `customer` kept)

## Schedule compatibility

- FK `scheduled_lessons.course_group_id` → `academy_class_id`
- `WeeklyScheduleController`, conflict service, deterministic planner, AI service, React weekly board updated
- Existing test schedule rows were wiped (test data)
- After migrate, empty weeks still work; new lessons attach to `AcademyClass`

## Tests

`tests/Feature/CoreAcademyDataModelTest.php` (sqlite `:memory:`, not production DB):

- Student belongs to one AcademyClass
- Student cannot be in two primary classes (unique `student_id`)
- Parent linked to several students
- Student has several parents
- Lesson assigned to class for AcademicYear
- Duplicate ClassLesson forbidden
- Several teachers on one ClassLesson
- ScheduledLesson belongs to AcademyClass; `course_group_id` column gone

## Commands executed

Local Windows clone: PHP is not on PATH; Vite/rolldown Windows native binding is missing. Artisan and `npm run build` were run on production after file copy.

| Command | Result |
|---------|--------|
| `php artisan migrate --force` | (production) |
| `php artisan migrate:status` | (production) |
| `php artisan route:list` | (production) |
| `php artisan test` | (production) |
| `npm run build` | (production) |
| `php artisan optimize:clear` | (production) |

## Files changed

### Created

- `app/Models/AcademicYear.php`
- `app/Models/Student.php`
- `app/Models/AcademyParent.php`
- `app/Models/AcademyClass.php`
- `app/Models/AcademyClassStudent.php`
- `app/Models/ClassLesson.php`
- `app/Http/Controllers/StudentsController.php`
- `database/migrations/2026_09_08_200000_introduce_core_academy_data_model.php`
- `database/seeders/CoreAcademySeeder.php`
- `tests/Feature/CoreAcademyDataModelTest.php`

### Modified

- Controllers: `CoursesGroupsController`, `WeeklyScheduleController`, `ActivityLogController`
- Models: `Course`, `Lesson`, `Teacher`, `ScheduledLesson`, `ActivityLog`
- Services: `ActivityLogger`, `WeeklySchedule/*`
- Routes: `routes/owl-admin-pages.php`
- UI: `CoursesGroups/Index.jsx`, `WeeklySchedule/Index.jsx`, `LessonPalette.jsx`, `TimelineLessonBlock.jsx`
- Seeders: old CourseGroup seeders now delegate to `CoreAcademySeeder`
- Docs RU+EN: CURRENT_STATE, ARCHITECTURE, DATA_MODEL_DRAFT, MODULE_ROADMAP, NEXT_STEPS, OPEN_QUESTIONS, USER_ROLES_AND_ACCESS, WEEKLY_SCHEDULE_SERVICE, DEVELOPMENT_RULES, MVP_SCOPE, README_PROJECT_OVERVIEW
- `docs/Development/Cursor_Work_Report.md`

### Deleted

- `app/Models/CourseGroup.php`

### Left unused

- `app/Http/Controllers/CustomersController.php` (not routed)
- kit `Customer` model/table

## Technical debt remaining

- Student/parent documents still on **public** disk — private storage is a required later Security Foundation step
- No AcademicYear admin UI
- URLs still `/customers` and `/courses-groups`
- `teachers.user_id` exists but no login / actor_type / API
- Enrollment workflow (statuses, history) not built
- Father/Mother UI only; extra guardians possible in DB but not in that UI
- Kit leftover `customers` / Orders / Services / Staff

## Next recommended step

**Identity implementation** (one User = one actor type: student / parent / teacher). Do not start it in this task. No REST API, Sanctum, or Flutter login until Identity.
