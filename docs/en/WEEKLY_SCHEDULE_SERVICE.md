# Weekly Schedule Service

## Admin entry

| Item | Value |
|------|-------|
| Menu label | Schedule service (`scheduleService`) |
| URL | `/schedule-service` |
| Route name | `weekly-schedule.index` |
| Legacy redirect | `/schedules`, `/weekly-schedule` → `/schedule-service` |
| Page | `resources/js/Pages/WeeklySchedule/Index.jsx` |

## Purpose

Create and manage the academy weekly timetable:

- one schedule per calendar week (Monday start);
- views: **General** (day × building × room) and **Group** (one group’s week);
- visual time axis with **30-minute** row labels;
- AI + deterministic planner place lessons on a **5-minute** planning grid;
- lesson blocks with group, subject, teacher, time;
- conflict detection; draft / publish; copy previous week; clear timeline;
- per-week work hours; per-course study window (shift).

## Data model

### `academy_buildings` / `academy_rooms`

Locations and halls (seeded). Managed under Settings → Academy.

### `schedule_weeks`

| Field | Notes |
|-------|-------|
| `week_start_date` | Unique, Monday |
| `week_end_date` | Friday of the same week (Mon–Fri board) |
| `work_starts_at`, `work_ends_at` | Working hours for the week (visual settings use 30-min steps) |
| `status` | `draft`, `published`, `locked` |
| `published_at`, `published_by` | Set on publish |

### `scheduled_lessons`

Links week, building, room, `course_group_id`, `teacher_id`, `lesson_id`, date, start/end, notes, color, status.

### `schedule_ai_runs`

Log of AI schedule runs: prompt, preferences snapshot, metrics, report, warnings, status.

### `courses.study_starts_at` / `courses.study_ends_at`

**Study window** (shift) for the whole course. All groups of that course are scheduled only inside this window. Editable in Courses & groups → course settings.

Typical shifts after migration seed:

- first shift: `08:00`–`13:00`
- second shift: `13:00`–`18:00`

Courses are balanced across shifts by weekly hour load.

### Related entities

- groups: `course_groups` (optional `color`)
- subjects: `lessons` (`duration_minutes` optional)
- teachers: `teachers`
- assignments: `course_group_lesson` — **multiple teachers** per group+lesson allowed (`hours` per teacher); eligibility via `lesson_teacher`

## Grid: visual vs planning

| Layer | Step | Used for |
|-------|------|----------|
| Visual timeline | `VISUAL_STEP_MINUTES = 30` | Row labels / board density (unchanged look) |
| Planning / AI | `GRID_STEP_MINUTES = 5` | Placement snap, breaks (e.g. 10 min), conflict packing |

Constants: `App\Services\WeeklySchedule\ScheduleConflictService`.

Blocks are positioned by exact start/end minutes, so 5-minute offsets display correctly on the 30-minute visual rows.

## AI scheduling (hybrid)

Flow:

1. User prompt in the AI assistant modal (modes: **Edit** / **Create new**).
2. Create mode clears the timeline first, then schedules.
3. LLM converts the prompt into compact **preferences** JSON (not placements).
4. `DeterministicSchedulePlanner` places sessions under hard constraints.
5. Result report + recommendations; preferences kept in `schedule_ai_runs` (not shown as “interpretation” in the user report).
6. In-modal **Instructions** lists supported levers.

### Default planner levers (examples)

| Lever | Typical preference |
|-------|-------------------|
| Lesson length | `default_session_minutes` (e.g. 120); hard rule: two identical consecutive 1h → one 2h block |
| Break between lessons | `min_break_minutes` (e.g. 10) — honored on 5-min grid |
| Max hours/day per group | `max_group_daily_minutes` |
| Max hours/day per teacher | default **480** (8h) |
| Same building per day | `same_building_per_day` |
| Compact day / max gap | `group_rules[].max_internal_gap_minutes` |
| Course shift | course `study_*` window (hard), not prompt day-half for every group |

Services:

- `App\Services\WeeklySchedule\WeeklyScheduleAiService`
- `App\Services\WeeklySchedule\DeterministicSchedulePlanner`
- `App\Services\WeeklySchedule\ScheduleConflictService`

Request timeout for AI schedule is raised in the controller (`set_time_limit(300)`) because full-week plans often exceed PHP-FPM’s default 30s.

### Recommendations button

**Apply recommendations and redistribute** does **not** mutate teacher assignments in the DB. It appends recommendation text to the prompt and re-runs **Create new**. Adding/splitting teachers must be done in Courses & groups (or data scripts).

Unplaced-hour warnings usually mean **no free slot** (study window capacity, rooms, conflicts), not always a missing teacher.

## UI layout

1. **Header** — week range, prev/next, status, settings, copy, clear, AI, publish.
2. **View tabs** — General / Group (group picker in toolbar).
3. **Lesson palette** — remaining weekly hours by group+lesson+teacher; drag onto the board.
4. **Timeline** — Mon–Fri; sticky time column; lesson blocks (drag/resize with 30-min UI snap for manual edits).
5. **AI modal** — connection status, prompt, history, instructions, confirm / partial / report.

## Conflict detection

Overlap on `[starts_at, ends_at)` for same room, teacher, or group; plus invalid room/building and time range.

Publishing is blocked while conflicts exist.

## Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/schedule-service` | `weekly-schedule.index` |
| GET | `/schedule-service/{week}/conflicts` | `weekly-schedule.conflicts` |
| POST | `/schedule-service/weeks` | `weekly-schedule.weeks.store` |
| PATCH | `/schedule-service/{week}/settings` | `weekly-schedule.settings.update` |
| POST | `/schedule-service/{week}/clear` | `weekly-schedule.clear` |
| POST | `/schedule-service/{week}/ai-schedule` | `weekly-schedule.ai-schedule` |
| POST | `/schedule-service/{week}/lessons` | `weekly-schedule.lessons.store` |
| PATCH | `/schedule-service/{week}/lessons/{lesson}` | `weekly-schedule.lessons.update` |
| DELETE | `/schedule-service/{week}/lessons/{lesson}` | `weekly-schedule.lessons.destroy` |
| POST | `/schedule-service/{week}/lessons/{lesson}/duplicate` | `weekly-schedule.lessons.duplicate` |
| POST | `/schedule-service/{week}/publish` | `weekly-schedule.publish` |
| POST | `/schedule-service/{week}/copy-previous` | `weekly-schedule.copy-previous` |

Write routes require `can.write`.

## Known limitations

- Board is Mon–Fri only (no Saturday/Sunday).
- Manual drag/resize still snaps to 30 minutes in the UI; AI uses 5 minutes.
- PDF export button is a placeholder.
- Course study windows are narrow (≈5h); large courses can hit room capacity even with enough teachers.
- `locked` week status exists in schema but is not set by UI yet.

## Implementation status

**Implemented (2026-07-20):** DB tables, buildings/rooms, CRUD board, conflicts, publish/copy/clear, work hours, AI hybrid planner, AI run log, course study windows, multi-teacher group lessons, General/Group views, activity logging.

## Future calendar (not implemented)

This board schedules **regular `scheduled_lessons` only**. **DECIDED for the future:** additional-group schedules (production / rehearsal / other; **not** a `Class`) must appear on the child’s **unified calendar**. Productions are late future. Calendar implementation and conflict detection remain OPEN.

Tech Lead has **not** chosen unified-session vs aggregating-layer architecture (Variant A vs B). Do not change `scheduled_lessons` in this documentation task. See [OPEN_QUESTIONS.md](OPEN_QUESTIONS.md) and [ARCHITECTURE.md](ARCHITECTURE.md).
