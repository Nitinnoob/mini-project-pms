<?php
session_start();
require 'dbs.php';
require_once 'notify.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$uid          = (int)$_SESSION['user_id'];
$issue_id     = (int)($_POST['issue_id'] ?? 0);
$classroom_id = (int)($_POST['classroom_id'] ?? 0);
$back_project = (int)($_POST['back_project_id'] ?? 0); // set when a teacher resolves from drilldown

$back = "dashboard.php?classroom_id=" . urlencode($classroom_id) . ($back_project ? "&project_id=$back_project" : '');

$stmt = $pdo->prepare("
    SELECT i.id, i.project_id, i.title, i.raised_by, i.status, p.mentor_id, c.created_by AS coordinator_id
    FROM issues i
    JOIN projects p ON i.project_id = p.id
    JOIN classrooms c ON p.classroom_id = c.id
    WHERE i.id = ? AND p.classroom_id = ?
");
$stmt->execute([$issue_id, $classroom_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issue) {
    header("Location: $back");
    exit;
}

// Allowed: assigned mentor, classroom coordinator, or this project's leader.
$allowed = ((int)$issue['mentor_id'] === $uid) || ((int)$issue['coordinator_id'] === $uid);
if (!$allowed) {
    $stmtL = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND is_leader = 1 AND join_status = 'Active'");
    $stmtL->execute([$issue['project_id'], $uid]);
    $allowed = (bool)$stmtL->fetch();
}

if ($allowed && $issue['status'] === 'open') {
    $pdo->prepare("UPDATE issues SET status = 'resolved', resolved_by = ?, resolved_at = CURRENT_TIMESTAMP WHERE id = ?")
        ->execute([$uid, $issue_id]);

    $pdo->prepare("INSERT INTO activity_log (project_id, user_id, action, details) VALUES (?, ?, 'Resolved Issue', ?)")
        ->execute([$issue['project_id'], $uid, "resolved the blocker: " . $issue['title']]);

    $who = htmlspecialchars_decode($_SESSION['username'] ?? 'Someone');
    notify_user($pdo, $issue['raised_by'] != $uid ? $issue['raised_by'] : null, 'issue_resolved',
        "$who marked your blocker \"" . $issue['title'] . "\" as resolved.",
        "dashboard.php?classroom_id=$classroom_id");
}

header("Location: $back&msg=" . urlencode("Blocker marked as resolved."));
exit;
