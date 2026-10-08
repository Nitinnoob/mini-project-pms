<?php
/**
 * Weekly log and mentor reviews repository.
 */

if (!function_exists('weekly_submission_get_status')) {
    function weekly_submission_get_status(PDO $pdo, int $projectId, int $weekNumber): ?array
    {
        $stmt = $pdo->prepare("
            SELECT s.id, r.status 
            FROM weekly_submissions s
            LEFT JOIN weekly_reviews r ON s.id = r.submission_id
            WHERE s.project_id = ? AND s.week_number = ?
        ");
        $stmt->execute([$projectId, $weekNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (!function_exists('classroom_get_min_team_size')) {
    function classroom_get_min_team_size(PDO $pdo, int $projectId): int
    {
        $stmt = $pdo->prepare("SELECT c.min_team_size FROM classrooms c JOIN projects p ON p.classroom_id = c.id WHERE p.id = ?");
        $stmt->execute([$projectId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($res['min_team_size']) ? (int)$res['min_team_size'] : 1;
    }
}

if (!function_exists('weekly_submission_save')) {
    function weekly_submission_save(PDO $pdo, int $projectId, int $weekNumber, int $userId, string $summary, string $nextSteps, ?int $existingId = null, ?string $reviewStatus = null): int
    {
        if ($existingId) {
            $stmt = $pdo->prepare("UPDATE weekly_submissions SET submitted_by = ?, work_summary = ?, next_steps = ?, submitted_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$userId, $summary, $nextSteps, $existingId]);

            if ($reviewStatus === 'revision_needed') {
                $stmtRev = $pdo->prepare("UPDATE weekly_reviews SET status = 'pending' WHERE submission_id = ?");
                $stmtRev->execute([$existingId]);
            }
            return $existingId;
        } else {
            $stmt = $pdo->prepare("INSERT INTO weekly_submissions (project_id, week_number, submitted_by, work_summary, next_steps) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$projectId, $weekNumber, $userId, $summary, $nextSteps]);
            $submissionId = (int)$pdo->lastInsertId();

            $stmtRev = $pdo->prepare("INSERT INTO weekly_reviews (submission_id, status) VALUES (?, 'pending')");
            $stmtRev->execute([$submissionId]);
            return $submissionId;
        }
    }
}

if (!function_exists('weekly_submission_add_file')) {
    function weekly_submission_add_file(PDO $pdo, int $submissionId, string $fileName, string $filePath): void
    {
        $stmt = $pdo->prepare("INSERT INTO weekly_submission_files (submission_id, file_name, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$submissionId, $fileName, $filePath]);
    }
}

if (!function_exists('weekly_submission_ensure_exists')) {
    function weekly_submission_ensure_exists(PDO $pdo, int $projectId, int $weekNumber): int
    {
        // For MySQL: INSERT IGNORE; for generic SQLite: check or insert
        $stmtCheck = $pdo->prepare("SELECT id FROM weekly_submissions WHERE project_id = ? AND week_number = ?");
        $stmtCheck->execute([$projectId, $weekNumber]);
        $existing = $stmtCheck->fetchColumn();
        if ($existing) {
            return (int)$existing;
        }

        $stmt = $pdo->prepare("INSERT INTO weekly_submissions (project_id, week_number) VALUES (?, ?)");
        $stmt->execute([$projectId, $weekNumber]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('weekly_review_save')) {
    function weekly_review_save(PDO $pdo, int $submissionId, int $reviewerId, string $status, string $remarks): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO weekly_reviews (submission_id, reviewed_by, status, mentor_remarks) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            reviewed_by = VALUES(reviewed_by), status = VALUES(status), mentor_remarks = VALUES(mentor_remarks)
        ");
        $stmt->execute([$submissionId, $reviewerId, $status, $remarks]);
    }
}

if (!function_exists('weekly_attendance_sync')) {
    function weekly_attendance_sync(PDO $pdo, int $submissionId, int $projectId, array $checkedUserIds): void
    {
        $stmtDel = $pdo->prepare("DELETE FROM weekly_attendance WHERE submission_id = ?");
        $stmtDel->execute([$submissionId]);

        $stmtRoster = $pdo->prepare("SELECT user_id FROM project_members WHERE project_id = ? AND join_status = 'Active'");
        $stmtRoster->execute([$projectId]);
        $rosterIds = $stmtRoster->fetchAll(PDO::FETCH_COLUMN);

        $stmtIns = $pdo->prepare("INSERT INTO weekly_attendance (submission_id, user_id, present) VALUES (?, ?, ?)");
        foreach ($rosterIds as $uid) {
            $isPresent = isset($checkedUserIds[$uid]) ? 1 : 0;
            $stmtIns->execute([$submissionId, $uid, $isPresent]);
        }
    }
}
