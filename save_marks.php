<?php
declare(strict_types=1);

require_once 'bootstrap.php';
require_once 'auth_guard.php';
require_once 'repositories/marks_repository.php';
require_once 'notify.php';

require_login();

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

csrf_verify();

$projectId   = (int)($_POST['project_id'] ?? 0);
$classroomId = (int)($_POST['classroom_id'] ?? 0);
$action      = trim((string)($_POST['action'] ?? 'save')); // 'save', 'finalize', 'unlock'
$reason      = trim((string)($_POST['reason'] ?? ''));

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($projectId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid project ID.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Invalid project ID.';
    header("Location: hub.php");
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Server-side authorization check: User must be project mentor or classroom Admin
if (!can_evaluate_project($pdo, $projectId, $currentUserId)) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Unauthorized: Only assigned mentors or classroom Admins may evaluate projects.']);
        exit;
    }
    $_SESSION['flash_error'] = 'Unauthorized: Only assigned mentors or classroom Admins may evaluate projects.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'unlock') {
        if (!can_override_finalized_marks($pdo, $projectId, $currentUserId)) {
            throw new RuntimeException("Unauthorized: Only classroom Admins can unlock finalized marks.");
        }
        marks_unlock($pdo, $projectId, $currentUserId, $reason);
        $pdo->commit();

        notify_project_members(
            $pdo,
            $projectId,
            'evaluation',
            "Evaluation marks were unlocked for revisions by Admin.",
            'dashboard.php?classroom_id=' . urlencode((string)$classroomId),
            $currentUserId
        );

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Evaluation unlocked successfully.']);
            exit;
        }
        $_SESSION['flash_success'] = 'Evaluation marks unlocked successfully.';
        header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
        exit;
    }

    // Process report marks
    $rawReport = $_POST['report_marks'] ?? null;
    $reportMarks = ($rawReport !== '' && $rawReport !== null) ? (float)$rawReport : null;

    // Process students presentation & QA marks
    $rawStudents = $_POST['students'] ?? [];
    $studentsMarks = [];

    if (is_array($rawStudents)) {
        foreach ($rawStudents as $uid => $data) {
            $uid = (int)$uid;
            if ($uid <= 0) continue;

            $p = $data['presentation_marks'] ?? ($data['presentation'] ?? null);
            $q = $data['qa_marks'] ?? ($data['qa'] ?? null);

            $studentsMarks[$uid] = [
                'presentation_marks' => ($p !== '' && $p !== null) ? (float)$p : null,
                'qa_marks'           => ($q !== '' && $q !== null) ? (float)$q : null,
            ];
        }
    }

    $finalize = ($action === 'finalize');

    $sheet = marks_save(
        $pdo,
        $projectId,
        $currentUserId,
        $reportMarks,
        $studentsMarks,
        $finalize,
        $reason !== '' ? $reason : null
    );

    $pdo->commit();

    $notifMsg = $finalize
        ? "Evaluation marks have been finalized by your guide."
        : "Evaluation marks draft has been updated by your guide.";

    notify_project_members(
        $pdo,
        $projectId,
        'evaluation',
        $notifMsg,
        'dashboard.php?classroom_id=' . urlencode((string)$classroomId),
        $currentUserId
    );

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'message'      => $finalize ? 'Evaluation marks finalized successfully!' : 'Evaluation marks draft saved successfully.',
            'is_finalized' => $finalize,
            'sheet'        => $sheet,
        ]);
        exit;
    }

    $_SESSION['flash_success'] = $finalize ? 'Evaluation marks finalized successfully!' : 'Evaluation marks saved successfully.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;

} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($isAjax) {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'A server error occurred while saving evaluation marks.']);
        exit;
    }
    $_SESSION['flash_error'] = 'A server error occurred while saving evaluation marks.';
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId) . "&project_id=" . urlencode((string)$projectId));
    exit;
}
