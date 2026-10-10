<?php
// meeting_engine.php - Saturday Review Schedule Derivation & Strict Temporal Week Locking
require_once __DIR__ . '/dbs.php';

/**
 * Derives all Saturdays between start date and end date
 * @return array<int, array{week_number: int, meeting_date: string, date_from: string}>
 */
function derive_saturdays(string $startDate, string $endDate): array {
    $saturdays = [];
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);

    // If start date is not a Saturday, advance to the first Saturday
    $current = clone $start;
    if ($current->format('N') != 6) {
        $current->modify('next saturday');
    }

    $weekNum = 1;
    $prevCycleEnd = clone $start;

    while ($current <= $end) {
        $meetingDate = $current->format('Y-m-d');
        
        // Cycle start date: either start date for week 1, or previous Sunday (meetingDate - 6 days)
        $dateFromObj = clone $current;
        $dateFromObj->modify('-6 days');
        if ($dateFromObj < $start) {
            $dateFrom = $start->format('Y-m-d');
        } else {
            $dateFrom = $dateFromObj->format('Y-m-d');
        }

        $saturdays[] = [
            'week_number'  => $weekNum,
            'meeting_date' => $meetingDate,
            'date_from'    => $dateFrom,
        ];

        $weekNum++;
        $current->modify('+7 days');
    }

    return $saturdays;
}

/**
 * Initializes or synchronizes the derived weekly_meetings records for a given project
 */
function sync_project_meetings(PDO $pdo, int $projectId): void {
    // Get classroom dates for this project
    $stmt = $pdo->prepare("
        SELECT c.start_date, c.end_date 
        FROM projects p 
        JOIN classrooms c ON p.classroom_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$projectId]);
    $dates = $stmt->fetch();
    if (!$dates) {
        return;
    }

    $schedule = derive_saturdays($dates['start_date'], $dates['end_date']);
    $insertStmt = $pdo->prepare("
        INSERT IGNORE INTO weekly_meetings (project_id, week_number, meeting_date, status)
        VALUES (?, ?, ?, 'scheduled')
    ");

    foreach ($schedule as $week) {
        $insertStmt->execute([$projectId, $week['week_number'], $week['meeting_date']]);
    }
}

/**
 * Determines strict temporal locking status for a meeting week
 * 
 * Rules:
 * 1. Future Weeks (meeting_date > today):
 *    - Student lock: cannot submit updates. Badge "Unlocks on [date_from]".
 *    - Teacher lock: cannot record advance attendance.
 * 2. Current Active Week (today between date_from and meeting_date, or today == meeting_date):
 *    - Student open: can submit work_done, next_steps, blockers.
 *    - Teacher open: can record review and attendance.
 * 3. Past Weeks (meeting_date < today or status == 'held'):
 *    - Student frozen: read-only archive.
 *    - Attendance frozen: modifications require audit reason in attendance_changes.
 */
function get_week_temporal_status(string $meetingDate, string $status = 'scheduled'): array {
    $today = date('Y-m-d');
    $mDate = new DateTime($meetingDate);
    
    // Derived week window start (Sunday preceding Saturday review)
    $wStart = clone $mDate;
    $wStart->modify('-6 days');
    $dateFrom = $wStart->format('Y-m-d');

    if ($meetingDate > $today) {
        // Is it within current week's review window?
        if ($today >= $dateFrom && $today <= $meetingDate) {
            $state = 'current';
        } else {
            $state = 'future';
        }
    } elseif ($meetingDate === $today) {
        $state = 'current';
    } else {
        $state = 'past';
    }

    // If meeting is already officially held, freeze both student submissions and casual teacher edits
    $isHeld = ($status === 'held');
    $studentCanEdit = ($state === 'current' && !$isHeld);
    $isFrozenOrPast = ($state === 'past' || $isHeld);
    $teacherCanConduct = ($state === 'current' && !$isHeld);

    return [
        'state'                      => $state, // 'future', 'current', 'past'
        'is_future'                  => ($state === 'future'),
        'is_current'                 => ($state === 'current'),
        'is_past'                    => ($state === 'past'),
        'is_held'                    => $isHeld,
        'is_locked'                  => $isFrozenOrPast,
        'date_from'                  => $dateFrom,
        'meeting_date'               => $meetingDate,
        'student_can_edit'           => $studentCanEdit,
        'teacher_can_conduct'        => $teacherCanConduct,
        'teacher_can_mark_attendance'=> ($state !== 'future'),
        'attendance_requires_reason' => $isFrozenOrPast,
    ];
}

/**
 * Calculates team attendance percentage & shortage status (< 75%)
 */
function calculate_project_attendance(PDO $pdo, int $projectId): array {
    // Total held meetings for this project
    $mStmt = $pdo->prepare("SELECT COUNT(*) as total_held FROM weekly_meetings WHERE project_id = ? AND status = 'held'");
    $mStmt->execute([$projectId]);
    $totalHeld = (int)($mStmt->fetch()['total_held'] ?? 0);

    // Get members of the project
    $memStmt = $pdo->prepare("
        SELECT u.id, u.name, u.identifier, pm.is_leader
        FROM project_members pm
        JOIN users u ON pm.student_id = u.id
        WHERE pm.project_id = ?
        ORDER BY pm.is_leader DESC, u.name ASC
    ");
    $memStmt->execute([$projectId]);
    $members = $memStmt->fetchAll();

    $stats = [];
    foreach ($members as $mem) {
        if ($totalHeld === 0) {
            $percent = 100.0;
            $presentCount = 0;
        } else {
            // Count present + excused as positive attendance (or present only, university standard present counts)
            $attStmt = $pdo->prepare("
                SELECT COUNT(*) as attended
                FROM meeting_attendance ma
                JOIN weekly_meetings wm ON ma.meeting_id = wm.id
                WHERE wm.project_id = ? AND ma.student_id = ? AND wm.status = 'held' AND ma.status IN ('present', 'excused')
            ");
            $attStmt->execute([$projectId, $mem['id']]);
            $presentCount = (int)($attStmt->fetch()['attended'] ?? 0);
            $percent = round(($presentCount / $totalHeld) * 100, 1);
        }

        $stats[$mem['id']] = [
            'student_id'   => $mem['id'],
            'name'         => $mem['name'],
            'identifier'   => $mem['identifier'],
            'is_leader'    => $mem['is_leader'],
            'total_held'   => $totalHeld,
            'attended'     => $presentCount,
            'percentage'   => $percent,
            'has_shortage' => ($percent < 75.0 && $totalHeld > 0),
        ];
    }

    return $stats;
}
