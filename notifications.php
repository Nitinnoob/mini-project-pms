<?php
/**
 * Notification API for the header bell.
 *   GET  notifications.php            -> {unread, items:[...latest 15]}
 *   POST notifications.php action=read id=<n>   (mark one read)
 *   POST notifications.php action=read_all      (mark all read)
 */
session_start();
require 'dbs.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['unread' => 0, 'items' => []]);
    exit;
}
$uid = (int)$_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'read_all') {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);
        } elseif ($action === 'read' && !empty($_POST['id'])) {
            $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?")->execute([(int)$_POST['id'], $uid]);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    $stmtU = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtU->execute([$uid]);
    $stmt = $pdo->prepare("SELECT id, type, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 15");
    $stmt->execute([$uid]);
    echo json_encode(['unread' => (int)$stmtU->fetchColumn(), 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    // notifications table not migrated yet
    echo json_encode(['unread' => 0, 'items' => []]);
}
