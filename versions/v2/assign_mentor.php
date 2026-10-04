<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? null;
    $mentor_id = !empty($_POST['mentor_id']) ? $_POST['mentor_id'] : null;
    $classroom_id = $_POST['classroom_id'] ?? null;

    if ($project_id && $classroom_id) {
        // Verify user is the Coordinator (classroom creator), not just any Admin
        $stmtAuth = $pdo->prepare("SELECT created_by FROM classrooms WHERE id = ?");
        $stmtAuth->execute([$classroom_id]);
        $classroom = $stmtAuth->fetch(PDO::FETCH_ASSOC);

        if ($classroom && (int)$classroom['created_by'] === (int)$_SESSION['user_id']) {
            // Update the project's mentor
            $stmtUpdate = $pdo->prepare("UPDATE projects SET mentor_id = ? WHERE id = ? AND classroom_id = ?");
            $stmtUpdate->execute([$mentor_id, $project_id, $classroom_id]);
        }
    }
    
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
    exit;
}
