<?php
$viewData = [];
$viewData['isCoordinator'] = false;
$viewData['isTeacherDrilldown'] = false;
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
$today = new DateTime();
$daysInMonth = (int) $today->format('t');
$firstOfMonth = new DateTime($today->format('Y-m-01'));
$startWeekday = (int) $firstOfMonth->format('N');
$todayNum = (int) $today->format('j');
$viewData['monthLabel'] = $today->format('F Y');

$viewData['calendarTasks'] = [];
$viewData['teamRoster'] = [];
$viewData['tasks'] = ['todo' => [], 'inprogress' => [], 'done' => []];
$viewData['issueCount'] = 0;
$viewData['blockerCount'] = 0;
$viewData['onTrackCount'] = 0;
$viewData['avgVelocity'] = 0;
$viewData['daysToDeadline'] = 3;


    // REAL DB LOGIC
    if (isset($viewData['myProjectId'])) {
        $stmtTasks = $pdo->prepare("SELECT t.*, u.username as assignee_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = ? ORDER BY t.created_at DESC");
        $stmtTasks->execute([$viewData['myProjectId']]);
        $allTasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        $totalTasks = 0;
        $doneTasksCount = 0;

        foreach ($allTasks as $t) {
            $viewData['tasks'][$t['status']][] = $t;
            $totalTasks++;
            if ($t['status'] === 'done') $doneTasksCount++;
            
            if (!empty($t['due_date'])) {
                try {
                    $due = new DateTime($t['due_date']);
                    if ($due->format('m') === $today->format('m')) {
                        $viewData['calendarTasks'][(int)$due->format('j')][] = [
                            'title' => $t['title'],
                            'priority' => $t['priority']
                        ];
                    }
                } catch (Exception $e) {}
            }
        }

        if ($totalTasks > 0) {
            $viewData['progressPercent'] = (int)round(($doneTasksCount / $totalTasks) * 100);
            $viewData['avgVelocity'] = $viewData['progressPercent'];
        }

        if (!empty($viewData['actualTeamRoster'])) {
            foreach ($viewData['actualTeamRoster'] as $member) {
                $userTasks = 0;
                $userDone = 0;
                foreach ($allTasks as $t) {
                    if ($t['assigned_to'] == $member['id']) {
                        $userTasks++;
                        if ($t['status'] === 'done') $userDone++;
                    }
                }
                $userPercent = ($userTasks > 0) ? (int)round(($userDone / $userTasks) * 100) : 0;
                
                $viewData['teamRoster'][] = [
                    'name' => $member['username'],
                    'role' => $member['is_leader'] ? 'Leader' : 'Member',
                    'status' => 'green',
                    'task' => ($userTasks > 0) ? "$userDone / $userTasks tasks done" : 'No tasks',
                    'percent' => $userPercent
                ];
            }
        }
        $viewData['onTrackCount'] = count($viewData['teamRoster']);

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
            foreach ($logsRaw as $log) {
                // Fetch files for this submission
                $stmtFiles = $pdo->prepare("SELECT file_name, file_path FROM weekly_submission_files WHERE submission_id = ?");
                $stmtFiles->execute([$log['id']]);
                $log['files'] = $stmtFiles->fetchAll(PDO::FETCH_ASSOC);
                
                // Fetch attendance
                $stmtAtt = $pdo->prepare("
                    SELECT wa.user_id, wa.present, u.username 
                    FROM weekly_attendance wa 
                    JOIN users u ON wa.user_id = u.id 
                    WHERE wa.submission_id = ?
                ");
                $stmtAtt->execute([$log['id']]);
                $log['attendance'] = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
                
                $viewData['weeklyLogs'][$log['week_number']] = $log;
            }
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

    foreach ($realProjects as $rp) {
        // Fetch members
        $stmtMems = $pdo->prepare("SELECT u.username FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ? AND pm.join_status = 'Active'");
        $stmtMems->execute([$rp['id']]);
        $memRows = $stmtMems->fetchAll(PDO::FETCH_ASSOC);
        $memNames = array_column($memRows, 'username');
        
        // Fetch task progress grouped by milestone
        $stmtTProgress = $pdo->prepare("SELECT status, milestone FROM tasks WHERE project_id = ?");
        $stmtTProgress->execute([$rp['id']]);
        $tasksRaw = $stmtTProgress->fetchAll(PDO::FETCH_ASSOC);
        
        $milestones = ['Synopsis', 'Phase 1', 'Phase 2', 'Final Demo'];
        $phaseStats = [];
        foreach ($milestones as $m) {
            $phaseTasks = array_filter($tasksRaw, fn($t) => $t['milestone'] === $m);
            $tot = count($phaseTasks);
            if ($tot > 0) {
                $don = count(array_filter($phaseTasks, fn($t) => $t['status'] === 'done'));
                $pct = (int)round(($don / $tot) * 100);
                $phaseStats[$m] = $pct;
            } else {
                $phaseStats[$m] = null; // No tasks for this phase yet
            }
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
            'phase_stats' => $phaseStats,
            'mentor_id' => $rp['mentor_id'],
            'mentor_name' => $rp['mentor_name']
        ];
    }
    
    // Fetch all students in the classroom
    $stmtClassRoster = $pdo->prepare("
        SELECT u.username, cm.usn, p.name as project_name 
        FROM classroom_members cm 
        JOIN users u ON cm.user_id = u.id 
        LEFT JOIN project_members pm ON u.id = pm.user_id AND pm.join_status = 'Active'
        LEFT JOIN projects p ON pm.project_id = p.id AND p.classroom_id = cm.classroom_id
        WHERE cm.classroom_id = ? AND cm.role = 'Team Member'
        ORDER BY u.username ASC
    ");
    $stmtClassRoster->execute([$viewData['classroom_id']]);
    $viewData['classroomRoster'] = $stmtClassRoster->fetchAll(PDO::FETCH_ASSOC);
}


// 5. Contribution heatmap (10 weeks x 7 days, fixed demo pattern)
$heatmapWeeks = 10;
$heatmapPattern = [0, 1, 3, 2, 4, 1, 0, 2, 3, 1, 0, 0, 2, 4, 3, 1, 0, 1, 2, 3, 4, 2, 0, 1, 3, 2, 1, 0, 0, 2, 3, 4, 1, 2, 0, 1, 3, 2, 4, 1, 0, 0, 2, 3, 1, 2, 4, 3, 1, 0, 2, 1, 0, 3, 2, 4, 1, 0, 2, 3, 1, 0, 4, 2, 3, 1, 0, 2, 1, 0];

function heat_level($count)
{
    if ($count <= 0) return 0;
    if ($count == 1) return 1;
    if ($count == 2) return 2;
    if ($count == 3) return 3;
    return 4;
}

require 'views/header.php';
require 'views/progress_bar.php';



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
    if (!empty($viewData['isTeacherDrilldown'])) {
        require 'views/mentor_review_modal.php';
    }
}

require 'views/footer.php';
