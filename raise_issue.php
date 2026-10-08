<?php
require_once 'bootstrap.php';
require_once 'notify.php';
require_once 'repositories/project_repository.php';
require_once 'repositories/task_repository.php';
require_once 'repositories/issue_repository.php';

require_login();
if (!is_post()) {
    header("Location: dashboard.php");
    exit;
}
csrf_verify();

$uid          = (int)$_SESSION['user_id'];
$project_id   = (int)($_POST['project_id'] ?? 0);
$classroom_id = (int)($_POST['classroom_id'] ?? 0);
$title        = trim($_POST['title'] ?? '');
$description  = trim($_POST['description'] ?? '');
$severity     = $_POST['severity'] ?? 'medium';
$week_number  = !empty($_POST['week_number']) ? (int)$_POST['week_number'] : null;
$task_id      = !empty($_POST['task_id']) ? (int)$_POST['task_id'] : null;

$back = "dashboard.php?classroom_id=" . urlencode($classroom_id);

if (!$project_id || !$classroom_id || $title === '' || $description === ''
    || !in_array($severity, ['low', 'medium', 'high', 'critical'], true)) {
    header("Location: $back&err=" . urlencode("Please fill in the blocker title and description."));
    exit;
}

// Only active members of this project (in this classroom) may raise a blocker.
if (!is_active_project_member($pdo, $project_id, $uid, $classroom_id)) {
    header("Location: $back");
    exit;
}

// The impacted task must belong to this project.
if ($task_id !== null && !task_exists_in_project($pdo, $task_id, $project_id)) {
    $task_id = null;
}

try {
    issue_create($pdo, [
        'project_id'  => $project_id,
        'raised_by'   => $uid,
        'title'       => $title,
        'description' => $description,
        'week_number' => $week_number,
        'task_id'     => $task_id,
        'severity'    => $severity
    ]);
} catch (Throwable $e) {
    header("Location: $back&err=" . urlencode("Blockers are not enabled yet - re-import schema.sql."));
    exit;
}

activity_log_add($pdo, $project_id, $uid, 'Raised Issue', "raised a " . $severity . " blocker: " . $title);

$projName = notify_project_name($pdo, $project_id);
$who = htmlspecialchars_decode($_SESSION['username'] ?? 'A teammate');
$msg = "$who raised a " . $severity . " blocker in $projName: $title";
notify_project_mentor($pdo, $project_id, 'issue_raised', $msg,
    "dashboard.php?classroom_id=$classroom_id&project_id=$project_id", $uid);
notify_project_leaders($pdo, $project_id, 'issue_raised', $msg,
    "dashboard.php?classroom_id=$classroom_id", $uid);

header("Location: $back&msg=" . urlencode("Escalation sent. Your mentor and project leader have been alerted."));
exit;
