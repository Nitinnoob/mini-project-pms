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
        $stmt = $pdo->prepare("
            SELECT cm.role 
            FROM classroom_members cm
            JOIN projects p ON p.classroom_id = cm.classroom_id
            WHERE cm.classroom_id = ? AND cm.user_id = ? AND p.id = ?
        ");
        $stmt->execute([$classroom_id, $_SESSION['user_id'], $project_id]);
        if ($stmt->fetch()) {
            // Check if request already exists
            $stmtCheck = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
            $stmtCheck->execute([$project_id, $_SESSION['user_id']]);
            
            if (!$stmtCheck->fetch()) {
                // Check if the student is already active/pending in another project in this classroom
                $stmtExisting = $pdo->prepare("
                    SELECT 1 FROM project_members pm
                    JOIN projects p ON pm.project_id = p.id
                    WHERE p.classroom_id = ? AND pm.user_id = ? AND pm.join_status IN ('Active', 'Pending')
                ");
                $stmtExisting->execute([$classroom_id, $_SESSION['user_id']]);
                
                if (!$stmtExisting->fetch()) {
                    // Check if the team is already full
                    $stmtMax = $pdo->prepare("SELECT c.max_team_size FROM classrooms c WHERE c.id = ?");
                    $stmtMax->execute([$classroom_id]);
                    $maxSize = $stmtMax->fetchColumn();
                    if ($maxSize === false) $maxSize = 10;
                    
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND join_status = 'Active'");
                    $stmtCount->execute([$project_id]);
                    $activeCount = $stmtCount->fetchColumn();
                    
                    if ($activeCount < $maxSize) {
                        // Insert join request
                        $stmtInsert = $pdo->prepare("INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (?, ?, 0, 'Pending')");
                        $stmtInsert->execute([$project_id, $_SESSION['user_id']]);
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
