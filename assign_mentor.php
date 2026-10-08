<?php
require_once 'bootstrap.php';

require_login();

if (is_post()) {
    csrf_verify();

    $project_id = $_POST['project_id'] ?? null;
    $mentor_id = !empty($_POST['mentor_id']) ? $_POST['mentor_id'] : null;
    $classroom_id = $_POST['classroom_id'] ?? null;

    if ($project_id && $classroom_id) {
        // Verify user is the Coordinator (classroom creator), not just any Admin
        if (is_classroom_coordinator($pdo, $classroom_id, $_SESSION['user_id'])) {
            // Update the project's mentor
            $stmtUpdate = $pdo->prepare("UPDATE projects SET mentor_id = ? WHERE id = ? AND classroom_id = ?");
            $stmtUpdate->execute([$mentor_id, $project_id, $classroom_id]);
        }
    }
    
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
    exit;
}

header("Location: dashboard.php");
exit;
