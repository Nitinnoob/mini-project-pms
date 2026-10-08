<?php
// Pure presentation helpers shared by views. No SQL here.
// Guarded so repeated includes never trigger "cannot redeclare" fatals.

if (!function_exists('e')) {
    // PHP 8 null-safe HTML escape. Accepts null/int/float/bool; never throws
    // TypeError the way htmlspecialchars(null) does. Extra args mirror
    // htmlspecialchars() so existing call sites can be swapped 1:1.
    function e(mixed $value, int $flags = ENT_QUOTES, ?string $encoding = 'UTF-8'): string
    {
        if ($value === null || is_array($value) || is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }
        return htmlspecialchars((string) $value, $flags | ENT_SUBSTITUTE, $encoding ?? 'UTF-8');
    }
}

if (!function_exists('heat_level')) {
    // Maps a daily activity count to a 0-4 heatmap intensity bucket.
    function heat_level($count): int
    {
        $count = (int)$count;
        if ($count <= 0) return 0;
        return min($count, 4);
    }
}

if (!function_exists('task_phase_chip')) {
    // Phase 4: task cards show which derived phase/week they belong to.
    // An unscheduled task is called out rather than silently bucketed.
    function task_phase_chip($task): string
    {
        if (!empty($task['week_label'])) {
            $label = htmlspecialchars($task['week_label'] ?? '', ENT_QUOTES, 'UTF-8');
            return "<span class=\"phase-chip\" style=\"background: var(--accent-2); color: var(--bg);\" title=\"Scheduled in this phase\">$label</span>";
        }
        return '<span class="phase-chip" style="background: transparent; border: 1px dashed var(--border); color: var(--muted);" title="This task has no due date or phase yet">Unscheduled</span>';
    }
}
