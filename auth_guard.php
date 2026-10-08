<?php
/**
 * Contextual authorization checks. Every check is scoped to a project or
 * classroom — PMS has no global roles (users.role is legacy/NULL).
 *
 * Loaded automatically by bootstrap.php.
 */

/**
 * Is $userId an Active member of $projectId?
 * Pass $classroomId to also require that the project lives in that classroom.
 */
function is_active_project_member(PDO $pdo, $projectId, $userId, $classroomId = null): bool
{
    if (!$projectId || !$userId) {
        return false;
    }
    if ($classroomId === null) {
        $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = 'Active'");
        $stmt->execute([$projectId, $userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 1 FROM project_members pm JOIN projects p ON pm.project_id = p.id
            WHERE pm.project_id = ? AND pm.user_id = ? AND pm.join_status = 'Active' AND p.classroom_id = ?
        ");
        $stmt->execute([$projectId, $userId, $classroomId]);
    }
    return (bool)$stmt->fetchColumn();
}

/**
 * Is $userId the Active leader of $projectId?
 * Pass $classroomId to also require that the project lives in that classroom.
 */
function is_project_leader(PDO $pdo, $projectId, $userId, $classroomId = null): bool
{
    if (!$projectId || !$userId) {
        return false;
    }
    if ($classroomId === null) {
        $stmt = $pdo->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND is_leader = 1 AND join_status = 'Active'");
        $stmt->execute([$projectId, $userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT 1 FROM project_members pm JOIN projects p ON pm.project_id = p.id
            WHERE pm.project_id = ? AND pm.user_id = ? AND pm.is_leader = 1 AND pm.join_status = 'Active' AND p.classroom_id = ?
        ");
        $stmt->execute([$projectId, $userId, $classroomId]);
    }
    return (bool)$stmt->fetchColumn();
}

/** Is $userId the coordinator (creator) of $classroomId? */
function is_classroom_coordinator(PDO $pdo, $classroomId, $userId): bool
{
    if (!$classroomId || !$userId) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT created_by FROM classrooms WHERE id = ?");
    $stmt->execute([$classroomId]);
    $createdBy = $stmt->fetchColumn();
    return $createdBy !== false && (int)$createdBy === (int)$userId;
}

/** Is $userId the assigned mentor of $projectId? */
function is_project_mentor(PDO $pdo, $projectId, $userId): bool
{
    if (!$projectId || !$userId) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT mentor_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $mentorId = $stmt->fetchColumn();
    return $mentorId !== false && $mentorId !== null && (int)$mentorId === (int)$userId;
}

/**
 * May $userId review/oversee $projectId? True for the project's assigned
 * mentor or the classroom coordinator, and only if the project belongs to
 * $classroomId.
 */
function is_project_reviewer(PDO $pdo, $projectId, $classroomId, $userId): bool
{
    if (!$projectId || !$classroomId || !$userId) {
        return false;
    }
    $stmt = $pdo->prepare("
        SELECT p.mentor_id, c.created_by
        FROM projects p JOIN classrooms c ON p.classroom_id = c.id
        WHERE p.id = ? AND p.classroom_id = ?
    ");
    $stmt->execute([$projectId, $classroomId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }
    return (int)$row['created_by'] === (int)$userId
        || ($row['mentor_id'] !== null && (int)$row['mentor_id'] === (int)$userId);
}

/** The user's role in a classroom: 'Admin', 'Team Member', or null if not a member. */
function classroom_role(PDO $pdo, $classroomId, $userId): ?string
{
    if (!$classroomId || !$userId) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->execute([$classroomId, $userId]);
    $role = $stmt->fetchColumn();
    return $role === false ? null : $role;
}

/**
 * May $userId mark or modify attendance for $projectId?
 * Authorized if user is the assigned mentor of the project or an Admin in the project's classroom.
 */
function can_manage_project_attendance(PDO $pdo, int $projectId, int $userId): bool
{
    if ($projectId <= 0 || $userId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT classroom_id, mentor_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }
    if ($row['mentor_id'] !== null && (int)$row['mentor_id'] === $userId) {
        return true;
    }
    return classroom_role($pdo, (int)$row['classroom_id'], $userId) === 'Admin';
}

/**
 * May $userId evaluate and enter marks for $projectId?
 * Authorized if user is the assigned mentor of the project or an Admin in the project's classroom.
 */
function can_evaluate_project(PDO $pdo, int $projectId, int $userId): bool
{
    return can_manage_project_attendance($pdo, $projectId, $userId);
}

/**
 * May $userId edit or unlock marks after they have been finalized?
 * Once finalized, subsequent updates are restricted strictly to classroom Admins.
 */
function can_override_finalized_marks(PDO $pdo, int $projectId, int $userId): bool
{
    if ($projectId <= 0 || $userId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT classroom_id FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $classroomId = $stmt->fetchColumn();
    if (!$classroomId) {
        return false;
    }
    return classroom_role($pdo, (int)$classroomId, $userId) === 'Admin';
}

