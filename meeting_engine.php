<?php
declare(strict_types=1);

/**
 * Meeting Engine — Saturday-aligned weekly guide meetings derivation for PMS.
 *
 * Derives Saturday meeting dates and week cycles from classroom start_date and
 * end_date without depending on tasks or classroom phases.
 *
 * Controller & offline friendly. Pure calculation engine.
 */

/**
 * Return the first Saturday on or after the given date string.
 *
 * @throws Exception If date cannot be parsed.
 */
function meeting_find_first_saturday(string $dateStr): DateTime
{
    $date = new DateTime($dateStr);
    // In PHP date('N'): 1 = Monday ... 6 = Saturday, 7 = Sunday
    $dayOfWeek = (int)$date->format('N');
    if ($dayOfWeek !== 6) {
        $daysToAdd = (6 - $dayOfWeek + 7) % 7;
        $date->modify("+{$daysToAdd} days");
    }
    return $date;
}

/**
 * Derive Saturday-aligned weekly meetings schedule from classroom bounds.
 *
 * Each week begins on the Sunday following the previous meeting (or start_date for week 1)
 * and concludes on the Saturday guide meeting date.
 *
 * @return array<int, array{
 *     week_number: int,
 *     meeting_date: string,
 *     label: string,
 *     date_from: string,
 *     date_to: string,
 *     is_current: bool,
 *     week_state: string
 * }>
 */
function meeting_derive_schedule(?string $startDate, ?string $endDate, ?string $today = null): array
{
    if (empty($startDate) || empty($endDate)) {
        return [];
    }

    try {
        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);
        $now   = $today !== null ? new DateTime($today) : new DateTime('today');
    } catch (Exception $e) {
        return [];
    }

    if ($end < $start) {
        return [];
    }

    try {
        $firstSat = meeting_find_first_saturday($startDate);
    } catch (Exception $e) {
        return [];
    }

    // If the first Saturday is already after end_date, there are no full Saturday reviews.
    if ($firstSat > $end) {
        return [];
    }

    $schedule = [];
    $currentSat = clone $firstSat;
    $weekNum = 1;
    $prevSat = null;

    while ($currentSat <= $end) {
        $meetingDateStr = $currentSat->format('Y-m-d');

        // Week interval bounds:
        // Week 1 starts at classroom start_date
        // Subsequent weeks start on Sunday (+1 day after previous Saturday)
        if ($weekNum === 1) {
            $fromStr = $start->format('Y-m-d');
        } else {
            $from = (clone $prevSat)->modify('+1 day');
            $fromStr = $from->format('Y-m-d');
        }
        $toStr = $meetingDateStr;

        $schedule[] = [
            'week_number'  => $weekNum,
            'meeting_date' => $meetingDateStr,
            'label'        => "Week $weekNum Meeting",
            'date_from'    => $fromStr,
            'date_to'      => $toStr,
            'is_current'   => false, // populated below
            'week_state'   => 'future', // 'past', 'current', 'future'
        ];

        $prevSat = clone $currentSat;
        $currentSat->modify('+7 days');
        $weekNum++;
    }

    if (empty($schedule)) {
        return [];
    }

    // Determine current week
    $nowDateStr = $now->format('Y-m-d');
    $currentWeekIndex = null;

    if ($nowDateStr < $schedule[0]['date_from']) {
        // Before start: Week 1 is upcoming
        $currentWeekIndex = 0;
    } else {
        foreach ($schedule as $idx => $item) {
            if ($nowDateStr >= $item['date_from'] && $nowDateStr <= $item['date_to']) {
                $currentWeekIndex = $idx;
                break;
            }
        }
        // If past all meetings, clamp to last or mark all past
        if ($currentWeekIndex === null) {
            $lastIdx = count($schedule) - 1;
            if ($nowDateStr > $schedule[$lastIdx]['date_to']) {
                $currentWeekIndex = $lastIdx; // clamp for UI active context
            }
        }
    }

    foreach ($schedule as $idx => &$item) {
        if ($currentWeekIndex !== null && $idx === $currentWeekIndex) {
            $item['is_current'] = true;
            $item['week_state'] = 'current';
        } elseif ($currentWeekIndex !== null && $idx < $currentWeekIndex) {
            $item['is_current'] = false;
            $item['week_state'] = 'past';
        } else {
            $item['is_current'] = false;
            $item['week_state'] = 'future';
        }
    }
    unset($item);

    return $schedule;
}

/**
 * Total number of Saturday meetings in schedule.
 */
function meeting_total_weeks(?string $startDate, ?string $endDate): int
{
    return count(meeting_derive_schedule($startDate, $endDate));
}

/**
 * Active current week number, or null if no schedule.
 */
function meeting_current_week(?string $startDate, ?string $endDate, ?string $today = null): ?int
{
    $schedule = meeting_derive_schedule($startDate, $endDate, $today);
    foreach ($schedule as $item) {
        if ($item['is_current']) {
            return $item['week_number'];
        }
    }
    return !empty($schedule) ? $schedule[0]['week_number'] : null;
}
