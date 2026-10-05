<?php
session_start();
require 'dbs.php';
require_once 'phase_engine.php';
require_once 'notify.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$project_id   = $_POST['project_id'] ?? null;
$classroom_id = $_POST['classroom_id'] ?? null;
$title        = trim($_POST['title'] ?? '');
$assigned_to  = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
$priority     = ($_POST['priority'] ?? 'normal') === 'high' ? 'high' : 'normal';
$due_date     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
$week_number  = $_POST['week_number'] ?? null;

if ($project_id && $title !== '') {
    // Only active members of THIS project may add tasks.
    $stmtCheck = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
    $stmtCheck->execute([$project_id, $_SESSION['user_id']]);

    if ($stmtCheck->fetch()) {
        // Assignee must actually be on the project, otherwise assignment is meaningless.
        if ($assigned_to !== null) {
            $stmtAsg = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
            $stmtAsg->execute([$project_id, $assigned_to]);
            if (!$stmtAsg->fetch()) {
                $assigned_to = null;
            }
        }

        // Derive the classroom schedule so we can validate the chosen phase.
        $stmtCrs = $pdo->prepare("SELECT c.start_date, c.end_date FROM classrooms c JOIN projects p ON p.classroom_id = c.id WHERE p.id = ? AND p.classroom_id = ?");
        $stmtCrs->execute([$project_id, $classroom_id]);
        $crs = $stmtCrs->fetch(PDO::FETCH_ASSOC) ?: [];

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

        if (phase_tasks_column_exists($pdo)) {
            // milestone stays populated for backward compatibility with any
            // older views still reading it, but week_number is authoritative.
            $legacyMilestone = 'Synopsis';
            if ($totalWeeks > 0 && $weekNumber !== null) {
                $q = max(1, (int)ceil($weekNumber / max(1, $totalWeeks) * 4));
                $legacyMilestone = ['Synopsis', 'Phase 1', 'Phase 2', 'Final Demo'][min(3, $q - 1)];
            }

            $stmt = $pdo->prepare("INSERT INTO tasks (project_id, title, assigned_to, priority, milestone, week_number, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'todo')");
            $stmt->execute([$project_id, $title, $assigned_to, $priority, $legacyMilestone, $weekNumber, $due_date]);
        } else {
            // Phase 4 migration not applied — insert without week_number.
            $stmt = $pdo->prepare("INSERT INTO tasks (project_id, title, assigned_to, priority, milestone, due_date, status) VALUES (?, ?, ?, ?, ?, ?, 'todo')");
            $stmt->execute([$project_id, $title, $assigned_to, $priority, 'Synopsis', $due_date]);
        }

        $stmtLog = $pdo->prepare("INSERT INTO activity_log (project_id, user_id, action, details) VALUES (?, ?, 'Created Task', ?)");
        $weekNote = $weekNumber !== null ? " [Phase $weekNumber]" : '';
        $stmtLog->execute([$project_id, $_SESSION['user_id'], "Created task: " . $title . $weekNote]);

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