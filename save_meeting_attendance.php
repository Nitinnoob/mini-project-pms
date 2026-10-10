<?php
// save_meeting_attendance.php - 1-Click Batch Attendance & Audit Logger
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/meeting_engine.php';

require_teacher();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$meetingId = (int)($_POST['meeting_id'] ?? 0);
$status = trim($_POST['status'] ?? 'held');
$feedback = trim($_POST['guide_feedback'] ?? '');
$attendanceData = $_POST['attendance'] ?? []; // student_id => 'present'|'absent'|'excused'
$auditReason = trim($_POST['audit_reason'] ?? '');

if ($meetingId <= 0) {
    set_flash('error', 'Invalid meeting identified.');
    header('Location: dashboard.php');
    exit;
}

// Fetch meeting and verify faculty authorization
$stmt = $pdo->prepare("
    SELECT wm.*, p.mentor_id, c.teacher_id, p.id as project_id
    FROM weekly_meetings wm
    JOIN projects p ON wm.project_id = p.id
    JOIN classrooms c ON p.classroom_id = c.id
    WHERE wm.id = ?
");
$stmt->execute([$meetingId]);
$meeting = $stmt->fetch();

if (!$meeting) {
    set_flash('error', 'Meeting not found.');
    header('Location: dashboard.php');
    exit;
}

// Ensure teacher is either assigned mentor or classroom coordinator
if ($meeting['mentor_id'] != $user['id'] && $meeting['teacher_id'] != $user['id']) {
    set_flash('error', 'Access denied: Only the assigned faculty guide or classroom coordinator can mark attendance.');
    header('Location: dashboard.php');
    exit;
}

$today = date('Y-m-d');

// Strict Temporal Lock: Reject future attendance
if ($meeting['meeting_date'] > $today) {
    set_flash('error', "🔒 Advance attendance rejected. Meeting date ({$meeting['meeting_date']}) has not occurred yet.");
    header('Location: dashboard.php');
    exit;
}

// Determine if this is a retroactive alteration
$temporal = get_week_temporal_status($meeting['meeting_date'], $meeting['status']);
$isRetroactive = ($meeting['status'] === 'held' || $meeting['meeting_date'] < $today);

// Check if attendance already exists
$existingAttStmt = $pdo->prepare("SELECT student_id, status FROM meeting_attendance WHERE meeting_id = ?");
$existingAttStmt->execute([$meetingId]);
$existingRecords = $existingAttStmt->fetchAll(PDO::FETCH_KEY_PAIR); // student_id => status

$pdo->beginTransaction();
try {
    // If retroactive change detected, enforce audit justification
    $hasAttendanceChanges = false;
    foreach ($attendanceData as $studentId => $newAttStatus) {
        $studentId = (int)$studentId;
        if (!in_array($newAttStatus, ['present', 'absent', 'excused'])) {
            continue;
        }

        if (isset($existingRecords[$studentId]) && $existingRecords[$studentId] !== $newAttStatus) {
            $hasAttendanceChanges = true;
            break;
        }
    }

    $hasFeedbackChanges = (trim($meeting['guide_feedback'] ?? '') !== $feedback);
    $hasStatusChanges = ($meeting['status'] !== $status);

    if ($isRetroactive && ($hasAttendanceChanges || $hasFeedbackChanges || $hasStatusChanges) && empty($auditReason)) {
        $pdo->rollBack();
        set_flash('error', '🔒 Access Denied: Week ' . $meeting['week_number'] . ' review is locked and concluded. A formal audit justification reason is mandatory to modify past review remarks or attendance.');
        header('Location: dashboard.php');
        exit;
    }

    // Process attendance updates & audit logs
    $saveAttStmt = $pdo->prepare("
        INSERT INTO meeting_attendance (meeting_id, student_id, status, marked_by, marked_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by), marked_at = NOW()
    ");

    $auditStmt = $pdo->prepare("
        INSERT INTO attendance_changes (meeting_id, student_id, old_status, new_status, changed_by, reason)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($attendanceData as $studentId => $newAttStatus) {
        $studentId = (int)$studentId;
        if (!in_array($newAttStatus, ['present', 'absent', 'excused'])) {
            $newAttStatus = 'present';
        }

        $oldStatus = $existingRecords[$studentId] ?? null;

        // If status changed and record existed previously, log to audit trail
        if ($oldStatus && $oldStatus !== $newAttStatus) {
            $auditStmt->execute([
                $meetingId,
                $studentId,
                $oldStatus,
                $newAttStatus,
                $user['id'],
                $auditReason ?: 'Routine correction during review session'
            ]);
        }

        $saveAttStmt->execute([$meetingId, $studentId, $newAttStatus, $user['id']]);
    }

    // Update meeting feedback and status
    $updateMeetingStmt = $pdo->prepare("
        UPDATE weekly_meetings 
        SET status = ?, 
            guide_feedback = ? 
        WHERE id = ?
    ");
    $updateMeetingStmt->execute([$status, $feedback, $meetingId]);

    $pdo->commit();
    set_flash('success', "Saturday Week {$meeting['week_number']} review remarks and attendance saved successfully!");

} catch (\Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Error recording attendance: ' . $e->getMessage());
}

header('Location: dashboard.php');
exit;
