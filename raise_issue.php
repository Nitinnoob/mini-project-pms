<?php
session_start();
require 'dbs.php';
require_once 'notify.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

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
$stmtAuth = $pdo->prepare("
    SELECT 1 FROM project_members pm JOIN projects p ON pm.project_id = p.id
    WHERE pm.project_id = ? AND pm.user_id = ? AND pm.join_status = 'Active' AND p.classroom_id = ?
");
$stmtAuth->execute([$project_id, $uid, $classroom_id]);
if (!$stmtAuth->fetch()) {
    header("Location: $back");
    exit;
}

// The impacted task must belong to this project.
if ($task_id !== null) {
    $stmtT = $pdo->prepare("SELECT 1 FROM tasks WHERE id = ? AND project_id = ?");
    $stmtT->execute([$task_id, $project_id]);
    if (!$stmtT->fetch()) $task_id = null;
}

try {
    $stmt = $pdo->prepare("INSERT INTO issues (project_id, raised_by, title, description, week_number, task_id, severity, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'open')");
    $stmt->execute([$project_id, $uid, mb_substr($title, 0, 200), $description, $week_number, $task_id, $severity]);
} catch (Throwable $e) {
    header("Location: $back&err=" . urlencode("Blockers are not enabled yet - re-import schema.sql."));
    exit;
}

$stmtLog = $pdo->prepare("INSERT INTO activity_log (project_id, user_id, action, details) VALUES (?, ?, 'Raised Issue', ?)");
$stmtLog->execute([$project_id, $uid, "raised a " . $severity . " blocker: " . $title]);

$projName = notify_project_name($pdo, $project_id);
$who = htmlspecialchars_decode($_SESSION['username'] ?? 'A teammate');
$msg = "$who raised a " . $severity . " blocker in $projName: $title";
notify_project_mentor($pdo, $project_id, 'issue_raised', $msg,
    "dashboard.php?classroom_id=$classroom_id&project_id=$project_id", $uid);
notify_project_leaders($pdo, $project_id, 'issue_raised', $msg,
    "dashboard.php?classroom_id=$classroom_id", $uid);

header("Location: $back&msg=" . urlencode("Escalation sent. Your mentor and project leader have been alerted."));
exit;
