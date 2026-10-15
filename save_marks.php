<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'repositories/marks_repository.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

$projectId   = (int)($_POST['project_id'] ?? 0);
$classroomId = (int)($_POST['classroom_id'] ?? 0);
$reportMarks = isset($_POST['report_marks']) && $_POST['report_marks'] !== '' ? (float)$_POST['report_marks'] : null;
$students    = $_POST['students'] ?? [];

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

if ($projectId <= 0) {
    $_SESSION['flash_error'] = 'Invalid project.';
    header("Location: hub.php");
    exit;
}

// Authorization: must be project mentor or classroom admin
if (!can_evaluate_project($pdo, $projectId, $currentUserId)) {
    $_SESSION['flash_error'] = 'Only the assigned guide or a classroom admin can enter marks.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

// Build student marks array
$studentMarks = [];
foreach ($students as $uid => $marks) {
    $pres = isset($marks['presentation']) && $marks['presentation'] !== '' ? (float)$marks['presentation'] : null;
    $qa   = isset($marks['qa']) && $marks['qa'] !== '' ? (float)$marks['qa'] : null;
    $studentMarks[(int)$uid] = ['presentation' => $pres, 'qa' => $qa];
}

try {
    marks_save($pdo, $projectId, $reportMarks, $studentMarks, $currentUserId);
    $_SESSION['flash_success'] = 'Marks saved successfully.';
} catch (\Throwable $e) {
    $_SESSION['flash_error'] = 'Failed to save marks. Please try again.';
}

header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
exit;
