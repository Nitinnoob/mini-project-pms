<?php
/**
 * Project / classroom / activity-log repository.
 */

if (!function_exists('classroom_find_schedule')) {
    /** ['start_date'=>..., 'end_date'=>...] for a classroom, or null. */
    function classroom_find_schedule(PDO $pdo, $classroomId): ?array
    {
        $stmt = $pdo->prepare("SELECT start_date, end_date FROM classrooms WHERE id = ?");
        $stmt->execute([$classroomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!function_exists('project_find_schedule')) {
    /** Classroom schedule for a project, scoped to the given classroom. */
    function project_find_schedule(PDO $pdo, $projectId, $classroomId): ?array
    {
        $stmt = $pdo->prepare(
            "SELECT c.start_date, c.end_date
             FROM classrooms c JOIN projects p ON p.classroom_id = c.id
             WHERE p.id = ? AND p.classroom_id = ?"
        );
        $stmt->execute([$projectId, $classroomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!function_exists('activity_log_add')) {
    function activity_log_add(PDO $pdo, $projectId, $userId, string $action, string $details): void
    {
        $stmt = $pdo->prepare("INSERT INTO activity_log (project_id, user_id, action, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$projectId, $userId, $action, $details]);
    }
}
