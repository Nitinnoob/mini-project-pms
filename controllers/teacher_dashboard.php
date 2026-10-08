<?php
// 4. Project groups (Teacher layout) — group-level ledger

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

    // Batch fetch members and task milestone stats across all projects in one go (eliminating N+1 queries)
    $membersByProject = [];
    $tasksByProject = [];
    $issuesByProject = [];

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

        $weekCol = $viewData['tasksHaveWeekColumn'] ? ', week_number, due_date' : '';
        $stmtAllTasks = $pdo->prepare("
            SELECT project_id, status, milestone $weekCol
            FROM tasks
            WHERE project_id IN ($placeholders)
        ");
        $stmtAllTasks->execute($projectIds);
        foreach ($stmtAllTasks->fetchAll(PDO::FETCH_ASSOC) as $tRow) {
            $tasksByProject[$tRow['project_id']][] = $tRow;
        }

        // Open blockers per project (one query for the whole ledger).
        try {
            $stmtIss = $pdo->prepare("SELECT project_id, COUNT(*) AS c FROM issues WHERE project_id IN ($placeholders) AND status = 'open' GROUP BY project_id");
            $stmtIss->execute($projectIds);
            foreach ($stmtIss->fetchAll(PDO::FETCH_ASSOC) as $ir) {
                $issuesByProject[$ir['project_id']] = (int)$ir['c'];
            }
        } catch (Throwable $e) {
            // issues table not on the Phase 5 schema yet
        }
    }

    foreach ($realProjects as $rp) {
        $memNames = $membersByProject[$rp['id']] ?? [];
        $tasksRaw = $tasksByProject[$rp['id']] ?? [];

        // Phase 4: per-week completion derived from the classroom schedule
        // instead of the legacy fixed 4-value milestone enum. Weeks with no
        // tasks stay null so the ledger can distinguish "0%" from "not started".
        $weekStats = [];
        foreach ($viewData['phases'] as $p) {
            $wk = $p['week_number'];
            $weekTasks = array_filter($tasksRaw, function ($t) use ($wk, $viewData) {
                $tWeek = $t['week_number'] ?? null;
                if (empty($tWeek)) {
                    $tWeek = phase_for_due_date($viewData['classroomStartDate'], $viewData['classroomEndDate'], $t['due_date'] ?? null);
                }
                return $tWeek !== null && (int)$tWeek === (int)$wk;
            });

            $tot = count($weekTasks);
            $don = count(array_filter($weekTasks, fn($t) => $t['status'] === 'done'));
            $weekStats[] = [
                'week_number' => $wk,
                'label'       => $p['label'],
                'percent'     => $tot > 0 ? (int)round(($don / $tot) * 100) : null,
                'is_current'  => $p['is_current'],
            ];
        }

        $totOverall = count($tasksRaw);
        $donOverall = count(array_filter($tasksRaw, fn($t) => $t['status'] === 'done'));
        $pctOverall = $totOverall > 0 ? (int)round(($donOverall / $totOverall) * 100) : 0;

        $viewData['projectGroups'][] = [
            'id' => $rp['id'],
            'name' => $rp['name'],
            'members' => count($memNames),
            'percent' => $pctOverall,
            'desc' => $rp['desc'],
            'member_names' => $memNames,
            'phase_stats' => $weekStats,
            'has_schedule' => $viewData['hasSchedule'],
            'mentor_id' => $rp['mentor_id'],
            'mentor_name' => $rp['mentor_name'],
            'open_issues' => $issuesByProject[$rp['id']] ?? 0
        ];
    }
    
    // Fetch all students in the classroom. The project lookup is a derived table
    // limited to THIS classroom's projects so a student who also sits in projects
    // of other classrooms is never duplicated here.
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
