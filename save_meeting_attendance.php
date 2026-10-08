<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'auth_guard.php';
require_once 'repositories/meeting_repository.php';
require_once 'notify.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

csrf_verify();

$meetingId     = (int)($_POST['meeting_id'] ?? 0);
$projectId     = (int)($_POST['project_id'] ?? 0);
$classroomId   = (int)($_POST['classroom_id'] ?? 0);
$meetingStatus = trim((string)($_POST['meeting_status'] ?? ''));
$reason        = trim((string)($_POST['reason'] ?? ''));
$attendanceRaw = $_POST['attendance'] ?? []; // Map of user_id => status

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($meetingId <= 0 || $projectId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid meeting or project ID.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Invalid meeting or project ID.';
    header("Location: hub.php");
    exit;
}

// Server-side authorization check: User must be assigned mentor or classroom Admin
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if (!can_manage_project_attendance($pdo, $projectId, $currentUserId)) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Only the project mentor or classroom Admin may record attendance.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Unauthorized: Only the project mentor or classroom Admin may record attendance.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

// Ensure meeting belongs to project
$meeting = meeting_find($pdo, $meetingId);
if (!$meeting || (int)$meeting['project_id'] !== $projectId) {
    if ($isAjax) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Meeting record not found for this project.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Meeting record not found for this project.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

// Sanitize attendance map: key => int user_id, value => status ('present'|'absent'|'excused')
$attendanceData = [];
if (is_array($attendanceRaw)) {
    foreach ($attendanceRaw as $uid => $status) {
        $uid = (int)$uid;
        $statusStr = strtolower(trim((string)$status));
        if ($uid > 0 && in_array($statusStr, ['present', 'absent', 'excused'], true)) {
            $attendanceData[$uid] = $statusStr;
        }
    }
}

try {
    $pdo->beginTransaction();

    $result = meeting_save_full_attendance(
        $pdo,
        $meetingId,
        $attendanceData,
        $currentUserId,
        $reason !== '' ? $reason : null,
        $meetingStatus !== '' ? $meetingStatus : null
    );

    $pdo->commit();

    // Notify project members
    $weekNum = (int)$meeting['week_number'];
    $statusLabel = ucfirst($result['meeting_status']);
    $notifMsg = "Guide recorded attendance for Week {$weekNum} meeting ({$statusLabel}).";
    if ($result['changes_logged'] > 0) {
        $notifMsg = "Guide updated Week {$weekNum} meeting attendance ({$result['changes_logged']} change(s) logged).";
    }

    notify_project_members(
        $pdo,
        $projectId,
        'attendance',
        $notifMsg,
        'dashboard.php?classroom_id=' . urlencode((string)$classroomId),
        $currentUserId
    );

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'        => true,
            'message'        => 'Attendance saved successfully.',
            'meeting_status' => $result['meeting_status'],
            'updated'        => $result['updated'],
            'changes_logged' => $result['changes_logged'],
        ]);
        exit;
    }

    $_SESSION['flash_success'] = 'Meeting attendance saved successfully.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($isAjax) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'A server error occurred while saving attendance.']);
        exit;
    }
    $_SESSION['flash_error'] = 'A server error occurred while saving attendance.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}
