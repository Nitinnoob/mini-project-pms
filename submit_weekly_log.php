<?php
// submit_weekly_log.php - Student Saturday Review Progress Submission
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/meeting_engine.php';

require_student();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$meetingId = (int)($_POST['meeting_id'] ?? 0);
$workDone = trim($_POST['work_done'] ?? '');
$nextSteps = trim($_POST['next_steps'] ?? '');
$blockers = trim($_POST['blockers'] ?? '');

if ($meetingId <= 0 || empty($workDone) || empty($nextSteps)) {
    set_flash('error', 'Please provide technical work done and planned next steps.');
    header('Location: dashboard.php');
    exit;
}

// Fetch meeting and verify student is in this project
$stmt = $pdo->prepare("
    SELECT wm.*, p.id as project_id, p.title as project_title
    FROM weekly_meetings wm
    JOIN projects p ON wm.project_id = p.id
    JOIN project_members pm ON p.id = pm.project_id
    WHERE wm.id = ? AND pm.student_id = ?
");
$stmt->execute([$meetingId, $user['id']]);
$meeting = $stmt->fetch();

if (!$meeting) {
    set_flash('error', 'Unauthorized or invalid meeting record.');
    header('Location: dashboard.php');
    exit;
}

// Check Strict Temporal Week Locking
$temporal = get_week_temporal_status($meeting['meeting_date'], $meeting['status']);

if (!$temporal['student_can_edit']) {
    if ($temporal['is_future']) {
        set_flash('error', "🔒 Week {$meeting['week_number']} is locked for future dates. Submissions unlock on {$temporal['date_from']}.");
    } else {
        set_flash('error', "🔒 Week {$meeting['week_number']} is frozen. Past reviews or held meetings cannot be altered.");
    }
    header('Location: dashboard.php');
    exit;
}

// Save update as structured JSON
$updateJson = json_encode([
    'work_done'  => $workDone,
    'next_steps' => $nextSteps,
    'blockers'   => $blockers ?: 'None reported',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$updateStmt = $pdo->prepare("
    UPDATE weekly_meetings 
    SET team_update = ?, 
        submitted_by = ?, 
        submitted_at = NOW()
    WHERE id = ?
");
$updateStmt->execute([$updateJson, $user['id'], $meetingId]);

set_flash('success', "Saturday Week {$meeting['week_number']} progress log successfully submitted for guide review!");
header('Location: dashboard.php');
exit;
