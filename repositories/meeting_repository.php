<?php
declare(strict_types=1);

/**
 * Weekly Meeting Repository — PMS Saturday Guide Review Engine.
 *
 * Provides database access for weekly meetings, guide attendance,
 * immutable attendance change logs, guide instructions, and meeting attachments.
 */

require_once __DIR__ . '/../meeting_engine.php';

if (!function_exists('meeting_encode_team_update')) {
    /**
     * Encode team update to JSON string or return text as-is.
     *
     * @param string|array{work_done?: string, next_steps?: string, blockers?: string} $update
     */
    function meeting_encode_team_update(string|array $update): string
    {
        if (is_array($update)) {
            return json_encode([
                'work_done'  => trim((string)($update['work_done'] ?? '')),
                'next_steps' => trim((string)($update['next_steps'] ?? '')),
                'blockers'   => trim((string)($update['blockers'] ?? '')),
            ], JSON_UNESCAPED_UNICODE);
        }
        return trim((string)$update);
    }
}

if (!function_exists('meeting_decode_team_update')) {
    /**
     * Decode team update string into associative array.
     *
     * @return array{work_done: string, next_steps: string, blockers: string}
     */
    function meeting_decode_team_update(?string $raw): array
    {
        $default = ['work_done' => '', 'next_steps' => '', 'blockers' => ''];
        if ($raw === null || trim($raw) === '') {
            return $default;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return [
                'work_done'  => (string)($decoded['work_done'] ?? ''),
                'next_steps' => (string)($decoded['next_steps'] ?? ''),
                'blockers'   => (string)($decoded['blockers'] ?? ''),
            ];
        }

        return [
            'work_done'  => $raw,
            'next_steps' => '',
            'blockers'   => '',
        ];
    }
}

