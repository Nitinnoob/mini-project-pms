<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classroom_id = $_POST['classroom_id'] ?? null;
    $project_id = $_POST['project_id'] ?? null;
    $target_user_id = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($classroom_id && $project_id && $target_user_id && $action === 'accept') {
        // Validate that the currently logged in user is actually the leader of this project
        $stmtCheck = $pdo->prepare("
            SELECT 1 FROM project_members 
            WHERE project_id = ? AND user_id = ? AND is_leader = 1 AND join_status = 'Active'
        ");
        $stmtCheck->execute([$project_id, $_SESSION['user_id']]);
        
        if ($stmtCheck->fetch()) {
            // Update the target user's join request from Pending to Active
            $stmtUpdate = $pdo->prepare("
                UPDATE project_members 
                SET join_status = 'Active' 
                WHERE project_id = ? AND user_id = ? AND join_status = 'Pending'
            ");
            $stmtUpdate->execute([$project_id, $target_user_id]);
        }
        
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
        exit;
    }
}
header("Location: hub.php");
exit;
