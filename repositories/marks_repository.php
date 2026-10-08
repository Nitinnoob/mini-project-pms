<?php
/**
 * Evaluation Marks Repository
 *
 * Continuous Internal Evaluation (CIE) & Viva Marks engine.
 * Project Report Marks (shared out of 50) + Individual Presentation Marks (out of 25) + Individual Q&A Marks (out of 25).
 * Enforces config-driven weights, evaluator permissions, finalize lock, and immutable audit trails.
 */

declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/meeting_repository.php';

/**
 * Returns the evaluation configuration (weights and thresholds).
 */
function marks_get_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $configFile = __DIR__ . '/../config/evaluation.php';
    if (file_exists($configFile)) {
        $loaded = require $configFile;
        if (is_array($loaded)) {
            $config = $loaded;
            return $config;
        }
    }

    $config = [
        'max_report_marks'              => 50.0,
        'max_presentation_marks'        => 25.0,
        'max_qa_marks'                  => 25.0,
        'total_max_marks'               => 100.0,
        'attendance_shortage_threshold' => 75.0,
        'components' => [
            'report'       => ['key' => 'report_marks', 'label' => 'Project Report', 'max' => 50.0, 'scope' => 'project'],
            'presentation' => ['key' => 'presentation_marks', 'label' => 'Presentation & Demo', 'max' => 25.0, 'scope' => 'student'],
            'qa'           => ['key' => 'qa_marks', 'label' => 'Viva / Q&A', 'max' => 25.0, 'scope' => 'student'],
        ],
    ];

    return $config;
}

/**
 * Fetches the project-level marks record for a project.
 */
