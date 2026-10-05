<?php
session_start();
require 'dbs.php';
require_once 'notify.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit('Unauthorized');
}

$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if ($task_id && in_array($new_status, ['todo', 'inprogress', 'done'])) {
    // IDOR verification: Check if user belongs to the project this task is in
    $stmtCheck = $pdo->prepare("
        SELECT t.project_id, t.title, t.assigned_to, p.classroom_id FROM project_members pm
        JOIN tasks t ON pm.project_id = t.project_id
        JOIN projects p ON p.id = t.project_id
        WHERE t.id = ? AND pm.user_id = ? AND pm.join_status = 'Active'
    ");
    $stmtCheck->execute([$task_id, $_SESSION['user_id']]);
    $taskRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($taskRow) {
        $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $task_id])) {
            $stmtLog = $pdo->prepare("INSERT INTO activity_log (project_id, user_id, action, details) VALUES (?, ?, 'Updated Task', ?)");
            $stmtLog->execute([$taskRow['project_id'], $_SESSION['user_id'], "Moved task '" . $taskRow['title'] . "' to " . $new_status]);

            if (!empty($taskRow['assigned_to']) && (int)$taskRow['assigned_to'] !== (int)$_SESSION['user_id']) {
                $labels = ['todo' => 'To Do', 'inprogress' => 'In Progress', 'done' => 'Done'];
                notify_user($pdo, $taskRow['assigned_to'], 'task_updated',
                    htmlspecialchars_decode($_SESSION['username'] ?? 'A teammate') . " moved your task '" . $taskRow['title'] . "' to " . $labels[$new_status] . '.',
                    'dashboard.php?classroom_id=' . urlencode($taskRow['classroom_id']));
            }
            echo json_encode(['success' => true]);
            exit;
        }
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized for this task']);
        exit;
    }
}
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid data']);
