<?php
require_once 'bootstrap.php';

require_login();

if (is_post()) {
    csrf_verify();

    $classroom_id = $_POST['classroom_id'] ?? null;
    $mentor_username = trim($_POST['mentor_username'] ?? '');

    if ($classroom_id && $mentor_username) {
        // Verify current user is the Coordinator (classroom creator), not just any Admin
        if (is_classroom_coordinator($pdo, $classroom_id, $_SESSION['user_id'])) {
            // Find the target user by username
            $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtUser->execute([$mentor_username]);
            $targetUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
            
            if ($targetUser) {
                // Check if they're already in this classroom
                $existingRole = classroom_role($pdo, $classroom_id, $targetUser['id']);

                if ($existingRole === 'Team Member') {
                    // Reject: cannot upgrade an existing student to Admin — would destroy their USN
                    // Coordinator should remove the student first if they truly intend this
                    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "&error=user_is_student#tab-roster");
                    exit;
                } elseif ($existingRole === 'Admin') {
                    // Already an Admin/Mentor — nothing to do
                } else {
                    // New member: insert as Admin (mentor)
                    $stmtAdd = $pdo->prepare("INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (?, ?, 'Admin', NULL)");
                    $stmtAdd->execute([$classroom_id, $targetUser['id']]);
                }
            }
        }
    }
    
    // Redirect back to the roster tab
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "#tab-roster");
    exit;
}

header("Location: dashboard.php");
exit;
