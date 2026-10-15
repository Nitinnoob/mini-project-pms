<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'repositories/meeting_repository.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

$meetingId   = !empty($_POST['meeting_id']) ? (int)$_POST['meeting_id'] : null;
$projectId   = (int)($_POST['project_id'] ?? 0);
$classroomId = (int)($_POST['classroom_id'] ?? 0);
$meetingDate = trim((string)($_POST['meeting_date'] ?? ''));
$guidance    = trim((string)($_POST['guidance_notes'] ?? ''));
$attendance  = $_POST['attendance'] ?? [];

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

if ($projectId <= 0 || empty($meetingDate)) {
    $_SESSION['flash_error'] = 'Invalid project or missing meeting date.';
    header("Location: hub.php");
    exit;
}

// Authorization: must be project mentor or classroom admin
if (!can_manage_project($pdo, $projectId, $currentUserId)) {
    $_SESSION['flash_error'] = 'Only the assigned guide or a classroom admin can manage meetings.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

try {
    meeting_save($pdo, $projectId, $meetingId, $meetingDate, $guidance, $attendance, $currentUserId);
    $_SESSION['flash_success'] = $meetingId ? 'Meeting updated.' : 'Meeting recorded.';
} catch (\Throwable $e) {
    $_SESSION['flash_error'] = 'Failed to save meeting. Please try again.';
}

header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
exit;
