<?php

namespace App\Services\WeeklySchedule;

use App\Models\AcademyBuilding;
use App\Models\ScheduledLesson;
use App\Models\ScheduleWeek;
use App\Services\Ai\AiCompletionService;
use App\Services\Ai\AiJsonParser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WeeklyScheduleAiService
{
    public const MIN_BREAK_MINUTES = 30;

    public const TEACHER_DAILY_HEAVY_MINUTES = 480;

    public const TEACHER_WEEKLY_HEAVY_MINUTES = 1600;

    public function __construct(
        private readonly AiCompletionService $ai,
        private readonly ScheduleConflictService $conflicts,
        private readonly DeterministicSchedulePlanner $planner,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $groupLessonCards
     * @param  list<array<string, mixed>>  $buildings
     * @param  list<array<string, mixed>>  $days
     * @param  list<array<string, mixed>>  $scheduledLessons
     * @return array<string, mixed>
     */
    public function analyzeCapacity(
        array $groupLessonCards,
        array $buildings,
        array $days,
        array $scheduledLessons,
        string $mode,
        ?ScheduleWeek $week = null,
    ): array {
        $cardsToPlace = $this->cardsToPlace($groupLessonCards, $mode);
        $neededMinutes = array_sum(array_map(
            fn (array $card): int => $this->minutesToPlaceForCard($card, $mode),
            $cardsToPlace,
        ));
        $roomCount = $this->roomCount($buildings);
        $bounds = ScheduleConflictService::resolveBounds($week);
        $gridMinutes = ScheduleConflictService::gridSpanMinutes($bounds['start'], $bounds['end']);
        $totalCapacity = $gridMinutes * max(1, $roomCount) * max(1, count($days));

        $occupiedMinutes = $mode === 'edit'
            ? array_sum(array_map(fn (array $lesson): int => $this->lessonDurationMinutes($lesson), $scheduledLessons))
            : 0;

        $freeCapacity = max(0, $totalCapacity - $occupiedMinutes);

        return [
            'cards_count' => count($cardsToPlace),
            'needed_minutes' => $neededMinutes,
            'free_capacity_minutes' => $freeCapacity,
            'likely_insufficient' => $neededMinutes > $freeCapacity,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $groupLessonCards
     * @param  list<array<string, mixed>>  $buildings
     * @param  list<array<string, mixed>>  $days
     * @param  list<array<string, mixed>>  $scheduledLessons
     * @return array<string, mixed>
     */
    public function schedule(
        ScheduleWeek $week,
        array $groupLessonCards,
        array $buildings,
        array $days,
        array $scheduledLessons,
        string $prompt,
        string $mode,
        string $locale,
        bool $allowPartial,
    ): array {
        $cardsToPlace = $this->normalizeCardsForMode(
            $this->cardsToPlace($groupLessonCards, $mode),
            $mode,
        );
        $analysis = $this->analyzeCapacity($groupLessonCards, $buildings, $days, $scheduledLessons, $mode, $week);

        if ($cardsToPlace === []) {
            return [
                'status' => 'success',
                'report' => $this->localizedMessage($locale, 'nothing_to_place'),
                'placed_count' => 0,
                'skipped_count' => 0,
                'warnings' => [],
            ];
        }

        $frozenLessons = $mode === 'edit' ? $scheduledLessons : [];
        $preferences = $this->requestPlanningPreferences(
            $prompt,
            $days,
            $locale,
            $week,
            $groupLessonCards,
        );
        $plan = $this->planner->plan(
            $cardsToPlace,
            $buildings,
            $days,
            $frozenLessons,
            $preferences,
        );

        if (($plan['metrics']['unplaced_minutes'] ?? 0) > 0 && ! $allowPartial) {
            return [
                'status' => 'needs_partial_confirmation',
                'message' => $this->plannedPartialConfirmationMessage($locale, $plan['metrics']),
                'analysis' => array_merge($analysis, $plan['metrics'], [
                    'preferences' => [
                        'summary' => (string) ($preferences['summary'] ?? ''),
                        'group_rules' => is_array($preferences['group_rules'] ?? null)
                            ? $preferences['group_rules']
                            : [],
                        'preferred_start' => $preferences['preferred_start'] ?? null,
                        'preferred_end' => $preferences['preferred_end'] ?? null,
                        'default_session_minutes' => $preferences['default_session_minutes'] ?? null,
                        'min_break_minutes' => $preferences['min_break_minutes'] ?? null,
                        'max_group_daily_minutes' => $preferences['max_group_daily_minutes'] ?? null,
                        'same_building_per_day' => $preferences['same_building_per_day'] ?? null,
                        'day_half_split' => $preferences['day_half_split'] ?? null,
                    ],
                ]),
                'prompt' => $prompt,
            ];
        }

        $result = $this->applyDeterministicPlan(
            $week,
            $plan['placements'],
            $cardsToPlace,
            $mode,
            $locale,
        );

        $week->refresh();
        $postScheduleAnalysis = $this->analyzeResultingSchedule($week, $groupLessonCards);
        $recommendations = $this->buildRecommendations(
            $postScheduleAnalysis,
            [],
            $prompt,
            $locale,
        );

        $report = $this->localizedMessage($locale, 'deterministic_plan_summary', [
            'requested_blocks' => $plan['metrics']['requested_blocks'] ?? 0,
            'placed_blocks' => $plan['metrics']['placed_blocks'] ?? 0,
            'requested_hours' => round(((int) ($plan['metrics']['requested_minutes'] ?? 0)) / 60, 1),
            'placed_hours' => round(((int) ($plan['metrics']['placed_minutes'] ?? 0)) / 60, 1),
            'unplaced_hours' => round(((int) ($plan['metrics']['unplaced_minutes'] ?? 0)) / 60, 1),
            'used_days' => $plan['metrics']['used_days'] ?? 0,
            'available_days' => $plan['metrics']['available_days'] ?? 0,
        ]);

        // Keep preferences in analysis/logs only — do not append interpretation to the user-facing report.
        $preferenceSummary = trim((string) ($preferences['summary'] ?? ''));
        $groupRules = is_array($preferences['group_rules'] ?? null) ? $preferences['group_rules'] : [];

        return [
            'status' => 'success',
            'report' => $report,
            'recommendations' => $recommendations,
            'placed_count' => $result['placed_count'],
            'skipped_count' => $result['skipped_count'],
            'warnings' => $this->planWarnings($plan['unplaced'], $locale),
            'analysis' => array_merge($analysis, $plan['metrics'], [
                'preferences' => [
                    'summary' => $preferenceSummary,
                    'group_rules' => $groupRules,
                    'preferred_start' => $preferences['preferred_start'] ?? null,
                    'preferred_end' => $preferences['preferred_end'] ?? null,
                    'default_session_minutes' => $preferences['default_session_minutes'] ?? null,
                    'min_break_minutes' => $preferences['min_break_minutes'] ?? null,
                    'max_group_daily_minutes' => $preferences['max_group_daily_minutes'] ?? null,
                    'same_building_per_day' => $preferences['same_building_per_day'] ?? null,
                    'day_half_split' => $preferences['day_half_split'] ?? null,
                ],
            ]),
            'prompt' => $prompt,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $groupLessonCards
     * @return list<array<string, mixed>>
     */
    private function cardsToPlace(array $groupLessonCards, string $mode): array
    {
        return array_values(array_filter(
            $groupLessonCards,
            fn (array $card): bool => $this->minutesToPlaceForCard($card, $mode) > 0,
        ));
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function minutesToPlaceForCard(array $card, string $mode): int
    {
        if ($mode === 'create') {
            return (int) ($card['weekly_minutes'] ?? 0);
        }

        return (int) ($card['remaining_minutes'] ?? 0);
    }

    /**
     * @param  list<array<string, mixed>>  $cardsToPlace
     * @return list<array<string, mixed>>
     */
    private function normalizeCardsForMode(array $cardsToPlace, string $mode): array
    {
        return array_map(function (array $card) use ($mode): array {
            $minutes = $this->minutesToPlaceForCard($card, $mode);

            return array_merge($card, [
                'remaining_minutes' => $minutes,
            ]);
        }, $cardsToPlace);
    }

    /**
     * @param  list<array<string, mixed>>  $buildings
     */
    private function roomCount(array $buildings): int
    {
        return array_sum(array_map(
            static fn (array $building): int => count($building['rooms'] ?? []),
            $buildings,
        ));
    }

    /**
     * @param  array<string, mixed>  $lesson
     */
    private function lessonDurationMinutes(array $lesson): int
    {
        $start = $this->timeToMinutes(substr((string) ($lesson['starts_at'] ?? '00:00'), 0, 5));
        $end = $this->timeToMinutes(substr((string) ($lesson['ends_at'] ?? '00:00'), 0, 5));

        return max(0, $end - $start);
    }

    /**
     * @param  list<array<string, mixed>>  $days
     * @return array<string, mixed>
     */
    /**
     * @param  list<array<string, mixed>>  $days
     * @param  list<array<string, mixed>>  $groupLessonCards
     * @return array<string, mixed>
     */
    private function requestPlanningPreferences(
        string $prompt,
        array $days,
        string $locale,
        ScheduleWeek $week,
        array $groupLessonCards = [],
    ): array {
        $bounds = ScheduleConflictService::resolveBounds($week);
        $defaults = [
            'day_order' => array_values(array_column($days, 'date')),
            'avoid_dates' => [],
            'grid_start' => $bounds['start'],
            'grid_end' => $bounds['end'],
            'preferred_start' => $bounds['start'],
            'preferred_end' => $bounds['end'],
            'max_teacher_daily_minutes' => self::TEACHER_DAILY_HEAVY_MINUTES,
            'max_group_daily_minutes' => 360,
            'min_break_minutes' => self::MIN_BREAK_MINUTES,
            'default_session_minutes' => null,
            'day_half_split' => '13:00',
            'same_building_per_day' => true,
            'group_rules' => [],
            'summary' => '',
        ];

        if (trim($prompt) === '') {
            return $defaults;
        }

        $language = match ($locale) {
            'ru' => 'Russian',
            'uk' => 'Ukrainian',
            'en' => 'English',
            default => 'Italian',
        };
        $availableDays = array_map(static fn (array $day): array => [
            'date' => $day['date'] ?? '',
            'label' => $day['label'] ?? '',
        ], $days);
        $availableGroups = array_values(array_unique(array_filter(array_map(
            static fn (array $card): string => trim((string) ($card['group_name'] ?? '')),
            $groupLessonCards,
        ))));

        $system = <<<PROMPT
Convert a user's school timetable request into compact scheduling preferences.
Do not generate lesson placements. Return only a valid JSON object with:
{
  "day_order": ["YYYY-MM-DD"],
  "avoid_dates": ["YYYY-MM-DD"],
  "preferred_start": "HH:MM",
  "preferred_end": "HH:MM",
  "max_teacher_daily_minutes": 480,
  "max_group_daily_minutes": 360,
  "min_break_minutes": 30,
  "default_session_minutes": 120,
  "day_half_split": "13:00",
  "same_building_per_day": true,
  "group_rules": [
    {
      "match": "exact or partial group name from available_groups",
      "day_half": "morning|afternoon|consistent",
      "max_internal_gap_minutes": 30,
      "preferred_start": "HH:MM",
      "preferred_end": "HH:MM"
    }
  ],
  "summary": "Short interpretation in {$language}"
}

Rules:
- Use only dates and group names supplied by the user context.
- Keep preferred_start/preferred_end inside the provided grid bounds.
- If the user asks that a specific class/group has no large gaps between lessons, set group_rules[].max_internal_gap_minutes (typically 30).
- If the user asks that a group is always in the first half of the day OR always in the second half (not mixed across the week), set group_rules[].day_half to "consistent" only for explicitly named groups. Prefer course study windows when available — do NOT force day_half=consistent for every group by default.
- IMPORTANT: Different courses already have study windows (e.g. 08:00-13:00 vs 13:00-18:00). Do not override those with a global morning bias.
- Use "morning" / "afternoon" only when the user forces one half explicitly for a named group.
- day_half_split is the boundary between first and second half (default 13:00).
- If the user asks max N hours/day per class, set max_group_daily_minutes = N*60.
- If the user asks break of N minutes between lessons, set min_break_minutes = N.
- If the user asks a class to stay in one building/location per day, set same_building_per_day = true.
- Teacher daily load default is 480 minutes (8 hours). Raise max_teacher_daily_minutes only if the user asks for more.
- If the user requests lessons lasting N hours/minutes (e.g. "2 hours", "по 2 часа", "due ore"), ALWAYS set default_session_minutes = N*60 (typically 120). This is required for double lessons / paired blocks.
- Keep safe defaults when the request does not specify a preference.
PROMPT;

        $content = json_encode([
            'request' => $prompt,
            'available_days' => $availableDays,
            'available_groups' => $availableGroups,
            'grid' => [
                'start' => $bounds['start'],
                'end' => $bounds['end'],
                'step_minutes' => ScheduleConflictService::GRID_STEP_MINUTES,
            ],
            'defaults' => $defaults,
        ], JSON_UNESCAPED_UNICODE);

        if ($content === false) {
            return $this->enrichPreferencesFromPrompt($prompt, $availableGroups, $defaults);
        }

        try {
            $raw = $this->ai->complete([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $content],
            ], 2048);
            $parsed = AiJsonParser::decodeObject($raw, 'AI preferences returned invalid JSON.');

            $merged = array_merge($defaults, array_intersect_key($parsed, array_flip([
                'day_order',
                'avoid_dates',
                'preferred_start',
                'preferred_end',
                'max_teacher_daily_minutes',
                'max_group_daily_minutes',
                'min_break_minutes',
                'default_session_minutes',
                'day_half_split',
                'same_building_per_day',
                'group_rules',
                'summary',
            ])), [
                'grid_start' => $bounds['start'],
                'grid_end' => $bounds['end'],
            ]);

            return $this->enrichPreferencesFromPrompt($prompt, $availableGroups, $merged);
        } catch (\Throwable) {
            return $this->enrichPreferencesFromPrompt($prompt, $availableGroups, $defaults);
        }
    }

    /**
     * Fallback/enrichment when the model misses compactness / day-half / capacity rules.
     *
     * @param  list<string>  $availableGroups
     * @param  array<string, mixed>  $preferences
     * @return array<string, mixed>
     */
    private function enrichPreferencesFromPrompt(string $prompt, array $availableGroups, array $preferences): array
    {
        $rules = is_array($preferences['group_rules'] ?? null) ? $preferences['group_rules'] : [];
        $normalizedPrompt = mb_strtolower($prompt);

        $wantsCompact = (bool) preg_match(
            '/перерыв|перерв|gap|compact|senza\s+buchi|senza\s+pause\s+lunghe|no\s+large\s+gap|без\s+больш|не\s+больше\s+\d+\s*мин/ui',
            $prompt,
        );
        $wantsHalf = (bool) preg_match(
            '/половин|half|mattina|pomeriggio|morning|afternoon|prima\s+met[aà]|seconda\s+met[aà]|перв(ая|ой)\s+половин|втор(ая|ой)\s+половин/ui',
            $prompt,
        );
        $wantsHalfForAll = false; // Course study windows own the shift; do not blanket-lock every group.

        if (preg_match('/(?:перерыв|break|pausa)[^\d]{0,40}?(\d+)\s*мин/ui', $prompt, $breakMatch) === 1
            || preg_match('/(\d+)\s*мин(?:ут(?:ы|у)?)?[^\n]{0,40}?(?:перерыв|break|pausa)/ui', $prompt, $breakMatch) === 1) {
            $preferences['min_break_minutes'] = max(0, min(120, (int) $breakMatch[1]));
        }

        if (preg_match('/(?:макс(?:имум)?|max|non\s+pi[uù]\s+di)\s*(\d+)\s*(?:ч|h|ore|hours?)/ui', $prompt, $hoursMatch) === 1) {
            $preferences['max_group_daily_minutes'] = max(120, min(600, ((int) $hoursMatch[1]) * 60));
        }

        // Double lessons / preferred block length (must not depend on AI remembering the field).
        $sessionMinutes = $this->detectDefaultSessionMinutes($prompt);
        if ($sessionMinutes !== null) {
            $preferences['default_session_minutes'] = $sessionMinutes;
        } elseif (
            ! isset($preferences['default_session_minutes'])
            || $preferences['default_session_minutes'] === null
            || (int) $preferences['default_session_minutes'] <= 0
        ) {
            $preferences['default_session_minutes'] = null;
        } else {
            $preferences['default_session_minutes'] = max(
                ScheduleConflictService::GRID_STEP_MINUTES,
                min(360, (int) $preferences['default_session_minutes']),
            );
        }

        if (preg_match('/(?:одно|один)\s+(?:здан|локац|sede|building)|same\s+building|una\s+sede|одной\s+локац/ui', $prompt) === 1) {
            $preferences['same_building_per_day'] = true;
        }

        // Day-half mode must use the full grid; a morning-biased preferred_end would block afternoons.
        if ($wantsHalf) {
            $preferences['preferred_start'] = $preferences['grid_start'] ?? ($preferences['preferred_start'] ?? '08:00');
            $preferences['preferred_end'] = $preferences['grid_end'] ?? ($preferences['preferred_end'] ?? '18:00');
        }

        if (! $wantsCompact && ! $wantsHalf) {
            $preferences['group_rules'] = $rules;

            return $preferences;
        }

        $matchedGroups = [];
        if ($wantsHalfForAll) {
            $matchedGroups = $availableGroups;
        } else {
            foreach ($availableGroups as $groupName) {
                $needle = mb_strtolower(trim($groupName));
                if ($needle !== '' && str_contains($normalizedPrompt, $needle)) {
                    $matchedGroups[] = $groupName;
                }
            }

            if ($matchedGroups === [] && preg_match('/\b(\d+)\s*anno\b/ui', $prompt, $annoMatch) === 1) {
                foreach ($availableGroups as $groupName) {
                    if (preg_match('/\b'.preg_quote($annoMatch[1], '/').'\s*anno\b/ui', $groupName) === 1) {
                        $matchedGroups[] = $groupName;
                    }
                }
            }

            // Named groups only — not "every class" (course study windows cover shifts).
            if ($matchedGroups === [] && $wantsHalf) {
                $matchedGroups = [];
            }
        }

        if ($matchedGroups === []) {
            $preferences['group_rules'] = $rules;

            return $preferences;
        }

        $gapMinutes = 30;
        if (preg_match('/(?:не\s+больше|макс(?:имум)?|max|al\s+massimo)\s*(\d+)\s*мин/ui', $prompt, $gapMatch) === 1) {
            $gapMinutes = max(0, min(240, (int) $gapMatch[1]));
        }

        $existingMatches = array_map(
            static fn (array $rule): string => mb_strtolower(trim((string) ($rule['match'] ?? ''))),
            $rules,
        );

        foreach ($matchedGroups as $groupName) {
            $key = mb_strtolower($groupName);
            if (in_array($key, $existingMatches, true)) {
                foreach ($rules as $index => $rule) {
                    if (mb_strtolower(trim((string) ($rule['match'] ?? ''))) !== $key) {
                        continue;
                    }
                    if ($wantsCompact && ! isset($rule['max_internal_gap_minutes'])) {
                        $rules[$index]['max_internal_gap_minutes'] = $gapMinutes;
                    }
                    if ($wantsHalf && empty($rule['day_half'])) {
                        $rules[$index]['day_half'] = 'consistent';
                    }
                }

                continue;
            }

            $rule = ['match' => $groupName];
            if ($wantsCompact) {
                $rule['max_internal_gap_minutes'] = $gapMinutes;
            }
            if ($wantsHalf) {
                $rule['day_half'] = 'consistent';
            }
            $rules[] = $rule;
        }

        $preferences['group_rules'] = $rules;
        if (($preferences['summary'] ?? '') === '') {
            $preferences['summary'] = 'Group rules: '.implode(', ', array_slice($matchedGroups, 0, 8))
                .(count($matchedGroups) > 8 ? '…' : '');
        }

        return $preferences;
    }

    /**
     * Detect preferred lesson block length from the user prompt.
     * Ignores daily/weekly hour caps like "максимум 6 часов в день".
     */
    private function detectDefaultSessionMinutes(string $prompt): ?int
    {
        $patterns = [
            // "уроки … по 2 часа", "lessons of 2 hours", "lezioni di 2 ore"
            '/(?:уроки|пары|lessons?|lezioni|blocchi)[^\n.]{0,80}?(?:по|di|of| lasting)?\s*(\d+)\s*(?:час(?:а|ов)?|h\b|ore|hours?)/ui',
            // "преимущественно по 2 часа", "preferibilmente 2 ore"
            '/(?:преимущественно|prefer(?:ably)?|preferibilmente|di\s+preferenza)[^\n.]{0,40}?(?:по\s*)?(\d+)\s*(?:час(?:а|ов)?|h\b|ore|hours?)/ui',
            // "по 2 часа(", "2 часа(если есть меньше"
            '/(?:^|[^\d])по\s*(\d+)\s*(?:час(?:а|ов)?|ore|hours?)/ui',
            '/(\d+)\s*(?:час(?:а|ов)?|ore|hours?)\s*\(/ui',
            // explicit minutes for a lesson/block
            '/(?:урок|пару|lesson|lezione|блок|block|session)[^\n.]{0,40}?(\d+)\s*мин/ui',
            '/(\d+)\s*мин(?:ут(?:ы|у)?)?[^\n.]{0,20}?(?:урок|пару|lesson|lezione)/ui',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $prompt, $match) !== 1) {
                continue;
            }

            $value = (int) ($match[1] ?? 0);
            if ($value <= 0) {
                continue;
            }

            // Values 1–6 are treated as hours; 30–360 as minutes.
            $minutes = $value <= 6 ? $value * 60 : $value;
            if ($minutes < ScheduleConflictService::GRID_STEP_MINUTES || $minutes > 360) {
                continue;
            }

            return $minutes;
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $placements
     * @param  list<array<string, mixed>>  $cards
     * @return array{placed_count: int, skipped_count: int}
     */
    private function applyDeterministicPlan(
        ScheduleWeek $week,
        array $placements,
        array $cards,
        string $mode,
        string $locale,
    ): array {
        $cardsByKey = [];
        foreach ($cards as $card) {
            $cardsByKey[(string) $card['key']] = $card;
        }

        DB::transaction(function () use ($week, $placements, $cardsByKey, $mode, $locale): void {
            if ($mode === 'create') {
                $week->scheduledLessons()->delete();
            }

            foreach ($placements as $placement) {
                $card = $cardsByKey[(string) ($placement['card_key'] ?? '')] ?? null;
                if ($card === null) {
                    throw new RuntimeException(
                        $this->localizedMessage($locale, 'unknown_card', [
                            'key' => (string) ($placement['card_key'] ?? ''),
                            'index' => 0,
                        ]),
                    );
                }

                $payload = [
                    'schedule_week_id' => $week->id,
                    'lesson_date' => (string) $placement['lesson_date'],
                    'starts_at' => (string) $placement['starts_at'],
                    'ends_at' => (string) $placement['ends_at'],
                    'academy_building_id' => (int) $placement['academy_building_id'],
                    'academy_room_id' => (int) $placement['academy_room_id'],
                    'course_group_id' => (int) $card['course_group_id'],
                    'teacher_id' => (int) $card['teacher_id'],
                    'lesson_id' => (int) $card['lesson_id'],
                    'status' => ScheduledLesson::STATUS_SCHEDULED,
                ];

                $conflicts = $this->conflicts->validatePayload($payload);
                if ($conflicts !== []) {
                    throw new RuntimeException(
                        $this->localizedMessage($locale, 'conflict', [
                            'key' => (string) $card['key'],
                            'message' => collect($conflicts)->pluck('message')->first() ?? 'Conflict',
                        ]),
                    );
                }

                $week->scheduledLessons()->create($payload);
            }
        });

        return [
            'placed_count' => count($placements),
            'skipped_count' => 0,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $unplaced
     * @return list<string>
     */
    private function planWarnings(array $unplaced, string $locale): array
    {
        if ($unplaced === []) {
            return [];
        }

        $byTeacher = [];
        foreach ($unplaced as $item) {
            $teacher = (string) ($item['teacher_name'] ?? '—');
            $byTeacher[$teacher] = ($byTeacher[$teacher] ?? 0) + (int) ($item['minutes'] ?? 0);
        }
        arsort($byTeacher);

        $warnings = [];
        foreach ($byTeacher as $teacher => $minutes) {
            $warnings[] = $this->localizedMessage($locale, 'unplaced_teacher_load', [
                'teacher' => $teacher,
                'hours' => round($minutes / 60, 1),
            ]);
        }

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function plannedPartialConfirmationMessage(string $locale, array $metrics): string
    {
        return $this->localizedMessage($locale, 'planned_partial_confirm', [
            'requested_hours' => round(((int) ($metrics['requested_minutes'] ?? 0)) / 60, 1),
            'placed_hours' => round(((int) ($metrics['placed_minutes'] ?? 0)) / 60, 1),
            'unplaced_hours' => round(((int) ($metrics['unplaced_minutes'] ?? 0)) / 60, 1),
            'days' => (int) ($metrics['used_days'] ?? 0),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $cardsToPlace
     * @param  list<array<string, mixed>>  $buildings
     * @param  list<array<string, mixed>>  $days
     * @param  list<array<string, mixed>>  $frozenLessons
     * @return array<string, mixed>
     */
    private function buildContext(
        ScheduleWeek $week,
        array $cardsToPlace,
        array $buildings,
        array $days,
        array $frozenLessons,
        string $mode,
        bool $allowPartial,
        array $analysis,
    ): array {
        $rooms = [];
        foreach ($buildings as $building) {
            foreach ($building['rooms'] ?? [] as $room) {
                $rooms[] = [
                    'id' => $room['id'],
                    'name' => $room['name'],
                    'building_id' => $building['id'],
                    'building_name' => $building['name'],
                ];
            }
        }

        $bounds = ScheduleConflictService::resolveBounds($week);

        return [
            'week' => [
                'id' => $week->id,
                'start_date' => $week->week_start_date->format('Y-m-d'),
                'end_date' => $week->week_end_date->format('Y-m-d'),
            ],
            'mode' => $mode,
            'allow_partial' => $allowPartial,
            'analysis' => $analysis,
            'grid' => [
                'start' => $bounds['start'],
                'end' => $bounds['end'],
                'step_minutes' => ScheduleConflictService::GRID_STEP_MINUTES,
            ],
            'days' => $days,
            'rooms' => $rooms,
            'cards_to_place' => array_map(static fn (array $card): array => [
                'key' => $card['key'],
                'course_group_id' => $card['course_group_id'],
                'lesson_id' => $card['lesson_id'],
                'teacher_id' => $card['teacher_id'],
                'group_name' => $card['group_name'],
                'course_name' => $card['course_name'],
                'discipline' => $card['discipline'],
                'lesson_name' => $card['lesson_name'],
                'teacher_name' => $card['teacher_name'],
                'remaining_minutes' => $card['remaining_minutes'],
                'weekly_minutes' => $card['weekly_minutes'],
                'default_session_minutes' => $card['duration_minutes'] ?? 60,
                'available_teachers' => $card['teachers'] ?? [],
            ], $cardsToPlace),
            'frozen_lessons' => array_map(static fn (array $lesson): array => [
                'id' => $lesson['id'],
                'lesson_date' => $lesson['lesson_date'],
                'starts_at' => $lesson['starts_at'],
                'ends_at' => $lesson['ends_at'],
                'academy_building_id' => $lesson['academy_building_id'],
                'academy_room_id' => $lesson['academy_room_id'],
                'course_group_id' => $lesson['course_group_id'],
                'teacher_id' => $lesson['teacher_id'],
                'lesson_id' => $lesson['lesson_id'],
                'group_name' => $lesson['group_name'],
                'course_name' => $lesson['course_name'],
                'lesson_name' => $lesson['subject_name'],
                'teacher_name' => $lesson['teacher_name'],
            ], $frozenLessons),
            'rules' => [
                'time_step_minutes' => ScheduleConflictService::GRID_STEP_MINUTES,
                'min_break_between_lessons_minutes' => self::MIN_BREAK_MINUTES,
                'no_room_overlap' => true,
                'no_teacher_overlap' => true,
                'no_group_overlap' => true,
                'frozen_lessons_must_not_change' => $mode === 'edit',
                'each_card_total_minutes_must_match_remaining' => true,
                'session_duration_multiple_of' => ScheduleConflictService::GRID_STEP_MINUTES,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function requestAiPlan(array $context, string $prompt, string $locale): string
    {
        $language = match ($locale) {
            'ru' => 'Russian',
            'uk' => 'Ukrainian',
            'en' => 'English',
            default => 'Italian',
        };

        $system = <<<PROMPT
You are a school weekly timetable planner for an academy with multiple courses (AUB, TAM, Carcano, etc.).
Plan lesson blocks on a Mon-Sat grid using the provided rooms.

Hard constraints:
- Start/end times must align to {$context['grid']['step_minutes']}-minute steps within {$context['grid']['start']}-{$context['grid']['end']}.
- Never double-book a room, teacher, or student group at overlapping times.
- In edit mode, frozen_lessons are fixed and must not be moved or removed.
- For each card in cards_to_place, schedule exactly its remaining_minutes total across one or more blocks.
- Each block duration must be a multiple of {$context['grid']['step_minutes']} minutes.
- Leave at least {$context['rules']['min_break_between_lessons_minutes']} minutes gap between consecutive lessons for the same teacher and for the same group on the same day when possible.
- Spread lessons across days and rooms to maximize slot usage.

If allow_partial is true and capacity is insufficient, prioritize cards with the largest remaining_minutes and place as many as possible. List unplaced cards in unplaced_cards.

Return ONLY a valid JSON object. Do not wrap it in markdown fences or add any text before or after the JSON.
{
  "placements": [
    {
      "card_key": "groupId-lessonId-teacherId",
      "lesson_date": "YYYY-MM-DD",
      "starts_at": "HH:MM",
      "ends_at": "HH:MM",
      "academy_building_id": 1,
      "academy_room_id": 2,
      "duration_minutes": 90
    }
  ],
  "report": "Human-readable summary in {$language}",
  "warnings": ["..."],
  "recommendations": ["Actionable improvement suggestions in {$language}"],
  "unplaced_cards": [{"card_key":"...", "reason":"..."}]
}

The recommendations array must analyze teacher/group/room load, gaps, unplaced hours, and available alternate teachers from cards_to_place.teachers / group_lesson_options. Example: if one teacher has excessive weekly load while another teacher is available for the same group+lesson, recommend assigning the second teacher and splitting hours.
PROMPT;

        $userContent = json_encode([
            'user_prompt' => $prompt,
            'scheduling_context' => $this->compactSchedulingContext($context),
        ], JSON_UNESCAPED_UNICODE);

        if ($userContent === false) {
            throw new RuntimeException('Failed to encode scheduling context.');
        }

        return $this->ai->complete([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userContent],
        ], 16384);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function compactSchedulingContext(array $context): array
    {
        return [
            'week' => $context['week'] ?? [],
            'mode' => $context['mode'] ?? 'edit',
            'allow_partial' => $context['allow_partial'] ?? false,
            'analysis' => $context['analysis'] ?? [],
            'grid' => $context['grid'] ?? [],
            'days' => array_map(static fn (array $day): array => [
                'date' => $day['date'] ?? '',
                'label' => $day['label'] ?? '',
            ], $context['days'] ?? []),
            'rooms' => array_map(static fn (array $room): array => [
                'id' => $room['id'] ?? null,
                'name' => $room['name'] ?? '',
                'building_id' => $room['building_id'] ?? null,
            ], $context['rooms'] ?? []),
            'cards_to_place' => array_map(static fn (array $card): array => [
                'key' => $card['key'] ?? '',
                'course_group_id' => $card['course_group_id'] ?? null,
                'lesson_id' => $card['lesson_id'] ?? null,
                'teacher_id' => $card['teacher_id'] ?? null,
                'group_name' => $card['group_name'] ?? '',
                'course_name' => $card['course_name'] ?? '',
                'lesson_name' => $card['lesson_name'] ?? '',
                'teacher_name' => $card['teacher_name'] ?? '',
                'remaining_minutes' => $card['remaining_minutes'] ?? 0,
                'default_session_minutes' => $card['default_session_minutes'] ?? 60,
                'available_teachers' => $card['available_teachers'] ?? [],
            ], $context['cards_to_place'] ?? []),
            'frozen_lessons' => array_map(static fn (array $lesson): array => [
                'id' => $lesson['id'] ?? null,
                'lesson_date' => $lesson['lesson_date'] ?? '',
                'starts_at' => $lesson['starts_at'] ?? '',
                'ends_at' => $lesson['ends_at'] ?? '',
                'academy_building_id' => $lesson['academy_building_id'] ?? null,
                'academy_room_id' => $lesson['academy_room_id'] ?? null,
                'course_group_id' => $lesson['course_group_id'] ?? null,
                'teacher_id' => $lesson['teacher_id'] ?? null,
                'lesson_id' => $lesson['lesson_id'] ?? null,
            ], $context['frozen_lessons'] ?? []),
            'rules' => $context['rules'] ?? [],
        ];
    }

    /**
     * @return array{placements: list<array<string, mixed>>, report: string, warnings: list<string>, unplaced_cards: list<array<string, mixed>>}
     */
    private function parseAiResponse(string $raw): array
    {
        $decoded = AiJsonParser::decodeObject($raw);

        return [
            'placements' => is_array($decoded['placements'] ?? null) ? $decoded['placements'] : [],
            'report' => (string) ($decoded['report'] ?? ''),
            'warnings' => is_array($decoded['warnings'] ?? null) ? array_map('strval', $decoded['warnings']) : [],
            'recommendations' => is_array($decoded['recommendations'] ?? null) ? array_map('strval', $decoded['recommendations']) : [],
            'unplaced_cards' => is_array($decoded['unplaced_cards'] ?? null) ? $decoded['unplaced_cards'] : [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $groupLessonCards
     * @return array<string, mixed>
     */
    private function analyzeResultingSchedule(ScheduleWeek $week, array $groupLessonCards): array
    {
        $lessons = $week->scheduledLessons()
            ->with([
                'teacher:id,name',
                'courseGroup:id,name',
                'lesson:id,name',
            ])
            ->orderBy('lesson_date')
            ->orderBy('starts_at')
            ->get();

        $teacherNames = [];
        $teacherWeeklyMinutes = [];
        $teacherDailyMinutes = [];
        $teacherDailyLabels = [];

        foreach ($lessons as $lesson) {
            $duration = max(0, $this->timeToMinutes(substr((string) $lesson->ends_at, 0, 5))
                - $this->timeToMinutes(substr((string) $lesson->starts_at, 0, 5)));
            $teacherId = (int) ($lesson->teacher_id ?? 0);

            if ($teacherId <= 0) {
                continue;
            }

            $teacherNames[$teacherId] = (string) ($lesson->teacher?->name ?? ('#'.$teacherId));
            $teacherWeeklyMinutes[$teacherId] = ($teacherWeeklyMinutes[$teacherId] ?? 0) + $duration;

            $day = $lesson->lesson_date?->format('Y-m-d') ?? (string) $lesson->lesson_date;
            $dayKey = $teacherId.'|'.$day;
            $teacherDailyMinutes[$dayKey] = ($teacherDailyMinutes[$dayKey] ?? 0) + $duration;
            $teacherDailyLabels[$dayKey] = $day;
        }

        $teacherWorkloads = [];
        foreach ($teacherWeeklyMinutes as $teacherId => $weeklyMinutes) {
            $dailyBreakdown = [];
            foreach ($teacherDailyMinutes as $dayKey => $minutes) {
                if (! str_starts_with($dayKey, $teacherId.'|')) {
                    continue;
                }

                $day = $teacherDailyLabels[$dayKey] ?? '';
                $dailyBreakdown[$day] = $minutes;
            }

            $teacherWorkloads[] = [
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherNames[$teacherId] ?? ('#'.$teacherId),
                'weekly_minutes' => $weeklyMinutes,
                'weekly_hours' => round($weeklyMinutes / 60, 2),
                'daily_minutes' => $dailyBreakdown,
                'max_daily_minutes' => $dailyBreakdown !== [] ? max($dailyBreakdown) : 0,
                'is_weekly_overloaded' => $weeklyMinutes >= self::TEACHER_WEEKLY_HEAVY_MINUTES,
                'is_daily_overloaded' => ($dailyBreakdown !== [] ? max($dailyBreakdown) : 0) >= self::TEACHER_DAILY_HEAVY_MINUTES,
            ];
        }

        $cardsByGroupLesson = [];
        foreach ($groupLessonCards as $card) {
            $groupLessonKey = $card['course_group_id'].'-'.$card['lesson_id'];
            $cardsByGroupLesson[$groupLessonKey][] = [
                'key' => $card['key'],
                'teacher_id' => $card['teacher_id'],
                'teacher_name' => $card['teacher_name'],
                'group_name' => $card['group_name'],
                'lesson_name' => $card['lesson_name'],
                'course_name' => $card['course_name'],
                'weekly_minutes' => $card['weekly_minutes'],
                'scheduled_minutes' => $card['scheduled_minutes'],
                'remaining_minutes' => $card['remaining_minutes'],
                'available_teachers' => $card['teachers'] ?? [],
            ];
        }

        $groupLessonAssignments = [];
        foreach ($cardsByGroupLesson as $groupLessonKey => $cards) {
            [$groupId, $lessonId] = array_map('intval', explode('-', $groupLessonKey, 2));
            $scheduledByTeacher = $week->scheduledLessons()
                ->where('course_group_id', $groupId)
                ->where('lesson_id', $lessonId)
                ->get(['teacher_id', 'starts_at', 'ends_at'])
                ->groupBy('teacher_id')
                ->map(function ($rows): int {
                    return (int) $rows->sum(function ($lesson): int {
                        return max(0, $this->timeToMinutes(substr((string) $lesson->ends_at, 0, 5))
                            - $this->timeToMinutes(substr((string) $lesson->starts_at, 0, 5)));
                    });
                })
                ->all();

            $firstCard = $cards[0] ?? [];
            $availableTeachers = collect($cards)
                ->map(fn (array $card): array => [
                    'teacher_id' => $card['teacher_id'],
                    'teacher_name' => $card['teacher_name'],
                    'weekly_minutes_budget' => $card['weekly_minutes'],
                    'scheduled_minutes' => (int) ($scheduledByTeacher[$card['teacher_id']] ?? 0),
                ])
                ->values()
                ->all();

            $groupLessonAssignments[] = [
                'group_lesson_key' => $groupLessonKey,
                'group_name' => $firstCard['group_name'] ?? '',
                'lesson_name' => $firstCard['lesson_name'] ?? '',
                'course_name' => $firstCard['course_name'] ?? '',
                'available_teachers' => $availableTeachers,
                'has_alternate_teachers' => count($availableTeachers) > 1,
                'single_teacher_carries_all' => count($availableTeachers) > 1
                    && count(array_filter($scheduledByTeacher, static fn (int $minutes): bool => $minutes > 0)) === 1,
            ];
        }

        return [
            'lessons_count' => $lessons->count(),
            'teacher_workloads' => $teacherWorkloads,
            'group_lesson_assignments' => $groupLessonAssignments,
            'thresholds' => [
                'teacher_daily_heavy_minutes' => self::TEACHER_DAILY_HEAVY_MINUTES,
                'teacher_weekly_heavy_minutes' => self::TEACHER_WEEKLY_HEAVY_MINUTES,
                'min_break_minutes' => self::MIN_BREAK_MINUTES,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $postScheduleAnalysis
     * @param  list<string>  $planRecommendations
     * @return list<string>
     */
    private function buildRecommendations(
        array $postScheduleAnalysis,
        array $planRecommendations,
        string $prompt,
        string $locale,
    ): array {
        $deterministic = $this->deterministicRecommendations($postScheduleAnalysis, $locale);

        try {
            $aiRecommendations = $this->requestAiRecommendations($postScheduleAnalysis, $prompt, $locale);
        } catch (\Throwable) {
            $aiRecommendations = [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $item): string => trim($item),
            array_merge($planRecommendations, $aiRecommendations, $deterministic),
        ), static fn (string $item): bool => $item !== '')));
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return list<string>
     */
    private function deterministicRecommendations(array $analysis, string $locale): array
    {
        $items = [];

        foreach ($analysis['teacher_workloads'] ?? [] as $workload) {
            if (($workload['is_weekly_overloaded'] ?? false) === true) {
                $items[] = $this->localizedMessage($locale, 'recommend_teacher_weekly_overload', [
                    'teacher' => $workload['teacher_name'] ?? '',
                    'hours' => round(((int) ($workload['weekly_minutes'] ?? 0)) / 60, 1),
                ]);
            }

            if (($workload['is_daily_overloaded'] ?? false) === true) {
                $items[] = $this->localizedMessage($locale, 'recommend_teacher_daily_overload', [
                    'teacher' => $workload['teacher_name'] ?? '',
                    'hours' => round(((int) ($workload['max_daily_minutes'] ?? 0)) / 60, 1),
                ]);
            }
        }

        foreach ($analysis['group_lesson_assignments'] ?? [] as $assignment) {
            if (($assignment['has_alternate_teachers'] ?? false) && ($assignment['single_teacher_carries_all'] ?? false)) {
                $items[] = $this->localizedMessage($locale, 'recommend_add_second_teacher', [
                    'group' => $assignment['group_name'] ?? '',
                    'lesson' => $assignment['lesson_name'] ?? '',
                ]);
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $analysis
     * @return list<string>
     */
    private function requestAiRecommendations(array $analysis, string $prompt, string $locale): array
    {
        $language = match ($locale) {
            'ru' => 'Russian',
            'uk' => 'Ukrainian',
            'en' => 'English',
            default => 'Italian',
        };

        $system = <<<PROMPT
You are a school schedule consultant. Analyze the resulting weekly schedule metrics and provide practical recommendations in {$language}.

Focus on:
- excessive teacher load (daily or weekly)
- when a group+lesson has multiple available teachers but only one is used
- uneven distribution across days/rooms
- remaining unplaced hours if visible in the analysis
- assigning an additional teacher and splitting hours when workload is too high

Return ONLY valid JSON:
{
  "recommendations": [
    "Specific actionable recommendation..."
  ]
}

Provide 2-6 concise recommendations. If everything looks balanced, still give 1-2 optimization tips.
PROMPT;

        $userContent = json_encode([
            'user_prompt' => $prompt,
            'schedule_analysis' => $analysis,
        ], JSON_UNESCAPED_UNICODE);

        if ($userContent === false) {
            throw new RuntimeException('Failed to encode recommendation context.');
        }

        $raw = $this->ai->complete([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userContent],
        ], 4096);

        $decoded = AiJsonParser::decodeObject($raw, 'AI recommendations returned invalid JSON.');
        $recommendations = $decoded['recommendations'] ?? [];

        return is_array($recommendations) ? array_map('strval', $recommendations) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $placements
     * @param  list<array<string, mixed>>  $cardsToPlace
     * @param  list<array<string, mixed>>  $frozenLessons
     * @return array{placed_count: int, skipped_count: int, warnings: list<string>, report: string}
     */
    private function applyPlacements(
        ScheduleWeek $week,
        array $placements,
        array $cardsToPlace,
        array $frozenLessons,
        string $locale,
    ): array {
        $cardsByKey = [];
        foreach ($cardsToPlace as $card) {
            $cardsByKey[$card['key']] = $card;
        }

        $remainingByKey = [];
        foreach ($cardsToPlace as $card) {
            $remainingByKey[$card['key']] = (int) $card['remaining_minutes'];
        }

        $placedCount = 0;
        $skippedCount = 0;
        $warnings = [];

        DB::transaction(function () use (
            $week,
            $placements,
            $cardsByKey,
            &$remainingByKey,
            $frozenLessons,
            &$placedCount,
            &$skippedCount,
            &$warnings,
            $locale,
        ): void {
            foreach ($placements as $index => $placement) {
                $cardKey = (string) ($placement['card_key'] ?? '');
                $card = $cardsByKey[$cardKey] ?? null;

                if ($card === null) {
                    $skippedCount++;
                    $warnings[] = $this->localizedMessage($locale, 'unknown_card', ['key' => $cardKey, 'index' => $index + 1]);

                    continue;
                }

                $duration = (int) ($placement['duration_minutes'] ?? 0);
                if ($duration <= 0) {
                    $startsAt = $this->conflicts->normalizeTime((string) ($placement['starts_at'] ?? ''));
                    $endsAt = $this->conflicts->normalizeTime((string) ($placement['ends_at'] ?? ''));
                    if ($startsAt !== null && $endsAt !== null) {
                        $duration = $this->timeToMinutes($endsAt) - $this->timeToMinutes($startsAt);
                    }
                }

                if ($duration <= 0 || $duration % ScheduleConflictService::GRID_STEP_MINUTES !== 0) {
                    $skippedCount++;
                    $warnings[] = $this->localizedMessage($locale, 'invalid_duration', ['key' => $cardKey]);

                    continue;
                }

                if (($remainingByKey[$cardKey] ?? 0) < $duration) {
                    $skippedCount++;
                    $warnings[] = $this->localizedMessage($locale, 'budget_exceeded', ['key' => $cardKey]);

                    continue;
                }

                $payload = [
                    'schedule_week_id' => $week->id,
                    'lesson_date' => (string) ($placement['lesson_date'] ?? ''),
                    'starts_at' => $this->conflicts->normalizeTime((string) ($placement['starts_at'] ?? '')),
                    'ends_at' => $this->conflicts->normalizeTime((string) ($placement['ends_at'] ?? '')),
                    'academy_building_id' => (int) ($placement['academy_building_id'] ?? 0),
                    'academy_room_id' => (int) ($placement['academy_room_id'] ?? 0),
                    'course_group_id' => (int) $card['course_group_id'],
                    'teacher_id' => (int) $card['teacher_id'],
                    'lesson_id' => (int) $card['lesson_id'],
                    'status' => ScheduledLesson::STATUS_SCHEDULED,
                ];

                if ($payload['starts_at'] === null || $payload['ends_at'] === null) {
                    $skippedCount++;
                    $warnings[] = $this->localizedMessage($locale, 'invalid_time', ['key' => $cardKey]);

                    continue;
                }

                $breakWarnings = $this->breakWarnings($payload, $frozenLessons, $week);
                $conflicts = $this->conflicts->validatePayload($payload);

                if ($conflicts !== []) {
                    $skippedCount++;
                    $warnings[] = $this->localizedMessage($locale, 'conflict', [
                        'key' => $cardKey,
                        'message' => collect($conflicts)->pluck('message')->first() ?? 'Conflict',
                    ]);

                    continue;
                }

                $week->scheduledLessons()->create($payload);
                $remainingByKey[$cardKey] -= $duration;
                $placedCount++;
                $warnings = array_merge($warnings, $breakWarnings);
            }
        });

        $unplacedBudget = array_filter($remainingByKey, static fn (int $minutes): bool => $minutes > 0);
        foreach ($unplacedBudget as $key => $minutes) {
            $warnings[] = $this->localizedMessage($locale, 'remaining_budget', [
                'key' => $key,
                'minutes' => $minutes,
            ]);
        }

        $report = $this->localizedMessage($locale, 'apply_summary', [
            'placed' => $placedCount,
            'skipped' => $skippedCount,
            'unplaced_cards' => count($unplacedBudget),
        ]);

        return [
            'placed_count' => $placedCount,
            'skipped_count' => $skippedCount,
            'warnings' => $warnings,
            'report' => $report,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array<string, mixed>>  $frozenLessons
     * @return list<string>
     */
    private function breakWarnings(array $payload, array $frozenLessons, ScheduleWeek $week): array
    {
        $warnings = [];
        $date = (string) $payload['lesson_date'];
        $start = $this->timeToMinutes((string) $payload['starts_at']);
        $end = $this->timeToMinutes((string) $payload['ends_at']);

        $sameDayLessons = $week->scheduledLessons()
            ->whereDate('lesson_date', $date)
            ->get(['teacher_id', 'course_group_id', 'starts_at', 'ends_at']);

        foreach ($sameDayLessons as $existing) {
            $existingStart = $this->timeToMinutes(substr((string) $existing->starts_at, 0, 5));
            $existingEnd = $this->timeToMinutes(substr((string) $existing->ends_at, 0, 5));

            if ($end <= $existingStart) {
                $gap = $existingStart - $end;
            } elseif ($start >= $existingEnd) {
                $gap = $start - $existingEnd;
            } else {
                continue;
            }

            if ($gap >= self::MIN_BREAK_MINUTES) {
                continue;
            }

            if ((int) $existing->teacher_id === (int) $payload['teacher_id']) {
                $warnings[] = 'Short break for teacher on '.$date;
            }

            if ((int) $existing->course_group_id === (int) $payload['course_group_id']) {
                $warnings[] = 'Short break for group on '.$date;
            }
        }

        foreach ($frozenLessons as $existing) {
            if (($existing['lesson_date'] ?? '') !== $date) {
                continue;
            }

            $existingStart = $this->timeToMinutes(substr((string) $existing['starts_at'], 0, 5));
            $existingEnd = $this->timeToMinutes(substr((string) $existing['ends_at'], 0, 5));

            if ($end <= $existingStart) {
                $gap = $existingStart - $end;
            } elseif ($start >= $existingEnd) {
                $gap = $start - $existingEnd;
            } else {
                continue;
            }

            if ($gap >= self::MIN_BREAK_MINUTES) {
                continue;
            }

            if ((int) ($existing['teacher_id'] ?? 0) === (int) $payload['teacher_id']) {
                $warnings[] = 'Short break for teacher near frozen lesson on '.$date;
            }
        }

        return $warnings;
    }

    /**
     * @param  array<string, int|string>  $replace
     */
    private function localizedMessage(string $locale, string $key, array $replace = []): string
    {
        $messages = [
            'nothing_to_place' => [
                'it' => 'Non ci sono lezioni da programmare: tutte le ore settimanali sono già distribuite.',
                'en' => 'Nothing to schedule: all weekly hours are already placed.',
                'ru' => 'Нечего распределять: все недельные часы уже поставлены в расписание.',
                'uk' => 'Немає чого розподіляти: усі тижневі години вже розставлені.',
            ],
            'partial_confirm' => [
                'it' => 'Servono {needed} minuti ma la capacità libera stimata è {free} minuti ({cards} lezioni). Non tutto potrà entrare. Procedere con la distribuzione parziale?',
                'en' => '{needed} minutes are required but estimated free capacity is {free} minutes ({cards} lessons). Not everything may fit. Proceed with partial scheduling?',
                'ru' => 'Нужно {needed} минут, а свободной ёмкости примерно {free} минут ({cards} уроков). Не всё поместится. Распределить тех, кто поместится?',
                'uk' => 'Потрібно {needed} хвилин, а вільної місткості приблизно {free} хвилин ({cards} занять). Не все поміститься. Розподілити тих, хто поміститься?',
            ],
            'apply_summary' => [
                'it' => 'Distribuzione completata: {placed} blocchi inseriti, {skipped} saltati, {unplaced_cards} schede con minuti residui.',
                'en' => 'Scheduling finished: {placed} blocks placed, {skipped} skipped, {unplaced_cards} cards with remaining minutes.',
                'ru' => 'Распределение завершено: поставлено блоков — {placed}, пропущено — {skipped}, карточек с остатком — {unplaced_cards}.',
                'uk' => 'Розподіл завершено: поставлено блоків — {placed}, пропущено — {skipped}, карток із залишком — {unplaced_cards}.',
            ],
            'unknown_card' => [
                'it' => 'Blocco #{index}: scheda sconosciuta {key}.',
                'en' => 'Block #{index}: unknown card {key}.',
                'ru' => 'Блок #{index}: неизвестная карточка {key}.',
                'uk' => 'Блок #{index}: невідома картка {key}.',
            ],
            'invalid_duration' => [
                'it' => 'Durata non valida per la scheda {key}.',
                'en' => 'Invalid duration for card {key}.',
                'ru' => 'Некорректная длительность для карточки {key}.',
                'uk' => 'Некоректна тривалість для картки {key}.',
            ],
            'budget_exceeded' => [
                'it' => 'Budget settimanale superato per la scheda {key}.',
                'en' => 'Weekly budget exceeded for card {key}.',
                'ru' => 'Превышен недельный бюджет для карточки {key}.',
                'uk' => 'Перевищено тижневий бюджет для картки {key}.',
            ],
            'invalid_time' => [
                'it' => 'Orario non valido per la scheda {key}.',
                'en' => 'Invalid time for card {key}.',
                'ru' => 'Некорректное время для карточки {key}.',
                'uk' => 'Некоректний час для картки {key}.',
            ],
            'conflict' => [
                'it' => 'Conflitto per {key}: {message}',
                'en' => 'Conflict for {key}: {message}',
                'ru' => 'Конфликт для {key}: {message}',
                'uk' => 'Конфлікт для {key}: {message}',
            ],
            'remaining_budget' => [
                'it' => 'Minuti residui non distribuiti per {key}: {minutes} min.',
                'en' => 'Remaining unscheduled minutes for {key}: {minutes} min.',
                'ru' => 'Не распределено минут для {key}: {minutes} мин.',
                'uk' => 'Не розподілено хвилин для {key}: {minutes} хв.',
            ],
            'deterministic_plan_summary' => [
                'it' => 'Distribuzione completata dal pianificatore: {placed_blocks} blocchi su {requested_blocks}, {placed_hours} ore su {requested_hours}. Ore non inserite: {unplaced_hours}. Giorni utilizzati: {used_days} su {available_days}.',
                'en' => 'Planner finished: {placed_blocks} of {requested_blocks} blocks, {placed_hours} of {requested_hours} hours. Unplaced: {unplaced_hours} hours. Days used: {used_days} of {available_days}.',
                'ru' => 'Распределение завершено: поставлено {placed_blocks} из {requested_blocks} блоков, {placed_hours} из {requested_hours} ч. Не помещено: {unplaced_hours} ч. Использовано дней: {used_days} из {available_days}.',
                'uk' => 'Розподіл завершено: поставлено {placed_blocks} із {requested_blocks} блоків, {placed_hours} із {requested_hours} год. Не розміщено: {unplaced_hours} год. Використано днів: {used_days} із {available_days}.',
            ],
            'preferences_summary' => [
                'it' => 'Interpretazione richiesta: {summary}',
                'en' => 'Request interpretation: {summary}',
                'ru' => 'Интерпретация запроса: {summary}',
                'uk' => 'Інтерпретація запиту: {summary}',
            ],
            'group_rules_summary' => [
                'it' => 'Regole per gruppo: {rules}',
                'en' => 'Group rules: {rules}',
                'ru' => 'Правила по группам: {rules}',
                'uk' => 'Правила по групах: {rules}',
            ],
            'planned_partial_confirm' => [
                'it' => 'Il pianificatore può inserire {placed_hours} ore su {requested_hours}; restano {unplaced_hours} ore (finestre corso, aule o conflitti). Verranno utilizzati {days} giorni. Applicare la parte che entra?',
                'en' => 'The planner can place {placed_hours} of {requested_hours} hours; {unplaced_hours} hours remain (course windows, rooms, or conflicts). It will use {days} days. Apply the part that fits?',
                'ru' => 'Планировщик может поставить {placed_hours} из {requested_hours} ч; останется {unplaced_hours} ч (окна курсов, залы или конфликты). Будет использовано дней: {days}. Применить ту часть, которая помещается?',
                'uk' => 'Планувальник може поставити {placed_hours} із {requested_hours} год; залишиться {unplaced_hours} год (вікна курсів, зали або конфлікти). Буде використано днів: {days}. Застосувати частину, що вміщується?',
            ],
            'unplaced_teacher_load' => [
                'it' => 'Non inserite {hours} ore per {teacher}: nessun slot libero (finestra del corso, aule occupate o conflitti). Non significa necessariamente che manchi un insegnante — controlla carico e assegnazioni.',
                'en' => '{hours} hours for {teacher} were not placed: no free slot (course study window, busy rooms, or conflicts). This does not always mean a missing teacher — check load and assignments.',
                'ru' => 'Не размещено {hours} ч преподавателя {teacher}: нет свободного слота (учебное окно курса, занятые залы или конфликты). Это не всегда значит, что не хватает преподавателя — проверьте нагрузку и назначения.',
                'uk' => 'Не розміщено {hours} год викладача {teacher}: немає вільного слота (навчальне вікно курсу, зайняті зали або конфлікти). Це не завжди означає брак викладача — перевірте навантаження та призначення.',
            ],
            'recommend_teacher_weekly_overload' => [
                'it' => 'Carico eccessivo per {teacher}: {hours} ore settimanali. Valuta di assegnare un secondo insegnante o ridistribuire le ore.',
                'en' => 'Excessive load on {teacher}: {hours} weekly hours. Consider assigning a second teacher or redistributing hours.',
                'ru' => 'Чрезмерная нагрузка на преподавателя {teacher}: {hours} ч в неделю. Рекомендуется назначить второго преподавателя или перераспределить часы.',
                'uk' => 'Надмірне навантаження на викладача {teacher}: {hours} год на тиждень. Рекомендується призначити другого викладача або перерозподілити години.',
            ],
            'recommend_teacher_daily_overload' => [
                'it' => 'Giornata troppo piena per {teacher}: fino a {hours} ore in un solo giorno. Sposta parte delle lezioni su altri giorni o a un altro insegnante.',
                'en' => 'Overloaded day for {teacher}: up to {hours} hours on a single day. Move some lessons to other days or assign another teacher.',
                'ru' => 'Перегруженный день у преподавателя {teacher}: до {hours} ч за один день. Перенесите часть занятий на другие дни или назначьте второго преподавателя.',
                'uk' => 'Перевантажений день у викладача {teacher}: до {hours} год за один день. Перенесіть частину занять на інші дні або призначте другого викладача.',
            ],
            'recommend_add_second_teacher' => [
                'it' => 'Per {group} / {lesson} è disponibile più di un insegnante, ma le ore sono concentrate su una sola persona. Assegna un secondo insegnante e dividi il monte ore.',
                'en' => 'For {group} / {lesson}, multiple teachers are available but hours are assigned to only one. Assign a second teacher and split the weekly hours.',
                'ru' => 'Для {group} / {lesson} доступно несколько преподавателей, но часы стоят на одном. Назначьте второго преподавателя и разделите недельную нагрузку.',
                'uk' => 'Для {group} / {lesson} доступно кілька викладачів, але години стоять на одному. Призначте другого викладача та розподіліть тижневе навантаження.',
            ],
        ];

        $template = $messages[$key][$locale] ?? $messages[$key]['en'] ?? $key;

        foreach ($replace as $name => $value) {
            $template = str_replace('{'.$name.'}', (string) $value, $template);
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $analysis
     */
    private function partialConfirmationMessage(string $locale, array $analysis): string
    {
        return $this->localizedMessage($locale, 'partial_confirm', [
            'needed' => (int) ($analysis['needed_minutes'] ?? 0),
            'free' => (int) ($analysis['free_capacity_minutes'] ?? 0),
            'cards' => (int) ($analysis['cards_count'] ?? 0),
        ]);
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
