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
    
    if ($classroom_id && $project_id && $target_user_id && in_array($action, ['accept', 'decline'])) {
        // Validate that the currently logged in user is actually the leader of this project AND project is in this classroom
        $stmtCheck = $pdo->prepare("
            SELECT 1 FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            WHERE pm.project_id = ? AND pm.user_id = ? AND pm.is_leader = 1 AND pm.join_status = 'Active' AND p.classroom_id = ?
        ");
        $stmtCheck->execute([$project_id, $_SESSION['user_id'], $classroom_id]);
        
        if ($stmtCheck->fetch()) {
            if ($action === 'accept') {
                // Check if the project already has 4 active members
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND join_status = 'Active'");
                $stmtCount->execute([$project_id]);
                $count = $stmtCount->fetchColumn();
                
                if ($count < 4) {
                    $stmtUpdate = $pdo->prepare("UPDATE project_members SET join_status = 'Active' WHERE project_id = ? AND user_id = ? AND join_status = 'Pending'");
                    $stmtUpdate->execute([$project_id, $target_user_id]);
                }
            } elseif ($action === 'decline') {
                // Remove the pending request entirely (or set to Declined)
                $stmtDelete = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Pending'");
                $stmtDelete->execute([$project_id, $target_user_id]);
            }
        }
        
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
        exit;
    }
}
header("Location: hub.php");
exit;
