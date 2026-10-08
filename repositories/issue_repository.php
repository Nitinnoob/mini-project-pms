<?php
/**
 * Issues repository - `issues` table operations.
 * Procedural PDO, prepared statements only.
 */

if (!function_exists('issue_find_with_classroom')) {
    /** Issue row joined with projects to ensure classroom isolation. */
    function issue_find_with_classroom(PDO $pdo, int $issueId, int $classroomId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT i.id, i.project_id, i.title, i.raised_by, i.status
            FROM issues i
            JOIN projects p ON i.project_id = p.id
            WHERE i.id = ? AND p.classroom_id = ?
        ");
        $stmt->execute([$issueId, $classroomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!function_exists('issue_create')) {
    function issue_create(PDO $pdo, array $data): int
    {
        $stmt = $pdo->prepare("
            INSERT INTO issues (project_id, raised_by, title, description, week_number, task_id, severity, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open')
        ");
        $stmt->execute([
            $data['project_id'],
            $data['raised_by'],
            mb_substr($data['title'], 0, 200),
            $data['description'],
            $data['week_number'] ?? null,
            $data['task_id'] ?? null,
            $data['severity']
        ]);
        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('issue_resolve')) {
    function issue_resolve(PDO $pdo, int $issueId, int $userId): bool
    {
        $stmt = $pdo->prepare("
            UPDATE issues 
            SET status = 'resolved', resolved_by = ?, resolved_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$userId, $issueId]);
    }
}
