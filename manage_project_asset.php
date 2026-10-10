<?php
// manage_project_asset.php - Faculty Media Curation, Spotlight & Deletion
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_login();
$user = current_user();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$assetId = (int)($_POST['asset_id'] ?? $_GET['asset_id'] ?? 0);

if ($assetId <= 0) {
    set_flash('error', 'Invalid asset specified.');
    header('Location: dashboard.php');
    exit;
}

// Fetch asset and check authorization
$stmt = $pdo->prepare("
    SELECT pa.*, p.mentor_id, c.teacher_id, p.id as project_id
    FROM project_assets pa
    JOIN projects p ON pa.project_id = p.id
    JOIN classrooms c ON p.classroom_id = c.id
    WHERE pa.id = ?
");
$stmt->execute([$assetId]);
$asset = $stmt->fetch();

if (!$asset) {
    set_flash('error', 'Asset not found.');
    header('Location: dashboard.php');
    exit;
}

$isTeacherOrMentor = is_teacher() && ($user['id'] == $asset['mentor_id'] || $user['id'] == $asset['teacher_id']);
$isOwner = ($user['id'] == $asset['uploaded_by']);

if ($action === 'toggle_spotlight') {
    // Only teacher can spotlight for exhibition reels
    if (!$isTeacherOrMentor) {
        set_flash('error', 'Only faculty guides can spotlight assets for exhibition.');
    } else {
        $newSpotlight = $asset['is_spotlight'] ? 0 : 1;
        $upd = $pdo->prepare("UPDATE project_assets SET is_spotlight = ? WHERE id = ?");
        $upd->execute([$newSpotlight, $assetId]);
        set_flash('success', $newSpotlight ? 'Asset pinned to Department Exhibition Spotlight ⭐' : 'Asset removed from spotlight.');
    }
} elseif ($action === 'delete') {
    if (!$isTeacherOrMentor && !$isOwner) {
        set_flash('error', 'Unauthorized to delete this asset.');
    } else {
        if (!empty($asset['file_path'])) {
            $absPath = __DIR__ . '/' . $asset['file_path'];
            if (file_exists($absPath) && is_file($absPath)) {
                @unlink($absPath);
            }
        }
        $del = $pdo->prepare("DELETE FROM project_assets WHERE id = ?");
        $del->execute([$assetId]);
        set_flash('success', 'Asset successfully deleted.');
    }
}

header('Location: dashboard.php');
exit;
