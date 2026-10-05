<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not signed in']);
    exit;
}
require 'dbs.php';
require_once 'notify.php';

header('Content-Type: application/json');

function respond($ok, $message, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method', 405);
}

$classroom_id      = $_POST['classroom_id'] ?? null;
$project_id        = $_POST['project_id'] ?? null;
$target_user_id    = $_POST['user_id'] ?? null;
$action            = $_POST['action'] ?? null;

if (!$classroom_id || !$project_id || !$target_user_id) {
    respond(false, 'Missing required parameters', 400);
}

// Confirm the acting user is the leader of this project, and the project lives in this classroom.
$stmtLeader = $pdo->prepare("
    SELECT 1 FROM project_members pm
    JOIN projects p ON pm.project_id = p.id
    WHERE pm.project_id = ? AND pm.user_id = ? AND pm.is_leader = 1
      AND pm.join_status = 'Active' AND p.classroom_id = ?
");
$stmtLeader->execute([$project_id, $_SESSION['user_id'], $classroom_id]);
$isLeader = (bool)$stmtLeader->fetch();

if ($action === 'invite' || $action === 'revoke') {
    if (!$isLeader) {
        respond(false, 'Only the project leader can manage invitations', 403);
    }

    // Target must be a non-Admin member of this same classroom.
    $stmtEligible = $pdo->prepare("
        SELECT 1 FROM classroom_members
        WHERE classroom_id = ? AND user_id = ? AND role != 'Admin'
    ");
    $stmtEligible->execute([$classroom_id, $target_user_id]);
    if (!$stmtEligible->fetch()) {
        respond(false, 'That person is not an eligible classmate in this classroom', 403);
    }

    // Existing relationship with this project, if any.
    $stmtExisting = $pdo->prepare("SELECT join_status FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmtExisting->execute([$project_id, $target_user_id]);
    $existing = $stmtExisting->fetch(PDO::FETCH_ASSOC);

    if ($action === 'revoke') {
        if (!$existing || $existing['join_status'] !== 'Invited') {
            respond(false, 'No pending invitation to revoke', 404);
        }
        $stmtDelete = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Invited'");
        $stmtDelete->execute([$project_id, $target_user_id]);
        respond(true, 'Invitation revoked');
    }

    // action === 'invite'
    if ($existing) {
        respond(false, 'This classmate already has a ' . strtolower($existing['join_status']) . ' record on this project', 409);
    }

    $stmtMax = $pdo->prepare("SELECT max_team_size FROM classrooms WHERE id = ?");
    $stmtMax->execute([$classroom_id]);
    $maxSize = $stmtMax->fetchColumn();
    if ($maxSize === false) $maxSize = 10;

    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND join_status = 'Active'");
    $stmtCount->execute([$project_id]);
    if ((int)$stmtCount->fetchColumn() >= (int)$maxSize) {
        respond(false, 'This team is already at its maximum size', 409);
    }

    $stmtInsert = $pdo->prepare("INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (?, ?, 0, 'Invited')");
    $stmtInsert->execute([$project_id, $target_user_id]);

    notify_user($pdo, $target_user_id, 'invite',
        'You have been invited to join ' . notify_project_name($pdo, $project_id) . '.',
        'dashboard.php?classroom_id=' . urlencode($classroom_id));

    respond(true, 'Invitation sent');

} elseif ($action === 'accept_invite' || $action === 'decline_invite') {
    // Only the invited student themself may respond to the invitation.
    if ($target_user_id != $_SESSION['user_id']) {
        respond(false, 'You can only respond to your own invitation', 403);
    }

    $stmtInvite = $pdo->prepare("
        SELECT pm.join_status FROM project_members pm
        JOIN projects p ON pm.project_id = p.id
        WHERE pm.project_id = ? AND pm.user_id = ? AND pm.join_status = 'Invited' AND p.classroom_id = ?
    ");
    $stmtInvite->execute([$project_id, $_SESSION['user_id'], $classroom_id]);
    if (!$stmtInvite->fetch()) {
        respond(false, 'This invitation is no longer active', 404);
    }

    if ($action === 'decline_invite') {
        $stmtDelete = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Invited'");
        $stmtDelete->execute([$project_id, $_SESSION['user_id']]);
        notify_project_leaders($pdo, $project_id, 'invite_response',
            htmlspecialchars_decode($_SESSION['username'] ?? 'A classmate') . ' declined your invitation to ' . notify_project_name($pdo, $project_id) . '.',
            'dashboard.php?classroom_id=' . urlencode($classroom_id), $_SESSION['user_id']);
        respond(true, 'Invitation declined');
    }

    // action === 'accept_invite' — re-check capacity at accept time, not just at invite time.
    $stmtRoom = $pdo->prepare("
        SELECT c.max_team_size,
               (SELECT COUNT(*) FROM project_members WHERE project_id = ? AND join_status = 'Active') AS active_count
        FROM classrooms c JOIN projects p ON p.classroom_id = c.id
        WHERE p.id = ?
    ");
    $stmtRoom->execute([$project_id, $project_id]);
    $room = $stmtRoom->fetch(PDO::FETCH_ASSOC);
    $maxSize = ($room && $room['max_team_size'] !== null) ? (int)$room['max_team_size'] : 10;

    if ((int)$room['active_count'] >= $maxSize) {
        respond(false, 'This team filled up before you could accept', 409);
    }

    $pdo->beginTransaction();
    try {
        $stmtAccept = $pdo->prepare("UPDATE project_members SET join_status = 'Active' WHERE project_id = ? AND user_id = ? AND join_status = 'Invited'");
        $stmtAccept->execute([$project_id, $_SESSION['user_id']]);

        // Joining closes any other outgoing request or invitation this student has in the classroom.
        $stmtClose = $pdo->prepare("
            DELETE pm FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            WHERE p.classroom_id = ? AND pm.user_id = ? AND pm.project_id != ?
              AND pm.join_status IN ('Pending', 'Invited')
        ");
        $stmtClose->execute([$classroom_id, $_SESSION['user_id'], $project_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        respond(false, 'Could not accept the invitation. Please try again.', 500);
    }

    notify_project_leaders($pdo, $project_id, 'invite_response',
        htmlspecialchars_decode($_SESSION['username'] ?? 'A classmate') . ' accepted your invitation and joined ' . notify_project_name($pdo, $project_id) . '.',
        'dashboard.php?classroom_id=' . urlencode($classroom_id), $_SESSION['user_id']);

    respond(true, 'You have joined the project');

} else {
    respond(false, 'Unknown action', 400);
}
