<?php
declare(strict_types=1);

/**
 * Marks Repository — CIE Evaluation.
 *
 * Project Report (/50) + Individual Presentation (/25) + Viva Q&A (/25) = 100.
 * No finalization locks, no audit trails, no config files.
 */

require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/meeting_repository.php';

/** Evaluation weight constants. */
function marks_get_config(): array
{
    return [
        'max_report_marks'       => 50.0,
        'max_presentation_marks' => 25.0,
        'max_qa_marks'           => 25.0,
        'total_max_marks'        => 100.0,
    ];
}

/**
 * Get the project-level report marks row.
 */
function marks_get_project_marks(PDO $pdo, int $projectId): ?array
{
    if ($projectId <= 0) return null;
    $stmt = $pdo->prepare("SELECT * FROM project_marks WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    return [
        'project_id'   => (int)$row['project_id'],
        'report_marks' => $row['report_marks'] !== null ? (float)$row['report_marks'] : null,
    ];
}

/**
 * Get individual student marks for a project (keyed by user_id).
 */
function marks_get_students_marks(PDO $pdo, int $projectId, ?int $userId = null): array
{
    if ($projectId <= 0) return [];

    if ($userId !== null && $userId > 0) {
        $stmt = $pdo->prepare("
            SELECT sm.*, u.username
            FROM student_marks sm
            JOIN users u ON sm.user_id = u.id
            WHERE sm.project_id = ? AND sm.user_id = ?
        ");
        $stmt->execute([$projectId, $userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT sm.*, u.username
            FROM student_marks sm
            JOIN users u ON sm.user_id = u.id
            WHERE sm.project_id = ?
            ORDER BY u.username ASC
        ");
        $stmt->execute([$projectId]);
    }

    $result = [];
    $project = marks_get_project_marks($pdo, $projectId);
    $reportMarks = $project ? $project['report_marks'] : null;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $uid = (int)$row['user_id'];
        $pres = $row['presentation_marks'] !== null ? (float)$row['presentation_marks'] : null;
        $qa   = $row['qa_marks'] !== null ? (float)$row['qa_marks'] : null;

        $total = null;
        if ($reportMarks !== null && $pres !== null && $qa !== null) {
            $total = $reportMarks + $pres + $qa;
        }

        $result[$uid] = [
            'user_id'             => $uid,
            'username'            => $row['username'],
            'report_marks'        => $reportMarks,
            'presentation_marks'  => $pres,
            'qa_marks'            => $qa,
            'total_marks'         => $total,
        ];
    }

    return $result;
}

/**
 * Get full evaluation sheet for a project (project marks + all student marks + attendance).
 */
function marks_get_full_evaluation_sheet(PDO $pdo, int $projectId): array
{
    $config  = marks_get_config();
    $projectMarks = marks_get_project_marks($pdo, $projectId);

    // Get project metadata
    $stmtProj = $pdo->prepare("
        SELECT p.id, p.name, p.classroom_id, p.mentor_id, u.username AS mentor_name
        FROM projects p
        LEFT JOIN users u ON p.mentor_id = u.id
        WHERE p.id = ?
    ");
    $stmtProj->execute([$projectId]);
    $projRow = $stmtProj->fetch(PDO::FETCH_ASSOC);

    // Get all active members
    $stmtMembers = $pdo->prepare("
        SELECT pm.user_id, pm.is_leader, u.username, cm.usn
        FROM project_members pm
        JOIN users u ON pm.user_id = u.id
        LEFT JOIN projects p ON pm.project_id = p.id
        LEFT JOIN classroom_members cm ON cm.classroom_id = p.classroom_id AND cm.user_id = pm.user_id
        WHERE pm.project_id = ? AND pm.join_status = 'Active'
        ORDER BY pm.is_leader DESC, u.username ASC
    ");
    $stmtMembers->execute([$projectId]);
    $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

    // Get existing student marks
    $studentMarks = marks_get_students_marks($pdo, $projectId);

    $reportMarks = $projectMarks ? $projectMarks['report_marks'] : null;

    $students = [];
    foreach ($members as $m) {
        $uid = (int)$m['user_id'];
        $existing = $studentMarks[$uid] ?? null;
        $pres = $existing['presentation_marks'] ?? null;
        $qa   = $existing['qa_marks'] ?? null;
        $total = null;
        if ($reportMarks !== null && $pres !== null && $qa !== null) {
            $total = $reportMarks + $pres + $qa;
        }

        // Attendance stats
        $att = meeting_calculate_attendance($pdo, $projectId, $uid);
        $att['held'] = $att['total'];
        $att['absent'] = max(0, $att['total'] - $att['present']);
        $att['excused'] = 0;

        $students[] = [
            'user_id'             => $uid,
            'username'            => $m['username'],
            'usn'                 => $m['usn'] ?? '',
            'is_leader'           => (bool)$m['is_leader'],
            'role_label'          => $m['is_leader'] ? 'Leader' : 'Member',
            'attendance'          => $att,
            'attendance_pct'      => ($att['total'] > 0) ? $att['percentage'] : 0.0,
            'is_shortage'         => $att['has_shortage'],
            'report_marks'        => $reportMarks,
            'presentation_marks'  => $pres,
            'qa_marks'            => $qa,
            'total_marks'         => $total,
            'percentage'          => $total !== null ? $total : null,
        ];
    }

    return [
        'project'       => [
            'id'           => $projectId,
            'name'         => $projRow['name'] ?? 'Project',
            'mentor_id'    => $projRow['mentor_id'] ?? null,
            'mentor_name'  => $projRow['mentor_name'] ?? 'Unassigned',
            'classroom_id' => $projRow['classroom_id'] ?? 0,
        ],
        'project_id'    => $projectId,
        'report_marks'  => $reportMarks,
        'project_marks' => [
            'report_marks' => $reportMarks,
        ],
        'is_finalized'  => false,
        'config'        => $config,
        'students'      => $students,
    ];
}

/**
 * Get evaluation sheets for all projects in a classroom.
 */
function marks_get_classroom_evaluation_sheet(PDO $pdo, int $classroomId): array
{
    $stmt = $pdo->prepare("SELECT id, name FROM projects WHERE classroom_id = ? ORDER BY name ASC");
    $stmt->execute([$classroomId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sheets = [];
    foreach ($projects as $p) {
        $sheet = marks_get_full_evaluation_sheet($pdo, (int)$p['id']);
        $sheets[] = $sheet;
    }
    return $sheets;
}

/**
 * Save marks for a project. Simple upsert, no locks or audit.
 */
function marks_save(PDO $pdo, int $projectId, ?float $reportMarks, array $studentMarks, int $updatedBy): void
{
    $config = marks_get_config();

    if ($reportMarks !== null) {
        $reportMarks = max(0, min($reportMarks, $config['max_report_marks']));
    }

    $stmt = $pdo->prepare("
        INSERT INTO project_marks (project_id, report_marks, updated_by)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE report_marks = VALUES(report_marks), updated_by = VALUES(updated_by)
    ");
    $stmt->execute([$projectId, $reportMarks, $updatedBy]);

    $stmtStu = $pdo->prepare("
        INSERT INTO student_marks (project_id, user_id, presentation_marks, qa_marks, updated_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            presentation_marks = VALUES(presentation_marks),
            qa_marks = VALUES(qa_marks),
            updated_by = VALUES(updated_by)
    ");

    foreach ($studentMarks as $userId => $marks) {
        $pres = isset($marks['presentation']) && $marks['presentation'] !== null ? max(0, min((float)$marks['presentation'], $config['max_presentation_marks'])) : null;
        $qa   = isset($marks['qa']) && $marks['qa'] !== null ? max(0, min((float)$marks['qa'], $config['max_qa_marks'])) : null;
        $stmtStu->execute([$projectId, (int)$userId, $pres, $qa, $updatedBy]);
    }
}
