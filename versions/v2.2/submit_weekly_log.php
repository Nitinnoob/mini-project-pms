<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'] ?? null;
    $classroom_id = $_POST['classroom_id'] ?? null;
    $week_number = $_POST['week_number'] ?? null;
    $work_summary = $_POST['work_summary'] ?? '';
    $next_steps = $_POST['next_steps'] ?? '';
    
    if (!$project_id || !$week_number || !$classroom_id) {
        header("Location: dashboard.php");
        exit;
    }
    
    // Verify user is in this project (Leader ideally, but any member can theoretically submit)
    $stmtAuth = $pdo->prepare("SELECT is_leader FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
    $stmtAuth->execute([$project_id, $_SESSION['user_id']]);
    if (!$stmtAuth->fetch()) {
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
        exit;
    }
    
    // Check if a submission already exists and its review status
    $stmtCheck = $pdo->prepare("
        SELECT s.id, r.status 
        FROM weekly_submissions s
        LEFT JOIN weekly_reviews r ON s.id = r.submission_id
        WHERE s.project_id = ? AND s.week_number = ?
    ");
    $stmtCheck->execute([$project_id, $week_number]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existing && $existing['status'] === 'approved') {
        // Cannot modify an approved log
        header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id) . "&error=log_approved");
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        if ($existing) {
            $submission_id = $existing['id'];
            // Update existing submission
            $stmtSub = $pdo->prepare("UPDATE weekly_submissions SET submitted_by = ?, work_summary = ?, next_steps = ?, submitted_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtSub->execute([$_SESSION['user_id'], $work_summary, $next_steps, $submission_id]);
            
            // If it was 'revision_needed', reset to 'pending' so mentor knows to review again
            if ($existing['status'] === 'revision_needed') {
                $stmtRevUpdate = $pdo->prepare("UPDATE weekly_reviews SET status = 'pending' WHERE submission_id = ?");
                $stmtRevUpdate->execute([$submission_id]);
            }
        } else {
            // Insert new submission
            $stmtSub = $pdo->prepare("INSERT INTO weekly_submissions (project_id, week_number, submitted_by, work_summary, next_steps) VALUES (?, ?, ?, ?, ?)");
            $stmtSub->execute([$project_id, $week_number, $_SESSION['user_id'], $work_summary, $next_steps]);
            $submission_id = $pdo->lastInsertId();
            
            // Ensure a 'pending' review row exists
            $stmtRev = $pdo->prepare("INSERT INTO weekly_reviews (submission_id, status) VALUES (?, 'pending')");
            $stmtRev->execute([$submission_id]);
        }
        
        // Handle file uploads
        if (!empty($_FILES['weekly_files']['name'][0])) {
            $uploadDir = 'uploads/weekly/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                // Secure upload dir
                file_put_contents($uploadDir . '.htaccess', "RemoveHandler .php .phtml .php3 .php4 .php5\nRemoveType .php .phtml .php3 .php4 .php5\nphp_flag engine off\nOptions -ExecCGI\n");
            }
            
            $allowedExts = ['pdf', 'docx', 'zip', 'pptx', 'txt', 'png', 'jpg', 'jpeg'];
            
            foreach ($_FILES['weekly_files']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['weekly_files']['error'][$key] === UPLOAD_ERR_OK) {
                    $originalName = basename($_FILES['weekly_files']['name'][$key]);
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowedExts)) {
                        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
                        $fileName = time() . '_' . rand(1000,9999) . '_' . $safeName;
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($tmp_name, $filePath)) {
                            $stmtFile = $pdo->prepare("INSERT INTO weekly_submission_files (submission_id, file_name, file_path) VALUES (?, ?, ?)");
                            $stmtFile->execute([$submission_id, $originalName, $filePath]);
                        }
                    }
                }
            }
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }
    
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
    exit;
}
