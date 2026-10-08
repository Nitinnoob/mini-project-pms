<?php
require_once 'bootstrap.php';
require_once 'notify.php';
require_once 'repositories/weekly_log_repository.php';

require_login();

if (is_post()) {
    csrf_verify();

    $project_id = $_POST['project_id'] ?? null;
    $classroom_id = $_POST['classroom_id'] ?? null;
    $week_number = $_POST['week_number'] ?? null;
    $status = $_POST['status'] ?? 'pending';
    if (!in_array($status, ['pending', 'approved', 'revision_needed'], true)) {
        $status = 'pending';
    }
    $mentor_remarks = $_POST['mentor_remarks'] ?? '';
    $attendance = $_POST['attendance'] ?? []; // Array of user_id => 1 if checked
    
    if (!$project_id || !$week_number || !$classroom_id) {
        header("Location: dashboard.php");
        exit;
    }
    
    // Verify project belongs to classroom AND user is authorized (Coordinator or Assigned Mentor)
    if (is_project_reviewer($pdo, $project_id, $classroom_id, $_SESSION['user_id'])) {
        try {
            $pdo->beginTransaction();
            
            // Ensure a weekly_submission row exists even if the students didn't upload files
            $submission_id = weekly_submission_ensure_exists($pdo, (int)$project_id, (int)$week_number);
            
            // Insert or Update the review
            weekly_review_save($pdo, $submission_id, (int)$_SESSION['user_id'], $status, $mentor_remarks);
            
            // Update attendance
            weekly_attendance_sync($pdo, $submission_id, (int)$project_id, $attendance);
            
            $pdo->commit();

            $statusText = ['approved' => 'approved', 'revision_needed' => 'sent back for revision', 'pending' => 'updated'][$status] ?? 'updated';
            notify_project_members($pdo, $project_id, 'review',
                'Your mentor ' . $statusText . ' the Week ' . (int)$week_number . ' log' . (trim($mentor_remarks) !== '' ? ' and left remarks.' : '.'),
                'dashboard.php?classroom_id=' . urlencode($classroom_id), $_SESSION['user_id']);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // log error
        }
    }
    
    // Redirect back to the drilldown view, and re-open the logs tab using a hash
    // The hash could be used in JS, but for now just taking them to the dashboard is fine
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "&project_id=" . urlencode($project_id));
    exit;
}

header("Location: dashboard.php");
exit;
