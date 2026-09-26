<?php
session_start();
require 'dbs.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$project_id = $_POST['project_id'] ?? null;
$title = trim($_POST['title'] ?? '');
$assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
$priority = $_POST['priority'] ?? 'normal';
$milestone = $_POST['milestone'] ?? 'Synopsis';

if ($project_id && !empty($title)) {
    // Validate that the user actually belongs to this project
    $stmtCheck = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
    $stmtCheck->execute([$project_id, $_SESSION['user_id']]);
    
    if ($stmtCheck->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO tasks (project_id, title, assigned_to, priority, milestone, status) VALUES (?, ?, ?, ?, ?, 'todo')");
        $stmt->execute([$project_id, $title, $assigned_to, $priority, $milestone]);
        
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (project_id, user_id, action, details) VALUES (?, ?, 'Created Task', ?)");
        $stmtLog->execute([$project_id, $_SESSION['user_id'], "Created task: " . $title]);
    }
}

$classroom_id = $_POST['classroom_id'] ?? '';
if ($classroom_id) {
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
} else {
    header("Location: dashboard.php");
}
exit;
