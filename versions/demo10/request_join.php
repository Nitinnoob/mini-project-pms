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
    
    if ($classroom_id && $project_id) {
        // Validate that the user is actually in this classroom
        $stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
        $stmt->execute([$classroom_id, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            // Check if request already exists
            $stmtCheck = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmtCheck->execute([$project_id, $_SESSION['user_id']]);
            
            if (!$stmtCheck->fetch()) {
                // Insert join request
                $stmtInsert = $pdo->prepare("INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (?, ?, 0, 'Pending')");
                $stmtInsert->execute([$project_id, $_SESSION['user_id']]);
            }
        }
        
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
        exit;
    }
}
header("Location: hub.php");
exit;
