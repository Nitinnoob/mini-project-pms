<?php
// Teacher dashboard — project groups ledger with meeting health & marks.

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

    // Available mentors (Admins in this classroom)
    $stmtMentors = $pdo->prepare("
        SELECT u.id, u.username
        FROM classroom_members cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.classroom_id = ? AND cm.role = 'Admin'
    ");
    $stmtMentors->execute([$viewData['classroom_id']]);
    $viewData['availableMentors'] = $stmtMentors->fetchAll(PDO::FETCH_ASSOC);

    // Batch fetch data
    require_once __DIR__ . '/../repositories/meeting_repository.php';
    require_once __DIR__ . '/../repositories/marks_repository.php';

    $membersByProject = [];
    $healthByProject = [];
    $marksByProject = [];

    if (!empty($realProjects)) {
        $projectIds = array_column($realProjects, 'id');
        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));

        // Members
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

        // Health metrics (meetings count, attendance)
        $healthByProject = meeting_compute_projects_health($pdo, $projectIds);

        // Marks
        foreach ($projectIds as $pid) {
            $marksByProject[$pid] = [
                'report_marks'       => null,
                'presentation_marks' => null,
                'qa_marks'           => null,
                'total_marks'        => null,
            ];
        }

        $stmtMarks = $pdo->prepare("
            SELECT project_id, report_marks FROM project_marks WHERE project_id IN ($placeholders)
        ");
        $stmtMarks->execute($projectIds);
        foreach ($stmtMarks->fetchAll(PDO::FETCH_ASSOC) as $pmRow) {
            $pid = (int)$pmRow['project_id'];
            if (isset($marksByProject[$pid])) {
                $marksByProject[$pid]['report_marks'] = $pmRow['report_marks'] !== null ? (float)$pmRow['report_marks'] : null;
            }
        }

        $stmtStudentMarks = $pdo->prepare("
            SELECT project_id,
                   AVG(presentation_marks) as avg_presentation,
                   AVG(qa_marks) as avg_qa
            FROM student_marks
            WHERE project_id IN ($placeholders)
            GROUP BY project_id
        ");
        $stmtStudentMarks->execute($projectIds);
        foreach ($stmtStudentMarks->fetchAll(PDO::FETCH_ASSOC) as $smRow) {
            $pid = (int)$smRow['project_id'];
            if (isset($marksByProject[$pid])) {
                $marksByProject[$pid]['presentation_marks'] = $smRow['avg_presentation'] !== null ? round((float)$smRow['avg_presentation'], 1) : null;
                $marksByProject[$pid]['qa_marks'] = $smRow['avg_qa'] !== null ? round((float)$smRow['avg_qa'], 1) : null;

                $r = $marksByProject[$pid]['report_marks'];
                $p = $marksByProject[$pid]['presentation_marks'];
                $q = $marksByProject[$pid]['qa_marks'];
                if ($r !== null && $p !== null && $q !== null) {
                    $marksByProject[$pid]['total_marks'] = round($r + $p + $q, 1);
                }
            }
        }
    }

    // Build final project groups array
    foreach ($realProjects as $proj) {
        $pid = (int)$proj['id'];
        $health = $healthByProject[$pid] ?? ['meetings_held' => 0, 'avg_attendance_pct' => 0, 'has_shortage' => false];

        $viewData['projectGroups'][] = [
            'id'              => $pid,
            'name'            => $proj['name'],
            'desc'            => $proj['desc'],
            'mentor_id'       => $proj['mentor_id'],
            'mentor_name'     => $proj['mentor_name'],
            'member_names'    => $membersByProject[$pid] ?? [],
            'health'          => $health,
            'marks'           => $marksByProject[$pid] ?? [],
        ];
    }

    // Classroom roster
    $stmtRoster = $pdo->prepare("
        SELECT u.id, u.username, cm.role, cm.usn
        FROM classroom_members cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.classroom_id = ?
        ORDER BY cm.role DESC, u.username ASC
    ");
    $stmtRoster->execute([$viewData['classroom_id']]);
    $viewData['classroomRoster'] = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);
}
