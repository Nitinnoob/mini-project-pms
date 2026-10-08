<?php
require_once 'bootstrap.php';
require_once 'notify.php';
require_once 'repositories/membership_repository.php';

require_login(true);

header('Content-Type: application/json');

function respond($ok, $message, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

if (!is_post()) {
    respond(false, 'Invalid request method', 405);
}
csrf_verify(true);

$classroom_id      = !empty($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : null;
$project_id        = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
$target_user_id    = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$action            = $_POST['action'] ?? null;

if (!$classroom_id || !$project_id || !$target_user_id) {
    respond(false, 'Missing required parameters', 400);
}

// Confirm the acting user is the leader of this project, and the project lives in this classroom.
$isLeader = is_project_leader($pdo, $project_id, $_SESSION['user_id'], $classroom_id);

if ($action === 'invite' || $action === 'revoke') {
    if (!$isLeader) {
        respond(false, 'Only the project leader can manage invitations', 403);
    }

    // Target must be a non-Admin member of this same classroom.
    if (!classroom_member_is_eligible_student($pdo, $classroom_id, $target_user_id)) {
        respond(false, 'That person is not an eligible classmate in this classroom', 403);
    }

    // Existing relationship with this project, if any.
    $existingStatus = project_member_get_status($pdo, $project_id, $target_user_id);

    if ($action === 'revoke') {
        if ($existingStatus !== 'Invited') {
            respond(false, 'No pending invitation to revoke', 404);
        }
        project_member_delete_by_status($pdo, $project_id, $target_user_id, 'Invited');
        respond(true, 'Invitation revoked');
    }

    // action === 'invite'
    if ($existingStatus !== null) {
        respond(false, 'This classmate already has a ' . strtolower($existingStatus) . ' record on this project', 409);
    }

    $maxSize = classroom_get_max_team_size($pdo, $classroom_id);
    $activeCount = project_member_active_count($pdo, $project_id);
    if ($activeCount >= $maxSize) {
        respond(false, 'This team is already at its maximum size', 409);
    }

    project_member_add_request($pdo, $project_id, $target_user_id, 'Invited');

    notify_user($pdo, $target_user_id, 'invite',
        'You have been invited to join ' . notify_project_name($pdo, $project_id) . '.',
        'dashboard.php?classroom_id=' . urlencode($classroom_id));

    respond(true, 'Invitation sent');

} elseif ($action === 'accept_invite' || $action === 'decline_invite') {
    // Only the invited student themself may respond to the invitation.
    if ($target_user_id != (int)$_SESSION['user_id']) {
        respond(false, 'You can only respond to your own invitation', 403);
    }

    $myStatus = project_member_get_status($pdo, $project_id, (int)$_SESSION['user_id']);
    if ($myStatus !== 'Invited') {
        respond(false, 'This invitation is no longer active', 404);
    }

    if ($action === 'decline_invite') {
        project_member_delete_by_status($pdo, $project_id, (int)$_SESSION['user_id'], 'Invited');
        notify_project_leaders($pdo, $project_id, 'invite_response',
            htmlspecialchars_decode($_SESSION['username'] ?? 'A classmate') . ' declined your invitation to ' . notify_project_name($pdo, $project_id) . '.',
            'dashboard.php?classroom_id=' . urlencode($classroom_id), $_SESSION['user_id']);
        respond(true, 'Invitation declined');
    }

    // action === 'accept_invite' — re-check capacity at accept time, not just at invite time.
    $maxSize = project_get_classroom_max_team_size($pdo, $project_id);
    $activeCount = project_member_active_count($pdo, $project_id);

    if ($activeCount >= $maxSize) {
        respond(false, 'This team filled up before you could accept', 409);
    }

    $pdo->beginTransaction();
    try {
        project_member_update_status($pdo, $project_id, (int)$_SESSION['user_id'], 'Invited', 'Active');

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
