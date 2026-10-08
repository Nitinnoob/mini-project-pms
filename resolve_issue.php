<?php
require_once 'bootstrap.php';
require_once 'notify.php';
require_once 'repositories/project_repository.php';
require_once 'repositories/issue_repository.php';

require_login();
if (!is_post()) {
    header("Location: dashboard.php");
    exit;
}
csrf_verify();

$uid          = (int)$_SESSION['user_id'];
$issue_id     = (int)($_POST['issue_id'] ?? 0);
$classroom_id = (int)($_POST['classroom_id'] ?? 0);
$back_project = (int)($_POST['back_project_id'] ?? 0); // set when a teacher resolves from drilldown

$back = "dashboard.php?classroom_id=" . urlencode($classroom_id) . ($back_project ? "&project_id=$back_project" : '');

$issue = issue_find_with_classroom($pdo, $issue_id, $classroom_id);

if (!$issue) {
    header("Location: $back");
    exit;
}

// Allowed: assigned mentor, classroom coordinator, or this project's leader.
$allowed = is_project_reviewer($pdo, $issue['project_id'], $classroom_id, $uid)
        || is_project_leader($pdo, $issue['project_id'], $uid);

if ($allowed && $issue['status'] === 'open') {
    issue_resolve($pdo, $issue_id, $uid);

    activity_log_add($pdo, $issue['project_id'], $uid, 'Resolved Issue', "resolved the blocker: " . $issue['title']);

    $who = htmlspecialchars_decode($_SESSION['username'] ?? 'Someone');
    notify_user($pdo, $issue['raised_by'] != $uid ? $issue['raised_by'] : null, 'issue_resolved',
        "$who marked your blocker \"" . $issue['title'] . "\" as resolved.",
        "dashboard.php?classroom_id=$classroom_id");
}

header("Location: $back&msg=" . urlencode("Blocker marked as resolved."));
exit;
