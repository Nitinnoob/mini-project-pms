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
    $actualView = $demoView;

    if ($actualView === 'Student' || $actualView === 'Project Leader') {
        $myProject = [
            'ID' => 9001,
            'NAME' => 'AI-Powered Analytics (Demo)',
            'IS_LEADER' => $actualView === 'Project Leader' ? 1 : 0,
            'JOIN_STATUS' => 'Active',
        ];
        $myProjectId = $myProject['ID'];
        $actualTeamRoster = [
            ['ID' => 1, 'USERNAME' => $_SESSION['username'] ?? 'You', 'IS_LEADER' => $actualView === 'Project Leader' ? 1 : 0],
            ['ID' => 2, 'USERNAME' => 'Nithin Gowda', 'IS_LEADER' => 0],
        ];
        $pendingRequests = $actualView === 'Project Leader'
            ? [['ID' => 3, 'USERNAME' => 'Vinutha H K']]
            : [];
        $unassignedClassmates = [
            ['ID' => 4, 'USERNAME' => 'Vinaya Kumar'],
            ['ID' => 5, 'USERNAME' => 'Twayib'],
        ];
    } elseif ($actualView === 'Marketplace') {
        $availableProjects = [
            ['ID' => 991, 'NAME' => 'Library Management System', 'DESCRIPTION' => 'A digital solution for campus library book tracking, issuing, and automated fine calculation.', 'ACTIVE_MEMBERS' => 3, 'MY_STATUS' => 'None'],
            ['ID' => 992, 'NAME' => 'Hostel Management System', 'DESCRIPTION' => 'Platform for room allocation, mess fee tracking, and hostel complaint logging.', 'ACTIVE_MEMBERS' => 3, 'MY_STATUS' => 'None'],
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

    // Fetch the user's role for THIS specific classroom
    $stmt = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
    $stmt->execute([$classroom_id, $_SESSION['user_id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        // User is not part of this classroom
        header("Location: hub.php");
        exit;
    }

    $contextualRole = $member['ROLE'] ?? 'Team Member'; // Admin or Team Member

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

        if ($myProject && $myProject['JOIN_STATUS'] === 'Active') {
            $actualView = ($myProject['IS_LEADER'] == 1) ? 'Project Leader' : 'Student';
            $myProjectId = $myProject['ID'];
            
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
                ['ID' => 991, 'NAME' => 'Library Management system', 'DESCRIPTION' => 'A digital solution for campus library book tracking, issuing, and automated fine calculation.', 'ACTIVE_MEMBERS' => 3, 'MY_STATUS' => 'None'],
                ['ID' => 992, 'NAME' => 'Hostel Management System', 'DESCRIPTION' => 'Platform for room allocation, mess fee tracking, and hostel complaint logging.', 'ACTIVE_MEMBERS' => 3, 'MY_STATUS' => 'None'],
                ['ID' => 993, 'NAME' => 'Placement management system', 'DESCRIPTION' => 'Web portal to track upcoming campus drives, student eligibility, and interview schedules.', 'ACTIVE_MEMBERS' => 3, 'MY_STATUS' => 'Pending'],
                ['ID' => 994, 'NAME' => 'Vehicle parking management System', 'DESCRIPTION' => 'Automated parking slot allocation and campus entry tracking using RFID.', 'ACTIVE_MEMBERS' => 4, 'MY_STATUS' => 'None'],
                ['ID' => 995, 'NAME' => 'Hospital Management System', 'DESCRIPTION' => 'Centralized patient record management, appointment booking, and inventory system.', 'ACTIVE_MEMBERS' => 4, 'MY_STATUS' => 'None']
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
    $headerTitle = htmlspecialchars($myProject['NAME'] ?? 'My Project');
} elseif ($actualView === 'Marketplace') {
    $headerTitle = "Available Projects";
} elseif ($actualView === 'Teacher') {
    $headerTitle = "Classroom Overview";
}

// 1. Global project progress (shown in every layout, styled per mode)
$isNewlyCreated = ($actualView === 'Student' || $actualView === 'Project Leader') && count($actualTeamRoster ?? []) <= 1;
$progressPercent = $isNewlyCreated ? 0 : 74;
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

// 2. Calendar data (Student layout) — current month, demo tasks pinned to days
$today = new DateTime();
$daysInMonth = (int) $today->format('t');
$firstOfMonth = new DateTime($today->format('Y-m-01'));
$startWeekday = (int) $firstOfMonth->format('N'); // 1 = Monday ... 7 = Sunday
$todayNum = (int) $today->format('j');
$monthLabel = $today->format('F Y');

$calendarTasksRaw = [
    5  => ['title' => 'DB schema review',    'priority' => 'high'],
    9  => ['title' => 'Team sync',            'priority' => 'normal'],
    $todayNum + 1 => ['title' => 'Phase 1 report due', 'priority' => 'high'],
    $todayNum + 5 => ['title' => 'Sprint retro',         'priority' => 'normal'],
];
$calendarTasks = [];
foreach ($calendarTasksRaw as $day => $task) {
    $calendarTasks[min($day, $daysInMonth)][] = $task;
}
$calendarTasks[$todayNum][] = ['title' => 'Architecture review', 'priority' => 'high'];

// 3. Team roster (Project Leader layout) — member-level status
$teamRoster = [
    ['name' => 'Nithin gowda',   'role' => 'Backend',  'status' => 'green', 'task' => 'Schema Design', 'percent' => 96],
    ['name' => 'Ranjith kumar',  'role' => 'Frontend', 'status' => 'green', 'task' => 'Dashboard UI',  'percent' => 98],
    ['name' => 'Vinutha H K',    'role' => 'QA',       'status' => 'green', 'task' => 'Test scripts',  'percent' => 95],
    ['name' => 'Vinaya kumar',   'role' => 'DevOps',   'status' => 'green', 'task' => 'Deployment',    'percent' => 95],
];

$blockerCount = count(array_filter($teamRoster, fn($m) => $m['status'] === 'red'));
$onTrackCount = count(array_filter($teamRoster, fn($m) => $m['status'] !== 'red'));
$avgVelocity = (int) round(array_sum(array_column($teamRoster, 'percent')) / count($teamRoster));
$daysToDeadline = 3;

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
?>
<!DOCTYPE html>
<html lang="en" data-mode="<?php echo $modeSlug; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - <?php echo htmlspecialchars($actualView); ?> view</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="syncspace.css">
</head>

<body class="min-h-screen flex flex-col overflow-x-hidden">

    <!-- Success Toast Notification -->
    <div id="toast" class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 card border-l-4 px-6 py-3 shadow-2xl flex items-center gap-3" style="border-left-color: var(--accent);">
        <i class="fas fa-check-circle text-accent text-xl"></i>
        <div>
            <h4 class="font-bold text-sm font-head">Task completed</h4>
            <p class="text-xs text-muted-ui">Weekly progress updated dynamically.</p>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="border-b border-ui bg-raised py-3 px-6 flex justify-between items-center sticky top-0 z-40">
        <div class="flex items-center gap-4">
            <a href="hub.php" class="flex items-center gap-4 hover:opacity-80 transition" title="Back to Hub">
                <div class="p-2 rounded" style="background: var(--accent); border-radius: var(--radius);">
                    <i class="fas fa-layer-group" style="color: var(--bg);"></i>
                </div>
                <h1 class="font-bold text-xl tracking-tight font-head">PMS</h1>
            </a>
        </div>

        <div class="flex items-center gap-5 text-sm">
            <button class="text-muted-ui hover:text-accent transition" aria-label="Notifications"><i class="fas fa-bell"></i></button>
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo $username; ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent-2); color: var(--bg);"><?php echo $initial; ?></div>
            </div>
            <a href="logout.php" class="text-danger hover:opacity-80 transition ml-1" title="Log out"><i class="fas fa-sign-out-alt text-lg"></i></a>
        </div>
    </nav>

    <?php if ($actualView !== 'Marketplace'): ?>
    <!-- Global project progress -->
    <!--<div class="px-6 pt-5">
        <div class="flex justify-between items-baseline mb-2">
            <h2 class="text-lg font-head font-semibold"><?php echo $headerTitle; ?></h2>
            <span class="text-sm font-mono-ui">
                <span class="font-bold"><?php echo $progressPercent; ?>%</span>
                <span class="text-muted-ui"> — <?php echo $progressWord; ?></span>
            </span>
        </div>
        <div class="progress-track w-full h-2.5">
            <div class="progress-fill h-full status-<?php echo $progressStatus; ?>" style="width: <?php echo $progressPercent; ?>%;"></div>
        </div>
    </div>-->
    <?php endif; ?>

    <!-- Main workspace -->
    <div class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">

        <?php if ($actualView === 'Student'): ?>
        <!-- ============================================================ -->
        <!-- STUDENT — Workbench: kanban / calendar                        -->
        <!-- ============================================================ -->
        <div class="<?php echo $isNewlyCreated ? 'lg:col-span-4' : 'lg:col-span-3'; ?> flex flex-col h-full">
            <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                <div class="flex items-center gap-1">
                    <button id="toggleKanbanBtn" class="tab-btn active px-3 py-2">board.kanban</button>
                    <button id="toggleCalendarBtn" class="tab-btn px-3 py-2">calendar.month</button>
                </div>
                <div class="flex gap-2">
                    <button class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent-2); color: var(--bg);">
                        <i class="fas fa-magic me-2"></i>Generate Friday wrap-up
                    </button>
                    <button class="btn-ui text-sm font-semibold px-4 py-2 text-danger" style="background: transparent;">
                        <i class="fas fa-exclamation-triangle me-2"></i>Escalation flare
                    </button>
                </div>
            </div>

            <div id="kanban-view" class="view-pane grid grid-cols-3 gap-4 flex-1">
                <div class="card p-4 flex flex-col">
                    <h3 class="font-semibold mb-3 flex justify-between font-head">To do <span class="bg-raised border border-ui px-2 text-xs py-0.5" style="border-radius: var(--radius);">2</span></h3>
                    <div id="todo-list" class="flex-1 space-y-3 min-h-[200px]">
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(69,208,195,.15); color: var(--accent-2); border-radius: var(--radius);">database</span>
                                <i class="fas fa-grip-vertical text-muted-ui"></i>
                            </div>
                            <p class="text-sm font-medium">Resolve explicit cursor syntax in Oracle schema</p>
                        </div>
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">os-lab</span>
                                <i class="fas fa-grip-vertical text-muted-ui"></i>
                            </div>
                            <p class="text-sm font-medium">Compile CPU scheduling algorithms</p>
                            <div class="mt-2 text-xs text-danger font-semibold"><i class="far fa-clock me-1"></i>Due tomorrow</div>
                        </div>
                    </div>
                </div>

                <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent-2);">
                    <h3 class="font-semibold mb-3 flex justify-between font-head">In progress <span class="px-2 text-xs py-0.5" style="background: rgba(69,208,195,.15); color: var(--accent-2); border-radius: var(--radius);">1</span></h3>
                    <div id="inprogress-list" class="flex-1 space-y-3 min-h-[200px]">
                        <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius); border-left: 2px solid var(--accent-2);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">docs</span>
                                <i class="fas fa-grip-vertical text-muted-ui"></i>
                            </div>
                            <p class="text-sm font-medium">Draft Phase 1 system architecture report</p>
                            <div class="flex mt-3 -space-x-2">
                                <img class="w-6 h-6 rounded-full border" style="border-color: var(--panel);" src="https://ui-avatars.com/api/?name=Nithin+G&background=4f46e5&color=fff">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent);">
                    <h3 class="font-semibold mb-3 flex justify-between font-head">Done <span class="px-2 text-xs py-0.5" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">1</span></h3>
                    <div id="done-list" class="flex-1 space-y-3 min-h-[200px]">
                        <div class="bg-raised border border-ui p-3 cursor-grab opacity-60" style="border-radius: var(--radius);">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-xs font-semibold px-2 py-1 font-mono-ui text-muted-ui" style="background: rgba(127,127,127,.15); border-radius: var(--radius);">frontend</span>
                            </div>
                            <p class="text-sm font-medium line-through text-muted-ui">Design multi-tenant dashboard UI</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="calendar-view" class="view-pane card p-4 flex-1 hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold font-head"><?php echo $monthLabel; ?></h3>
                    <div class="flex items-center gap-3 text-xs text-muted-ui font-mono-ui">
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--danger);"></span>high</span>
                        <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--accent-2);"></span>normal</span>
                    </div>
                </div>
                <div class="grid grid-cols-7 gap-2 text-center text-xs text-muted-ui font-semibold mb-2 font-mono-ui">
                    <div>mon</div><div>tue</div><div>wed</div><div>thu</div><div>fri</div><div>sat</div><div>sun</div>
                </div>
                <div class="grid grid-cols-7 gap-2">
                    <?php
                    for ($b = 1; $b < $startWeekday; $b++) echo '<div class="cal-cell"></div>';
                    for ($d = 1; $d <= $daysInMonth; $d++) {
                        $isToday = ($d === $todayNum);
                        echo '<div class="cal-cell bg-raised border border-ui p-1.5 flex flex-col ' . ($isToday ? 'is-today' : '') . '" style="border-radius: var(--radius);">';
                        echo '<span class="text-xs font-semibold font-mono-ui ' . ($isToday ? 'text-accent-2' : 'text-muted-ui') . '">' . $d . '</span><div class="mt-1 space-y-1">';
                        if (isset($calendarTasks[$d])) {
                            foreach ($calendarTasks[$d] as $t) {
                                $bg = $t['priority'] === 'high' ? 'rgba(239,100,97,.18)' : 'rgba(69,208,195,.18)';
                                $fg = $t['priority'] === 'high' ? 'var(--danger)' : 'var(--accent-2)';
                                echo '<div class="cal-pill" style="background:' . $bg . '; color:' . $fg . ';" title="' . htmlspecialchars($t['title']) . '">' . htmlspecialchars($t['title']) . '</div>';
                            }
                        }
                        echo '</div></div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php elseif ($actualView === 'Marketplace'): ?>
        <!-- ============================================================ -->
        <!-- MARKETPLACE — Unassigned Student View                         -->
        <!-- ============================================================ -->
        <div class="lg:col-span-4 flex flex-col h-full">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-2xl font-head font-semibold">Project Marketplace</h2>
                    <p class="text-muted-ui text-sm mt-1">Browse available projects to join, or start your own team.</p>
                </div>
                <a href="create_subproject.php?classroom_id=<?php echo urlencode($classroom_id); ?>" class="btn-ui px-4 py-2 text-sm font-semibold hover:opacity-90 transition" style="background: var(--accent-2); color: var(--bg);">
                    <i class="fas fa-plus mr-1"></i> Start a Project
                </a>
            </div>

            <?php if (empty($availableProjects)): ?>
            <div class="card p-10 text-center flex-1 flex flex-col items-center justify-center border-dashed">
                <i class="fas fa-folder-open text-4xl mb-4 text-muted-ui opacity-50"></i>
                <h3 class="text-lg font-bold mb-2">No projects yet</h3>
                <p class="text-muted-ui text-sm mb-6 max-w-md mx-auto">It looks like no one has started a project in this classroom yet. Be the first to start a team!</p>
                <a href="create_subproject.php?classroom_id=<?php echo urlencode($classroom_id); ?>" class="btn-ui px-6 py-2 text-sm font-semibold" style="background: var(--accent-2); color: var(--bg);">
                    Start a Project
                </a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($availableProjects as $proj): 
                    $isPending = ($proj['MY_STATUS'] === 'Pending');
                ?>
                <div class="card p-6 flex flex-col hover:border-[color:var(--accent-2)] transition-colors">
                    <h4 class="text-xl font-bold mb-2 truncate"><?php echo htmlspecialchars($proj['NAME']); ?></h4>
                    <p class="text-sm text-muted-ui mb-4 flex-1 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                        <?php echo htmlspecialchars($proj['DESCRIPTION'] ?: 'No description provided.'); ?>
                    </p>
                    <div class="flex justify-between items-center pt-4 border-t border-ui">
                        <div class="text-xs font-semibold text-muted-ui flex items-center gap-1">
                            <i class="fas fa-users"></i> <?php echo (int)$proj['ACTIVE_MEMBERS']; ?> members
                        </div>
                        <?php if ($isPending): ?>
                        <button class="px-4 py-1.5 text-xs font-semibold rounded bg-black/10 cursor-not-allowed opacity-70" disabled>
                            <i class="fas fa-clock mr-1"></i> Pending
                        </button>
                        <?php else: ?>
                        <form method="POST" action="request_join.php">
                            <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($classroom_id); ?>">
                            <input type="hidden" name="project_id" value="<?php echo $proj['ID']; ?>">
                            <button type="submit" class="btn-ui px-4 py-1.5 text-xs font-semibold hover:bg-black/10 transition" style="color: var(--accent); border-color: var(--accent);">
                                Request to Join
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($actualView === 'Project Leader'): ?>
        <!-- ============================================================ -->
        <!-- PROJECT LEADER — Control room: stats, ticker, roster          -->
        <!-- ============================================================ -->
        <div class="<?php echo $isNewlyCreated ? 'lg:col-span-4' : 'lg:col-span-3'; ?> flex flex-col h-full gap-4">


            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="card stat-tile tone-red p-4">
                    <div class="stat-num text-3xl"><?php echo $blockerCount; ?></div>
                    <div class="text-xs text-muted-ui mt-1">pending issues</div>
                </div>
                <div class="card stat-tile tone-green p-4">
                    <div class="stat-num text-3xl"><?php echo count($actualTeamRoster); ?>/<?php echo count($actualTeamRoster) + count($pendingRequests); ?></div>
                    <div class="text-xs text-muted-ui mt-1">members active</div>
                </div>
                <div class="card stat-tile tone-amber p-4">
                    <div class="stat-num text-3xl"><?php echo $avgVelocity; ?>%</div>
                    <div class="text-xs text-muted-ui mt-1">overall completion</div>
                </div>
                <div class="card stat-tile p-4">
                    <div class="stat-num text-3xl"><?php echo $daysToDeadline; ?></div>
                    <div class="text-xs text-muted-ui mt-1">days to deadline</div>
                </div>
            </div>

            <!-- Leader Tabs -->
            <div class="flex gap-6 border-b border-ui">
                <button id="btn-tab-board" class="tab-btn active pb-2" onclick="switchLeaderTab('board')">My Board</button>
                <button id="btn-tab-team" class="tab-btn pb-2" onclick="switchLeaderTab('team')">Team</button>
            </div>

            <!-- TAB 1: My Board (kanban / calendar) -->
            <div id="leader-tab-board" class="view-pane flex-1 flex flex-col">
                <div class="flex justify-between items-end mb-4 flex-wrap gap-3">
                    <div class="flex items-center gap-1">
                        <button id="toggleKanbanBtn" class="tab-btn active px-3 py-2">board.kanban</button>
                        <button id="toggleCalendarBtn" class="tab-btn px-3 py-2">calendar.month</button>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-ui text-sm font-semibold px-4 py-2" style="background: var(--accent-2); color: var(--bg);">
                            <i class="fas fa-magic me-2"></i>Generate Friday wrap-up
                        </button>
                        <button class="btn-ui text-sm font-semibold px-4 py-2 text-danger" style="background: transparent;">
                            <i class="fas fa-exclamation-triangle me-2"></i>Escalation flare
                        </button>
                    </div>
                </div>

                <div id="kanban-view" class="view-pane grid grid-cols-3 gap-4 flex-1">
                    <div class="card p-4 flex flex-col">
                        <h3 class="font-semibold mb-3 flex justify-between font-head">To do <span class="bg-raised border border-ui px-2 text-xs py-0.5" style="border-radius: var(--radius);">2</span></h3>
                        <div id="todo-list" class="flex-1 space-y-3 min-h-[200px]">
                            <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(69,208,195,.15); color: var(--accent-2); border-radius: var(--radius);">database</span>
                                    <i class="fas fa-grip-vertical text-muted-ui"></i>
                                </div>
                                <p class="text-sm font-medium">Resolve explicit cursor syntax in Oracle schema</p>
                            </div>
                            <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius);">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">os-lab</span>
                                    <i class="fas fa-grip-vertical text-muted-ui"></i>
                                </div>
                                <p class="text-sm font-medium">Compile CPU scheduling algorithms</p>
                                <div class="mt-2 text-xs text-danger font-semibold"><i class="far fa-clock me-1"></i>Due tomorrow</div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent-2);">
                        <h3 class="font-semibold mb-3 flex justify-between font-head">In progress <span class="px-2 text-xs py-0.5" style="background: rgba(69,208,195,.15); color: var(--accent-2); border-radius: var(--radius);">1</span></h3>
                        <div id="inprogress-list" class="flex-1 space-y-3 min-h-[200px]">
                            <div class="bg-raised border border-ui p-3 cursor-grab hover:opacity-90 transition shadow-sm" style="border-radius: var(--radius); border-left: 2px solid var(--accent-2);">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-semibold px-2 py-1 font-mono-ui" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">docs</span>
                                    <i class="fas fa-grip-vertical text-muted-ui"></i>
                                </div>
                                <p class="text-sm font-medium">Draft Phase 1 system architecture report</p>
                                <div class="flex mt-3 -space-x-2">
                                    <img class="w-6 h-6 rounded-full border" style="border-color: var(--panel);" src="https://ui-avatars.com/api/?name=Nithin+G&background=4f46e5&color=fff">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-4 flex flex-col" style="border-top: 2px solid var(--accent);">
                        <h3 class="font-semibold mb-3 flex justify-between font-head">Done <span class="px-2 text-xs py-0.5" style="background: rgba(242,169,59,.15); color: var(--accent); border-radius: var(--radius);">1</span></h3>
                        <div id="done-list" class="flex-1 space-y-3 min-h-[200px]">
                            <div class="bg-raised border border-ui p-3 cursor-grab opacity-60" style="border-radius: var(--radius);">
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-xs font-semibold px-2 py-1 font-mono-ui text-muted-ui" style="background: rgba(127,127,127,.15); border-radius: var(--radius);">frontend</span>
                                </div>
                                <p class="text-sm font-medium line-through text-muted-ui">Design multi-tenant dashboard UI</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="calendar-view" class="view-pane card p-4 flex-1 hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-semibold font-head"><?php echo $monthLabel; ?></h3>
                        <div class="flex items-center gap-3 text-xs text-muted-ui font-mono-ui">
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--danger);"></span>high</span>
                            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background: var(--accent-2);"></span>normal</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-2 text-center text-xs text-muted-ui font-semibold mb-2 font-mono-ui">
                        <div>mon</div><div>tue</div><div>wed</div><div>thu</div><div>fri</div><div>sat</div><div>sun</div>
                    </div>
                    <div class="grid grid-cols-7 gap-2">
                        <?php
                        for ($b = 1; $b < $startWeekday; $b++) echo '<div class="cal-cell"></div>';
                        for ($d = 1; $d <= $daysInMonth; $d++) {
                            $isToday = ($d === $todayNum);
                            echo '<div class="cal-cell bg-raised border border-ui p-1.5 flex flex-col ' . ($isToday ? 'is-today' : '') . '" style="border-radius: var(--radius);">';
                            echo '<span class="text-xs font-semibold font-mono-ui ' . ($isToday ? 'text-accent-2' : 'text-muted-ui') . '">' . $d . '</span><div class="mt-1 space-y-1">';
                            if (isset($calendarTasks[$d])) {
                                foreach ($calendarTasks[$d] as $t) {
                                    $bg = $t['priority'] === 'high' ? 'rgba(239,100,97,.18)' : 'rgba(69,208,195,.18)';
                                    $fg = $t['priority'] === 'high' ? 'var(--danger)' : 'var(--accent-2)';
                                    echo '<div class="cal-pill" style="background:' . $bg . '; color:' . $fg . ';" title="' . htmlspecialchars($t['title']) . '">' . htmlspecialchars($t['title']) . '</div>';
                                }
                            }
                            echo '</div></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Team (roster / requests / invite) -->
            <div id="leader-tab-team" class="view-pane flex-1 flex flex-col gap-8 hidden">
                <!-- Pending Join Requests -->
                <div>
                    <h3 class="font-head font-semibold mb-4 flex justify-between items-center">
                        Pending Requests
                        <?php if(count($pendingRequests) > 0): ?>
                            <span class="px-2 py-0.5 text-xs rounded bg-red-500/20 text-red-500"><?php echo count($pendingRequests); ?> new</span>
                        <?php endif; ?>
                    </h3>
                    
                    <?php if (empty($pendingRequests)): ?>
                        <div class="text-sm text-muted-ui p-4 border border-ui border-dashed rounded text-center">
                            No pending requests. Invite classmates from the Roster!
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($pendingRequests as $req): ?>
                            <div class="flex items-center justify-between p-3 bg-raised border border-ui rounded">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs bg-accent-2 text-bg">
                                        <?php echo strtoupper(substr($req['USERNAME'], 0, 1)); ?>
                                    </div>
                                    <span class="font-semibold text-sm"><?php echo htmlspecialchars($req['USERNAME']); ?></span>
                                </div>
                                <div class="flex gap-2">
                                    <form method="POST" action="manage_join_request.php">
                                        <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($classroom_id); ?>">
                                        <input type="hidden" name="project_id" value="<?php echo $myProjectId; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $req['ID']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button class="btn-ui px-3 py-1.5 text-xs font-semibold bg-accent text-bg hover:opacity-90">Accept</button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Active Team Roster -->
                <div>
                    <h3 class="font-head font-semibold mb-4">Active Team Roster</h3>
                    <div class="space-y-4">
                        <?php foreach ($actualTeamRoster as $m): ?>
                        <div class="flex items-center gap-4">
                            <span class="status-dot ontrack" aria-hidden="true"></span>
                            <div class="w-48 flex-none">
                                <div class="font-semibold text-sm"><?php echo htmlspecialchars($m['USERNAME']); ?></div>
                                <div class="text-xs text-muted-ui font-mono-ui"><?php echo $m['IS_LEADER'] == 1 ? 'Project Leader' : 'Team Member'; ?></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm truncate mb-1 text-muted-ui italic">Task assignment pending...</div>
                                <div class="roster-bar-track h-1.5 w-full">
                                    <div class="roster-bar-fill h-full" style="width: 0%; background: var(--accent);"></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Invite Classmates (Presentation Simulation) -->
                <div>
                    <h3 class="font-head font-semibold mb-4">Team Building</h3>
                    <div class="p-6 border border-ui border-dashed rounded text-center">
                        <p class="text-sm text-muted-ui mb-4">You have <?php echo max(0, 4 - count($actualTeamRoster)); ?> open slots remaining on your team (Max 4).</p>
                        <button onclick="document.getElementById('inviteModal').classList.add('show')" class="btn-ui px-4 py-2 text-sm font-semibold bg-accent-2 text-bg hover:opacity-90">
                            <i class="fas fa-user-plus mr-2"></i> Browse Classmates
                        </button>
                    </div>
                </div>

                <!-- Simulation Modal -->
                <div id="inviteModal" class="success-overlay">
                    <div class="bg-panel border border-ui p-6 rounded-lg w-full max-w-lg shadow-xl pointer-events-auto max-h-[80vh] flex flex-col">
                        <div class="flex justify-between items-center mb-4 pb-4 border-b border-ui">
                            <h2 class="text-xl font-bold font-head">Invite to Project</h2>
                            <button onclick="document.getElementById('inviteModal').classList.remove('show')" class="text-muted-ui hover:text-white transition"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="overflow-y-auto flex-1 space-y-3 pr-2">
                            <?php 
                            $simulatedClassmates = [
                                ['name' => 'VARSHINI G', 'usn' => '1RG24CS112'],
                                ['name' => 'SHIVANI KUMARI', 'usn' => '1RG24CS090'],
                                ['name' => 'SUSHMITA C M', 'usn' => '1RG24CS104'],
                                ['name' => 'TWAYIB', 'usn' => '1RG24CS109'],
                                ['name' => 'SIDDHARTH', 'usn' => '1RG24CS097'],
                                ['name' => 'SUHASS', 'usn' => '1RG24CS103'],
                            ];
                            foreach($simulatedClassmates as $c): ?>
                            <div class="flex items-center justify-between p-3 bg-raised border border-ui rounded">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-xs bg-black/20 text-muted-ui border border-ui">
                                        <?php echo substr($c['name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <span class="block font-semibold text-sm"><?php echo htmlspecialchars($c['name']); ?></span>
                                        <span class="block text-xs text-muted-ui font-mono-ui"><?php echo $c['usn']; ?></span>
                                    </div>
                                </div>
                                <button class="px-4 py-1.5 text-xs font-semibold rounded bg-black/20 text-muted-ui border border-ui hover:border-accent hover:text-accent transition" onclick="this.innerHTML='<i class=\'fas fa-check\'></i> Sent'; this.classList.add('text-accent', 'border-accent');">
                                    Invite
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ============================================================ -->
        <!-- TEACHER — Marking desk & Live Roster                          -->
        <!-- ============================================================ -->
        <div class="lg:col-span-4 flex flex-col h-full">
            
            <!-- Teacher Tabs -->
            <div class="flex gap-6 border-b border-ui mb-6">
                <button id="btn-tab-groups" class="tab-btn active pb-2" onclick="switchTeacherTab('groups')">Project Groups</button>
                <button id="btn-tab-roster" class="tab-btn pb-2" onclick="switchTeacherTab('roster')">Classroom</button>
            </div>

            <!-- TAB 1: Project Groups (Ledger) -->
            <div id="tab-groups" class="view-pane flex-1 flex flex-col">
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <h2 class="text-2xl font-head font-semibold">Classroom</h2>
                        <p class="text-muted-ui text-sm mt-1" id="ledger-stats">0 groups &middot; 0 nearly finished</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="simulateGroups()" class="btn-ui px-4 py-1.5 text-xs font-semibold hover:bg-black/10 transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                            <i class="fas fa-play"></i> Share link
                        </button>
                        <button id="sortLedgerBtn" class="ledger-head-btn text-sm font-semibold pb-0.5">
                            Sort by completion <i class="fas fa-arrow-down-short-wide ms-1 text-xs"></i>
                        </button>
                    </div>
                </div>

                <div class="card flex-1 flex flex-col">
                    <div class="grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-3 text-xs text-muted-ui font-mono-ui border-b border-ui">
                        <span>Group</span><span class="w-20 text-right">Members</span><span class="w-40">Completion</span><span class="w-16 text-right">Status</span>
                    </div>
                    <div id="ledger-body" class="flex-1">
                        <div class="py-12 text-center text-muted-ui text-sm italic" id="empty-groups-state">
                            Waiting for students to create project groups...
                        </div>
                        <?php foreach ($projectGroups as $index => $g):
                            $near = $g['percent'] >= 90;
                            $ink = $g['percent'] < 50 ? 'var(--accent-2)' : 'var(--accent)';
                            $statusWord = $g['percent'] < 50 ? 'At risk' : ($near ? 'Nearly done' : 'On track');
                            $statusColor = $g['percent'] < 50 ? 'var(--accent-2)' : 'var(--accent)';
                        ?>
                        <div class="ledger-item flex flex-col" data-percent="<?php echo $g['percent']; ?>">
                            <div class="ledger-row grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-4 items-center cursor-pointer hover:bg-black/5 transition" onclick="document.getElementById('details-<?php echo $index; ?>').classList.toggle('hidden'); document.getElementById('chevron-<?php echo $index; ?>').classList.toggle('rotate-180')">
                                <div class="flex items-center gap-3 min-w-0">
                                    <?php if ($near): ?>
                                    <span class="seal flex-none"><i class="fas fa-check"></i></span>
                                    <?php endif; ?>
                                    <span class="font-semibold truncate"><?php echo htmlspecialchars($g['name']); ?></span>
                                    <i class="fas fa-chevron-down text-xs text-muted-ui ml-2 transition-transform duration-200" id="chevron-<?php echo $index; ?>"></i>
                                </div>
                                <span class="w-20 text-right text-sm text-muted-ui"><?php echo (int)$g['members']; ?></span>
                                <div class="w-40">
                                    <div class="ink-bar-track w-full mb-1">
                                        <div class="ink-bar-fill" style="width: <?php echo (int)$g['percent']; ?>%; background: <?php echo $ink; ?>;"></div>
                                    </div>
                                    <span class="text-xs font-mono-ui text-muted-ui"><?php echo (int)$g['percent']; ?>%</span>
                                </div>
                                <span class="w-16 text-right text-sm font-semibold" style="color: <?php echo $statusColor; ?>;"><?php echo $statusWord; ?></span>
                            </div>
                            <div id="details-<?php echo $index; ?>" class="hidden px-14 py-4 bg-black/5 border-b border-ui text-sm">
                                <h4 class="font-semibold mb-1">Project Description</h4>
                                <p class="text-muted-ui mb-3"><?php echo htmlspecialchars($g['desc'] ?? 'No description provided.'); ?></p>
                                <div class="flex justify-between items-end">
                                    <div>
                                        <h4 class="font-semibold mb-1">Team Members</h4>
                                        <ul class="list-disc list-inside text-muted-ui">
                                            <?php foreach ($g['member_names'] ?? [] as $member): ?>
                                                <li><?php echo htmlspecialchars($member); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <a href="teacher_group_view.php?group=<?php echo $g['id']; ?>&classroom_id=<?php echo urlencode($classroom_id); ?>" class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition" style="color: var(--accent); border-color: var(--accent);">
                                        View Full Report <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Classroom Roster & Live Join Demo -->
            <div id="tab-roster" class="view-pane flex-1 flex flex-col hidden">
                <div class="flex justify-between items-end mb-4">
                    <div>
                        <h2 class="text-2xl font-head font-semibold">Classroom</h2>
                        <p class="text-muted-ui text-sm mt-1">Invite Code: <span class="font-mono-ui font-semibold px-2 py-1 bg-black/5 rounded">RGIT-CS-B</span></p>
                    </div>
                    <button onclick="simulateJoins()" class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition flex items-center gap-2" style="color: var(--accent); border-color: var(--accent);">
                        <i class="fas fa-play"></i> Share link
                    </button>
                </div>
                
                <div class="card p-5">
                    <div class="grid grid-cols-[auto_1fr_auto] gap-4 text-xs font-semibold text-muted-ui font-mono-ui border-b border-ui pb-2">
                        <span class="w-8"></span><span>Student Info</span><span>Status</span>
                    </div>
                    <div id="live-roster-list" class="flex flex-col mt-2">
                        <div class="py-6 text-center text-muted-ui text-sm italic" id="empty-roster-state">
                            Waiting for students to join via code...
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <script>
            function switchTeacherTab(tab) {
                document.getElementById('btn-tab-groups').classList.toggle('active', tab === 'groups');
                document.getElementById('btn-tab-roster').classList.toggle('active', tab === 'roster');
                document.getElementById('tab-groups').classList.toggle('hidden', tab !== 'groups');
                document.getElementById('tab-roster').classList.toggle('hidden', tab !== 'roster');
            }

            const mockStudents = [
                {name: "CHAITHRA AB", usn: "1RG24CS015"},
                {name: "KEERTHANA", usn: "1RG24CS034"},
                {name: "ANKITHA V", usn: "1RG24CS009"},
                {name: "ARCHANA NAVI", usn: "1RG24CS011"},
                {name: "Twayib", usn: "1RG24CS089"},
                {name: "Siddharth", usn: "1RG24CS112"}
            ];
            
            let simRunning = false;
            function simulateJoins() {
                if(simRunning) return;
                simRunning = true;
                const list = document.getElementById('live-roster-list');
                const emptyState = document.getElementById('empty-roster-state');
                if(emptyState) emptyState.remove();
                
                let delay = 0;
                mockStudents.forEach((student, index) => {
                    setTimeout(() => {
                        const row = document.createElement('div');
                        row.className = 'grid grid-cols-[auto_1fr_auto] gap-4 py-3 border-b border-ui items-center view-pane';
                        row.innerHTML = `
                            <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs" style="background: var(--accent); color: #fff;">
                                ${student.name.charAt(0)}
                            </div>
                            <div>
                                <div class="font-semibold text-sm">${student.name}</div>
                                <div class="text-xs text-muted-ui font-mono-ui tracking-wide">${student.usn}</div>
                            </div>
                            <div>
                                <span class="px-2 py-1 text-[10px] font-semibold rounded uppercase tracking-wider" style="background: rgba(63, 108, 81, 0.1); color: var(--accent);">Joined</span>
                            </div>
                        `;
                        list.prepend(row);
                        
                        // Show toast
                        const toast = document.getElementById('toast');
                        if (toast) {
                            toast.querySelector('h4').innerText = "New Member Joined";
                            toast.querySelector('p').innerText = `${student.name} (${student.usn}) entered the classroom.`;
                            toast.classList.add('show');
                            setTimeout(() => toast.classList.remove('show'), 3000);
                        }
                    }, delay);
                    // Add staggered timing for realism
                    delay += 1000 + Math.random() * 1200; 
                });
            }
        </script>
        <?php endif; ?>

        <?php if (($actualView === 'Student' || $actualView === 'Project Leader') && !$isNewlyCreated): ?>
        <!-- Right: analytics sidebar (Student / Leader only) -->
        <div class="flex flex-col gap-6">
            <div class="card p-5" style="border-top: 2px solid var(--accent-2);">
                <h3 class="font-semibold text-sm mb-4 font-head"><i class="fas fa-chart-pie me-2 text-accent-2"></i>Contribution tracker</h3>
                <div class="relative h-48 w-full">
                    <canvas id="contributionChart"></canvas>
                </div>

                <div class="mt-5 pt-4 border-t border-ui">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-muted-ui font-semibold">Activity heatmap</span>
                        <span class="text-[10px] text-muted-ui">last 10 weeks</span>
                    </div>
                    <div class="flex gap-[3px] overflow-x-auto pb-1">
                        <?php for ($w = 0; $w < $heatmapWeeks; $w++): ?>
                        <div class="flex flex-col gap-[3px]">
                            <?php for ($day = 0; $day < 7; $day++):
                                $level = heat_level($heatmapPattern[$w * 7 + $day] ?? 0);
                                $opacities = [0.12, 0.3, 0.5, 0.75, 1];
                            ?>
                            <div class="heat-square <?php echo $level === 4 ? 'heat-glow' : ''; ?>"
                                style="background: var(--accent-2); opacity: <?php echo $opacities[$level]; ?>;"
                                title="<?php echo $heatmapPattern[$w * 7 + $day] ?? 0; ?> tasks checked off"></div>
                            <?php endfor; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <div class="flex items-center justify-end gap-1 mt-2 text-[10px] text-muted-ui">
                        <span>less</span>
                        <?php foreach ([0.12, 0.3, 0.5, 0.75, 1] as $o): ?>
                            <span class="heat-square" style="background: var(--accent-2); opacity: <?php echo $o; ?>;"></span>
                        <?php endforeach; ?>
                        <span>more</span>
                    </div>
                </div>
            </div>

            <div class="card p-5 flex-1 flex flex-col">
                <h3 class="font-semibold text-sm mb-4 font-head"><i class="fas fa-satellite-dish me-2 text-accent"></i>Live activity</h3>
                <div id="activity-feed" class="space-y-4 overflow-hidden relative flex-1 text-sm"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Kanban drag and drop (Student layout only)
        function showToast(msg){ const t=document.getElementById('toast'); t.querySelector('p').textContent=msg;
          t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),3000); }

        const kanbanOptions = { group:'shared', animation:150,
          onEnd(evt){
            if (evt.to === evt.from) return;
            ['todo-list','inprogress-list','done-list'].forEach(id=>{
              const l=document.getElementById(id);
              if(l && l.previousElementSibling) l.previousElementSibling.querySelector('span').textContent=l.children.length;
            });
            const done = evt.to.id==='done-list';
            evt.item.classList.toggle('opacity-60', done);
            evt.item.querySelector('p')?.classList.toggle('line-through', done);
            const bar=document.querySelector('.progress-fill');
            if (bar) {
                const w = parseInt(bar.style.width) || 74;
                if (done) { bar.style.width=Math.min(100,w+2)+'%'; showToast('Weekly progress updated.'); }
                else if (evt.from.id==='done-list') bar.style.width=Math.max(0,w-2)+'%';
            }
          }};
        ['todo-list', 'inprogress-list', 'done-list'].forEach(id => {
            const el = document.getElementById(id);
            if (el) new Sortable(el, kanbanOptions);
        });

        // Kanban / Calendar toggle (Student layout only)
        const toggleKanbanBtn = document.getElementById('toggleKanbanBtn');
        const toggleCalendarBtn = document.getElementById('toggleCalendarBtn');
        const kanbanView = document.getElementById('kanban-view');
        const calendarView = document.getElementById('calendar-view');
        if (toggleKanbanBtn && toggleCalendarBtn) {
            toggleKanbanBtn.addEventListener('click', () => {
                kanbanView.classList.remove('hidden'); calendarView.classList.add('hidden');
                toggleKanbanBtn.classList.add('active'); toggleCalendarBtn.classList.remove('active');
            });
            toggleCalendarBtn.addEventListener('click', () => {
                calendarView.classList.remove('hidden'); kanbanView.classList.add('hidden');
                toggleCalendarBtn.classList.add('active'); toggleKanbanBtn.classList.remove('active');
            });
        }

        // Leader tabs: My Board <-> Team
        function switchLeaderTab(tab) {
            const boardBtn = document.getElementById('btn-tab-board');
            const teamBtn = document.getElementById('btn-tab-team');
            const boardPane = document.getElementById('leader-tab-board');
            const teamPane = document.getElementById('leader-tab-team');
            if (!boardBtn || !teamBtn || !boardPane || !teamPane) return;
            boardBtn.classList.toggle('active', tab === 'board');
            teamBtn.classList.toggle('active', tab === 'team');
            boardPane.classList.toggle('hidden', tab !== 'board');
            teamPane.classList.toggle('hidden', tab !== 'team');
        }

        // Ledger sort (Teacher layout only)
        const sortBtn = document.getElementById('sortLedgerBtn');
        if (sortBtn) {
            let descending = true;
            sortBtn.addEventListener('click', () => {
                const body = document.getElementById('ledger-body');
                const rows = Array.from(body.querySelectorAll('.ledger-item'));
                rows.sort((a, b) => {
                    const pa = parseInt(a.dataset.percent, 10), pb = parseInt(b.dataset.percent, 10);
                    return descending ? pa - pb : pb - pa;
                });
                rows.forEach(r => body.appendChild(r));
                descending = !descending;
                sortBtn.innerHTML = 'Sort by completion <i class="fas fa-arrow-' + (descending ? 'down-short-wide' : 'up-wide-short') + ' ms-1 text-xs"></i>';
            });
        }

        // Contribution chart
        const contribCanvas = document.getElementById('contributionChart');
        if (contribCanvas) {
            const rootStyles = getComputedStyle(document.documentElement);
            const accent = rootStyles.getPropertyValue('--accent').trim() || '#4f46e5';
            const accent2 = rootStyles.getPropertyValue('--accent-2').trim() || '#8b5cf6';
            const border = rootStyles.getPropertyValue('--panel').trim() || '#1f2937';
            const muted = rootStyles.getPropertyValue('--muted').trim() || '#9ca3af';
            new Chart(contribCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Nithin', 'Ranjith', 'Vinutha', 'Vinaya'],
                    datasets: [{ data: [45, 30, 15, 10], backgroundColor: [accent2, accent, muted, '#f04438'], borderColor: border, borderWidth: 2, hoverOffset: 4 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: { legend: { position: 'bottom', labels: { color: muted, usePointStyle: true, boxWidth: 6 } } }
                }
            });
        }

        // Simulated live feed
        const feedEvents = [
            { time: "Just now", icon: "fa-upload text-accent-2", text: "<span class='font-semibold'>VARSHINI G</span> uploaded <span class='text-accent-2'>database_schema.sql</span>" },
            { time: "2 mins ago", icon: "fa-check-double text-accent", text: "Dr Latha P H approved <span class='font-semibold'>Phase 1 Report</span>" },
            { time: "1 hr ago", icon: "fa-robot text-accent-2", text: "System: deadline for <span class='font-semibold'>Phase 1 report</span> is in 24 hours." },
            { time: "3 hrs ago", icon: "fa-code-commit text-muted-ui", text: "<span class='font-semibold'>SUSHMITA C M</span> moved a task to Review." }
        ];
        const feedContainer = document.getElementById('activity-feed');
        feedEvents.forEach((evt, index) => {
            setTimeout(() => {
                const el = document.createElement('div');
                el.className = "flex gap-3";
                el.innerHTML = `<div class="mt-1"><i class="fas ${evt.icon}"></i></div><div><p>${evt.text}</p><span class="text-xs text-muted-ui">${evt.time}</span></div>`;
                feedContainer.prepend(el);
            }, index * 800);
        });
        setTimeout(() => {
            const el = document.createElement('div');
            el.className = "flex gap-3 transition-all duration-500 ease-out translate-y-[-20px] opacity-0";
            el.innerHTML = `<div class="mt-1"><i class="fas fa-comment-dots text-accent"></i></div><div><p>Prof. Nanda Kumar left a voice note on <span class='font-semibold'>Architecture Draft</span></p><span class="text-xs text-muted-ui">Just now</span></div>`;
            feedContainer.prepend(el);
            requestAnimationFrame(() => el.classList.remove('translate-y-[-20px]', 'opacity-0'));
        }, 5000);

        let simGroupsRunning = false;
        function simulateGroups() {
            if(simGroupsRunning) return;
            simGroupsRunning = true;
            
            const emptyState = document.getElementById('empty-groups-state');
            if(emptyState) emptyState.remove();
            
            const ledgerBody = document.getElementById('ledger-body');
            const stats = document.getElementById('ledger-stats');
            
            const mockGroups = <?php echo json_encode($mockProjectGroupsRaw ?? []); ?>;
            let groupCount = 0;
            let finishedCount = 0;
            
            mockGroups.forEach((g, index) => {
                setTimeout(() => {
                    const near = g.percent >= 90;
                    const ink = g.percent < 50 ? 'var(--accent-2)' : 'var(--accent)';
                    const statusWord = g.percent < 50 ? 'At risk' : (near ? 'Nearly done' : 'On track');
                    const statusColor = g.percent < 50 ? 'var(--accent-2)' : 'var(--accent)';
                    
                    const rowHtml = `
                    <div class="ledger-item flex flex-col view-pane" data-percent="${g.percent}">
                        <div class="ledger-row grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-4 items-center cursor-pointer hover:bg-black/5 transition" onclick="document.getElementById('details-${index}').classList.toggle('hidden'); document.getElementById('chevron-${index}').classList.toggle('rotate-180')">
                            <div class="flex items-center gap-3 min-w-0">
                                ${near ? '<span class="seal flex-none"><i class="fas fa-check"></i></span>' : ''}
                                <span class="font-semibold truncate">${g.name}</span>
                                <i class="fas fa-chevron-down text-xs text-muted-ui ml-2 transition-transform duration-200" id="chevron-${index}"></i>
                            </div>
                            <span class="w-20 text-right text-sm text-muted-ui">${g.members}</span>
                            <div class="w-40">
                                <div class="ink-bar-track w-full mb-1">
                                    <div class="ink-bar-fill transition-all duration-1000 ease-out" style="width: 0%; background: ${ink};" id="bar-${index}"></div>
                                </div>
                                <span class="text-xs font-mono-ui text-muted-ui" id="pct-${index}">0%</span>
                            </div>
                            <span class="w-16 text-right text-sm font-semibold" style="color: ${statusColor};">${statusWord}</span>
                        </div>
                        <div id="details-${index}" class="hidden px-14 py-4 bg-black/5 border-b border-ui text-sm">
                            <h4 class="font-semibold mb-1">Project Description</h4>
                            <p class="text-muted-ui mb-3">${g.desc}</p>
                            <div class="flex justify-between items-end">
                                <div>
                                    <h4 class="font-semibold mb-1">Team Members</h4>
                                    <ul class="list-disc list-inside text-muted-ui">
                                        ${g.member_names.map(m => `<li>${m}</li>`).join('')}
                                    </ul>
                                </div>
                                <a href="teacher_group_view.php?group=${g.id}&classroom_id=<?php echo urlencode($classroom_id); ?>" class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition" style="color: var(--accent); border-color: var(--accent);">
                                    View Full Report <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    `;
                    ledgerBody.insertAdjacentHTML('beforeend', rowHtml);
                    
                    // Animate the bar and counter
                    setTimeout(() => {
                        const bar = document.getElementById(`bar-${index}`);
                        const pct = document.getElementById(`pct-${index}`);
                        if(bar) bar.style.width = g.percent + '%';
                        
                        let current = 0;
                        const inc = g.percent / 20;
                        const timer = setInterval(() => {
                            current += inc;
                            if (current >= g.percent) {
                                current = g.percent;
                                clearInterval(timer);
                            }
                            if(pct) pct.innerText = Math.round(current) + '%';
                        }, 50);
                    }, 100);

                    // Update stats
                    groupCount++;
                    if(near) finishedCount++;
                    stats.innerHTML = `${groupCount} groups &middot; ${finishedCount} nearly finished`;
                    
                }, index * 1500); 
            });
        }
    </script>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) { window.location.reload(); }
        });
</script>
</body>

</html>