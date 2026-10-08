<?php
/**
 * Project membership and join requests repository.
 */

if (!function_exists('project_member_get_status')) {
    function project_member_get_status(PDO $pdo, int $projectId, int $userId): ?string
    {
        $stmt = $pdo->prepare("SELECT join_status FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$projectId, $userId]);
        $res = $stmt->fetchColumn();
        return $res !== false ? $res : null;
    }
}

if (!function_exists('project_member_has_active_or_pending_in_classroom')) {
    function project_member_has_active_or_pending_in_classroom(PDO $pdo, int $classroomId, int $userId): bool
    {
        $stmt = $pdo->prepare("
            SELECT 1 FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            WHERE p.classroom_id = ? AND pm.user_id = ? AND pm.join_status IN ('Active', 'Pending', 'Invited')
        ");
        $stmt->execute([$classroomId, $userId]);
        return (bool)$stmt->fetchColumn();
    }
}

if (!function_exists('project_member_active_count')) {
    function project_member_active_count(PDO $pdo, int $projectId): int
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_members WHERE project_id = ? AND join_status = 'Active'");
        $stmt->execute([$projectId]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('classroom_get_max_team_size')) {
    function classroom_get_max_team_size(PDO $pdo, int $classroomId): int
    {
        $stmt = $pdo->prepare("SELECT max_team_size FROM classrooms WHERE id = ?");
        $stmt->execute([$classroomId]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? (int)$val : 10;
    }
}

if (!function_exists('project_get_classroom_max_team_size')) {
    function project_get_classroom_max_team_size(PDO $pdo, int $projectId): int
    {
        $stmt = $pdo->prepare("SELECT c.max_team_size FROM classrooms c JOIN projects p ON p.classroom_id = c.id WHERE p.id = ?");
        $stmt->execute([$projectId]);
        $val = $stmt->fetchColumn();
        return ($val !== false && $val !== null) ? (int)$val : 10;
    }
}

if (!function_exists('project_member_add_request')) {
    function project_member_add_request(PDO $pdo, int $projectId, int $userId, string $status = 'Pending'): bool
    {
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (?, ?, 0, ?)");
        return $stmt->execute([$projectId, $userId, $status]);
    }
}

if (!function_exists('project_member_update_status')) {
    function project_member_update_status(PDO $pdo, int $projectId, int $userId, string $currentStatus, string $newStatus): bool
    {
        $stmt = $pdo->prepare("UPDATE project_members SET join_status = ? WHERE project_id = ? AND user_id = ? AND join_status = ?");
        $stmt->execute([$newStatus, $projectId, $userId, $currentStatus]);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('project_member_delete_by_status')) {
    function project_member_delete_by_status(PDO $pdo, int $projectId, int $userId, string $status): bool
    {
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ? AND join_status = ?");
        $stmt->execute([$projectId, $userId, $status]);
        return $stmt->rowCount() > 0;
    }
}

if (!function_exists('classroom_member_is_eligible_student')) {
    function classroom_member_is_eligible_student(PDO $pdo, int $classroomId, int $userId): bool
    {
        $stmt = $pdo->prepare("
            SELECT 1 FROM classroom_members
            WHERE classroom_id = ? AND user_id = ? AND role != 'Admin'
        ");
        $stmt->execute([$classroomId, $userId]);
        return (bool)$stmt->fetchColumn();
    }
}
