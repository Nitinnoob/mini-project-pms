<?php
require_once 'bootstrap.php';
require_once 'notify.php';
require_once 'repositories/membership_repository.php';

require_login();

if (is_post()) {
    csrf_verify();

    $classroom_id = !empty($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : null;
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    
    if ($classroom_id && $project_id) {
        $user_id = (int)$_SESSION['user_id'];
        // Validate that the user is actually in this classroom
        $role = classroom_role($pdo, $classroom_id, $user_id);
        if ($role !== null) {
            // Check if request already exists
            $existingStatus = project_member_get_status($pdo, $project_id, $user_id);
            if ($existingStatus === null) {
                // One-project invariant: an Active membership, a Pending request, or an open
                // Invitation in this classroom all block a new outgoing request.
                if (!project_member_has_active_or_pending_in_classroom($pdo, $classroom_id, $user_id)) {
                    $maxSize = classroom_get_max_team_size($pdo, $classroom_id);
                    if ($maxSize > 4) { $maxSize = 4; }
                    if ($maxSize < 1) { $maxSize = 1; }
                    $activeCount = project_member_active_count($pdo, $project_id);
                    
                    if ($activeCount < $maxSize && $activeCount < 4) {
                        project_member_add_request($pdo, $project_id, $user_id, 'Pending');

                        notify_project_leaders($pdo, $project_id, 'join_request',
                            htmlspecialchars_decode($_SESSION['username'] ?? 'A classmate') . ' asked to join ' . notify_project_name($pdo, $project_id) . '.',
                            'dashboard.php?classroom_id=' . urlencode($classroom_id));
                    }
                }
            }
        }
        
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
        exit;
    }
}
header("Location: hub.php");
exit;