if (!function_exists('meeting_ensure_project_meetings')) {
    /**
     * Idempotently create scheduled weekly meeting rows for a project based on classroom schedule.
     *
     * @return array<int, array<string, mixed>> All project meetings.
     */
    function meeting_ensure_project_meetings(PDO $pdo, int $projectId, ?string $startDate, ?string $endDate): array
    {
        if ($projectId <= 0 || empty($startDate) || empty($endDate)) {
            return [];
        }

        $derived = meeting_derive_schedule($startDate, $endDate);
        if (empty($derived)) {
            return [];
        }

        // Fetch existing week numbers for this project
        $stmtExisting = $pdo->prepare("SELECT week_number FROM weekly_meetings WHERE project_id = ?");
        $stmtExisting->execute([$projectId]);
        $existingWeeks = array_flip($stmtExisting->fetchAll(PDO::FETCH_COLUMN));

        $stmtInsert = $pdo->prepare("
            INSERT INTO weekly_meetings (project_id, week_number, meeting_date, status)
            VALUES (?, ?, ?, 'scheduled')
        ");

        foreach ($derived as $item) {
            $wk = (int)$item['week_number'];
            if (!isset($existingWeeks[$wk])) {
                $stmtInsert->execute([$projectId, $wk, $item['meeting_date']]);
            }
        }

        return meeting_get_by_project($pdo, $projectId);
    }
}

if (!function_exists('meeting_get_by_project')) {
    /**
     * Get all weekly meetings for a project ordered by week_number ASC.
     *
     * @return array<int, array<string, mixed>>
     */
    function meeting_get_by_project(PDO $pdo, int $projectId): array
    {
        $stmt = $pdo->prepare("
            SELECT wm.*, u.username as submitted_by_name
            FROM weekly_meetings wm
            LEFT JOIN users u ON wm.submitted_by = u.id
            WHERE wm.project_id = ?
            ORDER BY wm.week_number ASC
        ");
        $stmt->execute([$projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $meetingIds = array_column($rows, 'id');
        $ph = implode(',', array_fill(0, count($meetingIds), '?'));

        // Batch fetch attendance summary
        $attSummary = [];
        $stmtAtt = $pdo->prepare("
            SELECT meeting_id, status, COUNT(*) as cnt
            FROM meeting_attendance
            WHERE meeting_id IN ($ph)
            GROUP BY meeting_id, status
        ");
        $stmtAtt->execute($meetingIds);
        foreach ($stmtAtt->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $attSummary[(int)$a['meeting_id']][$a['status']] = (int)$a['cnt'];
        }

        // Batch fetch open instruction counts
        $openInstCount = [];
        $stmtInst = $pdo->prepare("
            SELECT meeting_id, COUNT(*) as cnt
            FROM guide_instructions
            WHERE meeting_id IN ($ph) AND status != 'done'
            GROUP BY meeting_id
        ");
        $stmtInst->execute($meetingIds);
        foreach ($stmtInst->fetchAll(PDO::FETCH_ASSOC) as $i) {
            $openInstCount[(int)$i['meeting_id']] = (int)$i['cnt'];
        }

        // Batch fetch files count
        $filesCount = [];
        $stmtFiles = $pdo->prepare("
            SELECT meeting_id, COUNT(*) as cnt
            FROM weekly_submission_files
            WHERE meeting_id IN ($ph)
            GROUP BY meeting_id
        ");
        $stmtFiles->execute($meetingIds);
        foreach ($stmtFiles->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $filesCount[(int)$f['meeting_id']] = (int)$f['cnt'];
        }

        // Batch fetch detailed attendance
        $attendanceByMeeting = [];
        $attendanceByUserByMeeting = [];
        $stmtAttDetails = $pdo->prepare("
            SELECT ma.*, u.username, m.username as marked_by_name
            FROM meeting_attendance ma
            JOIN users u ON ma.user_id = u.id
            LEFT JOIN users m ON ma.marked_by = m.id
            WHERE ma.meeting_id IN ($ph)
            ORDER BY u.username ASC
        ");
        $stmtAttDetails->execute($meetingIds);
        foreach ($stmtAttDetails->fetchAll(PDO::FETCH_ASSOC) as $ad) {
            $mid = (int)$ad['meeting_id'];
            $uid = (int)$ad['user_id'];
            $attendanceByMeeting[$mid][] = $ad;
            $attendanceByUserByMeeting[$mid][$uid] = $ad;
        }

        // Batch fetch attendance changes (audit trail)
        $changesByMeeting = [];
        $stmtChanges = $pdo->prepare("
            SELECT ac.*, u.username, cb.username as changed_by_name
            FROM attendance_changes ac
            JOIN users u ON ac.user_id = u.id
            JOIN users cb ON ac.changed_by = cb.id
            WHERE ac.meeting_id IN ($ph)
            ORDER BY ac.changed_at DESC, ac.id DESC
        ");
        $stmtChanges->execute($meetingIds);
        foreach ($stmtChanges->fetchAll(PDO::FETCH_ASSOC) as $ch) {
            $mid = (int)$ch['meeting_id'];
            $changesByMeeting[$mid][] = $ch;
        }

        // Batch fetch files list
        $filesByMeeting = [];
        $stmtFilesList = $pdo->prepare("
            SELECT id, meeting_id, original_name, stored_name, file_name, file_path, file_size, mime_type, uploaded_at
            FROM weekly_submission_files
            WHERE meeting_id IN ($ph)
            ORDER BY id ASC
        ");
        $stmtFilesList->execute($meetingIds);
        foreach ($stmtFilesList->fetchAll(PDO::FETCH_ASSOC) as $fl) {
            $filesByMeeting[(int)$fl['meeting_id']][] = $fl;
        }

        // Batch fetch instructions list
        $instructionsByMeeting = [];
        $stmtInstList = $pdo->prepare("
            SELECT gi.*, u.username as created_by_name
            FROM guide_instructions gi
            JOIN users u ON gi.created_by = u.id
            WHERE gi.meeting_id IN ($ph)
            ORDER BY gi.id ASC
        ");
        $stmtInstList->execute($meetingIds);
        foreach ($stmtInstList->fetchAll(PDO::FETCH_ASSOC) as $in) {
            $instructionsByMeeting[(int)$in['meeting_id']][] = $in;
        }

        // Batch fetch feedback history (append-only)
        $feedbackByMeeting = [];
        try {
            $stmtFeedbackList = $pdo->prepare("
                SELECT mfh.*, u.username as reviewer_name
                FROM meeting_feedback_history mfh
                JOIN users u ON mfh.reviewer_id = u.id
                WHERE mfh.meeting_id IN ($ph)
                ORDER BY mfh.created_at ASC, mfh.id ASC
            ");
            $stmtFeedbackList->execute($meetingIds);
            foreach ($stmtFeedbackList->fetchAll(PDO::FETCH_ASSOC) as $fb) {
                $feedbackByMeeting[(int)$fb['meeting_id']][] = $fb;
            }
        } catch (Throwable) {
            // Degrades gracefully if meeting_feedback_history not yet migrated
            $feedbackByMeeting = [];
        }

        foreach ($rows as &$r) {
            $mid = (int)$r['id'];
            $r['team_update_parsed'] = meeting_decode_team_update($r['team_update'] ?? null);
            $r['attendance_summary'] = $attSummary[$mid] ?? ['present' => 0, 'absent' => 0, 'excused' => 0];
            $r['open_instructions_count'] = $openInstCount[$mid] ?? 0;
            $r['files_count'] = $filesCount[$mid] ?? 0;
            $r['files'] = $filesByMeeting[$mid] ?? [];
            $r['instructions'] = $instructionsByMeeting[$mid] ?? [];
            $r['feedback_history'] = $feedbackByMeeting[$mid] ?? [];
            $r['attendance'] = $attendanceByMeeting[$mid] ?? [];
            $r['attendance_by_user'] = $attendanceByUserByMeeting[$mid] ?? [];
            $r['attendance_changes'] = $changesByMeeting[$mid] ?? [];
        }
        unset($r);

        return $rows;
    }
}

if (!function_exists('meeting_find')) {
    /**
     * Find a single meeting by its ID.
     */
    function meeting_find(PDO $pdo, int $meetingId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT wm.*, u.username as submitted_by_name
            FROM weekly_meetings wm
            LEFT JOIN users u ON wm.submitted_by = u.id
            WHERE wm.id = ?
        ");
        $stmt->execute([$meetingId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['team_update_parsed'] = meeting_decode_team_update($row['team_update'] ?? null);
        return $row;
    }
}

if (!function_exists('meeting_find_by_week')) {
    /**
     * Find a project's meeting by week number.
     */
    function meeting_find_by_week(PDO $pdo, int $projectId, int $weekNumber): ?array
    {
        $stmt = $pdo->prepare("
            SELECT wm.*, u.username as submitted_by_name
            FROM weekly_meetings wm
            LEFT JOIN users u ON wm.submitted_by = u.id
            WHERE wm.project_id = ? AND wm.week_number = ?
        ");
        $stmt->execute([$projectId, $weekNumber]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['team_update_parsed'] = meeting_decode_team_update($row['team_update'] ?? null);
        return $row;
    }
}

if (!function_exists('meeting_update_status')) {
    /**
     * Update meeting status ('scheduled', 'held', 'rescheduled', 'holiday').
     */
    function meeting_update_status(PDO $pdo, int $meetingId, string $status): bool
    {
        $validStatuses = ['scheduled', 'held', 'rescheduled', 'holiday'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }
        $stmt = $pdo->prepare("UPDATE weekly_meetings SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $meetingId]);
    }
}

if (!function_exists('meeting_submit_team_update')) {
    /**
     * Submit team update for a meeting.
     *
     * @param string|array{work_done?: string, next_steps?: string, blockers?: string} $update
     */
    function meeting_submit_team_update(PDO $pdo, int $meetingId, int $submittedBy, string|array $update): bool
    {
        $encoded = meeting_encode_team_update($update);
        $stmt = $pdo->prepare("
            UPDATE weekly_meetings
            SET team_update = ?, submitted_by = ?, submitted_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$encoded, $submittedBy, $meetingId]);
    }
}

if (!function_exists('meeting_feedback_log_history')) {
    /**
     * Log an append-only guide feedback entry in meeting_feedback_history.
     *
     * @throws InvalidArgumentException If feedback is empty.
     */
    function meeting_feedback_log_history(
        PDO $pdo,
        int $meetingId,
        int $reviewerId,
        string $feedback,
        string $status = 'held'
    ): int {
        $trimmed = trim($feedback);
        if ($trimmed === '') {
            throw new InvalidArgumentException("Feedback text cannot be empty.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO meeting_feedback_history (meeting_id, reviewer_id, feedback, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$meetingId, $reviewerId, $trimmed, $status]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('meeting_get_feedback_history')) {
    /**
     * Get append-only feedback audit history for a meeting.
     *
     * @return array<int, array<string, mixed>>
     */
    function meeting_get_feedback_history(PDO $pdo, int $meetingId): array
    {
        try {
            $stmt = $pdo->prepare("
                SELECT mfh.*, u.username as reviewer_name
                FROM meeting_feedback_history mfh
                JOIN users u ON mfh.reviewer_id = u.id
                WHERE mfh.meeting_id = ?
                ORDER BY mfh.created_at ASC, mfh.id ASC
            ");
            $stmt->execute([$meetingId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            return [];
        }
    }
}

if (!function_exists('meeting_save_guide_feedback')) {
    /**
     * Save guide feedback remarks for a meeting.
     * Appends to meeting_feedback_history if $reviewerId is provided and feedback is non-empty.
     */
    function meeting_save_guide_feedback(
        PDO $pdo,
        int $meetingId,
        string $guideFeedback,
        ?int $reviewerId = null,
        ?string $status = null
    ): bool {
        $trimmed = trim($guideFeedback);
        $stmt = $pdo->prepare("UPDATE weekly_meetings SET guide_feedback = ? WHERE id = ?");
        $res = $stmt->execute([$trimmed, $meetingId]);

        if ($reviewerId !== null && $trimmed !== '') {
            try {
                meeting_feedback_log_history($pdo, $meetingId, $reviewerId, $trimmed, $status ?? 'held');
            } catch (Throwable) {
                // Keep resilient if history table not yet created
            }
        }

        return $res;
    }
}

if (!function_exists('meeting_attendance_record')) {
    /**
     * Record or update attendance for a single user in a meeting.
     *
     * Valid statuses: 'present', 'absent', 'excused'
     */
    function meeting_attendance_record(PDO $pdo, int $meetingId, int $userId, string $status, ?int $markedBy = null): bool
    {
        $validStatuses = ['present', 'absent', 'excused'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        // Portable upsert: check then insert/update
        $stmtCheck = $pdo->prepare("SELECT status FROM meeting_attendance WHERE meeting_id = ? AND user_id = ?");
        $stmtCheck->execute([$meetingId, $userId]);
        $existing = $stmtCheck->fetchColumn();

        if ($existing !== false) {
            $stmt = $pdo->prepare("
                UPDATE meeting_attendance
                SET status = ?, marked_by = ?, marked_at = CURRENT_TIMESTAMP
                WHERE meeting_id = ? AND user_id = ?
            ");
            return $stmt->execute([$status, $markedBy, $meetingId, $userId]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO meeting_attendance (meeting_id, user_id, status, marked_by)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$meetingId, $userId, $status, $markedBy]);
    }
}

if (!function_exists('meeting_attendance_get')) {
    /**
     * Get all attendance rows for a meeting with user information.
     *
     * @return array<int, array{meeting_id: int, user_id: int, username: string, status: string, marked_by: ?int, marked_at: string}>
     */
    function meeting_attendance_get(PDO $pdo, int $meetingId): array
    {
        $stmt = $pdo->prepare("
            SELECT ma.*, u.username, m.username as marked_by_name
            FROM meeting_attendance ma
            JOIN users u ON ma.user_id = u.id
            LEFT JOIN users m ON ma.marked_by = m.id
            WHERE ma.meeting_id = ?
            ORDER BY u.username ASC
        ");
        $stmt->execute([$meetingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('meeting_attendance_log_change')) {
    /**
     * Log an attendance modification in attendance_changes.
     * Requires a mandatory non-empty reason.
     *
     * @throws InvalidArgumentException If reason is empty or statuses invalid.
     */
    function meeting_attendance_log_change(
        PDO $pdo,
        int $meetingId,
        int $userId,
        string $oldStatus,
        string $newStatus,
        int $changedBy,
        string $reason
    ): int {
        $validStatuses = ['present', 'absent', 'excused'];
        if (!in_array($oldStatus, $validStatuses, true) || !in_array($newStatus, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid attendance status transition: $oldStatus -> $newStatus");
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new InvalidArgumentException("A non-empty reason is required for attendance modification.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO attendance_changes (meeting_id, user_id, old_status, new_status, changed_by, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$meetingId, $userId, $oldStatus, $newStatus, $changedBy, $trimmedReason]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('meeting_attendance_get_changes')) {
    /**
     * Get the immutable audit history of attendance modifications for a meeting.
     */
    function meeting_attendance_get_changes(PDO $pdo, int $meetingId): array
    {
        $stmt = $pdo->prepare("
            SELECT ac.*, u.username, cb.username as changed_by_name
            FROM attendance_changes ac
            JOIN users u ON ac.user_id = u.id
            JOIN users cb ON ac.changed_by = cb.id
            WHERE ac.meeting_id = ?
            ORDER BY ac.changed_at DESC, ac.id DESC
        ");
        $stmt->execute([$meetingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('meeting_calculate_student_attendance')) {
    /**
     * Calculate personal attendance metrics for a student in a project.
     * Rescheduled and holiday meetings do not count as absences and are not evaluated against students.
     *
     * @return array{
     *     percentage: float,
     *     present: int,
     *     absent: int,
     *     excused: int,
     *     holiday: int,
     *     rescheduled: int,
     *     held: int,
     *     total_meetings: int,
     *     evaluated: int,
     *     is_shortage: bool
     * }
     */
    function meeting_calculate_student_attendance(PDO $pdo, int $projectId, int $userId): array
    {
        $stmt = $pdo->prepare("
            SELECT wm.id, wm.status as meeting_status, ma.status as attendance_status
            FROM weekly_meetings wm
            LEFT JOIN meeting_attendance ma ON wm.id = ma.meeting_id AND ma.user_id = ?
            WHERE wm.project_id = ?
            ORDER BY wm.week_number ASC
        ");
        $stmt->execute([$userId, $projectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($rows);
        $present = 0;
        $absent = 0;
        $excused = 0;
        $holiday = 0;
        $rescheduled = 0;
        $held = 0;

        foreach ($rows as $r) {
            $mStatus = $r['meeting_status'];
            $aStatus = $r['attendance_status'];

            if ($mStatus === 'holiday') {
                $holiday++;
                continue; // No absences counted
            }
            if ($mStatus === 'rescheduled') {
                $rescheduled++;
                continue; // No absences counted
            }
            if ($mStatus === 'held') {
                $held++;
            }

            if ($aStatus === 'present') {
                $present++;
            } elseif ($aStatus === 'absent') {
                if ($mStatus === 'held') {
                    $absent++;
                }
            } elseif ($aStatus === 'excused') {
                $excused++;
            }
        }

        $evaluated = $present + $absent;
        $percentage = $evaluated > 0 ? round(($present / $evaluated) * 100, 1) : 100.0;
        $isShortage = ($percentage < 75.0 && $evaluated > 0);

        return [
            'percentage'     => $percentage,
            'present'        => $present,
            'absent'         => $absent,
            'excused'        => $excused,
            'holiday'        => $holiday,
            'rescheduled'    => $rescheduled,
            'held'           => $held,
            'total_meetings' => $total,
            'evaluated'      => $evaluated,
            'is_shortage'    => $isShortage,
        ];
    }
}

if (!function_exists('meeting_save_full_attendance')) {
    /**
     * Save attendance for multiple team members in a meeting.
     * Enforces that post-save modifications require a non-empty reason and logs to attendance_changes.
     *
     * @param array<int|string, string> $attendanceData Map of user_id => status ('present'|'absent'|'excused')
     * @param int $actorId The guide or admin performing the action
     * @param string|null $reason Mandatory if modifying an existing attendance status
     * @param string|null $meetingStatus Optional new meeting status ('scheduled'|'held'|'rescheduled'|'holiday')
     * @return array{success: bool, updated: int, changes_logged: int, meeting_status: string}
     * @throws InvalidArgumentException If status invalid or modification reason missing
     */
    function meeting_save_full_attendance(
        PDO $pdo,
        int $meetingId,
        array $attendanceData,
        int $actorId,
        ?string $reason = null,
        ?string $meetingStatus = null
    ): array {
        $validStatuses = ['present', 'absent', 'excused'];
        $validMeetingStatuses = ['scheduled', 'held', 'rescheduled', 'holiday'];

        $meeting = meeting_find($pdo, $meetingId);
        if (!$meeting) {
            throw new InvalidArgumentException("Meeting ID {$meetingId} not found.");
        }

        $targetStatus = $meeting['status'];
        if ($meetingStatus !== null && in_array($meetingStatus, $validMeetingStatuses, true)) {
            $targetStatus = $meetingStatus;
        } elseif ($meeting['status'] === 'scheduled' && !empty($attendanceData)) {
            $targetStatus = 'held';
        }

        if ($targetStatus !== $meeting['status']) {
            meeting_update_status($pdo, $meetingId, $targetStatus);
        }

        // Fetch existing attendance records
        $stmtExisting = $pdo->prepare("SELECT user_id, status FROM meeting_attendance WHERE meeting_id = ?");
        $stmtExisting->execute([$meetingId]);
        $existing = [];
        foreach ($stmtExisting->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existing[(int)$row['user_id']] = $row['status'];
        }

        // Detect modifications to previously saved records
        $modifications = [];
        foreach ($attendanceData as $userId => $newStatus) {
            $userId = (int)$userId;
            $newStatus = trim((string)$newStatus);
            if (!in_array($newStatus, $validStatuses, true)) {
                throw new InvalidArgumentException("Invalid attendance status '{$newStatus}' for user {$userId}.");
            }
            if (isset($existing[$userId]) && $existing[$userId] !== $newStatus) {
                $modifications[$userId] = [
                    'old' => $existing[$userId],
                    'new' => $newStatus,
                ];
            }
        }

        // Mandatory reason check on modification
        $trimmedReason = trim((string)($reason ?? ''));
        if (!empty($modifications) && $trimmedReason === '') {
            throw new InvalidArgumentException("A non-empty reason is required for attendance modification.");
        }

        $changesLogged = 0;
        $updatedCount = 0;

        foreach ($attendanceData as $userId => $newStatus) {
            $userId = (int)$userId;
            $newStatus = trim((string)$newStatus);

            if (isset($modifications[$userId])) {
                meeting_attendance_log_change(
                    $pdo,
                    $meetingId,
                    $userId,
                    $modifications[$userId]['old'],
                    $modifications[$userId]['new'],
                    $actorId,
                    $trimmedReason
                );
                $changesLogged++;
            }

            meeting_attendance_record($pdo, $meetingId, $userId, $newStatus, $actorId);
            $updatedCount++;
        }

        return [
            'success'        => true,
            'updated'        => $updatedCount,
            'changes_logged' => $changesLogged,
            'meeting_status' => $targetStatus,
        ];
    }
}

if (!function_exists('guide_instruction_add')) {
    /**
     * Add a guide instruction for a meeting.
     */
    function guide_instruction_add(PDO $pdo, int $meetingId, string $text, int $createdBy): int
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            throw new InvalidArgumentException("Instruction text cannot be empty.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO guide_instructions (meeting_id, text, status, created_by)
            VALUES (?, ?, 'open', ?)
        ");
        $stmt->execute([$meetingId, $trimmed, $createdBy]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('guide_instruction_update_status')) {
    /**
     * Update guide instruction status ('open', 'acknowledged', 'done').
     */
    function guide_instruction_update_status(PDO $pdo, int $instructionId, string $status): bool
    {
        $validStatuses = ['open', 'acknowledged', 'done'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        if ($status === 'done') {
            $stmt = $pdo->prepare("UPDATE guide_instructions SET status = ?, closed_at = CURRENT_TIMESTAMP WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE guide_instructions SET status = ?, closed_at = NULL WHERE id = ?");
        }
        return $stmt->execute([$status, $instructionId]);
    }
}

if (!function_exists('guide_instructions_get_by_meeting')) {
    /**
     * Get instructions created for a specific meeting.
     */
    function guide_instructions_get_by_meeting(PDO $pdo, int $meetingId): array
    {
        $stmt = $pdo->prepare("
            SELECT gi.*, u.username as created_by_name
            FROM guide_instructions gi
            JOIN users u ON gi.created_by = u.id
            WHERE gi.meeting_id = ?
            ORDER BY gi.id ASC
        ");
        $stmt->execute([$meetingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('guide_instructions_get_open_by_project')) {
    /**
     * Get all open/acknowledged instructions across all meetings of a project (for next-meeting rollover).
     */
    function guide_instructions_get_open_by_project(PDO $pdo, int $projectId): array
    {
        $stmt = $pdo->prepare("
            SELECT gi.*, wm.week_number, wm.meeting_date, u.username as created_by_name
            FROM guide_instructions gi
            JOIN weekly_meetings wm ON gi.meeting_id = wm.id
            JOIN users u ON gi.created_by = u.id
            WHERE wm.project_id = ? AND gi.status IN ('open', 'acknowledged')
            ORDER BY wm.week_number ASC, gi.id ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('guide_instructions_get_by_project')) {
    /**
     * Get all instructions across all meetings of a project.
     *
     * @return array<int, array<string, mixed>>
     */
    function guide_instructions_get_by_project(PDO $pdo, int $projectId): array
    {
        $stmt = $pdo->prepare("
            SELECT gi.*, wm.week_number, wm.meeting_date, u.username as created_by_name
            FROM guide_instructions gi
            JOIN weekly_meetings wm ON gi.meeting_id = wm.id
            JOIN users u ON gi.created_by = u.id
            WHERE wm.project_id = ?
            ORDER BY wm.week_number ASC, gi.id ASC
        ");
        $stmt->execute([$projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('guide_instruction_find')) {
    /**
     * Find a single instruction by ID with meeting and project context.
     */
    function guide_instruction_find(PDO $pdo, int $instructionId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT gi.*, wm.project_id, wm.week_number, p.classroom_id, u.username as created_by_name
            FROM guide_instructions gi
            JOIN weekly_meetings wm ON gi.meeting_id = wm.id
            JOIN projects p ON wm.project_id = p.id
            JOIN users u ON gi.created_by = u.id
            WHERE gi.id = ?
        ");
        $stmt->execute([$instructionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('meeting_file_add')) {
    /**
     * Link an uploaded file attachment to a weekly meeting.
     */
    function meeting_file_add(
        PDO $pdo,
        int $meetingId,
        string $originalName,
        string $storedName,
        string $filePath,
        ?int $fileSize = null,
        ?string $mimeType = null
    ): int {
        $stmt = $pdo->prepare("
            INSERT INTO weekly_submission_files (
                meeting_id, original_name, stored_name, file_name, file_path, file_size, mime_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $meetingId,
            $originalName,
            $storedName,
            $originalName, // file_name legacy alias
            $filePath,
            $fileSize,
            $mimeType,
        ]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('meeting_files_get')) {
    /**
     * Get all file attachments for a meeting.
     */
    function meeting_files_get(PDO $pdo, int $meetingId): array
    {
        $stmt = $pdo->prepare("
            SELECT id, meeting_id, original_name, stored_name, file_name, file_path, file_size, mime_type, uploaded_at
            FROM weekly_submission_files
            WHERE meeting_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$meetingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('meeting_compute_projects_health')) {
    /**
     * Phase 5 Teacher Ledger Rework:
     * Compute project health strictly from meetings held, student attendance,
     * and open guide instruction count. Zero dependencies on tasks.
     *
     * @param PDO $pdo
     * @param array<int> $projectIds
     * @param string|null $referenceDate Optional Y-m-d string for deterministic testing (defaults to today)
     * @return array<int, array{
     *     status: 'healthy'|'warning'|'critical'|'neutral',
     *     label: string,
     *     badge_class: string,
     *     dot_color: string,
     *     meetings_held: int,
     *     meetings_total: int,
     *     meetings_past_unheld: int,
     *     attendance_pct: float,
     *     open_instructions: int,
     *     reasons: array<string>
     * }>
     */
    function meeting_compute_projects_health(PDO $pdo, array $projectIds, ?string $referenceDate = null): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $projectIds = array_values(array_unique(array_map('intval', $projectIds)));
        $ph = implode(',', array_fill(0, count($projectIds), '?'));

        $ref = $referenceDate !== null ? new DateTime($referenceDate) : new DateTime('today');
        $ref->setTime(0, 0, 0);
        $refDateStr = $ref->format('Y-m-d');

        // 1. Fetch meeting statuses and dates
        $stmtMeetings = $pdo->prepare("
            SELECT project_id, status, meeting_date
            FROM weekly_meetings
            WHERE project_id IN ($ph)
            ORDER BY week_number ASC
        ");
        $stmtMeetings->execute($projectIds);
        $meetingsData = $stmtMeetings->fetchAll(PDO::FETCH_ASSOC);

        $meetingsByProject = [];
        foreach ($meetingsData as $m) {
            $meetingsByProject[$m['project_id']][] = $m;
        }

        // 2. Fetch attendance aggregated across held meetings
        $stmtAttendance = $pdo->prepare("
            SELECT wm.project_id, ma.status as att_status, COUNT(*) as c
            FROM weekly_meetings wm
            JOIN meeting_attendance ma ON wm.id = ma.meeting_id
            WHERE wm.project_id IN ($ph) AND wm.status = 'held'
            GROUP BY wm.project_id, ma.status
        ");
        $stmtAttendance->execute($projectIds);
        $attData = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);

        $attCountsByProject = [];
        foreach ($attData as $a) {
            $pid = (int)$a['project_id'];
            $st  = $a['att_status'];
            $attCountsByProject[$pid][$st] = (int)$a['c'];
        }

        // 3. Fetch open instruction count per project
        $stmtInstructions = $pdo->prepare("
            SELECT wm.project_id, COUNT(*) as open_count
            FROM weekly_meetings wm
            JOIN guide_instructions gi ON wm.id = gi.meeting_id
            WHERE wm.project_id IN ($ph) AND gi.status = 'open'
            GROUP BY wm.project_id
        ");
        $stmtInstructions->execute($projectIds);
        $instData = $stmtInstructions->fetchAll(PDO::FETCH_ASSOC);

        $openInstByProject = [];
        foreach ($instData as $inst) {
            $openInstByProject[(int)$inst['project_id']] = (int)$inst['open_count'];
        }

        // 4. Compute health per project
        $results = [];
        foreach ($projectIds as $pid) {
            $pMeetings = $meetingsByProject[$pid] ?? [];
            $totalMeetings = count($pMeetings);
            $heldCount = 0;
            $holidayCount = 0;
            $rescheduledCount = 0;
            $pastUnheldCount = 0;
            $dueMeetingsCount = 0;

            foreach ($pMeetings as $pm) {
                $st = $pm['status'];
                $mDate = $pm['meeting_date'];

                if ($st === 'held') {
                    $heldCount++;
                } elseif ($st === 'holiday') {
                    $holidayCount++;
                } elseif ($st === 'rescheduled') {
                    $rescheduledCount++;
                }

                if ($mDate <= $refDateStr) {
                    $dueMeetingsCount++;
                    if ($st === 'scheduled') {
                        $pastUnheldCount++;
                    }
                }
            }

            $pAtt = $attCountsByProject[$pid] ?? [];
            $presentCount = $pAtt['present'] ?? 0;
            $absentCount  = $pAtt['absent'] ?? 0;
            $evaluated = $presentCount + $absentCount;
            $attPct = $evaluated > 0 ? round(($presentCount / $evaluated) * 100, 1) : 100.0;

            $openInst = $openInstByProject[$pid] ?? 0;

            // Health rules
            $status = 'healthy';
            $label = 'On Track';
            $badgeClass = 'badge-accent';
            $dotColor = 'bg-emerald-400';
            $reasons = [];

            if ($dueMeetingsCount === 0 && $heldCount === 0) {
                $status = 'neutral';
                $label = 'Pending';
                $badgeClass = 'badge-muted';
                $dotColor = 'bg-muted-ui';
                $reasons[] = 'Kickoff pending (first meeting upcoming)';
            } else {
                // Critical checks
                $isCritical = false;
                if ($pastUnheldCount >= 2) {
                    $isCritical = true;
                    $reasons[] = "{$pastUnheldCount} past review meetings missed";
                }
                if ($evaluated > 0 && $attPct < 60.0) {
                    $isCritical = true;
                    $reasons[] = "Severe attendance shortage ({$attPct}%)";
                }
                if ($openInst >= 5) {
                    $isCritical = true;
                    $reasons[] = "{$openInst} unresolved guide directives";
                }

                if ($isCritical) {
                    $status = 'critical';
                    $label = 'At Risk';
                    $badgeClass = 'badge-danger';
                    $dotColor = 'bg-rose-500';
                } else {
                    // Warning checks
                    $isWarning = false;
                    if ($pastUnheldCount === 1) {
                        $isWarning = true;
                        $reasons[] = '1 past meeting unheld';
                    }
                    if ($evaluated > 0 && $attPct < 75.0) {
                        $isWarning = true;
                        $reasons[] = "Attendance below 75% threshold ({$attPct}%)";
                    }
                    if ($openInst >= 3) {
                        $isWarning = true;
                        $reasons[] = "{$openInst} open guide directives";
                    }

                    if ($isWarning) {
                        $status = 'warning';
                        $label = 'Needs Attention';
                        $badgeClass = 'badge-warning';
                        $dotColor = 'bg-amber-400';
                    } else {
                        $reasons[] = 'Saturday meetings and attendance on track';
                    }
                }
            }

            $results[$pid] = [
                'status'               => $status,
                'label'                => $label,
                'badge_class'          => $badgeClass,
                'dot_color'            => $dotColor,
                'meetings_held'        => $heldCount,
                'meetings_total'       => $totalMeetings,
                'meetings_past_unheld' => $pastUnheldCount,
                'attendance_pct'       => $attPct,
                'open_instructions'    => $openInst,
                'reasons'              => $reasons,
            ];
        }

        return $results;
    }
}

if (!function_exists('meeting_compute_project_health')) {
    /**
     * Compute single project health.
     */
    function meeting_compute_project_health(PDO $pdo, int $projectId, ?string $referenceDate = null): array
    {
        $res = meeting_compute_projects_health($pdo, [$projectId], $referenceDate);
        return $res[$projectId] ?? [
            'status'               => 'neutral',
            'label'                => 'Pending',
            'badge_class'          => 'badge-muted',
            'dot_color'            => 'bg-muted-ui',
            'meetings_held'        => 0,
            'meetings_total'       => 0,
            'meetings_past_unheld' => 0,
            'attendance_pct'       => 100.0,
            'open_instructions'    => 0,
            'reasons'              => ['No meeting data available'],
        ];
    }
}