function marks_get_project_marks(PDO $pdo, int $projectId): ?array
{
    if ($projectId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT pm.*, u_fin.username AS finalized_by_name, u_upd.username AS updated_by_name
        FROM project_marks pm
        LEFT JOIN users u_fin ON pm.finalized_by = u_fin.id
        LEFT JOIN users u_upd ON pm.updated_by = u_upd.id
        WHERE pm.project_id = ?
    ");
    $stmt->execute([$projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    return [
        'project_id'        => (int)$row['project_id'],
        'report_marks'      => ($row['report_marks'] !== null) ? (float)$row['report_marks'] : null,
        'is_finalized'      => (bool)$row['is_finalized'],
        'finalized_by'      => $row['finalized_by'] ? (int)$row['finalized_by'] : null,
        'finalized_by_name' => $row['finalized_by_name'] ?? null,
        'finalized_at'      => $row['finalized_at'],
        'updated_by'        => $row['updated_by'] ? (int)$row['updated_by'] : null,
        'updated_by_name'   => $row['updated_by_name'] ?? null,
        'updated_at'        => $row['updated_at'],
    ];
}

/**
 * Fetches student-level marks for a project (optionally filtered by specific user).
 * Returns an associative array keyed by user_id.
 */
function marks_get_students_marks(PDO $pdo, int $projectId, ?int $userId = null): array
{
    if ($projectId <= 0) {
        return [];
    }

    if ($userId !== null && $userId > 0) {
        $stmt = $pdo->prepare("
            SELECT sm.*, u.username, u_upd.username AS updated_by_name
            FROM student_marks sm
            JOIN users u ON sm.user_id = u.id
            LEFT JOIN users u_upd ON sm.updated_by = u_upd.id
            WHERE sm.project_id = ? AND sm.user_id = ?
        ");
        $stmt->execute([$projectId, $userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT sm.*, u.username, u_upd.username AS updated_by_name
            FROM student_marks sm
            JOIN users u ON sm.user_id = u.id
            LEFT JOIN users u_upd ON sm.updated_by = u_upd.id
            WHERE sm.project_id = ?
        ");
        $stmt->execute([$projectId]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];

    foreach ($rows as $r) {
        $uid = (int)$r['user_id'];
        $result[$uid] = [
            'project_id'         => (int)$r['project_id'],
            'user_id'            => $uid,
            'username'           => $r['username'],
            'presentation_marks' => ($r['presentation_marks'] !== null) ? (float)$r['presentation_marks'] : null,
            'qa_marks'           => ($r['qa_marks'] !== null) ? (float)$r['qa_marks'] : null,
            'updated_by'         => $r['updated_by'] ? (int)$r['updated_by'] : null,
            'updated_by_name'    => $r['updated_by_name'] ?? null,
            'updated_at'         => $r['updated_at'],
        ];
    }

    return $result;
}

/**
 * Compiles a comprehensive evaluation sheet for a project including attendance and totals.
 */
function marks_get_full_evaluation_sheet(PDO $pdo, int $projectId): array
{
    $config = marks_get_config();

    $stmtProj = $pdo->prepare("
        SELECT p.id, p.name, p.description, p.classroom_id, p.mentor_id,
               c.name AS classroom_name, c.start_date, c.end_date,
               u_mentor.username AS mentor_name
        FROM projects p
        JOIN classrooms c ON p.classroom_id = c.id
        LEFT JOIN users u_mentor ON p.mentor_id = u_mentor.id
        WHERE p.id = ?
    ");
    $stmtProj->execute([$projectId]);
    $project = $stmtProj->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        throw new InvalidArgumentException("Project #$projectId not found.");
    }

    $projectMarks = marks_get_project_marks($pdo, $projectId) ?? [
        'project_id'        => $projectId,
        'report_marks'      => null,
        'is_finalized'      => false,
        'finalized_by'      => null,
        'finalized_by_name' => null,
        'finalized_at'      => null,
        'updated_by'        => null,
        'updated_by_name'   => null,
        'updated_at'        => null,
    ];

    $studentsMarks = marks_get_students_marks($pdo, $projectId);

    // Fetch active team members with USN
    $stmtMembers = $pdo->prepare("
        SELECT pm.user_id, pm.is_leader, u.username, cm.usn
        FROM project_members pm
        JOIN users u ON pm.user_id = u.id
        LEFT JOIN classroom_members cm ON (cm.classroom_id = ? AND cm.user_id = pm.user_id)
        WHERE pm.project_id = ? AND pm.join_status = 'Active'
        ORDER BY pm.is_leader DESC, u.username ASC
    ");
    $stmtMembers->execute([(int)$project['classroom_id'], $projectId]);
    $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);

    $studentsEvaluated = [];
    $reportMarks = $projectMarks['report_marks'];

    foreach ($members as $m) {
        $uid = (int)$m['user_id'];
        $sMarks = $studentsMarks[$uid] ?? [
            'presentation_marks' => null,
            'qa_marks'           => null,
            'updated_by_name'    => null,
            'updated_at'         => null,
        ];

        $presMarks = $sMarks['presentation_marks'];
        $qaMarks = $sMarks['qa_marks'];

        // Calculate student total if any marks are entered
        $hasAnyMarks = ($reportMarks !== null || $presMarks !== null || $qaMarks !== null);
        $totalMarks = $hasAnyMarks
            ? round((float)($reportMarks ?? 0) + (float)($presMarks ?? 0) + (float)($qaMarks ?? 0), 2)
            : null;

        $totalMax = (float)($config['total_max_marks'] ?? 100.0);
        $percentage = ($totalMarks !== null && $totalMax > 0)
            ? round(($totalMarks / $totalMax) * 100, 1)
            : null;

        // Fetch student attendance summary
        $attSummary = meeting_calculate_student_attendance($pdo, $projectId, $uid);

        $studentsEvaluated[] = [
            'user_id'            => $uid,
            'username'           => $m['username'],
            'usn'                => $m['usn'] ?? 'N/A',
            'is_leader'          => (bool)$m['is_leader'],
            'role_label'         => $m['is_leader'] ? 'Project Leader' : 'Team Member',
            'attendance'         => $attSummary,
            'attendance_pct'     => $attSummary['percentage'],
            'is_shortage'        => $attSummary['is_shortage'],
            'report_marks'       => $reportMarks,
            'presentation_marks' => $presMarks,
            'qa_marks'           => $qaMarks,
            'total_marks'        => $totalMarks,
            'percentage'         => $percentage,
            'is_fully_marked'    => ($reportMarks !== null && $presMarks !== null && $qaMarks !== null),
        ];
    }

    return [
        'project'       => [
            'id'             => (int)$project['id'],
            'name'           => $project['name'],
            'description'    => $project['description'],
            'classroom_id'   => (int)$project['classroom_id'],
            'classroom_name' => $project['classroom_name'],
            'mentor_id'      => $project['mentor_id'] ? (int)$project['mentor_id'] : null,
            'mentor_name'    => $project['mentor_name'],
        ],
        'project_marks' => $projectMarks,
        'students'      => $studentsEvaluated,
        'config'        => $config,
        'is_finalized'  => (bool)$projectMarks['is_finalized'],
    ];
}

/**
 * Returns evaluation sheets for all projects in a classroom.
 */
function marks_get_classroom_evaluation_sheet(PDO $pdo, int $classroomId): array
{
    if ($classroomId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT id FROM projects WHERE classroom_id = ? ORDER BY name ASC");
    $stmt->execute([$classroomId]);
    $projectIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sheets = [];
    foreach ($projectIds as $pid) {
        $sheets[] = marks_get_full_evaluation_sheet($pdo, (int)$pid);
    }

    return $sheets;
}

/**
 * Saves evaluation marks (project report + individual student presentation/QA marks).
 * Validates config limits, evaluator authorization, finalize lock, and logs post-finalize changes.
 */
function marks_save(
    PDO $pdo,
    int $projectId,
    int $userId,
    ?float $reportMarks,
    array $studentsMarks,
    bool $finalize = false,
    ?string $reason = null
): array {
    if ($projectId <= 0 || $userId <= 0) {
        throw new InvalidArgumentException("Invalid project or user ID.");
    }

    $config = marks_get_config();
    $maxReport = (float)($config['max_report_marks'] ?? 50.0);
    $maxPres   = (float)($config['max_presentation_marks'] ?? 25.0);
    $maxQa     = (float)($config['max_qa_marks'] ?? 25.0);

    // 1. Validate Report Marks Range
    if ($reportMarks !== null) {
        if ($reportMarks < 0.0 || $reportMarks > $maxReport) {
            throw new InvalidArgumentException("Report marks must be between 0 and $maxReport.");
        }
    }

    // 2. Validate Student Marks Range
    $sanitizedStudentMarks = [];
    foreach ($studentsMarks as $uid => $marks) {
        $uid = (int)$uid;
        if ($uid <= 0) continue;

        $pres = isset($marks['presentation_marks']) && $marks['presentation_marks'] !== '' && $marks['presentation_marks'] !== null
            ? (float)$marks['presentation_marks']
            : null;
        $qa = isset($marks['qa_marks']) && $marks['qa_marks'] !== '' && $marks['qa_marks'] !== null
            ? (float)$marks['qa_marks']
            : null;

        if ($pres !== null && ($pres < 0.0 || $pres > $maxPres)) {
            throw new InvalidArgumentException("Presentation marks for student #$uid must be between 0 and $maxPres.");
        }
        if ($qa !== null && ($qa < 0.0 || $qa > $maxQa)) {
            throw new InvalidArgumentException("Q&A marks for student #$uid must be between 0 and $maxQa.");
        }

        $sanitizedStudentMarks[$uid] = [
            'presentation_marks' => $pres,
            'qa_marks'           => $qa,
        ];
    }

    // 3. Check Finalize Lock and Enforce Admin-Only Edits with Mandatory Reason
    $existingProjMarks = marks_get_project_marks($pdo, $projectId);
    $isAlreadyFinalized = $existingProjMarks ? (bool)$existingProjMarks['is_finalized'] : false;

    if ($isAlreadyFinalized) {
        if (!can_override_finalized_marks($pdo, $projectId, $userId)) {
            throw new RuntimeException("Evaluation is finalized and locked. Only classroom Admins can modify finalized marks.");
        }

        $reason = trim($reason ?? '');
        if ($reason === '') {
            throw new InvalidArgumentException("A non-empty reason is mandatory when modifying finalized marks.");
        }

        // Audit Trail: Record changes to project_marks and student_marks
        $existingStudentsMarks = marks_get_students_marks($pdo, $projectId);

        // Project report marks change audit
        $oldReport = $existingProjMarks['report_marks'];
        if ($oldReport !== $reportMarks) {
            $stmtChange = $pdo->prepare("
                INSERT INTO marks_changes (project_id, user_id, field_name, old_value, new_value, changed_by, reason)
                VALUES (?, NULL, 'report_marks', ?, ?, ?, ?)
            ");
            $stmtChange->execute([
                $projectId,
                $oldReport !== null ? (string)$oldReport : 'NULL',
                $reportMarks !== null ? (string)$reportMarks : 'NULL',
                $userId,
                $reason
            ]);
        }

        // Student marks changes audit
        foreach ($sanitizedStudentMarks as $uid => $sm) {
            $oldSm = $existingStudentsMarks[$uid] ?? ['presentation_marks' => null, 'qa_marks' => null];

            if ($oldSm['presentation_marks'] !== $sm['presentation_marks']) {
                $stmtChange = $pdo->prepare("
                    INSERT INTO marks_changes (project_id, user_id, field_name, old_value, new_value, changed_by, reason)
                    VALUES (?, ?, 'presentation_marks', ?, ?, ?, ?)
                ");
                $stmtChange->execute([
                    $projectId,
                    $uid,
                    $oldSm['presentation_marks'] !== null ? (string)$oldSm['presentation_marks'] : 'NULL',
                    $sm['presentation_marks'] !== null ? (string)$sm['presentation_marks'] : 'NULL',
                    $userId,
                    $reason
                ]);
            }

            if ($oldSm['qa_marks'] !== $sm['qa_marks']) {
                $stmtChange = $pdo->prepare("
                    INSERT INTO marks_changes (project_id, user_id, field_name, old_value, new_value, changed_by, reason)
                    VALUES (?, ?, 'qa_marks', ?, ?, ?, ?)
                ");
                $stmtChange->execute([
                    $projectId,
                    $uid,
                    $oldSm['qa_marks'] !== null ? (string)$oldSm['qa_marks'] : 'NULL',
                    $sm['qa_marks'] !== null ? (string)$sm['qa_marks'] : 'NULL',
                    $userId,
                    $reason
                ]);
            }
        }
    }

    // 4. Upsert project_marks
    $now = date('Y-m-d H:i:s');
    $isFinalizedVal = ($isAlreadyFinalized || $finalize) ? 1 : 0;
    $finalizedByVal = $finalize ? $userId : ($existingProjMarks['finalized_by'] ?? null);
    $finalizedAtVal = $finalize ? $now : ($existingProjMarks['finalized_at'] ?? null);

    if ($existingProjMarks) {
        $stmtUpdateProj = $pdo->prepare("
            UPDATE project_marks
            SET report_marks = ?, is_finalized = ?, finalized_by = ?, finalized_at = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
            WHERE project_id = ?
        ");
        $stmtUpdateProj->execute([$reportMarks, $isFinalizedVal, $finalizedByVal, $finalizedAtVal, $userId, $projectId]);
    } else {
        $stmtInsertProj = $pdo->prepare("
            INSERT INTO project_marks (project_id, report_marks, is_finalized, finalized_by, finalized_at, updated_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtInsertProj->execute([$projectId, $reportMarks, $isFinalizedVal, $finalizedByVal, $finalizedAtVal, $userId]);
    }

    // 5. Upsert student_marks
    foreach ($sanitizedStudentMarks as $uid => $sm) {
        $stmtCheck = $pdo->prepare("SELECT 1 FROM student_marks WHERE project_id = ? AND user_id = ?");
        $stmtCheck->execute([$projectId, $uid]);
        $exists = (bool)$stmtCheck->fetchColumn();

        if ($exists) {
            $stmtUpdateStu = $pdo->prepare("
                UPDATE student_marks
                SET presentation_marks = ?, qa_marks = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                WHERE project_id = ? AND user_id = ?
            ");
            $stmtUpdateStu->execute([$sm['presentation_marks'], $sm['qa_marks'], $userId, $projectId, $uid]);
        } else {
            $stmtInsertStu = $pdo->prepare("
                INSERT INTO student_marks (project_id, user_id, presentation_marks, qa_marks, updated_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtInsertStu->execute([$projectId, $uid, $sm['presentation_marks'], $sm['qa_marks'], $userId]);
        }
    }

    return marks_get_full_evaluation_sheet($pdo, $projectId);
}

/**
 * Unlocks finalized marks (restricted strictly to classroom Admins).
 */
function marks_unlock(PDO $pdo, int $projectId, int $userId, string $reason): bool
{
    if ($projectId <= 0 || $userId <= 0) {
        return false;
    }

    if (!can_override_finalized_marks($pdo, $projectId, $userId)) {
        throw new RuntimeException("Only classroom Admins can unlock finalized marks.");
    }

    $reason = trim($reason);
    if ($reason === '') {
        throw new InvalidArgumentException("A non-empty reason is required to unlock marks.");
    }

    $stmtChange = $pdo->prepare("
        INSERT INTO marks_changes (project_id, user_id, field_name, old_value, new_value, changed_by, reason)
        VALUES (?, NULL, 'is_finalized', '1', '0', ?, ?)
    ");
    $stmtChange->execute([$projectId, $userId, $reason]);

    $stmtUpdate = $pdo->prepare("
        UPDATE project_marks
        SET is_finalized = 0, finalized_by = NULL, finalized_at = NULL, updated_by = ?, updated_at = CURRENT_TIMESTAMP
        WHERE project_id = ?
    ");
    return $stmtUpdate->execute([$userId, $projectId]);
}

/**
 * Retrieves the audit history of changes made to a project's marks.
 */
function marks_get_audit_history(PDO $pdo, int $projectId): array
{
    if ($projectId <= 0) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT mc.*, u_chg.username AS changed_by_name, u_stu.username AS student_name
        FROM marks_changes mc
        JOIN users u_chg ON mc.changed_by = u_chg.id
        LEFT JOIN users u_stu ON mc.user_id = u_stu.id
        WHERE mc.project_id = ?
        ORDER BY mc.changed_at DESC, mc.id DESC
    ");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
