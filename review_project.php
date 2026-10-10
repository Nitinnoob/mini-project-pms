<?php
// review_project.php - Faculty guide approves a team's project idea or asks for changes
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/meeting_engine.php';

require_teacher();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

function finish(string $type, string $message): void {
    set_flash($type, $message);
    header('Location: dashboard.php');
    exit;
}

$projectId = (int)($_POST['project_id'] ?? 0);
$action    = $_POST['action'] ?? '';
$note      = trim($_POST['note'] ?? '');

$stmt = $pdo->prepare("
    SELECT p.*, c.teacher_id
    FROM projects p JOIN classrooms c ON p.classroom_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    finish('error', 'Project not found.');
}
if ($project['mentor_id'] != $user['id'] && $project['teacher_id'] != $user['id']) {
    finish('error', 'Only the assigned guide or the classroom coordinator can review this idea.');
}
if ($project['status'] !== 'pending') {
    finish('error', 'This idea is not waiting for review.');
}

if ($action === 'approve') {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE projects SET status = 'approved', review_note = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$note ?: null, $projectId]);
        sync_project_meetings($pdo, $projectId); // weekly reviews start with approval
        $pdo->commit();
    } catch (\Exception $e) {
        $pdo->rollBack();
        finish('error', 'Could not approve: ' . $e->getMessage());
    }
    finish('success', "'{$project['title']}' approved. Its weekly reviews are now scheduled.");
}

if ($action === 'reject') {
    if ($note === '') {
        finish('error', 'Add a note so the team knows what to change.');
    }
    $pdo->prepare("UPDATE projects SET status = 'rejected', review_note = ?, reviewed_at = NOW() WHERE id = ?")
        ->execute([$note, $projectId]);
    finish('success', 'Changes requested. The team leader can revise and resubmit.');
}

finish('error', 'Unknown action.');
