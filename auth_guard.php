<?php
/**
 * Contextual authorization checks scoped to classroom or project.
 * Loaded automatically by bootstrap.php.
 */

/** The user's role in a classroom: 'Admin', 'Team Member', or null. */
function classroom_role(PDO $pdo, $classroomId, $userId): ?string
{
    if (!$classroomId || !$userId) return null;
    $stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->execute([$classroomId, $userId]);
    $role = $stmt->fetchColumn();
    return $role === false ? null : $role;
}

/** Is $userId the coordinator (creator) of $classroomId? */
function is_classroom_coordinator(PDO $pdo, $classroomId, $userId): bool
{
    if (!$classroomId || !$userId) return false;
    $stmt = $pdo->prepare("SELECT created_by FROM classrooms WHERE id = ?");
    $stmt->execute([$classroomId]);
    $createdBy = $stmt->fetchColumn();
    return $createdBy !== false && (int)$createdBy === (int)$userId;
}

/** Is $userId the assigned mentor of $projectId? */
function is_project_mentor(PDO $pdo, $projectId, $userId): bool
{
    if (!$projectId || !$userId) return false;
    $stmt = $pdo->prepare("SELECT mentor_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $mentorId = $stmt->fetchColumn();
    return $mentorId !== false && $mentorId !== null && (int)$mentorId === (int)$userId;
}

/** Is $userId an Active member of $projectId? */
function is_active_project_member(PDO $pdo, $projectId, $userId, $classroomId = null): bool
{
    if (!$projectId || !$userId) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
    $stmt->execute([$projectId, $userId]);
    return (bool)$stmt->fetchColumn();
}

/** Is $userId the Active leader of $projectId? */
function is_project_leader(PDO $pdo, $projectId, $userId, $classroomId = null): bool
{
    if (!$projectId || !$userId) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND is_leader = 1 AND join_status = 'Active'");
    $stmt->execute([$projectId, $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Can $userId manage a project (add meetings, mark attendance)?
 * True if user is the assigned mentor or a classroom Admin.
 */
function can_manage_project(PDO $pdo, int $projectId, int $userId): bool
{
    if ($projectId <= 0 || $userId <= 0) return false;
    $stmt = $pdo->prepare("SELECT classroom_id, mentor_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    if ($row['mentor_id'] !== null && (int)$row['mentor_id'] === $userId) return true;
    return classroom_role($pdo, (int)$row['classroom_id'], $userId) === 'Admin';
}

// Aliases for backward compatibility with existing code
function can_manage_project_attendance(PDO $pdo, int $projectId, int $userId): bool
{
    return can_manage_project($pdo, $projectId, $userId);
}

function can_evaluate_project(PDO $pdo, int $projectId, int $userId): bool
{
    return can_manage_project($pdo, $projectId, $userId);
}

function is_project_reviewer(PDO $pdo, $projectId, $classroomId, $userId): bool
{
    return can_manage_project($pdo, (int)$projectId, (int)$userId);
}
