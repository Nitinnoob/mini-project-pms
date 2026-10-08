<?php
require_once 'bootstrap.php';
require_once 'notify.php';
require_once 'repositories/task_repository.php';
require_once 'repositories/project_repository.php';

header('Content-Type: application/json');
require_login(true);
if (!is_post()) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}
csrf_verify(true);

$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if ($task_id && in_array($new_status, TASK_STATUSES, true)) {
    // IDOR verification: the user must be an active member of the project this task is in
    $taskRow = task_find_with_project($pdo, (int)$task_id);
    
    if ($taskRow && is_active_project_member($pdo, $taskRow['project_id'], $_SESSION['user_id'])) {
        if (task_update_status($pdo, (int)$task_id, $new_status)) {
            activity_log_add($pdo, $taskRow['project_id'], $_SESSION['user_id'], 'Updated Task', "Moved task '" . $taskRow['title'] . "' to " . $new_status);

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
