<?php
// upload_project_asset.php - Student Project Media & Showcase Asset Uploader
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$projectId = (int)($_POST['project_id'] ?? 0);
$assetType = trim($_POST['asset_type'] ?? '');
$title = trim($_POST['title'] ?? '');
$caption = trim($_POST['caption'] ?? '');
$externalUrl = trim($_POST['external_url'] ?? '');

if ($projectId <= 0 || empty($title) || !in_array($assetType, ['screenshot', 'photo', 'codebase', 'demo_link'])) {
    set_flash('error', 'Please provide a valid asset title and asset category.');
    header('Location: dashboard.php');
    exit;
}

// Check membership or mentor authorization
$authStmt = $pdo->prepare("
    SELECT p.id 
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE p.id = ? AND (pm.student_id = ? OR p.mentor_id = ?)
    LIMIT 1
");
$authStmt->execute([$projectId, $user['id'], $user['id']]);
if (!$authStmt->fetch()) {
    set_flash('error', 'Unauthorized: You are not assigned to this project.');
    header('Location: dashboard.php');
    exit;
}

$filePath = null;

if (in_array($assetType, ['screenshot', 'photo'])) {
    if (!isset($_FILES['asset_file']) || $_FILES['asset_file']['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Please select a valid image file to upload.');
        header('Location: dashboard.php');
        exit;
    }

    $file = $_FILES['asset_file'];
    $maxSize = 10 * 1024 * 1024; // 10MB limit

    if ($file['size'] > $maxSize) {
        set_flash('error', 'File size exceeds maximum permitted limit of 10MB.');
        header('Location: dashboard.php');
        exit;
    }

    // Defensive Server-Side MIME Inspection
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimes[$mimeType])) {
        set_flash('error', 'Invalid file format. Only JPEG, PNG, and WebP images are permitted.');
        header('Location: dashboard.php');
        exit;
    }

    $extension = $allowedMimes[$mimeType];
    $uploadDir = __DIR__ . '/uploads/projects/' . $projectId;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Secure randomized filename
    $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetDestination = $uploadDir . '/' . $randomName;

    if (!move_uploaded_file($file['tmp_name'], $targetDestination)) {
        set_flash('error', 'Failed to store uploaded file on server.');
        header('Location: dashboard.php');
        exit;
    }

    $filePath = 'uploads/projects/' . $projectId . '/' . $randomName;
    $externalUrl = null;

} else {
    // Codebase or Demo Link
    if (empty($externalUrl) || !filter_var($externalUrl, FILTER_VALIDATE_URL)) {
        set_flash('error', 'Please provide a valid complete URL (e.g. https://github.com/...)');
        header('Location: dashboard.php');
        exit;
    }
    $filePath = null;
}

$insert = $pdo->prepare("
    INSERT INTO project_assets (project_id, uploaded_by, asset_type, title, caption, file_path, external_url, is_spotlight)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0)
");
$insert->execute([$projectId, $user['id'], $assetType, $title, $caption ?: null, $filePath, $externalUrl]);

set_flash('success', "Project asset '{$title}' successfully published to showcase gallery!");
header('Location: dashboard.php');
exit;
