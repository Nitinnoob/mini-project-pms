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

$role = $_SESSION['role'] ?? 'Developer';
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));

// ---------------------------------------------------------------------
// Demo data below is hardcoded for presentation purposes only, in the
// same spirit as the rest of this trailer build. In production this
// would be swapped for real query results without touching the markup.
// ---------------------------------------------------------------------

// Normalize the session role into one of the three layouts this page knows.
$roleMap = [
    'Developer'      => 'Student',
    'Student'        => 'Student',
    'Project Leader' => 'Project Leader',
    'Lead'           => 'Project Leader',
    'Teacher'        => 'Teacher',
];
$actualView = $roleMap[$role] ?? 'Student';

// Demo-only preview switch. This never touches $_SESSION or auth -- it just
// lets a presenter flip between the three layouts without three logins.
$validViews = ['Student', 'Project Leader', 'Teacher'];
$demoView = $_GET['demo_view'] ?? $actualView;
if (!in_array($demoView, $validViews, true)) {
    $demoView = $actualView;
}
$modeSlug = ['Student' => 'student', 'Project Leader' => 'leader', 'Teacher' => 'teacher'][$demoView];

// 1. Global project progress (shown in every layout, styled per mode)
$progressPercent = 74;
if ($progressPercent < 50) {
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
$projectGroups = [
    ['id' => 23, 'name' => 'Group 23 — Project management system', 'members' => 4, 'percent' => 96, 'desc' => 'A comprehensive platform for students to manage mini-projects, track milestones, and communicate with their guides asynchronously.', 'member_names' => ['Ranjith kumar', 'Nithin gowda', 'Vinutha H K', 'Vinaya kumar']],
    ['id' => 14, 'name' => 'Group 14 — Library Management system', 'members' => 3, 'percent' => 92, 'desc' => 'A digital solution for campus library book tracking, issuing, and automated fine calculation.', 'member_names' => ['Twayib', 'Siddharth', 'Suhass']],
    ['id' => 16, 'name' => 'Group 16 — Placement management system',  'members' => 3, 'percent' => 78, 'desc' => 'Web portal to track upcoming campus drives, student eligibility, and interview schedules.', 'member_names' => ['Varshini G', 'Shivani Kumari', 'Sushmita C M']],
    ['id' => 15, 'name' => 'Group 15 — Hostel Management System',        'members' => 3, 'percent' => 60, 'desc' => 'Platform for room allocation, mess fee tracking, and hostel complaint logging.', 'member_names' => ['Sameer', 'Saquib', 'Raiyaan']],
    ['id' => 17, 'name' => 'Group 17 — Vehicle parking management System', 'members' => 4, 'percent' => 45, 'desc' => 'Automated parking slot allocation and campus entry tracking using RFID.', 'member_names' => ['Shodhan R', 'Prajwal', 'Sudiksha D', 'Sneha Sanjeev Mayannavar']],
    ['id' => 18, 'name' => 'Group 18 — Hospital Management System',       'members' => 4, 'percent' => 30, 'desc' => 'Centralized patient record management, appointment booking, and inventory system.', 'member_names' => ['Vinay kumar G S', 'Pavan kalli', 'Prashanth K S', 'Shreyas']],
];
usort($projectGroups, fn($a, $b) => $b['percent'] <=> $a['percent']);

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
    <title>PMS — <?php echo htmlspecialchars($demoView); ?> view</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="pms.css">
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
            
            <select class="ml-6 bg-panel border border-ui text-sm px-3 py-1.5 outline-none cursor-pointer transition" style="border-radius: var(--radius);">
                <option>5th Sem CS Projects</option>
                <option>Weekend Hackathon</option>
                <option>Personal Sandbox</option>
            </select>
            <?php if ($actualView !== 'Teacher'): ?>
            <a href="create_project.php" class="ml-3 text-xs font-semibold px-3 py-1.5 border border-ui hover:opacity-80 transition" style="border-radius: var(--radius); color: var(--text);">
                <i class="fas fa-plus mr-1"></i> New Workspace
            </a>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-5 text-sm">
            <!-- Demo preview switcher: display-only, does not touch session auth -->
            <div class="hidden md:flex items-center gap-2 text-xs text-muted-ui">
                <span>Preview</span>
                <div class="flex border border-ui overflow-hidden" style="border-radius: var(--radius);">
                    <?php foreach ($validViews as $v): ?>
                        <a href="?demo_view=<?php echo urlencode($v); ?>"
                           class="demo-switch-btn px-2.5 py-1 <?php echo $v === $demoView ? 'active' : 'text-muted-ui'; ?>">
                           <?php echo htmlspecialchars($v); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <button class="text-muted-ui hover:text-accent transition" aria-label="Notifications"><i class="fas fa-bell"></i></button>
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo $username; ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent-2); color: var(--bg);"><?php echo $initial; ?></div>
            </div>
            <a href="logout.php" class="text-danger hover:opacity-80 transition ml-1" title="Log out"><i class="fas fa-sign-out-alt text-lg"></i></a>
        </div>
    </nav>
        <?php if ($demoView === 'Project Leader'): ?>
        <div class="overflow-hidden border-b border-ui flex items-center text-xs font-mono py-2 px-6" style="background: var(--bg-raised); color: var(--danger);">
            <div class="flex-shrink-0 font-bold mr-4"><i class="fas fa-bolt"></i> SYSTEM ALERTS:</div>
            <marquee scrollamount="5" class="flex-1">
                BLOCKER &bull; Vinaya kumar &bull; Deployment blocked on AWS credits &bull; 2 DAYS
            </marquee>
        </div>
        <?php endif; ?>

    <p class="px-6 pt-3 text-xs text-muted-ui md:hidden">Signed in as <?php echo $username; ?>. Previewing the <?php echo htmlspecialchars($demoView); ?> layout —
        <?php foreach ($validViews as $v): if ($v === $demoView) continue; ?>
            <a class="underline" href="?demo_view=<?php echo urlencode($v); ?>"><?php echo htmlspecialchars($v); ?></a>&nbsp;
        <?php endforeach; ?>
    </p>

    <!-- Global project progress -->
    <div class="px-6 pt-5">
        <div class="flex justify-between items-baseline mb-2">
            <h2 class="text-lg font-head font-semibold">Sprint 4: Core Architecture</h2>
            <span class="text-sm font-mono-ui">
                <span class="font-bold"><?php echo $progressPercent; ?>%</span>
                <span class="text-muted-ui"> — <?php echo $progressWord; ?></span>
            </span>
        </div>
        <div class="progress-track w-full h-2.5">
            <div class="progress-fill h-full status-<?php echo $progressStatus; ?>" style="width: <?php echo $progressPercent; ?>%;"></div>
        </div>
    </div>

    <!-- Main workspace -->
    <div class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">

        <?php if ($demoView === 'Student'): ?>
        <!-- ============================================================ -->
        <!-- STUDENT — Workbench: kanban / calendar                        -->
        <!-- ============================================================ -->
        <div class="lg:col-span-3 flex flex-col h-full">
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

        <?php elseif ($demoView === 'Project Leader'): ?>
        <!-- ============================================================ -->
        <!-- PROJECT LEADER — Control room: stats, ticker, roster          -->
        <!-- ============================================================ -->
        <div class="lg:col-span-3 flex flex-col h-full gap-4">


            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="card stat-tile tone-red p-4">
                    <div class="stat-num text-3xl"><?php echo $blockerCount; ?></div>
                    <div class="text-xs text-muted-ui mt-1">blockers open</div>
                </div>
                <div class="card stat-tile tone-green p-4">
                    <div class="stat-num text-3xl"><?php echo $onTrackCount; ?>/<?php echo count($teamRoster); ?></div>
                    <div class="text-xs text-muted-ui mt-1">team on track</div>
                </div>
                <div class="card stat-tile tone-amber p-4">
                    <div class="stat-num text-3xl"><?php echo $avgVelocity; ?>%</div>
                    <div class="text-xs text-muted-ui mt-1">average velocity</div>
                </div>
                <div class="card stat-tile p-4">
                    <div class="stat-num text-3xl"><?php echo $daysToDeadline; ?></div>
                    <div class="text-xs text-muted-ui mt-1">days to deadline</div>
                </div>
            </div>

            <div class="card p-5 flex-1">
                <h3 class="font-head font-semibold mb-4">Team roster</h3>
                <div class="space-y-4">
                    <?php foreach ($teamRoster as $m):
                        $barColor = $m['status'] === 'red' ? 'var(--danger)' : ($m['status'] === 'amber' ? 'var(--accent-2)' : 'var(--accent)');
                    ?>
                    <div class="flex items-center gap-4">
                        <span class="status-dot <?php echo $m['status']; ?>" aria-hidden="true"></span>
                        <div class="w-32 flex-none">
                            <div class="font-semibold text-sm"><?php echo htmlspecialchars($m['name']); ?></div>
                            <div class="text-xs text-muted-ui font-mono-ui"><?php echo htmlspecialchars($m['role']); ?></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm truncate mb-1"><?php echo htmlspecialchars($m['task']); ?></div>
                            <div class="roster-bar-track h-1.5 w-full">
                                <div class="roster-bar-fill h-full" style="width: <?php echo (int)$m['percent']; ?>%; background: <?php echo $barColor; ?>;"></div>
                            </div>
                        </div>
                        <div class="stat-num text-sm w-10 text-right flex-none"><?php echo (int)$m['percent']; ?>%</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- ============================================================ -->
        <!-- TEACHER — Marking desk: gradebook ledger                      -->
        <!-- ============================================================ -->
        <div class="lg:col-span-4 flex flex-col h-full">
            <div class="flex justify-between items-end mb-4">
                <div>
                    <h2 class="text-2xl font-head font-semibold">Classroom gradebook</h2>
                    <p class="text-muted-ui text-sm mt-1"><?php echo count($projectGroups); ?> groups &middot;
                        <?php echo count(array_filter($projectGroups, fn($p) => $p['percent'] >= 90)); ?> nearly finished</p>
                </div>
                <button id="sortLedgerBtn" class="ledger-head-btn text-sm font-semibold pb-0.5">
                    Sort by completion <i class="fas fa-arrow-down-short-wide ms-1 text-xs"></i>
                </button>
            </div>

            <div class="card">
                <div class="grid grid-cols-[1fr_auto_auto_auto] gap-4 px-5 py-3 text-xs text-muted-ui font-mono-ui border-b border-ui">
                    <span>Group</span><span class="w-20 text-right">Members</span><span class="w-40">Completion</span><span class="w-16 text-right">Status</span>
                </div>
                <div id="ledger-body">
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
                                <a href="teacher_group_view.php?group=<?php echo $g['id']; ?>" class="btn-ui px-4 py-2 text-xs font-semibold hover:bg-black/10 transition" style="color: var(--accent); border-color: var(--accent);">
                                    View Full Report <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($demoView !== 'Teacher'): ?>
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
        const kanbanOptions = {
            group: 'shared', animation: 150, ghostClass: 'kanban-ghost', dragClass: 'kanban-drag',
            onEnd: function (evt) {
                if (evt.to !== evt.from) {
                    showToast('Weekly progress updated dynamically.');
                    
                    // Update counts
                    ['todo-col', 'progress-col', 'review-col', 'done-col'].forEach(id => {
                        const col = document.getElementById(id);
                        if (col) {
                            const count = col.querySelectorAll('.card').length;
                            const badge = col.previousElementSibling.querySelector('.px-2');
                            if (badge) badge.innerText = count;
                        }
                    });

                    // Bump progress bar playfully
                    const bar = document.querySelector('.progress-fill');
                    if (bar && evt.to.id === 'done-col') {
                        let currentWidth = parseInt(bar.style.width) || 74;
                        bar.style.width = Math.min(100, currentWidth + 2) + '%';
                    }
                }
                if (evt.to.id === 'done-col') {
                    evt.item.classList.add('opacity-60');
                    const text = evt.item.querySelector('p');
                    if (text) { text.classList.add('line-through', 'text-muted-ui'); }
                    const toast = document.getElementById('toast');
                    if (toast) {
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }
                }
            },
            onRemove: function (evt) {
                if (evt.from.id === 'done-col') {
                    evt.item.classList.remove('opacity-60');
                    const text = evt.item.querySelector('p');
                    if (text) { text.classList.remove('line-through', 'text-muted-ui'); }
                }
            }
        };
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
            { time: "Just now", icon: "fa-upload text-accent-2", text: "<span class='font-semibold'>Sarah</span> uploaded <span class='text-accent-2'>database_schema.sql</span>" },
            { time: "2 mins ago", icon: "fa-check-double text-accent", text: "Instructor approved <span class='font-semibold'>Phase 1 Report</span>" },
            { time: "1 hr ago", icon: "fa-robot text-accent-2", text: "System: deadline for <span class='font-semibold'>Phase 1 report</span> is in 24 hours." },
            { time: "3 hrs ago", icon: "fa-code-commit text-muted-ui", text: "<span class='font-semibold'>Nithin</span> moved a task to Review." }
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
            el.innerHTML = `<div class="mt-1"><i class="fas fa-comment-dots text-accent"></i></div><div><p>Prof. Sharma left a voice note on <span class='font-semibold'>Architecture Draft</span></p><span class="text-xs text-muted-ui">Just now</span></div>`;
            feedContainer.prepend(el);
            requestAnimationFrame(() => el.classList.remove('translate-y-[-20px]', 'opacity-0'));
        }, 5000);
    </script>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) { window.location.reload(); }
        });
    
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            if (e.key === '1') window.location.href = '?demo_view=Student';
            if (e.key === '2') window.location.href = '?demo_view=Project Leader';
            if (e.key === '3') window.location.href = '?demo_view=Teacher';
        });
</script>
</body>

</html>