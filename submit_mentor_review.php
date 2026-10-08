<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'auth_guard.php';
require_once 'notify.php';
require_once 'repositories/weekly_log_repository.php';
require_once 'repositories/meeting_repository.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

csrf_verify();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

$projectId     = (int)($_POST['project_id'] ?? 0);
$classroomId   = (int)($_POST['classroom_id'] ?? 0);
$weekNumber    = (int)($_POST['week_number'] ?? 0);
$status        = trim((string)($_POST['status'] ?? 'approved'));
$mentorRemarks = trim((string)($_POST['mentor_remarks'] ?? ''));
$attendance    = $_POST['attendance'] ?? [];
$instructionsText = trim((string)($_POST['instructions_text'] ?? ''));
$newInstructions  = $_POST['new_instructions'] ?? [];

$validStatuses = ['pending', 'approved', 'revision_needed'];
if (!in_array($status, $validStatuses, true)) {
    $status = 'approved';
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

if ($projectId <= 0 || $weekNumber <= 0 || $classroomId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid project, classroom, or week number.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Invalid project, classroom, or week number.';
    header("Location: dashboard.php");
    exit;
}

// Authorization check: User must be Assigned Mentor or Classroom Coordinator (Admin)
if (!is_project_reviewer($pdo, $projectId, $classroomId, $currentUserId)) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Only project reviewers can submit guide feedback.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Unauthorized: Only project reviewers can submit guide feedback.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

try {
    $pdo->beginTransaction();

    // Ensure weekly_meetings row exists
    $meeting = meeting_find_by_week($pdo, $projectId, $weekNumber);
    if (!$meeting) {
        $meetingDate = date('Y-m-d');
        $stmtIns = $pdo->prepare("
            INSERT INTO weekly_meetings (project_id, week_number, meeting_date, status)
            VALUES (?, ?, ?, 'scheduled')
        ");
        $stmtIns->execute([$projectId, $weekNumber, $meetingDate]);
        $meetingId = (int)$pdo->lastInsertId();
    } else {
        $meetingId = (int)$meeting['id'];
    }

    // 4. Save guide feedback (append-only history logged to meeting_feedback_history)
    meeting_save_guide_feedback($pdo, $meetingId, $mentorRemarks, $currentUserId, $status);

    if ($status === 'approved' && (!$meeting || $meeting['status'] === 'scheduled')) {
        meeting_update_status($pdo, $meetingId, 'held');
    }

    // 2. Add specific action items into guide_instructions
    $instructionsAdded = 0;
    // Process array of instruction items
    if (is_array($newInstructions)) {
        foreach ($newInstructions as $inst) {
            $instText = trim((string)$inst);
            if ($instText !== '') {
                guide_instruction_add($pdo, $meetingId, $instText, $currentUserId);
                $instructionsAdded++;
            }
        }
    }
    // Process newline-separated instructions
    if ($instructionsText !== '') {
        $lines = preg_split('/\r\n|\r|\n/', $instructionsText);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $lineText = trim((string)$line);
                if ($lineText !== '') {
                    guide_instruction_add($pdo, $meetingId, $lineText, $currentUserId);
                    $instructionsAdded++;
                }
            }
        }
    }

    // Sync legacy weekly_submission and weekly_reviews
    $submissionId = weekly_submission_ensure_exists($pdo, $projectId, $weekNumber);
    weekly_review_save($pdo, $submissionId, $currentUserId, $status, $mentorRemarks);

    // Sync attendance if provided
    if (is_array($attendance)) {
        weekly_attendance_sync($pdo, $submissionId, $projectId, $attendance);
    }

    $pdo->commit();

    // Notify project members
    $statusText = match ($status) {
        'approved'        => 'approved',
        'revision_needed' => 'requested revisions on',
        default           => 'reviewed',
    };
    $hasRemarksText = ($mentorRemarks !== '' ? ' and left feedback.' : '.');
    $instructionsNote = ($instructionsAdded > 0 ? " ({$instructionsAdded} new action item(s) assigned)" : '');

    notify_project_members(
        $pdo,
        $projectId,
        'review',
        "Your guide {$statusText} the Week {$weekNumber} meeting log{$hasRemarksText}{$instructionsNote}",
        "dashboard.php?classroom_id=" . urlencode((string)$classroomId),
        $currentUserId
    );

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'            => true,
            'message'            => 'Guide feedback and instructions saved successfully.',
            'status'             => $status,
            'instructions_added' => $instructionsAdded,
        ]);
        exit;
    }

    $_SESSION['flash_success'] = "Week {$weekNumber} guide review and instructions saved successfully.";
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'A server error occurred while saving the review.']);
        exit;
    }
    $_SESSION['flash_error'] = 'A server error occurred while saving the review.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}
