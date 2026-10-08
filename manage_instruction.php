<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'auth_guard.php';
require_once 'notify.php';
require_once 'repositories/meeting_repository.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

csrf_verify();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

$action        = trim((string)($_POST['action'] ?? ''));
$instructionId = (int)($_POST['instruction_id'] ?? 0);
$meetingId     = (int)($_POST['meeting_id'] ?? 0);
$projectId     = (int)($_POST['project_id'] ?? 0);
$classroomId   = (int)($_POST['classroom_id'] ?? 0);
$text          = trim((string)($_POST['text'] ?? ''));

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

if ($action === 'add') {
    if ($meetingId <= 0 || $text === '') {
        if ($isAjax) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Meeting ID and non-empty instruction text are required.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Meeting ID and non-empty instruction text are required.';
        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
        exit;
    }

    $meeting = meeting_find($pdo, $meetingId);
    if (!$meeting) {
        if ($isAjax) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Meeting not found.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Meeting not found.';
        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
        exit;
    }

    $projectId = (int)$meeting['project_id'];
    $stmtC = $pdo->prepare("SELECT classroom_id FROM projects WHERE id = ?");
    $stmtC->execute([$projectId]);
    $classroomId = (int)$stmtC->fetchColumn();

    if (!is_project_reviewer($pdo, $projectId, $classroomId, $currentUserId)) {
        if ($isAjax) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized: Only project reviewers can add guide instructions.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Unauthorized: Only project reviewers can add guide instructions.';
        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
        exit;
    }

    $newId = guide_instruction_add($pdo, $meetingId, $text, $currentUserId);

    $snippet = mb_strimwidth($text, 0, 50, '...');
    notify_project_members(
        $pdo,
        $projectId,
        'instruction',
        "Guide added a new action item: \"{$snippet}\"",
        "dashboard.php?classroom_id=" . urlencode((string)$classroomId),
        $currentUserId
    );

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'        => true,
            'message'        => 'Instruction added successfully.',
            'instruction_id' => $newId,
            'text'           => $text,
            'status'         => 'open',
        ]);
        exit;
    }

    $_SESSION['flash_success'] = 'Action item added successfully.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

// Actions on existing instruction (acknowledge, mark_done, reopen)
if ($instructionId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid instruction ID.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Invalid instruction ID.';
    header("Location: dashboard.php");
    exit;
}

$inst = guide_instruction_find($pdo, $instructionId);
if (!$inst) {
    if ($isAjax) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Instruction not found.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Instruction not found.';
    header("Location: dashboard.php");
    exit;
}

$projectId   = (int)$inst['project_id'];
$classroomId = (int)$inst['classroom_id'];
$snippet     = mb_strimwidth($inst['text'], 0, 50, '...');

if ($action === 'acknowledge') {
    // 3. Students acknowledge items: Any active project member can acknowledge
    if (!is_active_project_member($pdo, $projectId, $currentUserId, $classroomId)) {
        if ($isAjax) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized: Only active team members can acknowledge instructions.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Unauthorized: Only active team members can acknowledge instructions.';
        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
        exit;
    }

    guide_instruction_update_status($pdo, $instructionId, 'acknowledged');

    $studentName = $_SESSION['username'] ?? 'A student';
    notify_project_mentor(
        $pdo,
        $projectId,
        'instruction',
        "{$studentName} acknowledged the action item: \"{$snippet}\"",
        "dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId),
        $currentUserId
    );

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Action item acknowledged.',
            'status'  => 'acknowledged',
        ]);
        exit;
    }

    $_SESSION['flash_success'] = 'Action item acknowledged.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

if ($action === 'mark_done') {
    // 3. Guide marks them done: Only project reviewers can mark done
    if (!is_project_reviewer($pdo, $projectId, $classroomId, $currentUserId)) {
        if ($isAjax) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized: Only project reviewers can mark instructions as done.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Unauthorized: Only project reviewers can mark instructions as done.';
        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
        exit;
    }

    guide_instruction_update_status($pdo, $instructionId, 'done');

    notify_project_members(
        $pdo,
        $projectId,
        'instruction',
        "Guide marked action item as done: \"{$snippet}\"",
        "dashboard.php?classroom_id=" . urlencode((string)$classroomId),
        $currentUserId
    );

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Action item marked as done.',
            'status'  => 'done',
        ]);
        exit;
    }

    $_SESSION['flash_success'] = 'Action item marked as done.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

if ($action === 'reopen') {
    if (!is_project_reviewer($pdo, $projectId, $classroomId, $currentUserId)) {
        if ($isAjax) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Unauthorized: Only project reviewers can reopen instructions.']);
            exit;
        }
        $_SESSION['flash_error'] = 'Unauthorized: Only project reviewers can reopen instructions.';
        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
        exit;
    }

    guide_instruction_update_status($pdo, $instructionId, 'open');

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Action item reopened.',
            'status'  => 'open',
        ]);
        exit;
    }

    $_SESSION['flash_success'] = 'Action item reopened.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

if ($isAjax) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
    exit;
}
$_SESSION['flash_error'] = 'Unknown action.';
header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
exit;
