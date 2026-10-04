<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classroom_id = $_POST['classroom_id'] ?? null;
    $mentor_username = trim($_POST['mentor_username'] ?? '');

    if ($classroom_id && $mentor_username) {
        // Verify current user is the Coordinator (classroom creator), not just any Admin
        $stmtAuth = $pdo->prepare("SELECT created_by FROM classrooms WHERE id = ?");
        $stmtAuth->execute([$classroom_id]);
        $classroom = $stmtAuth->fetch(PDO::FETCH_ASSOC);

        if ($classroom && (int)$classroom['created_by'] === (int)$_SESSION['user_id']) {
            // Find the target user by username
            $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmtUser->execute([$mentor_username]);
            $targetUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
            
            if ($targetUser) {
                // Check if they're already in this classroom
                $stmtExisting = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
                $stmtExisting->execute([$classroom_id, $targetUser['id']]);
                $existing = $stmtExisting->fetch(PDO::FETCH_ASSOC);

                if ($existing && $existing['role'] === 'Team Member') {
                    // Reject: cannot upgrade an existing student to Admin — would destroy their USN
                    // Coordinator should remove the student first if they truly intend this
                    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "&error=user_is_student#tab-roster");
                    exit;
                } elseif ($existing && $existing['role'] === 'Admin') {
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
