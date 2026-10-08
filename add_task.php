<?php
require_once 'bootstrap.php';
require_once 'phase_engine.php';
require_once 'notify.php';
require_once 'repositories/task_repository.php';
require_once 'repositories/project_repository.php';

require_login();
if (!is_post()) {
    header("Location: dashboard.php");
    exit;
}
csrf_verify();

$project_id   = $_POST['project_id'] ?? null;
$classroom_id = $_POST['classroom_id'] ?? null;
$title        = trim($_POST['title'] ?? '');
$assigned_to  = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
$priority     = ($_POST['priority'] ?? 'normal') === 'high' ? 'high' : 'normal';
$due_date     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$week_number  = $_POST['week_number'] ?? null;

if ($project_id && $title !== '') {
    // Only active members of THIS project may add tasks.
    if (is_active_project_member($pdo, $project_id, $_SESSION['user_id'])) {
        // Assignee must actually be on the project, otherwise assignment is meaningless.
        if ($assigned_to !== null && !is_active_project_member($pdo, $project_id, $assigned_to)) {
            $assigned_to = null;
        }

        // Derive the classroom schedule so we can validate the chosen phase.
        $crs = project_find_schedule($pdo, $project_id, $classroom_id) ?? [];

        $startDate = $crs['start_date'] ?? null;
        $endDate   = $crs['end_date'] ?? null;
        $totalWeeks = phase_total_weeks($startDate, $endDate);

        // Validate the phase: must be an integer within the derived range.
        $weekNumber = null;
        if ($week_number !== null && $week_number !== '') {
            $candidate = (int)$week_number;
            if ($totalWeeks > 0 && $candidate >= 1 && $candidate <= $totalWeeks) {
                $weekNumber = $candidate;
            }
        }

        // A task with a due date should land in the phase containing that date.
        // An explicit, valid phase choice always wins over derivation.
        if ($weekNumber === null) {
            $weekNumber = phase_for_due_date($startDate, $endDate, $due_date);
        }

        // milestone stays populated for backward compatibility with any
        // older views still reading it, but week_number is authoritative.
        $withWeek = phase_tasks_column_exists($pdo);
        task_create($pdo, [
            'project_id'  => $project_id,
            'title'       => $title,
            'assigned_to' => $assigned_to,
            'priority'    => $priority,
            'milestone'   => $withWeek ? task_legacy_milestone($weekNumber, $totalWeeks) : 'Synopsis',
            'week_number' => $weekNumber,
            'due_date'    => $due_date,
        ], $withWeek);

        $weekNote = $weekNumber !== null ? " [Phase $weekNumber]" : '';
        activity_log_add($pdo, $project_id, $_SESSION['user_id'], 'Created Task', "Created task: " . $title . $weekNote);

        if ($assigned_to !== null && $assigned_to !== (int)$_SESSION['user_id']) {
            notify_user($pdo, $assigned_to, 'task_assigned',
                htmlspecialchars_decode($_SESSION['username'] ?? 'A teammate') . ' assigned you a task: ' . $title,
                'dashboard.php?classroom_id=' . urlencode($classroom_id));
        }
    }
}

if ($classroom_id) {
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
} else {
    header("Location: dashboard.php");
}
exit;