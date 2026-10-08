<?php
/**
 * Deliverables repository - file uploads management.
 */

if (!function_exists('deliverable_create')) {
    function deliverable_create(PDO $pdo, int $projectId, ?int $taskId, int $userId, string $fileName, string $filePath): int
    {
        $stmt = $pdo->prepare("
            INSERT INTO deliverables (project_id, task_id, uploaded_by, file_name, file_path)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$projectId, $taskId, $userId, $fileName, $filePath]);
        return (int) $pdo->lastInsertId();
    }
}
