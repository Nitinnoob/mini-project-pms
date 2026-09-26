<?php
session_start();
require 'dbs.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$classroom_id = $_POST['classroom_id'] ?? null;
$project_id = $_POST['project_id'] ?? null;
$task_id = !empty($_POST['task_id']) ? $_POST['task_id'] : null;

// Validate user is active member or leader
$stmtCheck = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
$stmtCheck->execute([$project_id, $_SESSION['user_id']]);
if (!$stmtCheck->fetch()) {
    header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
    exit;
}

if (isset($_FILES['deliverable']) && $_FILES['deliverable']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Add defense-in-depth: .htaccess to prevent script execution
    $htaccess_path = $uploadDir . ".htaccess";
    if (!file_exists($htaccess_path)) {
        file_put_contents($htaccess_path, "php_flag engine off\nOptions -ExecCGI\nSetHandler default-handler\n");
    }
    
    $fileName = basename($_FILES['deliverable']['name']);
    // Sanitize file name
    $fileName = preg_replace("/[^a-zA-Z0-9.-]/", "_", $fileName);
    
    // Add unique prefix to prevent overwriting
    $uniqueFileName = time() . '_' . $fileName;
    $targetPath = $uploadDir . $uniqueFileName;
    
    // Validate file extension
    $file_extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $allowed_extensions = ['pdf', 'docx', 'zip', 'pptx', 'txt'];

    if (!in_array($file_extension, $allowed_extensions)) {
        die("Error: Invalid file type. Only PDF, DOCX, ZIP, PPTX, and TXT are allowed.");
    }
    
    if (move_uploaded_file($_FILES['deliverable']['tmp_name'], $targetPath)) {
        $stmt = $pdo->prepare("INSERT INTO deliverables (project_id, task_id, uploaded_by, file_name, file_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$project_id, $task_id, $_SESSION['user_id'], $fileName, $targetPath]);
        
        $stmtLog = $pdo->prepare("INSERT INTO activity_log (project_id, user_id, action, details) VALUES (?, ?, 'Uploaded Deliverable', ?)");
        $stmtLog->execute([$project_id, $_SESSION['user_id'], "Uploaded file: " . $fileName]);
    }
}

header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
exit;
