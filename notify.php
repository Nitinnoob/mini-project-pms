<?php
/**
 * Notification helpers (Phase 5).
 *
 * Every function is best-effort: if the `notifications` table has not been
 * created yet (migration not applied) or an insert fails, the calling
 * feature must keep working, so all errors are swallowed here.
 */

function notify_user($pdo, $userId, $type, $message, $link = null)
{
    if (!$userId) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([(int)$userId, $type, mb_substr($message, 0, 500), $link]);
    } catch (Throwable $e) {
        // Table missing or other failure: notifications are non-critical.
    }
}

function notify_users($pdo, array $userIds, $type, $message, $link = null, $excludeUserId = null)
{
    foreach (array_unique(array_map('intval', $userIds)) as $uid) {
        if ($excludeUserId !== null && $uid === (int)$excludeUserId) continue;
        notify_user($pdo, $uid, $type, $message, $link);
    }
}

function notify_project_leaders($pdo, $projectId, $type, $message, $link = null, $excludeUserId = null)
{
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM project_members WHERE project_id = ? AND is_leader = 1 AND join_status = 'Active'");
        $stmt->execute([$projectId]);
        notify_users($pdo, $stmt->fetchAll(PDO::FETCH_COLUMN), $type, $message, $link, $excludeUserId);
    } catch (Throwable $e) {}
}

function notify_project_members($pdo, $projectId, $type, $message, $link = null, $excludeUserId = null)
{
    try {
        $stmt = $pdo->prepare("SELECT user_id FROM project_members WHERE project_id = ? AND join_status = 'Active'");
        $stmt->execute([$projectId]);
        notify_users($pdo, $stmt->fetchAll(PDO::FETCH_COLUMN), $type, $message, $link, $excludeUserId);
    } catch (Throwable $e) {}
}

/**
 * Notify the project's assigned mentor; when none is assigned, fall back to
 * the classroom coordinator so the alert never goes nowhere.
 */
function notify_project_mentor($pdo, $projectId, $type, $message, $link = null, $excludeUserId = null)
{
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(p.mentor_id, c.created_by)
            FROM projects p JOIN classrooms c ON p.classroom_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$projectId]);
        $uid = $stmt->fetchColumn();
        if ($uid) notify_users($pdo, [$uid], $type, $message, $link, $excludeUserId);
    } catch (Throwable $e) {}
}

/** Short display name for a project, used inside notification text. */
function notify_project_name($pdo, $projectId)
{
    try {
        $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        return $stmt->fetchColumn() ?: 'your project';
    } catch (Throwable $e) {
        return 'your project';
    }
}
