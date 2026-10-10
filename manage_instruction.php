<?php
// manage_instruction.php - Actionable Mentor Directives Lifecycle & Rollover
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_login();
$user = current_user();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    require_teacher();
    $meetingId = (int)($_POST['meeting_id'] ?? 0);
    $text = trim($_POST['text'] ?? '');

    if ($meetingId <= 0 || empty($text)) {
        set_flash('error', 'Directive text is required.');
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO guide_instructions (meeting_id, text, status, created_by)
            VALUES (?, ?, 'open', ?)
        ");
        $stmt->execute([$meetingId, $text, $user['id']]);
        set_flash('success', 'Mentor directive added successfully.');
    }
} elseif ($action === 'update_status') {
    $instructionId = (int)($_POST['instruction_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    if (!in_array($newStatus, ['open', 'acknowledged', 'done'])) {
        set_flash('error', 'Invalid instruction status.');
    } else {
        // Students can only acknowledge; teachers can sign off as done
        if ($newStatus === 'done' && !is_teacher()) {
            set_flash('error', 'Only faculty guides can sign off directives as Done.');
        } else {
            $closedAt = ($newStatus === 'done') ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("
                UPDATE guide_instructions 
                SET status = ?, closed_at = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $closedAt, $instructionId]);
            set_flash('success', "Directive updated to '" . ucfirst($newStatus) . "'.");
        }
    }
} elseif ($action === 'delete') {
    require_teacher();
    $instructionId = (int)($_POST['instruction_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM guide_instructions WHERE id = ?");
    $stmt->execute([$instructionId]);
    set_flash('success', 'Directive deleted.');
}

header('Location: dashboard.php');
exit;
