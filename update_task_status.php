<?php
session_start();
require 'dbs.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Unauthorized');
}

$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if ($task_id && in_array($new_status, ['todo', 'inprogress', 'done'])) {
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $task_id])) {
        echo json_encode(['success' => true]);
        exit;
    }
}
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid data']);
