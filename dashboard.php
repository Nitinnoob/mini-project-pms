<?php
$viewData = [];
$viewData['isCoordinator'] = false;
$viewData['isTeacherDrilldown'] = false;
$viewData['isLeaderView'] = false; // Phase 4: gates the weekly-log submission form
$viewData['classroomStartDate'] = null;
$viewData['classroomEndDate'] = null;
$viewData['weeklyLogs'] = [];
session_start();
// Prevent caching so the back button doesn't work after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));
$viewData['classroom_id'] = $_GET['classroom_id'] ?? null;


    require 'dbs.php';
    require_once 'phase_engine.php';

    if (!$viewData['classroom_id']) {
        header("Location: hub.php");
        exit;
    }


    // Fetch classroom details
    $stmtClass = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
    $stmtClass->execute([$viewData['classroom_id']]);
    $actualClassroom = $stmtClass->fetch(PDO::FETCH_ASSOC);
    if (!$actualClassroom) { header("Location: hub.php"); exit; }
    $viewData['isCoordinator'] = ($actualClassroom['created_by'] == $_SESSION['user_id']);
    $viewData['classroomName'] = $actualClassroom['name'] ?? 'Classroom';
    $viewData['inviteCode'] = $actualClassroom['invite_code'] ?? 'XXXXXX';
    $viewData['maxTeamSize'] = $actualClassroom['max_team_size'] ?? 10;
    $viewData['classroomStartDate'] = $actualClassroom['start_date'] ?? null;
    $viewData['classroomEndDate'] = $actualClassroom['end_date'] ?? null;
    // Fetch the user's role for THIS specific classroom
    $stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->execute([$viewData['classroom_id'], $_SESSION['user_id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        // User is not part of this classroom
        header("Location: hub.php");
        exit;
    }

    $contextualRole = $member['role'] ?? 'Team Member'; // Admin or Team Member

    if ($contextualRole === 'Admin') {
        if (!empty($_GET['project_id'])) {
            // Teacher Drilldown Mode: Pretend to be the Project Leader, but disable writes
            $viewData['actualView'] = 'Project Leader';
            $viewData['isTeacherDrilldown'] = true;
            $viewData['myProjectId'] = (int)$_GET['project_id'];
            
            // Fetch the project details
            $stmtProj = $pdo->prepare("SELECT id, name, description, mentor_id FROM projects WHERE id = ? AND classroom_id = ?");
            $stmtProj->execute([$viewData['myProjectId'], $viewData['classroom_id']]);
            $viewData['myProject'] = $stmtProj->fetch(PDO::FETCH_ASSOC);
            
            if (!$viewData['myProject']) { header("Location: dashboard.php?classroom_id=" . urlencode($viewData['classroom_id'])); exit; }
            
            // Fetch roster
            $stmtRoster = $pdo->prepare("SELECT u.id, u.username, pm.is_leader FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ? AND pm.join_status = 'Active'");
            $stmtRoster->execute([$viewData['myProjectId']]);
            $viewData['actualTeamRoster'] = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);
            
            $viewData['pendingRequests'] = []; // Teachers don't manage team invites
            $viewData['unassignedClassmates'] = [];
        } else {
            $viewData['actualView'] = 'Teacher';
        }
    } else {
        // Check if the user is part of an active project in this classroom
        $stmtProj = $pdo->prepare("
            SELECT p.id, p.name, pm.is_leader, pm.join_status 
            FROM projects p 
            JOIN project_members pm ON p.id = pm.project_id 
            WHERE p.classroom_id = ? AND pm.user_id = ?
        ");
        $stmtProj->execute([$viewData['classroom_id'], $_SESSION['user_id']]);
        $viewData['myProject'] = $stmtProj->fetch(PDO::FETCH_ASSOC);

        if ($viewData['myProject'] && $viewData['myProject']['join_status'] === 'Active') {
            $viewData['actualView'] = ($viewData['myProject']['is_leader'] == 1) ? 'Project Leader' : 'Student';
            $viewData['myProjectId'] = $viewData['myProject']['id'];
            
            if ($viewData['actualView'] === 'Project Leader') {
                $viewData['isLeaderView'] = true;
                $stmtPending = $pdo->prepare("
                    SELECT u.id, u.username 
                    FROM project_members pm
                    JOIN users u ON pm.user_id = u.id
                    WHERE pm.project_id = ? AND pm.join_status = 'Pending'
                ");
                $stmtPending->execute([$viewData['myProjectId']]);
                $viewData['pendingRequests'] = $stmtPending->fetchAll(PDO::FETCH_ASSOC);
                
                $stmtRoster = $pdo->prepare("
                    SELECT u.id, u.username, pm.is_leader
                    FROM project_members pm
                    JOIN users u ON pm.user_id = u.id
                    WHERE pm.project_id = ? AND pm.join_status = 'Active'
                ");
                $stmtRoster->execute([$viewData['myProjectId']]);
                $viewData['actualTeamRoster'] = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);
                
                // Fetch unassigned classmates to invite
                $stmtClassmates = $pdo->prepare("
                    SELECT u.id, u.username 
                    FROM classroom_members cm
                    JOIN users u ON cm.user_id = u.id
                    WHERE cm.classroom_id = ? AND cm.role != 'Admin'
                    AND u.id NOT IN (
                        SELECT user_id FROM project_members WHERE project_id = ?
                    )
                ");
                $stmtClassmates->execute([$viewData['classroom_id'], $viewData['myProjectId']]);
                $viewData['unassignedClassmates'] = $stmtClassmates->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $viewData['actualView'] = 'Marketplace';
            
            // Fetch all projects in this classroom for the marketplace
            $stmtAllProjs = $pdo->prepare("
                SELECT p.id, p.name, p.description,
                       (SELECT COUNT(*) FROM project_members WHERE project_id = p.id AND join_status = 'Active') as active_members,
                       (SELECT join_status FROM project_members WHERE project_id = p.id AND user_id = ?) as my_status
                FROM projects p
                WHERE p.classroom_id = ?
            ");
            $stmtAllProjs->execute([$_SESSION['user_id'], $viewData['classroom_id']]);
            $viewData['availableProjects'] = $stmtAllProjs->fetchAll(PDO::FETCH_ASSOC);
        }
    }
$modeSlug = ['Student' => 'student', 'Project Leader' => 'leader', 'Teacher' => 'teacher', 'Marketplace' => 'student'][$viewData['actualView']];
if (!empty($viewData['isTeacherDrilldown'])) {
    $modeSlug = 'teacher';
}

$headerTitle = "PMS";
if ($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader') {
    $headerTitle = htmlspecialchars($viewData['myProject']['name'] ?? 'My Project');
} elseif ($viewData['actualView'] === 'Marketplace') {
    $headerTitle = "Available Projects";
} elseif ($viewData['actualView'] === 'Teacher') {
    $headerTitle = "Classroom Overview";
}

// 1. Global project progress (shown in every layout, styled per mode)
$viewData['isNewlyCreated'] = ($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader') && count($viewData['actualTeamRoster'] ?? []) <= 1;
if (!isset($viewData['progressPercent'])) { $viewData['progressPercent'] = 0; }
if ($viewData['progressPercent'] === 0) {
    $viewData['progressStatus'] = 'ontrack';
    $viewData['progressWord'] = 'project initialized';
} elseif ($viewData['progressPercent'] < 50) {
    $viewData['progressStatus'] = 'behind';
    $viewData['progressWord'] = 'falling behind pace';
} elseif ($viewData['progressPercent'] >= 80) {
    $viewData['progressStatus'] = 'ahead';
    $viewData['progressWord'] = 'ahead of pace';
} else {
    $viewData['progressStatus'] = 'ontrack';
    $viewData['progressWord'] = 'on pace for Friday';
}

// 2. Calendar and Team Data
$calYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$calMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
if ($calMonth < 1) { $calMonth = 12; $calYear--; }
if ($calMonth > 12) { $calMonth = 1; $calYear++; }

$calDate = new DateTime(sprintf('%04d-%02d-01', $calYear, $calMonth));
$daysInMonth = (int) $calDate->format('t');
$startWeekday = (int) $calDate->format('N');
$todayNum = (int) date('j');
$isCurrentMonth = ($calYear === (int)date('Y') && $calMonth === (int)date('n'));

$viewData['monthLabel'] = $calDate->format('F Y');
$viewData['isCurrentMonth'] = $isCurrentMonth;
$viewData['todayNum'] = $todayNum;
$viewData['startWeekday'] = $startWeekday;
$viewData['daysInMonth'] = $daysInMonth;

// Generate prev/next month navigation URLs
$prevMonth = $calMonth - 1; $prevYear = $calYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $calMonth + 1; $nextYear = $calYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$baseCalUrl = "dashboard.php?classroom_id=" . urlencode($viewData['classroom_id']) . (!empty($viewData['myProjectId']) ? "&project_id=" . $viewData['myProjectId'] : "");
$viewData['prevMonthUrl'] = $baseCalUrl . "&month=$prevMonth&year=$prevYear&view=calendar";
$viewData['nextMonthUrl'] = $baseCalUrl . "&month=$nextMonth&year=$nextYear&view=calendar";
$viewData['activeBoardView'] = (isset($_GET['view']) && $_GET['view'] === 'calendar') ? 'calendar' : 'kanban';

$viewData['calendarTasks'] = [];
$viewData['teamRoster'] = [];
$viewData['tasks'] = ['todo' => [], 'inprogress' => [], 'done' => []];
$viewData['issueCount'] = 0;
$viewData['blockerCount'] = 0;
$viewData['onTrackCount'] = 0;
$viewData['avgVelocity'] = 0;
$viewData['daysToDeadline'] = '-';
$viewData['deadlineLabel'] = 'no deadline set';
$viewData['openIssues'] = [];
$viewData['canResolveIssues'] = false;
$viewData['activity_log'] = [];
$viewData['deliverables'] = [];

    // ---- Phase 4: build the auto-derived week/phase list (1 phase = 1 week) ----
    // Done here in the controller so views never query.
    phase_seed($pdo, $viewData['classroom_id'], $viewData['classroomStartDate'], $viewData['classroomEndDate']);
    $viewData['phases'] = phase_build_list(
        $viewData['classroomStartDate'],
        $viewData['classroomEndDate'],
        phase_load_overrides($pdo, $viewData['classroom_id'])
    );
    $viewData['totalWeeks'] = count($viewData['phases']);
    $currentPhase = null;
    foreach ($viewData['phases'] as $p) {
        if ($p['is_current']) { $currentPhase = $p; break; }
    }
    $viewData['currentPhase'] = $currentPhase;
    $viewData['hasSchedule'] = ($viewData['totalWeeks'] > 0);
    $viewData['tasksHaveWeekColumn'] = phase_tasks_column_exists($pdo);
    // Active log weeks exclude merged-away weeks (they fold into their target).
    $viewData['logWeeks'] = array_values(array_filter(
        $viewData['phases'],
        fn($p) => !$p['is_merged']
    ));


    // REAL DB LOGIC
    if (isset($viewData['myProjectId'])) {
        // Phase 4: select week_number when the migration has been applied,
        // otherwise fall back to the pre-migration column set.
        $weekSelect = $viewData['tasksHaveWeekColumn'] ? ', t.week_number' : '';
        $stmtTasks = $pdo->prepare("SELECT t.*, u.username as assignee_name $weekSelect FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = ? ORDER BY t.created_at DESC");
        $stmtTasks->execute([$viewData['myProjectId']]);
        $allTasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        $totalTasks = 0;
        $doneTasksCount = 0;
        $viewData['tasksByWeek'] = [];
        $viewData['weekStats'] = [];

        foreach ($viewData['phases'] as $p) {
            $viewData['weekStats'][$p['week_number']] = ['total' => 0, 'done' => 0, 'percent' => 0];
        }

        foreach ($allTasks as $t) {
            // Bind each task to a phase/week. Prefer an explicit week_number;
            // otherwise derive it from due_date so legacy rows still land in a
            // phase instead of falling out of every weekly rollup.
            if (!array_key_exists('week_number', $t)) {
                $t['week_number'] = null;
            }
            if (empty($t['week_number'])) {
                $derived = phase_for_due_date(
                    $viewData['classroomStartDate'],
                    $viewData['classroomEndDate'],
                    $t['due_date'] ?? null
                );
                $t['week_number'] = $derived;
            }
            $t['week_label'] = null;
            foreach ($viewData['phases'] as $p) {
                if ($p['week_number'] == $t['week_number']) {
                    $t['week_label'] = $p['label'];
                    break;
                }
            }
            // A task in a merged week also rolls up into the week it merged into.
            $rollupWeeks = [$t['week_number']];
            foreach ($viewData['phases'] as $p) {
                if ($p['is_merged'] && $p['week_number'] == $t['week_number']) {
                    $rollupWeeks[] = (int)$p['merged_into_week'];
                }
            }

            $viewData['tasks'][$t['status']][] = $t;
            $totalTasks++;
            if ($t['status'] === 'done') $doneTasksCount++;

            foreach ($rollupWeeks as $rw) {
                if ($rw === null) continue;
                if (!isset($viewData['tasksByWeek'][$rw])) $viewData['tasksByWeek'][$rw] = [];
                $viewData['tasksByWeek'][$rw][] = $t;

                if (isset($viewData['weekStats'][$rw])) {
                    $viewData['weekStats'][$rw]['total']++;
                    if ($t['status'] === 'done') $viewData['weekStats'][$rw]['done']++;
                }
            }

            if (!empty($t['due_date'])) {
                try {
                    $due = new DateTime($t['due_date']);
                    if ($due->format('Y-m') === $calDate->format('Y-m')) {
                        $viewData['calendarTasks'][(int)$due->format('j')][] = [
                            'title' => $t['title'],
                            'priority' => $t['priority']
                        ];
                    }
                } catch (Exception $e) {}
            }
        }

        foreach ($viewData['weekStats'] as $wk => $st) {
            $viewData['weekStats'][$wk]['percent'] = $st['total'] > 0
                ? (int)round(($st['done'] / $st['total']) * 100)
                : 0;
        }
        $viewData['scheduledTaskCount'] = count(array_filter($allTasks, fn($t) => !empty($t['week_number'])));

        if ($totalTasks > 0) {
            $viewData['progressPercent'] = (int)round(($doneTasksCount / $totalTasks) * 100);
            $viewData['avgVelocity'] = $viewData['progressPercent'];
        }

        // Health dots: red = has overdue tasks, amber = a task due within 48h,
        // green = on pace. Done tasks never count against a member.
        $todayDt = new DateTime('today');
        $soonDt  = (clone $todayDt)->modify('+2 days');

        if (!empty($viewData['actualTeamRoster'])) {
            foreach ($viewData['actualTeamRoster'] as $member) {
                $userTasks = 0;
                $userDone = 0;
                $overdue = 0;
                $dueSoon = 0;
                foreach ($allTasks as $t) {
                    if ($t['assigned_to'] == $member['id']) {
                        $userTasks++;
                        if ($t['status'] === 'done') {
                            $userDone++;
                        } elseif (!empty($t['due_date'])) {
                            try {
                                $dueD = new DateTime($t['due_date']);
                                if ($dueD < $todayDt) $overdue++;
                                elseif ($dueD <= $soonDt) $dueSoon++;
                            } catch (Exception $e) {}
                        }
                    }
                }
                $userPercent = ($userTasks > 0) ? (int)round(($userDone / $userTasks) * 100) : 0;

                if ($overdue > 0) {
                    $health = 'red';
                    $healthNote = $overdue . ' overdue task' . ($overdue > 1 ? 's' : '');
                } elseif ($dueSoon > 0) {
                    $health = 'amber';
                    $healthNote = $dueSoon . ' task' . ($dueSoon > 1 ? 's' : '') . ' due within 48 hours';
                } else {
                    $health = 'green';
                    $healthNote = 'On pace';
                }

                $viewData['teamRoster'][] = [
                    'name' => $member['username'],
                    'role' => $member['is_leader'] ? 'Leader' : 'Member',
                    'status' => $health,
                    'status_note' => $healthNote,
                    'task' => ($userTasks > 0) ? "$userDone / $userTasks tasks done" : 'No tasks',
                    'percent' => $userPercent
                ];
            }
        }
        $viewData['onTrackCount'] = count(array_filter($viewData['teamRoster'], fn($m) => $m['status'] === 'green'));

        // Days to deadline: the next phase deadline that is not yet past; once every
        // phase has ended, fall back to the classroom end date (and report overdue).
        $deadlineTarget = null;
        foreach ($viewData['phases'] as $p) {
            if (!$p['is_merged'] && !empty($p['date_to']) && new DateTime($p['date_to']) >= $todayDt) {
                $deadlineTarget = $p['date_to'];
                break;
            }
        }
        if ($deadlineTarget === null && !empty($viewData['classroomEndDate'])) {
            $deadlineTarget = $viewData['classroomEndDate'];
        }
        if ($deadlineTarget !== null) {
            $daysDiff = (int)$todayDt->diff(new DateTime($deadlineTarget))->format('%r%a');
            $viewData['daysToDeadline'] = abs($daysDiff);
            $viewData['deadlineLabel'] = $daysDiff < 0 ? 'days overdue' : ($daysDiff === 0 ? 'due today' : 'days to deadline');
        } else {
            $viewData['daysToDeadline'] = '-';
            $viewData['deadlineLabel'] = 'no deadline set';
        }

        // Fetch Weekly Logs
        $viewData['weeklyLogs'] = [];
        if (!empty($viewData['classroomStartDate']) && !empty($viewData['classroomEndDate'])) {
            $stmtLogs = $pdo->prepare("
                SELECT ws.*, wr.status as review_status, wr.mentor_remarks, wr.reviewed_at
                FROM weekly_submissions ws
                LEFT JOIN weekly_reviews wr ON ws.id = wr.submission_id
                WHERE ws.project_id = ?
            ");
            $stmtLogs->execute([$viewData['myProjectId']]);
            $logsRaw = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($logsRaw)) {
                $subIds = array_column($logsRaw, 'id');
                $ph = implode(',', array_fill(0, count($subIds), '?'));

                // Batch files — avoids one query per submission.
                $filesBySub = [];
                $stmtFiles = $pdo->prepare("SELECT submission_id, file_name, file_path FROM weekly_submission_files WHERE submission_id IN ($ph)");
                $stmtFiles->execute($subIds);
                foreach ($stmtFiles->fetchAll(PDO::FETCH_ASSOC) as $f) {
                    $filesBySub[$f['submission_id']][] = ['file_name' => $f['file_name'], 'file_path' => $f['file_path']];
                }

                // Batch attendance — avoids one query per submission.
                $attBySub = [];
                $stmtAtt = $pdo->prepare("
                    SELECT wa.submission_id, wa.user_id, wa.present, u.username
                    FROM weekly_attendance wa
                    JOIN users u ON wa.user_id = u.id
                    WHERE wa.submission_id IN ($ph)
                ");
                $stmtAtt->execute($subIds);
                foreach ($stmtAtt->fetchAll(PDO::FETCH_ASSOC) as $a) {
                    $attBySub[$a['submission_id']][] = ['user_id' => $a['user_id'], 'present' => $a['present'], 'username' => $a['username']];
                }
            }

            foreach ($logsRaw as $log) {
                $log['files'] = $filesBySub[$log['id']] ?? [];
                $log['attendance'] = $attBySub[$log['id']] ?? [];
                $viewData['weeklyLogs'][$log['week_number']] = $log;
            }
        }

        // Fetch Activity Log for Sidebar Feed
        $stmtAct = $pdo->prepare("
            SELECT a.action, a.details, a.created_at, COALESCE(u.username, 'Deleted user') AS username
            FROM activity_log a 
            LEFT JOIN users u ON a.user_id = u.id
            WHERE a.project_id = ? 
            ORDER BY a.created_at DESC LIMIT 20
        ");
        $stmtAct->execute([$viewData['myProjectId']]);
        $viewData['activity_log'] = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

        // Fetch Deliverables for Sidebar Drawer
        $stmtDel = $pdo->prepare("
            SELECT d.file_name, d.file_path, d.uploaded_at, COALESCE(u.username, 'Deleted user') AS uploader_name
            FROM deliverables d 
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.project_id = ? 
            ORDER BY d.uploaded_at DESC
        ");
        $stmtDel->execute([$viewData['myProjectId']]);
        $viewData['deliverables'] = $stmtDel->fetchAll(PDO::FETCH_ASSOC);

        // Open blockers (Escalation flare). Count drives the "pending issues" stat.
        try {
            $stmtIssues = $pdo->prepare("
                SELECT i.id, i.title, i.description, i.severity, i.week_number, i.created_at,
                       COALESCE(u.username, 'Deleted user') AS raised_by_name,
                       t.title AS task_title
                FROM issues i
                LEFT JOIN users u ON i.raised_by = u.id
                LEFT JOIN tasks t ON i.task_id = t.id
                WHERE i.project_id = ? AND i.status = 'open'
                ORDER BY FIELD(i.severity, 'critical', 'high', 'medium', 'low'), i.created_at DESC
            ");
            $stmtIssues->execute([$viewData['myProjectId']]);
            $viewData['openIssues'] = $stmtIssues->fetchAll(PDO::FETCH_ASSOC);
            foreach ($viewData['openIssues'] as &$iss) {
                $iss['phase_label'] = null;
                foreach ($viewData['phases'] as $p) {
                    if (!empty($iss['week_number']) && $p['week_number'] == $iss['week_number']) {
                        $iss['phase_label'] = $p['label'];
                        break;
                    }
                }
            }
            unset($iss);
        } catch (Throwable $e) {
            $viewData['openIssues'] = []; // issues table not on the Phase 5 schema yet
        }
        $viewData['blockerCount'] = count($viewData['openIssues']);

        // Who may mark blockers resolved: this project's leader, its mentor, or the coordinator.
        $viewData['canResolveIssues'] = ($viewData['actualView'] === 'Project Leader');
        if (!empty($viewData['isTeacherDrilldown'])) {
            $viewData['canResolveIssues'] = $viewData['isCoordinator']
                || ((int)($viewData['myProject']['mentor_id'] ?? 0) === (int)$_SESSION['user_id']);
        }
    }

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
            $weekTasks = array_filter($tasksRaw, function ($t) use ($wk) {
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


// 5. Contribution heatmap (10 weeks x 7 days) from real activity_log counts.
// Cell index = week * 7 + day, oldest first; the last cell is today.
$heatmapWeeks = 10;
$heatmapPattern = array_fill(0, $heatmapWeeks * 7, 0);
if (isset($viewData['myProjectId'])) {
    $heatDays = $heatmapWeeks * 7;
    $stmtHeat = $pdo->prepare("
        SELECT DATE(created_at) AS d, COUNT(*) AS c
        FROM activity_log
        WHERE project_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
    ");
    $stmtHeat->execute([$viewData['myProjectId'], $heatDays - 1]);
    $heatCounts = [];
    foreach ($stmtHeat->fetchAll(PDO::FETCH_ASSOC) as $hr) {
        $heatCounts[$hr['d']] = (int)$hr['c'];
    }
    $heatToday = new DateTime('today');
    for ($i = 0; $i < $heatDays; $i++) {
        $dayKey = (clone $heatToday)->modify('-' . ($heatDays - 1 - $i) . ' days')->format('Y-m-d');
        $heatmapPattern[$i] = $heatCounts[$dayKey] ?? 0;
    }
}

function heat_level($count)
{
    if ($count <= 0) return 0;
    if ($count == 1) return 1;
    if ($count == 2) return 2;
    if ($count == 3) return 3;
    return 4;
}

require 'views/header.php';

$errorMessages = [
    'min_team_size' => 'Final week log blocked: your team has fewer active members than this classroom\'s minimum team size. Invite classmates from the Team tab before submitting.',
    'log_approved'  => 'That weekly log has already been approved by your mentor and can no longer be edited.',
];
$errorKey = $_GET['error'] ?? null;
if ($errorKey && isset($errorMessages[$errorKey])):
    $msg = htmlspecialchars($errorMessages[$errorKey]);
?>
<div class="px-6 pt-6">
    <div class="card p-4 flex items-start gap-3" style="border-color: var(--danger);">
        <i class="fas fa-circle-exclamation mt-0.5 flex-none" style="color: var(--danger);"></i>
        <div>
            <h4 class="font-semibold text-sm font-head">Action blocked</h4>
            <p class="text-sm text-muted-ui mt-1"><?php echo $msg; ?></p>
        </div>
    </div>
</div>
<?php endif;

// Phase edits redirect back with a one-line outcome instead of a typed error key.
$phaseNotice = trim($_GET['msg'] ?? '') !== '' ? htmlspecialchars($_GET['msg']) : null;
$phaseError  = trim($_GET['err'] ?? '') !== '' ? htmlspecialchars($_GET['err']) : null;
if ($phaseNotice || $phaseError):
    $tone = $phaseError ? 'var(--danger)' : 'var(--accent)';
?>
<div class="px-6 pt-6">
    <div class="card p-4 flex items-start gap-3" style="border-color: <?php echo $tone; ?>;">
        <i class="fas <?php echo $phaseError ? 'fa-circle-exclamation' : 'fa-circle-check'; ?> mt-0.5 flex-none" style="color: <?php echo $tone; ?>;"></i>
        <div>
            <?php if ($phaseError): ?>
                <h4 class="font-semibold text-sm font-head">Could not update the schedule</h4>
            <?php endif; ?>
            <p class="text-sm text-muted-ui mt-1"><?php echo $phaseError ?? $phaseNotice; ?></p>
        </div>
    </div>
</div>
<?php endif;

echo '<div class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">';



if ($viewData['actualView'] === 'Student') {
    require 'views/student.php';
} elseif ($viewData['actualView'] === 'Marketplace') {
    require 'views/marketplace.php';
} elseif ($viewData['actualView'] === 'Project Leader') {
    require 'views/leader.php';
} else {
    require 'views/teacher.php';
}

if (($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader') && !$viewData['isNewlyCreated']) {
    require 'views/sidebar.php';
}

echo '</div>';

if (($viewData['actualView'] === 'Student' || $viewData['actualView'] === 'Project Leader')) {
    require 'views/add_task_modal.php';
    require 'views/upload_modal.php';
    require 'views/weekly_log_modal.php';
    require 'views/escalation_modal.php';
    require 'views/report_assistant_modal.php';
    if (!empty($viewData['isTeacherDrilldown'])) {
        require 'views/mentor_review_modal.php';
    }
}

require 'views/footer.php';
