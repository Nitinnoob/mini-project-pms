<?php
declare(strict_types=1);

/**
 * Meeting Repository — Lean Academic Project Lab Manager.
 *
 * Simple CRUD for project meetings and attendance calculation.
 */

/**
 * Get all meetings for a project, ordered chronologically.
 * Each row includes attendance array.
 */
function meeting_get_by_project(PDO $pdo, int $projectId): array
{
    if ($projectId <= 0) return [];

    $stmt = $pdo->prepare("
        SELECT pm.*, u.username AS created_by_name
        FROM project_meetings pm
        LEFT JOIN users u ON pm.created_by = u.id
        WHERE pm.project_id = ?
        ORDER BY pm.meeting_date ASC, pm.id ASC
    ");
    $stmt->execute([$projectId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) return [];

    // Batch fetch attendance
    $meetingIds = array_column($rows, 'id');
    $ph = implode(',', array_fill(0, count($meetingIds), '?'));

    $stmtAtt = $pdo->prepare("
        SELECT ma.meeting_id, ma.user_id, ma.status, u.username
        FROM meeting_attendance ma
        JOIN users u ON ma.user_id = u.id
        WHERE ma.meeting_id IN ($ph)
        ORDER BY u.username ASC
    ");
    $stmtAtt->execute($meetingIds);

    $attByMeeting = [];
    foreach ($stmtAtt->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $attByMeeting[(int)$a['meeting_id']][] = [
            'user_id'  => (int)$a['user_id'],
            'username' => $a['username'],
            'status'   => $a['status'],
        ];
    }

    // Merge attendance into meetings
    foreach ($rows as &$row) {
        $row['attendance'] = $attByMeeting[(int)$row['id']] ?? [];
    }

    return $rows;
}

/**
 * Find a single meeting by ID.
 */
function meeting_find(PDO $pdo, int $meetingId): ?array
{
    if ($meetingId <= 0) return null;
    $stmt = $pdo->prepare("SELECT * FROM project_meetings WHERE id = ?");
    $stmt->execute([$meetingId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Save a meeting (create or update). Returns the meeting ID.
 *
 * @param array<int, string> $attendance  Map of user_id => 'present'|'absent'
 */
function meeting_save(
    PDO $pdo,
    int $projectId,
    ?int $meetingId,
    string $date,
    string $guidanceNotes,
    array $attendance,
    int $createdBy
): int {
    $pdo->beginTransaction();

    try {
        if ($meetingId && $meetingId > 0) {
            // Update existing meeting
            $stmt = $pdo->prepare("
                UPDATE project_meetings
                SET meeting_date = ?, guidance_notes = ?
                WHERE id = ? AND project_id = ?
            ");
            $stmt->execute([$date, $guidanceNotes, $meetingId, $projectId]);
        } else {
            // Create new meeting
            $stmt = $pdo->prepare("
                INSERT INTO project_meetings (project_id, meeting_date, guidance_notes, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$projectId, $date, $guidanceNotes, $createdBy]);
            $meetingId = (int)$pdo->lastInsertId();
        }

        // Replace attendance: delete old, insert new
        $stmtDel = $pdo->prepare("DELETE FROM meeting_attendance WHERE meeting_id = ?");
        $stmtDel->execute([$meetingId]);

        if (!empty($attendance)) {
            $stmtIns = $pdo->prepare("
                INSERT INTO meeting_attendance (meeting_id, user_id, status)
                VALUES (?, ?, ?)
            ");
            foreach ($attendance as $userId => $status) {
                $status = in_array($status, ['present', 'absent'], true) ? $status : 'absent';
                $stmtIns->execute([$meetingId, (int)$userId, $status]);
            }
        }

        $pdo->commit();
        return $meetingId;

    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Delete a meeting and its attendance records.
 */
function meeting_delete(PDO $pdo, int $meetingId): bool
{
    $stmt = $pdo->prepare("DELETE FROM project_meetings WHERE id = ?");
    $stmt->execute([$meetingId]);
    return $stmt->rowCount() > 0;
}

/**
 * Calculate attendance percentage for a student across all meetings in a project.
 *
 * @return array{total: int, present: int, percentage: float, has_shortage: bool}
 */
function meeting_calculate_attendance(PDO $pdo, int $projectId, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN ma.status = 'present' THEN 1 ELSE 0 END) AS present_count
        FROM meeting_attendance ma
        JOIN project_meetings pm ON ma.meeting_id = pm.id
        WHERE pm.project_id = ? AND ma.user_id = ?
    ");
    $stmt->execute([$projectId, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $total   = (int)($row['total'] ?? 0);
    $present = (int)($row['present_count'] ?? 0);
    $pct     = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;

    return [
        'total'        => $total,
        'present'      => $present,
        'percentage'   => $pct,
        'has_shortage' => ($total > 0 && $pct < 75.0),
    ];
}

// Backward-compatibility alias used in existing project_data.php
function meeting_calculate_student_attendance(PDO $pdo, int $projectId, int $userId): array
{
    $att = meeting_calculate_attendance($pdo, $projectId, $userId);
    $att['held'] = $att['total'];
    return $att;
}

/**
 * Batch compute health metrics for a list of projects (used in teacher ledger).
 * Returns project_id => ['meetings_held', 'avg_attendance_pct', 'has_shortage']
 */
function meeting_compute_projects_health(PDO $pdo, array $projectIds): array
{
    if (empty($projectIds)) return [];

    $ph = implode(',', array_fill(0, count($projectIds), '?'));
    $result = [];

    // Count meetings per project
    $stmt = $pdo->prepare("
        SELECT project_id, COUNT(*) AS cnt
        FROM project_meetings
        WHERE project_id IN ($ph)
        GROUP BY project_id
    ");
    $stmt->execute($projectIds);
    $meetingCounts = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $meetingCounts[(int)$r['project_id']] = (int)$r['cnt'];
    }

    // Attendance per project per member
    $stmt2 = $pdo->prepare("
        SELECT pm2.project_id, ma.user_id,
               COUNT(*) AS total,
               SUM(CASE WHEN ma.status = 'present' THEN 1 ELSE 0 END) AS present_count
        FROM meeting_attendance ma
        JOIN project_meetings pm2 ON ma.meeting_id = pm2.id
        WHERE pm2.project_id IN ($ph)
        GROUP BY pm2.project_id, ma.user_id
    ");
    $stmt2->execute($projectIds);

    $attByProject = [];
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $pid = (int)$r['project_id'];
        $total = (int)$r['total'];
        $present = (int)$r['present_count'];
        $pct = $total > 0 ? ($present / $total) * 100 : 100;
        $attByProject[$pid][] = $pct;
    }

    foreach ($projectIds as $pid) {
        $pid = (int)$pid;
        $pcts = $attByProject[$pid] ?? [];
        $avg = !empty($pcts) ? round(array_sum($pcts) / count($pcts), 1) : 0;
        $hasShortage = false;
        foreach ($pcts as $p) {
            if ($p < 75.0) { $hasShortage = true; break; }
        }

        $result[$pid] = [
            'meetings_held'     => $meetingCounts[$pid] ?? 0,
            'avg_attendance_pct' => $avg,
            'has_shortage'       => $hasShortage,
            // Legacy keys for teacher view compat
            'total_meetings'    => $meetingCounts[$pid] ?? 0,
            'open_instructions' => 0,
        ];
    }

    return $result;
}
