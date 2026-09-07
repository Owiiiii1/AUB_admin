<?php

namespace App\Services\WeeklySchedule;

class DeterministicSchedulePlanner
{
    /**
     * @param  list<array<string, mixed>>  $cards
     * @param  list<array<string, mixed>>  $buildings
     * @param  list<array<string, mixed>>  $days
     * @param  list<array<string, mixed>>  $frozenLessons
     * @param  array<string, mixed>  $preferences
     * @return array{placements: list<array<string, mixed>>, unplaced: list<array<string, mixed>>, metrics: array<string, mixed>}
     */
    public function plan(
        array $cards,
        array $buildings,
        array $days,
        array $frozenLessons,
        array $preferences = [],
    ): array {
        $rooms = $this->rooms($buildings);
        $dayDates = array_values(array_filter(array_map(
            static fn (array $day): string => (string) ($day['date'] ?? ''),
            $days,
        )));

        $dayDates = $this->orderedDays($dayDates, $preferences);
        $blockedDates = array_flip(array_map('strval', $preferences['avoid_dates'] ?? []));
        $usableDays = array_values(array_filter(
            $dayDates,
            static fn (string $date): bool => ! isset($blockedDates[$date]),
        ));

        if ($usableDays === []) {
            $usableDays = $dayDates;
        }

        $gridStart = $this->normalizePreferenceTime(
            $preferences['grid_start'] ?? null,
            $this->timeToMinutes(ScheduleConflictService::GRID_START),
        );
        $gridEnd = $this->normalizePreferenceTime(
            $preferences['grid_end'] ?? null,
            $this->timeToMinutes(ScheduleConflictService::GRID_END),
        );

        if ($gridEnd <= $gridStart) {
            $gridStart = $this->timeToMinutes(ScheduleConflictService::GRID_START);
            $gridEnd = $this->timeToMinutes(ScheduleConflictService::GRID_END);
        }

        $gridSpanMinutes = max(ScheduleConflictService::GRID_STEP_MINUTES, $gridEnd - $gridStart);
        $preferredStart = $this->normalizePreferenceTime(
            $preferences['preferred_start'] ?? null,
            $gridStart,
        );
        $preferredEnd = $this->normalizePreferenceTime(
            $preferences['preferred_end'] ?? null,
            $gridEnd,
        );

        $preferredStart = max($gridStart, min($preferredStart, $gridEnd - ScheduleConflictService::GRID_STEP_MINUTES));
        $preferredEnd = max($preferredStart + ScheduleConflictService::GRID_STEP_MINUTES, min($preferredEnd, $gridEnd));

        if ($preferredEnd <= $preferredStart) {
            $preferredStart = $gridStart;
            $preferredEnd = $gridEnd;
        }

        $maxTeacherDaily = $this->boundedMinutes(
            $preferences['max_teacher_daily_minutes'] ?? null,
            WeeklyScheduleAiService::TEACHER_DAILY_HEAVY_MINUTES,
            120,
            $gridSpanMinutes,
        );
        $maxGroupDaily = $this->boundedMinutes(
            $preferences['max_group_daily_minutes'] ?? null,
            360,
            120,
            $gridSpanMinutes,
        );
        $minBreak = $this->boundedMinutes(
            $preferences['min_break_minutes'] ?? null,
            WeeklyScheduleAiService::MIN_BREAK_MINUTES,
            0,
            120,
        );
        $defaultSessionMinutes = isset($preferences['default_session_minutes'])
            ? $this->boundedMinutes(
                $preferences['default_session_minutes'],
                60,
                ScheduleConflictService::GRID_STEP_MINUTES,
                360,
            )
            : null;

        $dayHalfSplit = $this->normalizePreferenceTime(
            $preferences['day_half_split'] ?? null,
            $this->timeToMinutes('13:00'),
        );
        $dayHalfSplit = max($gridStart + ScheduleConflictService::GRID_STEP_MINUTES, min($dayHalfSplit, $gridEnd));
        $groupRules = is_array($preferences['group_rules'] ?? null) ? $preferences['group_rules'] : [];
        $sameBuildingPerDay = (bool) ($preferences['same_building_per_day'] ?? true);

        $occupancy = array_map(
            fn (array $lesson): array => $this->normalizeExistingLesson($lesson),
            $frozenLessons,
        );

        $teacherDailyMinutes = [];
        $groupDailyMinutes = [];
        $dayMinutes = [];
        $roomDayMinutes = [];
        $halfLoadMinutes = ['morning' => 0, 'afternoon' => 0];
        /** @var array<int, string> $groupWeekHalf */
        $groupWeekHalf = [];

        foreach ($occupancy as $lesson) {
            $duration = max(0, $lesson['end_minutes'] - $lesson['start_minutes']);
            $date = $lesson['lesson_date'];
            $teacherDailyMinutes[$lesson['teacher_id'].'|'.$date] =
                ($teacherDailyMinutes[$lesson['teacher_id'].'|'.$date] ?? 0) + $duration;
            $groupDailyMinutes[$lesson['course_group_id'].'|'.$date] =
                ($groupDailyMinutes[$lesson['course_group_id'].'|'.$date] ?? 0) + $duration;
            $dayMinutes[$date] = ($dayMinutes[$date] ?? 0) + $duration;
            $roomDayMinutes[$lesson['academy_room_id'].'|'.$date] =
                ($roomDayMinutes[$lesson['academy_room_id'].'|'.$date] ?? 0) + $duration;

            $half = $lesson['start_minutes'] < $dayHalfSplit ? 'morning' : 'afternoon';
            $halfLoadMinutes[$half] += $duration;
            $groupId = (int) $lesson['course_group_id'];
            if ($groupId > 0 && ! isset($groupWeekHalf[$groupId])) {
                $groupWeekHalf[$groupId] = $half;
            }
        }

        $sessions = $this->sessions($cards, $defaultSessionMinutes);
        $placements = [];
        $unplaced = [];

        foreach ($sessions as $session) {
            $candidate = $this->bestCandidate(
                $session,
                $rooms,
                $usableDays,
                $occupancy,
                $teacherDailyMinutes,
                $groupDailyMinutes,
                $dayMinutes,
                $roomDayMinutes,
                $gridStart,
                $gridEnd,
                $preferredStart,
                $preferredEnd,
                $maxTeacherDaily,
                $maxGroupDaily,
                $minBreak,
                $dayHalfSplit,
                $groupRules,
                $groupWeekHalf,
                $halfLoadMinutes,
                $sameBuildingPerDay,
            );

            if ($candidate === null) {
                $unplaced[] = [
                    'card_key' => $session['card_key'],
                    'group_name' => $session['group_name'],
                    'lesson_name' => $session['lesson_name'],
                    'teacher_name' => $session['teacher_name'],
                    'minutes' => $session['duration_minutes'],
                    'reason' => 'no_valid_slot',
                ];

                continue;
            }

            $placement = [
                'card_key' => $session['card_key'],
                'lesson_date' => $candidate['lesson_date'],
                'starts_at' => $this->minutesToTime($candidate['start_minutes']),
                'ends_at' => $this->minutesToTime($candidate['end_minutes']),
                'academy_building_id' => $candidate['academy_building_id'],
                'academy_room_id' => $candidate['academy_room_id'],
                'course_group_id' => $session['course_group_id'],
                'teacher_id' => $session['teacher_id'],
                'lesson_id' => $session['lesson_id'],
                'duration_minutes' => $session['duration_minutes'],
            ];
            $placements[] = $placement;

            $occupancy[] = array_merge($placement, [
                'start_minutes' => $candidate['start_minutes'],
                'end_minutes' => $candidate['end_minutes'],
            ]);

            $date = $candidate['lesson_date'];
            $duration = $session['duration_minutes'];
            $teacherKey = $session['teacher_id'].'|'.$date;
            $groupKey = $session['course_group_id'].'|'.$date;
            $roomKey = $candidate['academy_room_id'].'|'.$date;
            $teacherDailyMinutes[$teacherKey] = ($teacherDailyMinutes[$teacherKey] ?? 0) + $duration;
            $groupDailyMinutes[$groupKey] = ($groupDailyMinutes[$groupKey] ?? 0) + $duration;
            $dayMinutes[$date] = ($dayMinutes[$date] ?? 0) + $duration;
            $roomDayMinutes[$roomKey] = ($roomDayMinutes[$roomKey] ?? 0) + $duration;

            $half = $candidate['start_minutes'] < $dayHalfSplit ? 'morning' : 'afternoon';
            $halfLoadMinutes[$half] += $duration;
            $groupId = (int) $session['course_group_id'];
            if ($groupId > 0 && ! isset($groupWeekHalf[$groupId])) {
                $groupWeekHalf[$groupId] = $half;
            }
        }

        $requestedMinutes = (int) array_sum(array_column($sessions, 'duration_minutes'));
        $placedMinutes = (int) array_sum(array_column($placements, 'duration_minutes'));

        return [
            'placements' => $placements,
            'unplaced' => $unplaced,
            'metrics' => [
                'requested_blocks' => count($sessions),
                'placed_blocks' => count($placements),
                'unplaced_blocks' => count($unplaced),
                'requested_minutes' => $requestedMinutes,
                'placed_minutes' => $placedMinutes,
                'unplaced_minutes' => max(0, $requestedMinutes - $placedMinutes),
                'used_days' => count(array_filter(
                    $dayMinutes,
                    static fn (int $minutes): bool => $minutes > 0,
                )),
                'available_days' => count($usableDays),
                'max_teacher_daily_minutes' => $maxTeacherDaily,
                'max_group_daily_minutes' => $maxGroupDaily,
                'min_break_minutes' => $minBreak,
                'default_session_minutes' => $defaultSessionMinutes,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @return list<array<string, mixed>>
     */
    private function sessions(array $cards, ?int $defaultSessionMinutes = null): array
    {
        $teacherDemand = [];
        foreach ($cards as $card) {
            $teacherId = (int) ($card['teacher_id'] ?? 0);
            $teacherDemand[$teacherId] =
                ($teacherDemand[$teacherId] ?? 0) + (int) ($card['remaining_minutes'] ?? 0);
        }

        usort($cards, static function (array $left, array $right) use ($teacherDemand): int {
            $leftDemand = $teacherDemand[(int) ($left['teacher_id'] ?? 0)] ?? 0;
            $rightDemand = $teacherDemand[(int) ($right['teacher_id'] ?? 0)] ?? 0;

            return [
                (int) ($left['course_group_id'] ?? 0),
                -$leftDemand,
                -(int) ($left['remaining_minutes'] ?? 0),
            ] <=> [
                (int) ($right['course_group_id'] ?? 0),
                -$rightDemand,
                -(int) ($right['remaining_minutes'] ?? 0),
            ];
        });

        $sessions = [];
        foreach ($cards as $card) {
            $remaining = max(0, (int) ($card['remaining_minutes'] ?? 0));
            $preferredDuration = max(
                ScheduleConflictService::GRID_STEP_MINUTES,
                $defaultSessionMinutes ?? (int) ($card['duration_minutes'] ?? 60),
            );
            $preferredDuration = $this->snapMinutes($preferredDuration);

            while ($remaining > 0) {
                // Pair rule: two sequential identical 1h lessons → one 2h block
                // (same group + lesson + teacher). Applies when the preferred unit is
                // a single hour; longer preferred durations (e.g. 120) already emit doubles.
                if ($preferredDuration <= 60 && $remaining >= 120) {
                    $duration = 120;
                } else {
                    $duration = min($preferredDuration, $remaining);
                }

                $duration = $this->snapMinutes($duration);

                if ($duration > $remaining) {
                    $duration = $remaining;
                }

                if ($duration < ScheduleConflictService::GRID_STEP_MINUTES) {
                    break;
                }

                $sessions[] = [
                    'card_key' => (string) $card['key'],
                    'course_group_id' => (int) $card['course_group_id'],
                    'lesson_id' => (int) $card['lesson_id'],
                    'teacher_id' => (int) $card['teacher_id'],
                    'group_name' => (string) ($card['group_name'] ?? ''),
                    'lesson_name' => (string) ($card['lesson_name'] ?? ''),
                    'teacher_name' => (string) ($card['teacher_name'] ?? ''),
                    'duration_minutes' => $duration,
                    'study_starts_at' => isset($card['study_starts_at']) ? (string) $card['study_starts_at'] : null,
                    'study_ends_at' => isset($card['study_ends_at']) ? (string) $card['study_ends_at'] : null,
                ];
                $remaining -= $duration;
            }
        }

        return $sessions;
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  list<array<string, mixed>>  $rooms
     * @param  list<string>  $days
     * @param  list<array<string, mixed>>  $occupancy
     * @param  array<string, int>  $teacherDailyMinutes
     * @param  array<string, int>  $groupDailyMinutes
     * @param  array<string, int>  $dayMinutes
     * @param  array<string, int>  $roomDayMinutes
     * @return array<string, mixed>|null
     */
    /**
     * @param  list<array<string, mixed>>  $groupRules
     * @param  array<int, string>  $groupWeekHalf
     * @param  array{morning: int, afternoon: int}  $halfLoadMinutes
     */
    private function bestCandidate(
        array $session,
        array $rooms,
        array $days,
        array $occupancy,
        array $teacherDailyMinutes,
        array $groupDailyMinutes,
        array $dayMinutes,
        array $roomDayMinutes,
        int $gridStart,
        int $gridEnd,
        int $preferredStart,
        int $preferredEnd,
        int $maxTeacherDaily,
        int $maxGroupDaily,
        int $minBreak,
        int $dayHalfSplit,
        array $groupRules,
        array $groupWeekHalf,
        array $halfLoadMinutes,
        bool $sameBuildingPerDay,
    ): ?array {
        $best = null;
        $bestScore = PHP_INT_MAX;
        $duration = (int) $session['duration_minutes'];
        $groupId = (int) $session['course_group_id'];
        $groupRule = $this->resolveGroupRule($session, $groupRules) ?? [];
        $sessionPreferredStart = $this->normalizePreferenceTime(
            $groupRule['preferred_start'] ?? null,
            $preferredStart,
        );
        $sessionPreferredEnd = $this->normalizePreferenceTime(
            $groupRule['preferred_end'] ?? null,
            $preferredEnd,
        );
        $maxInternalGap = isset($groupRule['max_internal_gap_minutes'])
            ? $this->boundedMinutes($groupRule['max_internal_gap_minutes'], 30, 0, 240)
            : null;
        // Prefer true compact packing on the planning grid.
        if ($maxInternalGap !== null) {
            $maxInternalGap = max($maxInternalGap, $minBreak);
        }
        $dayHalf = strtolower(trim((string) ($groupRule['day_half'] ?? '')));
        $lockedWeekHalf = $groupWeekHalf[$groupId] ?? null;
        $preferredHalfForBalance = $this->lessLoadedHalf($halfLoadMinutes);
        $studyStart = $this->optionalPreferenceTime($session['study_starts_at'] ?? null);
        $studyEnd = $this->optionalPreferenceTime($session['study_ends_at'] ?? null);
        $hasStudyWindow = $studyStart !== null && $studyEnd !== null && $studyEnd > $studyStart;
        // Course study window is the hard shift; ignore prompt day-half locking when present.
        if ($hasStudyWindow) {
            $dayHalf = '';
            $lockedWeekHalf = null;
        }

        foreach ($days as $dayIndex => $date) {
            $teacherKey = $session['teacher_id'].'|'.$date;
            $groupKey = $session['course_group_id'].'|'.$date;

            if (($teacherDailyMinutes[$teacherKey] ?? 0) + $duration > $maxTeacherDaily) {
                continue;
            }

            if (($groupDailyMinutes[$groupKey] ?? 0) + $duration > $maxGroupDaily) {
                continue;
            }

            $groupBlocks = $this->groupDayBlocks($occupancy, $groupId, $date);
            $lockedBuildingId = $sameBuildingPerDay
                ? $this->groupDayBuilding($occupancy, $groupId, $date)
                : null;
            [$windowStart, $windowEnd] = $this->resolveDayHalfWindow(
                $dayHalf,
                $groupBlocks,
                $gridStart,
                $gridEnd,
                $dayHalfSplit,
                $lockedWeekHalf,
            );

            if ($hasStudyWindow) {
                $windowStart = max($windowStart, $studyStart);
                $windowEnd = min($windowEnd, $studyEnd);
            }

            if ($windowEnd - $windowStart < $duration) {
                continue;
            }

            for ($start = $windowStart; $start + $duration <= $windowEnd; $start += ScheduleConflictService::GRID_STEP_MINUTES) {
                $end = $start + $duration;
                $slotHalf = $start < $dayHalfSplit ? 'morning' : 'afternoon';
                // When day-half rules are active, do not hard-penalize slots outside the global
                // preferred window — that window often collapses to morning and blocks afternoons.
                $outsidePreferred = $dayHalf === ''
                    && ! $hasStudyWindow
                    && ($start < $sessionPreferredStart || $end > $sessionPreferredEnd);
                $nearestGap = $this->nearestGapToBlocks($start, $end, $groupBlocks);

                if ($maxInternalGap !== null && $groupBlocks !== [] && $nearestGap > $maxInternalGap) {
                    continue;
                }

                if ($dayHalf === 'consistent' && $lockedWeekHalf !== null && $slotHalf !== $lockedWeekHalf) {
                    continue;
                }

                foreach ($rooms as $room) {
                    if (
                        $lockedBuildingId !== null
                        && (int) $room['academy_building_id'] !== $lockedBuildingId
                    ) {
                        continue;
                    }

                    $candidate = [
                        'lesson_date' => $date,
                        'start_minutes' => $start,
                        'end_minutes' => $end,
                        'academy_building_id' => $room['academy_building_id'],
                        'academy_room_id' => $room['academy_room_id'],
                        'course_group_id' => $session['course_group_id'],
                        'teacher_id' => $session['teacher_id'],
                    ];

                    if ($this->hasConflict($candidate, $occupancy, $minBreak)) {
                        continue;
                    }

                    $roomKey = $room['academy_room_id'].'|'.$date;
                    $halfBalancePenalty = 0;
                    if ($dayHalf === 'consistent' && $lockedWeekHalf === null) {
                        // Strong enough to beat day/room packing soft scores so rooms fill both halves.
                        $halfBalancePenalty = $slotHalf === $preferredHalfForBalance ? 0 : 5000;
                    }

                    // Compact / study-window days: pack from the start of the window so
                    // consecutive 2h blocks fit (centering the first block wastes the edges).
                    $timePackScore = ($maxInternalGap !== null || $hasStudyWindow)
                        ? (int) floor(($start - $windowStart) / ScheduleConflictService::GRID_STEP_MINUTES) * 12
                        : (int) floor(abs(($start + $end) / 2 - (($windowStart + $windowEnd) / 2)) / 30);

                    // Keep a group's lessons on the same day when compact packing is requested,
                    // instead of spreading one block per day to "balance" dayMinutes.
                    $continueGroupDayBonus = (
                        ($maxInternalGap !== null || $hasStudyWindow)
                        && $groupBlocks !== []
                    ) ? -8000 : 0;

                    $score =
                        (($dayMinutes[$date] ?? 0) * 8)
                        + (($teacherDailyMinutes[$teacherKey] ?? 0) * 5)
                        + (($groupDailyMinutes[$groupKey] ?? 0) * 4)
                        + (($roomDayMinutes[$roomKey] ?? 0) * 2)
                        + ($dayIndex * 5)
                        + ($outsidePreferred ? 100000 : 0)
                        + ($nearestGap * 40)
                        + $halfBalancePenalty
                        + $timePackScore
                        + $continueGroupDayBonus;

                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $best = $candidate;
                    }
                }
            }
        }

        return $best;
    }

    /**
     * @param  array{morning: int, afternoon: int}  $halfLoadMinutes
     */
    private function lessLoadedHalf(array $halfLoadMinutes): string
    {
        $morning = (int) ($halfLoadMinutes['morning'] ?? 0);
        $afternoon = (int) ($halfLoadMinutes['afternoon'] ?? 0);

        return $afternoon < $morning ? 'afternoon' : 'morning';
    }

    /**
     * @param  list<array<string, mixed>>  $groupRules
     * @return array<string, mixed>|null
     */
    private function resolveGroupRule(array $session, array $groupRules): ?array
    {
        $groupName = mb_strtolower(trim((string) ($session['group_name'] ?? '')));
        if ($groupName === '' || $groupRules === []) {
            return null;
        }

        $best = null;
        $bestLength = -1;
        foreach ($groupRules as $rule) {
            if (! is_array($rule)) {
                continue;
            }
            $match = mb_strtolower(trim((string) ($rule['match'] ?? $rule['group'] ?? '')));
            if ($match === '') {
                continue;
            }
            if (! str_contains($groupName, $match) && ! str_contains($match, $groupName)) {
                continue;
            }
            $length = mb_strlen($match);
            if ($length > $bestLength) {
                $best = $rule;
                $bestLength = $length;
            }
        }

        return $best;
    }

    /**
     * @param  list<array<string, mixed>>  $occupancy
     * @return list<array<string, mixed>>
     */
    private function groupDayBlocks(array $occupancy, int $groupId, string $date): array
    {
        $blocks = [];
        foreach ($occupancy as $item) {
            if ((int) ($item['course_group_id'] ?? 0) !== $groupId) {
                continue;
            }
            if (($item['lesson_date'] ?? '') !== $date) {
                continue;
            }
            $blocks[] = $item;
        }

        usort(
            $blocks,
            static fn (array $left, array $right): int => ((int) ($left['start_minutes'] ?? 0))
                <=> ((int) ($right['start_minutes'] ?? 0)),
        );

        return $blocks;
    }

    /**
     * @param  list<array<string, mixed>>  $groupBlocks
     * @return array{0: int, 1: int}
     */
    private function resolveDayHalfWindow(
        string $dayHalf,
        array $groupBlocks,
        int $gridStart,
        int $gridEnd,
        int $dayHalfSplit,
        ?string $lockedWeekHalf = null,
    ): array {
        $forced = match ($dayHalf) {
            'morning', 'first', 'am' => 'morning',
            'afternoon', 'second', 'pm' => 'afternoon',
            default => null,
        };

        if ($forced === null && $dayHalf === 'consistent') {
            if ($lockedWeekHalf === 'morning' || $lockedWeekHalf === 'afternoon') {
                $forced = $lockedWeekHalf;
            } elseif ($groupBlocks !== []) {
                $anchor = (int) ($groupBlocks[0]['start_minutes'] ?? $gridStart);
                $forced = $anchor < $dayHalfSplit ? 'morning' : 'afternoon';
            }
        }

        return match ($forced) {
            'morning' => [$gridStart, min($gridEnd, $dayHalfSplit)],
            'afternoon' => [max($gridStart, $dayHalfSplit), $gridEnd],
            default => [$gridStart, $gridEnd],
        };
    }

    /**
     * @param  list<array<string, mixed>>  $occupancy
     */
    private function groupDayBuilding(array $occupancy, int $groupId, string $date): ?int
    {
        foreach ($occupancy as $item) {
            if ((int) ($item['course_group_id'] ?? 0) !== $groupId) {
                continue;
            }
            if (($item['lesson_date'] ?? '') !== $date) {
                continue;
            }
            $buildingId = (int) ($item['academy_building_id'] ?? 0);

            return $buildingId > 0 ? $buildingId : null;
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function nearestGapToBlocks(int $start, int $end, array $blocks): int
    {
        if ($blocks === []) {
            return 0;
        }

        $nearest = PHP_INT_MAX;
        foreach ($blocks as $block) {
            $blockStart = (int) ($block['start_minutes'] ?? 0);
            $blockEnd = (int) ($block['end_minutes'] ?? 0);

            if ($end <= $blockStart) {
                $nearest = min($nearest, $blockStart - $end);
            } elseif ($start >= $blockEnd) {
                $nearest = min($nearest, $start - $blockEnd);
            } else {
                return 0;
            }
        }

        return $nearest === PHP_INT_MAX ? 0 : $nearest;
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @param  list<array<string, mixed>>  $occupancy
     */
    private function hasConflict(array $candidate, array $occupancy, int $minBreak): bool
    {
        foreach ($occupancy as $existing) {
            if ($candidate['lesson_date'] !== ($existing['lesson_date'] ?? '')) {
                continue;
            }

            $existingStart = (int) ($existing['start_minutes'] ?? 0);
            $existingEnd = (int) ($existing['end_minutes'] ?? 0);
            $overlaps = $candidate['start_minutes'] < $existingEnd
                && $candidate['end_minutes'] > $existingStart;

            $sameRoom = (int) $candidate['academy_room_id'] === (int) ($existing['academy_room_id'] ?? 0);
            $sameTeacher = (int) $candidate['teacher_id'] === (int) ($existing['teacher_id'] ?? 0);
            $sameGroup = (int) $candidate['course_group_id'] === (int) ($existing['course_group_id'] ?? 0);

            if ($overlaps && ($sameRoom || $sameTeacher || $sameGroup)) {
                return true;
            }

            if (! $sameTeacher && ! $sameGroup) {
                continue;
            }

            if ($candidate['end_minutes'] <= $existingStart) {
                $gap = $existingStart - $candidate['end_minutes'];
            } elseif ($candidate['start_minutes'] >= $existingEnd) {
                $gap = $candidate['start_minutes'] - $existingEnd;
            } else {
                continue;
            }

            // Sub-step breaks below the planning grid cannot be represented.
            // Allow back-to-back (gap 0); still enforce larger requested breaks.
            $requiredGap = $minBreak < ScheduleConflictService::GRID_STEP_MINUTES
                ? 0
                : $minBreak;

            if ($gap < $requiredGap) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $buildings
     * @return list<array<string, int>>
     */
    private function rooms(array $buildings): array
    {
        $rooms = [];
        foreach ($buildings as $building) {
            foreach ($building['rooms'] ?? [] as $room) {
                $rooms[] = [
                    'academy_building_id' => (int) $building['id'],
                    'academy_room_id' => (int) $room['id'],
                ];
            }
        }

        return $rooms;
    }

    /**
     * @param  array<string, mixed>  $lesson
     * @return array<string, mixed>
     */
    private function normalizeExistingLesson(array $lesson): array
    {
        return [
            'lesson_date' => substr((string) ($lesson['lesson_date'] ?? ''), 0, 10),
            'start_minutes' => $this->timeToMinutes(substr((string) ($lesson['starts_at'] ?? '00:00'), 0, 5)),
            'end_minutes' => $this->timeToMinutes(substr((string) ($lesson['ends_at'] ?? '00:00'), 0, 5)),
            'academy_building_id' => (int) ($lesson['academy_building_id'] ?? 0),
            'academy_room_id' => (int) ($lesson['academy_room_id'] ?? 0),
            'course_group_id' => (int) ($lesson['course_group_id'] ?? 0),
            'teacher_id' => (int) ($lesson['teacher_id'] ?? 0),
        ];
    }

    /**
     * @param  list<string>  $days
     * @param  array<string, mixed>  $preferences
     * @return list<string>
     */
    private function orderedDays(array $days, array $preferences): array
    {
        $requested = array_map('strval', $preferences['day_order'] ?? []);
        $validRequested = array_values(array_filter(
            $requested,
            static fn (string $date): bool => in_array($date, $days, true),
        ));

        return array_values(array_unique(array_merge($validRequested, $days)));
    }

    private function normalizePreferenceTime(mixed $value, int $fallback): int
    {
        if (! is_string($value) || ! preg_match('/^\d{2}:\d{2}$/', substr($value, 0, 5))) {
            return $fallback;
        }

        return $this->timeToMinutes(substr($value, 0, 5));
    }

    private function optionalPreferenceTime(mixed $value): ?int
    {
        if (! is_string($value)) {
            return null;
        }

        $time = substr($value, 0, 5);
        if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
            return null;
        }

        return $this->timeToMinutes($time);
    }

    private function boundedMinutes(mixed $value, int $fallback, int $min, int $max): int
    {
        if (! is_numeric($value)) {
            return $fallback;
        }

        return max($min, min($max, (int) $value));
    }

    private function snapMinutes(int $minutes): int
    {
        return max(
            ScheduleConflictService::GRID_STEP_MINUTES,
            (int) round($minutes / ScheduleConflictService::GRID_STEP_MINUTES)
                * ScheduleConflictService::GRID_STEP_MINUTES,
        );
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
