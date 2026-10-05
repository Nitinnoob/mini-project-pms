<?php
/**
 * Phase Engine — "1 Phase = 1 Week" derivation for PMS.
 *
 * Weeks are derived from a classroom's start_date / end_date. Phase labels
 * live in `classroom_phases` so a teacher can rename or merge weeks without
 * touching code; anything not overridden falls back to "Week N".
 *
 * Controller-side only. Views receive a fully-built array and never query.
 * All functions degrade gracefully when the classroom has no dates set or
 * the Phase 4 migration has not been applied yet.
 */

/**
 * Total number of week-long phases spanning a classroom's schedule.
 * Returns 0 when either date is missing (logs stay disabled).
 */
function phase_total_weeks($startDate, $endDate)
{
    if (empty($startDate) || empty($endDate)) {
        return 0;
    }
    try {
        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);
    } catch (Exception $e) {
        return 0;
    }
    if ($end < $start) {
        return 0;
    }
    $days = (int)$start->diff($end)->days;
    // Inclusive of the final partial week: a 10-day span is 2 weeks, not 1.
    $weeks = (int)ceil(($days + 1) / 7);
    return max(1, $weeks);
}

/**
 * Inclusive calendar bounds of a given phase/week.
 */
function phase_bounds($startDate, $weekNumber)
{
    $start = new DateTime($startDate);
    $offset = ((int)$weekNumber - 1) * 7;
    $from = clone $start;
    $from->modify("+{$offset} days");
    $to = clone $from;
    $to->modify('+6 days');
    return ['from' => $from, 'to' => $to];
}

/**
 * Which phase is "current" for a given date — i.e. the week containing it.
 * Clamped into [1, totalWeeks] so a date past end_date still resolves to the
 * final phase rather than returning nothing.
 */
function phase_current_week($startDate, $totalWeeks, $onDate = null)
{
    if (empty($startDate) || $totalWeeks < 1) {
        return null;
    }
    try {
        $start = new DateTime($startDate);
        $today = $onDate ? new DateTime($onDate) : new DateTime('today');
    } catch (Exception $e) {
        return null;
    }
    if ($today < $start) {
        return 1;
    }
    $week = (int)floor($start->diff($today)->days / 7) + 1;
    return max(1, min($totalWeeks, $week));
}

/**
 * Build the full phase list for a classroom, applying teacher overrides.
 * Returns array of:
 *   week_number, label, is_current, merged_into_week, merged_from[],
 *   date_from, date_to, is_merged, week_state ('past'|'current'|'future')
 *
 * Only queries when a $pdo is supplied, so it stays usable in offline/unit
 * contexts with overrides omitted.
 */
function phase_build_list($startDate, $endDate, array $overrides = [], $today = null)
{
    $totalWeeks = phase_total_weeks($startDate, $endDate);
    if ($totalWeeks < 1) {
        return [];
    }

    $currentWeek = phase_current_week($startDate, $totalWeeks, $today);

    // Index overrides by week for O(1) lookup.
    $byWeek = [];
    foreach ($overrides as $row) {
        $byWeek[(int)$row['week_number']] = $row;
    }

    // Invert merges: week N merged_into_week M means M collects weeks [N].
    $mergedFrom = [];
    foreach ($byWeek as $week => $row) {
        $target = $row['merged_into_week'];
        if ($target !== null && $target !== '' && (int)$target !== (int)$week) {
            $mergedFrom[(int)$target][] = (int)$week;
        }
    }

    $phases = [];
    $end = null;
    if (!empty($endDate)) {
        try {
            $end = new DateTime($endDate);
        } catch (Exception $e) {
            $end = null;
        }
    }

    for ($w = 1; $w <= $totalWeeks; $w++) {
        $row = $byWeek[$w] ?? null;
        $bounds = phase_bounds($startDate, $w);

        // The final week is usually a partial one. Don't report a date range
        // that runs past the day the classroom actually closes.
        $toDate = $bounds['to'];
        if ($end !== null && $toDate > $end) {
            $toDate = clone $end;
        }

        $phases[] = [
            'week_number'     => $w,
            'label'           => $row && !empty($row['label']) ? $row['label'] : "Week $w",
            'is_current'      => ($w === $currentWeek),
            'week_state'      => $currentWeek === null
                                ? 'future'
                                : ($w < $currentWeek ? 'past' : ($w === $currentWeek ? 'current' : 'future')),
            'merged_into_week' => $row && isset($row['merged_into_week']) ? $row['merged_into_week'] : null,
            'merged_from'     => $mergedFrom[$w] ?? [],
            'is_merged'       => !empty($row['merged_into_week']) && (int)$row['merged_into_week'] !== $w,
            'date_from'       => $bounds['from']->format('Y-m-d'),
            'date_to'         => $toDate->format('Y-m-d'),
        ];
    }

    return $phases;
}

/**
 * Fetch a classroom's teacher phase overrides.
 * Returns [] if the Phase 4 migration hasn't been run — callers must treat
 * an empty result as "all defaults", never as an error.
 */
function phase_load_overrides($pdo, $classroomId)
{
    if (empty($classroomId)) {
        return [];
    }
    try {
        $stmt = $pdo->prepare("SELECT week_number, label, merged_into_week FROM classroom_phases WHERE classroom_id = ? ORDER BY week_number");
        $stmt->execute([$classroomId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Migration not applied yet — fall back to default "Week N" labels.
        return [];
    }
}

/**
 * Auto-create phase rows when a teacher has never customised them.
 * Idempotent: only inserts missing week numbers, never overwrites an
 * existing label or merge (those are teacher decisions).
 */
function phase_seed($pdo, $classroomId, $startDate, $endDate)
{
    $totalWeeks = phase_total_weeks($startDate, $endDate);
    if ($totalWeeks < 1 || empty($classroomId)) {
        return;
    }
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO classroom_phases (classroom_id, week_number, label) VALUES (?, ?, ?)");
        for ($w = 1; $w <= $totalWeeks; $w++) {
            $stmt->execute([$classroomId, $w, "Week $w"]);
        }
    } catch (PDOException $e) {
        // Migration not applied — defaults are used instead.
    }
}

/**
 * Does tasks have the Phase 4 week_number column?
 * Cached per-request so the board can degrade instead of fataling.
 */
function phase_tasks_column_exists($pdo)
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'week_number'");
        $exists = (bool)$stmt->fetch();
    } catch (PDOException $e) {
        $exists = false;
    }
    return $exists;
}

/**
 * Week that a task's due date falls into, or the current week if undated.
 * Used to preselect the phase when creating a task.
 */
function phase_for_due_date($startDate, $endDate, $dueDate = null, $today = null)
{
    $totalWeeks = phase_total_weeks($startDate, $endDate);
    if ($totalWeeks < 1) {
        return null;
    }
    if (empty($dueDate)) {
        return phase_current_week($startDate, $totalWeeks, $today);
    }
    return phase_current_week($startDate, $totalWeeks, $dueDate);
}