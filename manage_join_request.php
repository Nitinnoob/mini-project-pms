<?php
require_once 'bootstrap.php';
require_once 'repositories/membership_repository.php';

require_login();

if (is_post()) {
    csrf_verify();

    $classroom_id = !empty($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : null;
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    $action = $_POST['action'] ?? null;

    if ($classroom_id && $project_id && in_array($action, ['accept', 'decline', 'withdraw'], true)) {
        if ($action === 'accept' || $action === 'decline') {
            $target_user_id = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
            if (!$target_user_id) {
                header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroom_id));
                exit;
            }

            // Only project leaders can accept/decline requests.
            if (is_project_leader($pdo, $project_id, (int)$_SESSION['user_id'], $classroom_id)) {
                if ($action === 'accept') {
                    // Fetch the classroom's configured max team size (hard-capped at 4)
                    $maxSize = project_get_classroom_max_team_size($pdo, $project_id);
                    if ($maxSize > 4) { $maxSize = 4; }
                    if ($maxSize < 1) { $maxSize = 1; }

                    // Check if the project already has max active members
                    $count = project_member_active_count($pdo, $project_id);

                    if ($count < $maxSize && $count < 4) {
                        project_member_update_status($pdo, $project_id, $target_user_id, 'Pending', 'Active');
                    }
                } elseif ($action === 'decline') {
                    // Remove the pending request
                    project_member_delete_by_status($pdo, $project_id, $target_user_id, 'Pending');
                }
            }
        } elseif ($action === 'withdraw') {
            // Server always uses $_SESSION['user_id'] and ignores any client-supplied target user_id
            $withdrawing_user_id = (int)$_SESSION['user_id'];
            $status = project_member_get_status($pdo, $project_id, $withdrawing_user_id);
            if ($status === 'Pending') {
                project_member_delete_by_status($pdo, $project_id, $withdrawing_user_id, 'Pending');
            }
        }

        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroom_id));
        exit;
    }
}
header("Location: hub.php");
exit;
