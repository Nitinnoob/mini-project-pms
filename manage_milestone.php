<?php
/**
 * Milestone Management Endpoint.
 * Allows classroom coordinators to add, edit, or delete classroom milestones.
 */

require_once 'bootstrap.php';
require_once 'repositories/milestone_repository.php';

require_login();

function milestone_back(int $classroomId, bool $ok, string $message): void
{
    header("Location: dashboard.php?classroom_id=" . urlencode((string)$classroomId)
        . "&tab=milestones&" . ($ok ? 'msg=' : 'err=') . urlencode($message));
    exit;
}

if (!is_post()) {
    header("Location: hub.php");
    exit;
}

csrf_verify();

$classroomId = isset($_POST['classroom_id']) ? (int)$_POST['classroom_id'] : 0;
$action      = $_POST['action'] ?? '';

if ($classroomId <= 0 || !in_array($action, ['add', 'delete', 'update'], true)) {
    header("Location: hub.php");
    exit;
}

// Only the classroom coordinator may manage milestones.
if (!is_classroom_coordinator($pdo, $classroomId, $_SESSION['user_id'])) {
    milestone_back($classroomId, false, 'Only the classroom coordinator can create or manage milestones.');
}

try {
    if ($action === 'add') {
        $title   = trim($_POST['title'] ?? '');
        $dueDate = trim($_POST['due_date'] ?? '');

        if ($title === '') {
            milestone_back($classroomId, false, 'Please enter a milestone title.');
        }
        if ($dueDate === '') {
            milestone_back($classroomId, false, 'Please enter a target due date.');
        }

        classroom_milestone_create($pdo, $classroomId, $title, $dueDate);
        milestone_back($classroomId, true, 'Milestone added successfully.');
    }

    if ($action === 'delete') {
        $milestoneId = isset($_POST['milestone_id']) ? (int)$_POST['milestone_id'] : 0;
        if ($milestoneId <= 0) {
            milestone_back($classroomId, false, 'Invalid milestone selected.');
        }

        $deleted = classroom_milestone_delete($pdo, $classroomId, $milestoneId);
        if ($deleted) {
            milestone_back($classroomId, true, 'Milestone removed.');
        } else {
            milestone_back($classroomId, false, 'Milestone could not be found.');
        }
    }

    if ($action === 'update') {
        $milestoneId = isset($_POST['milestone_id']) ? (int)$_POST['milestone_id'] : 0;
        $title       = trim($_POST['title'] ?? '');
        $dueDate     = trim($_POST['due_date'] ?? '');

        if ($milestoneId <= 0) {
            milestone_back($classroomId, false, 'Invalid milestone selected.');
        }
        if ($title === '') {
            milestone_back($classroomId, false, 'Please enter a milestone title.');
        }
        if ($dueDate === '') {
            milestone_back($classroomId, false, 'Please enter a target due date.');
        }

        classroom_milestone_update($pdo, $classroomId, $milestoneId, $title, $dueDate);
        milestone_back($classroomId, true, 'Milestone updated successfully.');
    }
} catch (InvalidArgumentException $e) {
    milestone_back($classroomId, false, $e->getMessage());
} catch (PDOException $e) {
    milestone_back($classroomId, false, 'Database error while saving milestone.');
}
