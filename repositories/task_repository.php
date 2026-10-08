<?php
/**
 * Task repository - the only place that knows the `tasks` table shape.
 * Procedural PDO, prepared statements only.
 */

if (!function_exists('task_find_with_project')) {
    /** Task row plus its project's classroom_id, or null. */
    function task_find_with_project(PDO $pdo, int $taskId): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT t.id, t.project_id, t.title, t.assigned_to, t.status, p.classroom_id
             FROM tasks t JOIN projects p ON p.id = t.project_id
             WHERE t.id = ?"
        );
        $stmt->execute([$taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!function_exists('task_exists_in_project')) {
    function task_exists_in_project(PDO $pdo, int $taskId, int $projectId): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM tasks WHERE id = ? AND project_id = ?");
        $stmt->execute([$taskId, $projectId]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('task_create')) {
    /**
     * Insert a new 'todo' task. Pass $weekNumber only when the week_number
     * column exists (see phase_tasks_column_exists()).
     */
    function task_create(PDO $pdo, array $t, bool $withWeekNumber): int
    {
        if ($withWeekNumber) {
            $stmt = $pdo->prepare(
                "INSERT INTO tasks (project_id, title, assigned_to, priority, milestone, week_number, due_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'todo')"
            );
            $stmt->execute([$t['project_id'], $t['title'], $t['assigned_to'], $t['priority'],
                            $t['milestone'], $t['week_number'], $t['due_date']]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO tasks (project_id, title, assigned_to, priority, milestone, due_date, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'todo')"
            );
            $stmt->execute([$t['project_id'], $t['title'], $t['assigned_to'], $t['priority'],
                            $t['milestone'], $t['due_date']]);
        }
        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('task_update_status')) {
    function task_update_status(PDO $pdo, int $taskId, string $status): bool
    {
        if (!in_array($status, TASK_STATUSES, true)) {
            return false;
        }
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $taskId]);
    }
}

if (!defined('TASK_STATUSES')) {
    define('TASK_STATUSES', ['todo', 'inprogress', 'done']);
}

if (!function_exists('task_legacy_milestone')) {
    /** Map a phase week onto the legacy 4-milestone label (pure). */
    function task_legacy_milestone(?int $weekNumber, int $totalWeeks): string
    {
        if ($totalWeeks <= 0 || $weekNumber === null) {
            return 'Synopsis';
        }
        $q = max(1, (int) ceil($weekNumber / max(1, $totalWeeks) * 4));
        return ['Synopsis', 'Phase 1', 'Phase 2', 'Final Demo'][min(3, $q - 1)];
    }
}
