<?php
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
$classroom_id = $_GET['classroom_id'] ?? null;

// ------------------------------------------------------------------
// DEMO / PRESENTATION MODE
// Lets any logged-in user preview the Student, Project Leader, Teacher,
// or Marketplace layout with safe mock data — no real classroom
// membership or seeded project rows required. Triggered by ?demo_view=...
// (see hub.php's "Preview the UI" cards, and the Back link in
// teacher_group_view.php). Deliberately skips the DB entirely so a demo
// keeps working even on a fresh account or a flaky DB connection.
// ------------------------------------------------------------------
$allowedDemoViews = ['Student', 'Project Leader', 'Teacher', 'Marketplace'];
$demoView = $_GET['demo_view'] ?? null;
$isDemo = in_array($demoView, $allowedDemoViews, true);

if ($isDemo) {
    $classroom_id = $classroom_id ?: 'demo';
    $classroomName = 'Section B Demo';
    $inviteCode = 'RGIT-CS-B';
    $actualView = $demoView;

    if ($actualView === 'Student' || $actualView === 'Project Leader') {
        $myProject = [
            'id' => 9001,
            'name' => 'AI-Powered Analytics (Demo)',
            'is_leader' => $actualView === 'Project Leader' ? 1 : 0,
            'join_status' => 'Active',
        ];
        $myProjectId = $myProject['id'];
        $actualTeamRoster = [
            ['id' => 1, 'username' => $_SESSION['username'] ?? 'You', 'is_leader' => $actualView === 'Project Leader' ? 1 : 0],
            ['id' => 2, 'username' => 'Nithin Gowda', 'is_leader' => 0],
        ];
        $pendingRequests = $actualView === 'Project Leader'
            ? [['id' => 3, 'username' => 'Vinutha H K']]
            : [];
        $unassignedClassmates = [
            ['id' => 4, 'username' => 'Vinaya Kumar'],
            ['id' => 5, 'username' => 'Twayib'],
        ];
    } elseif ($actualView === 'Marketplace') {
        $availableProjects = [
            ['id' => 991, 'name' => 'Library Management System', 'description' => 'A digital solution for campus library book tracking, issuing, and automated fine calculation.', 'active_members' => 3, 'my_status' => 'None'],
            ['id' => 992, 'name' => 'Hostel Management System', 'description' => 'Platform for room allocation, mess fee tracking, and hostel complaint logging.', 'active_members' => 3, 'my_status' => 'None'],
        ];
    }
    // Teacher view needs no extra variables here — its ledger content is
    // already static demo data lower in the file.
} else {
    require 'dbs.php';

    if (!$classroom_id) {
        header("Location: hub.php");
        exit;
    }


    // Fetch classroom details
    $stmtClass = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
    $stmtClass->execute([$classroom_id]);
    $actualClassroom = $stmtClass->fetch(PDO::FETCH_ASSOC);
    $classroomName = $actualClassroom['name'] ?? 'Classroom';
    $inviteCode = $actualClassroom['invite_code'] ?? 'XXXXXX';
    // Fetch the user's role for THIS specific classroom
    $stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->execute([$classroom_id, $_SESSION['user_id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        // User is not part of this classroom
        header("Location: hub.php");
        exit;
    }

    $contextualRole = $member['role'] ?? 'Team Member'; // Admin or Team Member

    if ($contextualRole === 'Admin') {
        $actualView = 'Teacher';
    } else {
        // Check if the user is part of an active project in this classroom
        $stmtProj = $pdo->prepare("
            SELECT p.id, p.name, pm.is_leader, pm.join_status 
            FROM projects p 
            JOIN project_members pm ON p.id = pm.project_id 
            WHERE p.classroom_id = ? AND pm.user_id = ?
        ");
        $stmtProj->execute([$classroom_id, $_SESSION['user_id']]);
        $myProject = $stmtProj->fetch(PDO::FETCH_ASSOC);

        if ($myProject && $myProject['join_status'] === 'Active') {
            $actualView = ($myProject['is_leader'] == 1) ? 'Project Leader' : 'Student';
            $myProjectId = $myProject['id'];
            
            if ($actualView === 'Project Leader') {
                $stmtPending = $pdo->prepare("
                    SELECT u.id, u.username 
                    FROM project_members pm
                    JOIN users u ON pm.user_id = u.id
                    WHERE pm.project_id = ? AND pm.join_status = 'Pending'
                ");
                $stmtPending->execute([$myProjectId]);
                $pendingRequests = $stmtPending->fetchAll(PDO::FETCH_ASSOC);
                
                $stmtRoster = $pdo->prepare("
                    SELECT u.id, u.username, pm.is_leader
                    FROM project_members pm
                    JOIN users u ON pm.user_id = u.id
                    WHERE pm.project_id = ? AND pm.join_status = 'Active'
                ");
                $stmtRoster->execute([$myProjectId]);
                $actualTeamRoster = $stmtRoster->fetchAll(PDO::FETCH_ASSOC);
                
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
                $stmtClassmates->execute([$classroom_id, $myProjectId]);
                $unassignedClassmates = $stmtClassmates->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $actualView = 'Marketplace';
            
            // Fetch all projects in this classroom for the marketplace
            $stmtAllProjs = $pdo->prepare("
                SELECT p.id, p.name, p.description,
                       (SELECT COUNT(*) FROM project_members WHERE project_id = p.id AND join_status = 'Active') as active_members,
                       (SELECT join_status FROM project_members WHERE project_id = p.id AND user_id = ?) as my_status
                FROM projects p
                WHERE p.classroom_id = ?
            ");
            $stmtAllProjs->execute([$_SESSION['user_id'], $classroom_id]);
            $availableProjects = $stmtAllProjs->fetchAll(PDO::FETCH_ASSOC);
            
            // Artificial Simulation for Demo: Ensure there are at least 5 projects displayed
            $mockProjectsPool = [
                ['id' => 991, 'name' => 'Library Management system', 'description' => 'A digital solution for campus library book tracking, issuing, and automated fine calculation.', 'active_members' => 3, 'my_status' => 'None'],
                ['id' => 992, 'name' => 'Hostel Management System', 'description' => 'Platform for room allocation, mess fee tracking, and hostel complaint logging.', 'active_members' => 3, 'my_status' => 'None'],
                ['id' => 993, 'name' => 'Placement management system', 'description' => 'Web portal to track upcoming campus drives, student eligibility, and interview schedules.', 'active_members' => 3, 'my_status' => 'Pending'],
                ['id' => 994, 'name' => 'Vehicle parking management System', 'description' => 'Automated parking slot allocation and campus entry tracking using RFID.', 'active_members' => 4, 'my_status' => 'None'],
                ['id' => 995, 'name' => 'Hospital Management System', 'description' => 'Centralized patient record management, appointment booking, and inventory system.', 'active_members' => 4, 'my_status' => 'None']
            ];
            
            $currentCount = count($availableProjects);
            if ($currentCount < 5) {
                $needed = 5 - $currentCount;
                // Slice the required number of mock projects and merge them with the real ones
                $mockSlice = array_slice($mockProjectsPool, 0, $needed);
                $availableProjects = array_merge($availableProjects, $mockSlice);
            }
        }
    }
}
$modeSlug = ['Student' => 'student', 'Project Leader' => 'leader', 'Teacher' => 'teacher', 'Marketplace' => 'student'][$actualView];

$headerTitle = "PMS";
if ($actualView === 'Student' || $actualView === 'Project Leader') {
    $headerTitle = htmlspecialchars($myProject['name'] ?? 'My Project');
} elseif ($actualView === 'Marketplace') {
    $headerTitle = "Available Projects";
} elseif ($actualView === 'Teacher') {
    $headerTitle = "Classroom Overview";
}

// 1. Global project progress (shown in every layout, styled per mode)
$isNewlyCreated = ($actualView === 'Student' || $actualView === 'Project Leader') && count($actualTeamRoster ?? []) <= 1;
if ($isDemo) { $progressPercent = $isNewlyCreated ? 0 : 74; }
if (!isset($progressPercent)) { $progressPercent = 0; }
if ($progressPercent === 0) {
    $progressStatus = 'ontrack';
    $progressWord = 'project initialized';
} elseif ($progressPercent < 50) {
    $progressStatus = 'behind';
    $progressWord = 'falling behind pace';
} elseif ($progressPercent >= 80) {
    $progressStatus = 'ahead';
    $progressWord = 'ahead of pace';
} else {
    $progressStatus = 'ontrack';
    $progressWord = 'on pace for Friday';
}

// 2. Calendar and Team Data
$today = new DateTime();
$daysInMonth = (int) $today->format('t');
$firstOfMonth = new DateTime($today->format('Y-m-01'));
$startWeekday = (int) $firstOfMonth->format('N');
$todayNum = (int) $today->format('j');
$monthLabel = $today->format('F Y');

$calendarTasks = [];
$teamRoster = [];
$tasks = ['todo' => [], 'inprogress' => [], 'done' => []];
$issueCount = 0;
$blockerCount = 0;
$onTrackCount = 0;
$avgVelocity = 0;
$daysToDeadline = 3;

if ($isDemo) {
    // Demo Mock Data
    $calendarTasksRaw = [
        5  => ['title' => 'DB schema review',    'priority' => 'high'],
        9  => ['title' => 'Team sync',            'priority' => 'normal'],
        $todayNum + 1 => ['title' => 'Phase 1 report due', 'priority' => 'high'],
        $todayNum + 5 => ['title' => 'Sprint retro',         'priority' => 'normal'],
    ];
    foreach ($calendarTasksRaw as $day => $task) {
        $calendarTasks[min($day, $daysInMonth)][] = $task;
    }
    $calendarTasks[$todayNum][] = ['title' => 'Architecture review', 'priority' => 'high'];

    $teamRoster = [
        ['name' => 'Nithin gowda',   'role' => 'Backend',  'status' => 'green', 'task' => 'Schema Design', 'percent' => 96],
        ['name' => 'Ranjith kumar',  'role' => 'Frontend', 'status' => 'green', 'task' => 'Dashboard UI',  'percent' => 98],
        ['name' => 'Vinutha H K',    'role' => 'QA',       'status' => 'green', 'task' => 'Test scripts',  'percent' => 95],
        ['name' => 'Vinaya kumar',   'role' => 'DevOps',   'status' => 'green', 'task' => 'Deployment',    'percent' => 95],
    ];
    $onTrackCount = count(array_filter($teamRoster, fn($m) => $m['status'] !== 'red'));
    $avgVelocity = 96;
} else {
    // REAL DB LOGIC
    if (isset($myProjectId)) {
        $stmtTasks = $pdo->prepare("SELECT t.*, u.username as assignee_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = ? ORDER BY t.created_at DESC");
        $stmtTasks->execute([$myProjectId]);
        $allTasks = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);

        $totalTasks = 0;
        $doneTasksCount = 0;

        foreach ($allTasks as $t) {
            $tasks[$t['status']][] = $t;
            $totalTasks++;
            if ($t['status'] === 'done') $doneTasksCount++;
            
            if (!empty($t['due_date'])) {
                try {
                    $due = new DateTime($t['due_date']);
                    if ($due->format('m') === $today->format('m')) {
                        $calendarTasks[(int)$due->format('j')][] = [
                            'title' => $t['title'],
                            'priority' => $t['priority']
                        ];
                    }
                } catch (Exception $e) {}
            }
        }

        if ($totalTasks > 0) {
            $progressPercent = (int)round(($doneTasksCount / $totalTasks) * 100);
            $avgVelocity = $progressPercent;
        }

        if (!empty($actualTeamRoster)) {
            foreach ($actualTeamRoster as $member) {
                $userTasks = 0;
                $userDone = 0;
                foreach ($allTasks as $t) {
                    if ($t['assigned_to'] == $member['id']) {
                        $userTasks++;
                        if ($t['status'] === 'done') $userDone++;
                    }
                }
                $userPercent = ($userTasks > 0) ? (int)round(($userDone / $userTasks) * 100) : 0;
                
                $teamRoster[] = [
                    'name' => $member['username'],
                    'role' => $member['is_leader'] ? 'Leader' : 'Member',
                    'status' => 'green',
                    'task' => ($userTasks > 0) ? "$userDone / $userTasks tasks done" : 'No tasks',
                    'percent' => $userPercent
                ];
            }
        }
        $onTrackCount = count($teamRoster);
    }
}

// 4. Project groups (Teacher layout) — group-level ledger
$mockProjectGroupsRaw = [
    ['id' => 23, 'name' => 'Group 23 — Project management system', 'members' => 4, 'percent' => 96, 'desc' => 'A comprehensive platform for students to manage mini-projects, track milestones, and communicate with their guides asynchronously.', 'member_names' => ['Ranjith kumar', 'Nithin gowda', 'Vinutha H K', 'Vinaya kumar']],
    ['id' => 14, 'name' => 'Group 14 — Library Management system', 'members' => 3, 'percent' => 92, 'desc' => 'A digital solution for campus library book tracking, issuing, and automated fine calculation.', 'member_names' => ['Twayib', 'Siddharth', 'Suhass']],
    ['id' => 16, 'name' => 'Group 16 — Placement management system',  'members' => 3, 'percent' => 78, 'desc' => 'Web portal to track upcoming campus drives, student eligibility, and interview schedules.', 'member_names' => ['Varshini G', 'Shivani Kumari', 'Sushmita C M']],
    ['id' => 15, 'name' => 'Group 15 — Hostel Management System',        'members' => 3, 'percent' => 60, 'desc' => 'Platform for room allocation, mess fee tracking, and hostel complaint logging.', 'member_names' => ['Sameer', 'Saquib', 'Raiyaan']],
    ['id' => 17, 'name' => 'Group 17 — Vehicle parking management System', 'members' => 4, 'percent' => 45, 'desc' => 'Automated parking slot allocation and campus entry tracking using RFID.', 'member_names' => ['Shodhan R', 'Prajwal', 'Sudiksha D', 'Sneha Sanjeev Mayannavar']],
    ['id' => 18, 'name' => 'Group 18 — Hospital Management System',       'members' => 4, 'percent' => 30, 'desc' => 'Centralized patient record management, appointment booking, and inventory system.', 'member_names' => ['Vinay kumar G S', 'Pavan kalli', 'Prashanth K S', 'Shreyas']],
];
$projectGroups = []; // Starts empty for the live animation demo

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



if ($actualView === 'Student') {
    require 'views/student.php';
} elseif ($actualView === 'Marketplace') {
    require 'views/marketplace.php';
} elseif ($actualView === 'Project Leader') {
    require 'views/leader.php';
} else {
    require 'views/teacher.php';
}

if (($actualView === 'Student' || $actualView === 'Project Leader') && !$isNewlyCreated) {
    require 'views/sidebar.php';
}

echo '</div>';

require 'views/footer.php';
