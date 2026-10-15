<?php
declare(strict_types=1);

require_once 'bootstrap.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

$projectId   = (int)($_POST['project_id'] ?? 0);
$classroomId = (int)($_POST['classroom_id'] ?? 0);
$githubUrl   = trim((string)($_POST['github_url'] ?? ''));

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

if ($projectId <= 0) {
    header("Location: hub.php");
    exit;
}

// Authorization: user must be the project leader
if (!is_project_leader($pdo, $projectId, $currentUserId)) {
    $_SESSION['flash_error'] = 'Only the project leader can update the repository URL.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

// Basic URL validation
if (!empty($githubUrl) && !filter_var($githubUrl, FILTER_VALIDATE_URL)) {
    $_SESSION['flash_error'] = 'Please enter a valid URL (e.g. https://github.com/user/repo).';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

$stmt = $pdo->prepare("UPDATE projects SET github_url = ? WHERE id = ?");
$stmt->execute([empty($githubUrl) ? null : $githubUrl, $projectId]);

$_SESSION['flash_success'] = 'Repository link updated.';
header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
exit;
