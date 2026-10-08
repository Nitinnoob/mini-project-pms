<?php
// Project groups (Teacher layout) — group-level ledger

$viewData['projectGroups'] = [];

if ($viewData['actualView'] === 'Teacher' && isset($viewData['classroom_id'])) {
    if ($viewData['isCoordinator']) {
        $stmtTGroups = $pdo->prepare("
            SELECT p.id, p.name, p.description as `desc`, p.mentor_id, u.username as mentor_name 
            FROM projects p 
            LEFT JOIN users u ON p.mentor_id = u.id
            WHERE p.classroom_id = ?
        ");
        $stmtTGroups->execute([$viewData['classroom_id']]);
    } else {
        $stmtTGroups = $pdo->prepare("
            SELECT p.id, p.name, p.description as `desc`, p.mentor_id, u.username as mentor_name 
            FROM projects p 
            LEFT JOIN users u ON p.mentor_id = u.id
            WHERE p.classroom_id = ? AND p.mentor_id = ?
        ");
        $stmtTGroups->execute([$viewData['classroom_id'], $_SESSION['user_id']]);
    }
    
    $realProjects = $stmtTGroups->fetchAll(PDO::FETCH_ASSOC);

    $stmtMentors = $pdo->prepare("
        SELECT u.id, u.username
        FROM classroom_members cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.classroom_id = ? AND cm.role = 'Admin'
    ");
    $stmtMentors->execute([$viewData['classroom_id']]);
    $viewData['availableMentors'] = $stmtMentors->fetchAll(PDO::FETCH_ASSOC);

    // Batch fetch members, health metrics, and marks across all projects in one go
    require_once __DIR__ . '/../repositories/meeting_repository.php';
    require_once __DIR__ . '/../repositories/marks_repository.php';
    $membersByProject = [];
    $healthByProject = [];
    $marksByProject = [];

    if (!empty($realProjects)) {
        $projectIds = array_column($realProjects, 'id');
        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));

        $stmtAllMems = $pdo->prepare("
            SELECT pm.project_id, u.username 
            FROM project_members pm 
            JOIN users u ON pm.user_id = u.id 
            WHERE pm.project_id IN ($placeholders) AND pm.join_status = 'Active'
        ");
        $stmtAllMems->execute($projectIds);
        foreach ($stmtAllMems->fetchAll(PDO::FETCH_ASSOC) as $mRow) {
            $membersByProject[$mRow['project_id']][] = $mRow['username'];
        }

        // Phase 5 Teacher Ledger Rework: compute health from meetings, attendance, instructions
        $healthByProject = meeting_compute_projects_health($pdo, $projectIds);

        // Phase 6 Marks: batch fetch project-level marks & finalization
        $stmtMarks = $pdo->prepare("
            SELECT pm.project_id, pm.report_marks, pm.is_finalized, pm.finalized_at, u.username AS finalized_by_name
            FROM project_marks pm
            LEFT JOIN users u ON pm.finalized_by = u.id
            WHERE pm.project_id IN ($placeholders)
        ");
        $stmtMarks->execute($projectIds);
        foreach ($stmtMarks->fetchAll(PDO::FETCH_ASSOC) as $pmRow) {
            $marksByProject[$pmRow['project_id']] = [
                'report_marks'      => $pmRow['report_marks'] !== null ? (float)$pmRow['report_marks'] : null,
                'is_finalized'      => (bool)$pmRow['is_finalized'],
                'finalized_at'      => $pmRow['finalized_at'],
                'finalized_by_name' => $pmRow['finalized_by_name'],
            ];
        }
    }

    foreach ($realProjects as $rp) {
        $memNames = $membersByProject[$rp['id']] ?? [];

        $viewData['projectGroups'][] = [
            'id'           => $rp['id'],
            'name'         => $rp['name'],
            'members'      => count($memNames),
            'desc'         => $rp['desc'],
            'member_names' => $memNames,
            'has_schedule' => $viewData['hasSchedule'],
            'mentor_id'    => $rp['mentor_id'],
            'mentor_name'  => $rp['mentor_name'],
            'marks'        => $marksByProject[$rp['id']] ?? [
                'report_marks'      => null,
                'is_finalized'      => false,
                'finalized_at'      => null,
                'finalized_by_name' => null,
            ],
            'health'       => $healthByProject[$rp['id']] ?? [
                'status'               => 'neutral',
                'label'                => 'Pending',
                'badge_class'          => 'badge-muted',
                'dot_color'            => 'bg-muted-ui',
                'meetings_held'        => 0,
                'meetings_total'       => 0,
                'meetings_past_unheld' => 0,
                'attendance_pct'       => 100.0,
                'open_instructions'    => 0,
                'reasons'              => ['Pending'],
            ],
        ];
    }
    
    // Fetch all students in the classroom.
    $stmtClassRoster = $pdo->prepare("
        SELECT u.username, cm.usn, cp.project_name
        FROM classroom_members cm
        JOIN users u ON cm.user_id = u.id
        LEFT JOIN (
            SELECT pm.user_id, p.name AS project_name
            FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            WHERE pm.join_status = 'Active' AND p.classroom_id = ?
        ) cp ON cp.user_id = u.id
        WHERE cm.classroom_id = ? AND cm.role = 'Team Member'
        ORDER BY u.username ASC
    ");
    $stmtClassRoster->execute([$viewData['classroom_id'], $viewData['classroom_id']]);
    $viewData['classroomRoster'] = $stmtClassRoster->fetchAll(PDO::FETCH_ASSOC);
}
