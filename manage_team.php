<?php
// manage_team.php - Student team actions: request_join, accept, decline, leave, submit, revise
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_student();
$user = current_user();

const MAX_TEAM_SIZE = 4;

function finish(string $type, string $message): void {
    set_flash($type, $message);
    header('Location: dashboard.php');
    exit;
}

function team_size(PDO $pdo, int $projectId): int {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ?");
    $stmt->execute([$projectId]);
    return (int)$stmt->fetchColumn();
}

function has_team(PDO $pdo, int $classroomId, int $studentId): bool {
    $stmt = $pdo->prepare("
        SELECT 1 FROM project_members pm JOIN projects p ON p.id = pm.project_id
        WHERE p.classroom_id = ? AND pm.student_id = ?
    ");
    $stmt->execute([$classroomId, $studentId]);
    return (bool)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$action    = $_POST['action'] ?? '';
$projectId = (int)($_POST['project_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();
if (!$project) {
    finish('error', 'Team not found.');
}

$classroomId = (int)$project['classroom_id'];
$isLeader    = ($project['created_by'] == $user['id']);

switch ($action) {

    case 'request_join':
        if ($project['status'] !== 'forming') finish('error', 'This team is no longer accepting members.');
        if (has_team($pdo, $classroomId, $user['id'])) finish('error', 'You are already in a team.');
        if (team_size($pdo, $projectId) >= MAX_TEAM_SIZE) finish('error', 'This team is full.');

        $pdo->prepare("INSERT INTO team_requests (project_id, student_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE status = 'pending'")
            ->execute([$projectId, $user['id']]);
        finish('success', 'Request sent. The team leader will accept or decline it.');

    case 'accept':
    case 'decline':
        if (!$isLeader) finish('error', 'Only the team leader can answer join requests.');
        if ($project['status'] !== 'forming') finish('error', 'This team is no longer accepting members.');

        $studentId = (int)($_POST['student_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT 1 FROM team_requests WHERE project_id = ? AND student_id = ? AND status = 'pending'");
        $stmt->execute([$projectId, $studentId]);
        if (!$stmt->fetch()) finish('error', 'That request no longer exists.');

        if ($action === 'decline') {
            $pdo->prepare("UPDATE team_requests SET status = 'declined' WHERE project_id = ? AND student_id = ?")->execute([$projectId, $studentId]);
            finish('success', 'Request declined.');
        }

        if (team_size($pdo, $projectId) >= MAX_TEAM_SIZE) finish('error', 'Your team is full.');
        if (has_team($pdo, $classroomId, $studentId)) {
            $pdo->prepare("UPDATE team_requests SET status = 'declined' WHERE project_id = ? AND student_id = ?")->execute([$projectId, $studentId]);
            finish('error', 'That student has already joined another team.');
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO project_members (project_id, student_id, is_leader) VALUES (?, ?, 0)")->execute([$projectId, $studentId]);
            $pdo->prepare("INSERT IGNORE INTO student_marks (project_id, student_id) VALUES (?, ?)")->execute([$projectId, $studentId]);
            $pdo->prepare("UPDATE team_requests SET status = 'accepted' WHERE project_id = ? AND student_id = ?")->execute([$projectId, $studentId]);
            // Their requests to other teams in this classroom are now moot
            $pdo->prepare("
                UPDATE team_requests tr JOIN projects p ON p.id = tr.project_id
                SET tr.status = 'declined'
                WHERE tr.student_id = ? AND tr.status = 'pending' AND p.classroom_id = ?
            ")->execute([$studentId, $classroomId]);
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            finish('error', 'Could not add the student: ' . $e->getMessage());
        }
        finish('success', 'Student added to your team.');

    case 'leave':
        if ($isLeader) finish('error', 'The team leader cannot leave the team.');
        if ($project['status'] !== 'forming') finish('error', 'The team has been submitted and can no longer change.');

        $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND student_id = ?")->execute([$projectId, $user['id']]);
        $pdo->prepare("DELETE FROM student_marks WHERE project_id = ? AND student_id = ?")->execute([$projectId, $user['id']]);
        finish('success', 'You left the team.');

    case 'submit':
        if (!$isLeader) finish('error', 'Only the team leader can submit the team.');
        if ($project['status'] !== 'forming') finish('error', 'This team has already been submitted.');

        $pdo->prepare("UPDATE projects SET status = 'pending' WHERE id = ?")->execute([$projectId]);
        $pdo->prepare("UPDATE team_requests SET status = 'declined' WHERE project_id = ? AND status = 'pending'")->execute([$projectId]);
        finish('success', 'Submitted. Your guide will review the idea.');

    case 'revise':
        if (!$isLeader) finish('error', 'Only the team leader can edit the idea.');
        if (!in_array($project['status'], ['forming', 'rejected'])) finish('error', 'The idea can no longer be edited.');

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($title === '') finish('error', 'Project title is required.');

        $newStatus = ($project['status'] === 'rejected') ? 'pending' : 'forming';
        $pdo->prepare("UPDATE projects SET title = ?, description = ?, status = ? WHERE id = ?")->execute([$title, $description, $newStatus, $projectId]);
        finish('success', $newStatus === 'pending' ? 'Resubmitted. Your guide will review it again.' : 'Idea saved.');

    default:
        finish('error', 'Unknown action.');
}
