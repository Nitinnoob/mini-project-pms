<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['role'] ?? 'Developer';
if ($role !== 'Teacher' && $role !== 'Developer') {
    header("Location: dashboard.php");
    exit;
}
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));
$groupId = (int)($_GET['group'] ?? 23);
$classroom_id = $_GET['classroom_id'] ?? '';

$allGroups = [
    23 => [
        'name' => 'Project management system',
        'guide' => 'Prof Nagalakshmi',
        'roster' => [
            ['name' => 'Nithin gowda',   'role' => 'Backend',  'status' => 'green', 'task' => 'Schema Design', 'percent' => 96],
            ['name' => 'Ranjith kumar',  'role' => 'Frontend', 'status' => 'green', 'task' => 'Dashboard UI',  'percent' => 98],
            ['name' => 'Vinutha H K',    'role' => 'QA',       'status' => 'green', 'task' => 'Test scripts',  'percent' => 95],
            ['name' => 'Vinaya kumar',   'role' => 'DevOps',   'status' => 'green', 'task' => 'Deployment',    'percent' => 95],
        ],
        'pendingIssues' => []
    ],
    14 => [
        'name' => 'Library Management system',
        'guide' => 'Prof Meghashree',
        'roster' => [
            ['name' => 'Twayib',    'role' => 'Backend',  'status' => 'green', 'task' => 'Book API', 'percent' => 95],
            ['name' => 'Siddharth', 'role' => 'Frontend', 'status' => 'green', 'task' => 'Search UI', 'percent' => 92],
            ['name' => 'Suhass',    'role' => 'DBA',      'status' => 'amber', 'task' => 'Schema',   'percent' => 89],
        ],
        'pendingIssues' => [['who' => 'Suhass', 'issue' => 'Needs review on schema', 'severity' => 'amber']]
    ],
    16 => [
        'name' => 'Placement management system',
        'guide' => 'Dr Latha P H',
        'roster' => [
            ['name' => 'Varshini G',     'role' => 'Fullstack', 'status' => 'green', 'task' => 'Student Portal', 'percent' => 85],
            ['name' => 'Shivani Kumari', 'role' => 'Backend',   'status' => 'amber', 'task' => 'Email alerts',   'percent' => 75],
            ['name' => 'Sushmita C M',   'role' => 'Frontend',  'status' => 'green', 'task' => 'Admin Panel',    'percent' => 74],
        ],
        'pendingIssues' => [['who' => 'Shivani Kumari', 'issue' => 'SMTP server blocked', 'severity' => 'amber']]
    ],
    15 => [
        'name' => 'Hostel Management System',
        'guide' => 'Prof Soniya Komal',
        'roster' => [
            ['name' => 'Sameer',  'role' => 'Backend', 'status' => 'amber', 'task' => 'Room Allocation API', 'percent' => 65],
            ['name' => 'Saquib',  'role' => 'Frontend','status' => 'red',   'task' => 'Mess Fee UI',         'percent' => 45],
            ['name' => 'Raiyaan', 'role' => 'QA',      'status' => 'green', 'task' => 'Test Cases',          'percent' => 70],
        ],
        'pendingIssues' => [['who' => 'Saquib', 'issue' => 'Blocked on UI mockups', 'severity' => 'red']]
    ],
    17 => [
        'name' => 'Vehicle parking management System',
        'guide' => 'Prof Bhagyashree wakde',
        'roster' => [
            ['name' => 'Shodhan R',  'role' => 'Hardware', 'status' => 'red', 'task' => 'RFID Scanner', 'percent' => 30],
            ['name' => 'Prajwal',    'role' => 'Backend',  'status' => 'red', 'task' => 'Entry API',    'percent' => 40],
            ['name' => 'Sudiksha D', 'role' => 'Frontend', 'status' => 'amber','task' => 'Slot UI',     'percent' => 55],
            ['name' => 'Sneha S.',   'role' => 'QA',       'status' => 'amber','task' => 'Testing',     'percent' => 55],
        ],
        'pendingIssues' => [['who' => 'Shodhan R', 'issue' => 'RFID module defective', 'severity' => 'red']]
    ],
    18 => [
        'name' => 'Hospital Management System',
        'guide' => 'Dr Arudra A',
        'roster' => [
            ['name' => 'Vinay G S',  'role' => 'Backend',  'status' => 'red', 'task' => 'Patient DB', 'percent' => 25],
            ['name' => 'Pavan kalli','role' => 'Frontend', 'status' => 'red', 'task' => 'Doctor UI',  'percent' => 30],
            ['name' => 'Prashanth',  'role' => 'Mobile',   'status' => 'red', 'task' => 'App Setup',  'percent' => 30],
            ['name' => 'Shreyas',    'role' => 'QA',       'status' => 'amber','task' => 'Test Plan',  'percent' => 35],
        ],
        'pendingIssues' => [['who' => 'Vinay G S', 'issue' => 'Needs help with HIPAA compliance', 'severity' => 'red']]
    ]
];

if (!isset($allGroups[$groupId])) {
    $groupId = 23;
}
$groupData = $allGroups[$groupId];

$projectName = $groupData['name'];
$guideName = $groupData['guide'];
$teamRoster = $groupData['roster'];
$pendingIssues = $groupData['pendingIssues'];
$feedEvents = [];
if (count($teamRoster) > 0) {
    $feedEvents[] = ['time' => '1 hr ago', 'icon' => 'fa-upload text-accent', 'text' => "<span class='font-semibold'>{$teamRoster[0]['name']}</span> pushed 3 commits to <span class='font-mono'>main</span>"];
}
if (count($teamRoster) > 1) {
    $feedEvents[] = ['time' => '3 hrs ago', 'icon' => 'fa-check text-accent', 'text' => "<span class='font-semibold'>{$teamRoster[1]['name']}</span> completed task: {$teamRoster[1]['task']}"];
}
if (count($pendingIssues) > 0) {
    $feedEvents[] = ['time' => 'Yesterday', 'icon' => 'fa-triangle-exclamation text-danger', 'text' => "<span class='font-semibold'>{$pendingIssues[0]['who']}</span> reported an issue: {$pendingIssues[0]['issue']}"];
} elseif (count($teamRoster) > 2) {
    $feedEvents[] = ['time' => 'Yesterday', 'icon' => 'fa-comment text-accent', 'text' => "<span class='font-semibold'>{$teamRoster[2]['name']}</span> commented on the PR."];
}

$issueCount = count(array_filter($teamRoster, fn($m) => $m['status'] === 'red'));
$onTrackCount = count(array_filter($teamRoster, fn($m) => $m['status'] !== 'red'));
$avgProgress = (int) round(array_sum(array_column($teamRoster, 'percent')) / count($teamRoster));
$Deadline = 5;

$heatmapWeeks = 10;
$heatmapPattern = [];
srand($groupId);
for ($i = 0; $i < 70; $i++) {
    $heatmapPattern[] = rand(0, 4) > 2 ? rand(1, 4) : 0;
}

function heat_level($count) {
    if ($count <= 0) return 0;
    if ($count == 1) return 1;
    if ($count == 2) return 2;
    if ($count == 3) return 3;
    return 4;
}
?>
<!DOCTYPE html>
<html lang="en" data-mode="teacher">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Group <?php echo $groupId; ?> Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="syncspace.css">
</head>
<body class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="border-b border-ui bg-raised py-3 px-6 flex justify-between items-center sticky top-0 z-40">
        <div class="flex items-center gap-4">
            <a href="dashboard.php?classroom_id=<?php echo urlencode($classroom_id); ?>&demo_view=Teacher" class="text-muted-ui hover:text-accent transition mr-2" title="Back to Gradebook">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="p-2 rounded" style="background: var(--accent); border-radius: var(--radius);">
                <i class="fas fa-layer-group" style="color: #fff;"></i>
            </div>
            <h1 class="font-bold text-xl tracking-tight font-head">PMS</h1>
        </div>

        <div class="flex items-center gap-5 text-sm">
            <span class="text-xs font-semibold px-2 py-1 border border-ui rounded" style="color: var(--accent);"><span style="color: var(--bg);">Teacher Mode (Read-Only)</span></span>
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo $username; ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent); color: #fff;"><?php echo $initial; ?></div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="px-6 pt-5 pb-2 border-b border-ui">
        <h2 class="text-2xl font-head font-semibold">Group <?php echo $groupId; ?> — <?php echo htmlspecialchars($projectName); ?></h2>
        <p class="text-muted-ui text-sm mt-1">Guide: <?php echo htmlspecialchars($guideName); ?> &middot; Section B</p>
    </div>

    <!-- Main Workspace -->
    <div class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-4 gap-6">

        <!-- Left: Roster and Stats -->
        <div class="lg:col-span-3 flex flex-col h-full gap-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="card stat-tile tone-red p-4">
                    <div class="stat-num text-3xl"><?php echo $issueCount; ?></div>
                    <div class="text-xs text-muted-ui mt-1">open issues</div>
                </div>
                <div class="card stat-tile tone-green p-4">
                    <div class="stat-num text-3xl"><?php echo $onTrackCount; ?>/<?php echo count($teamRoster); ?></div>
                    <div class="text-xs text-muted-ui mt-1">students active</div>
                </div>
                <div class="card stat-tile tone-amber p-4">
                    <div class="stat-num text-3xl"><?php echo $avgProgress; ?>%</div>
                    <div class="text-xs text-muted-ui mt-1">average progress</div>
                </div>
                <div class="card stat-tile p-4">
                    <div class="stat-num text-3xl"><?php echo $Deadline; ?></div>
                    <div class="text-xs text-muted-ui mt-1">deadline</div>
                </div>
            </div>

            <div class="card p-5 flex-1">
                <h3 class="font-head font-semibold mb-4">Student Progress</h3>
                <div class="space-y-4">
                    <?php foreach ($teamRoster as $m):
                        $barColor = $m['status'] === 'red' ? 'var(--danger)' : ($m['status'] === 'amber' ? '#8a7f3f' : 'var(--accent)');
                    ?>
                    <div class="flex items-center gap-4 py-2 border-b border-ui last:border-0">
                        <span class="status-dot <?php echo $m['status']; ?>" aria-hidden="true"></span>
                        <div class="w-40 flex-none">
                            <div class="font-semibold text-sm"><?php echo htmlspecialchars($m['name']); ?></div>
                            <div class="text-xs text-muted-ui font-mono-ui"><?php echo htmlspecialchars($m['role']); ?></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm truncate mb-1">Working on: <span class="font-semibold"><?php echo htmlspecialchars($m['task']); ?></span></div>
                            <div class="roster-bar-track h-1.5 w-full">
                                <div class="roster-bar-fill h-full" style="width: <?php echo (int)$m['percent']; ?>%; background: <?php echo $barColor; ?>;"></div>
                            </div>
                        </div>
                        <div class="stat-num text-sm w-12 text-right flex-none"><?php echo (int)$m['percent']; ?>%</div>
                        <button onclick="alert('Logs for ' + this.dataset.name + ' are being exported...')" data-name="<?php echo htmlspecialchars($m['name']); ?>" class="ml-4 text-xs font-semibold px-3 py-1 border border-ui rounded hover:bg-black/5 transition">View Logs</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card p-5">
                <h3 class="font-head font-semibold mb-3 text-danger"><i class="fas fa-triangle-exclamation mr-2"></i>Pending Issues</h3>
                <?php if (count($pendingIssues) > 0): ?>
                <ul class="text-sm space-y-2">
                    <?php foreach ($pendingIssues as $e): ?>
                        <li class="flex items-center gap-2">
                            <span class="font-semibold"><?php echo htmlspecialchars($e['who']); ?>:</span>
                            <span class="text-muted-ui"><?php echo htmlspecialchars($e['issue']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="flex items-center gap-2 text-sm" style="color: var(--accent);">
                    <i class="fas fa-check-circle"></i>
                    <span>No pending issues. The team is progressing.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Analytics Sidebar -->
        <div class="flex flex-col gap-6">
            <div class="card p-5" style="border-top: 2px solid var(--accent);">
                <h3 class="font-semibold text-sm mb-4 font-head"><i class="fas fa-chart-pie me-2 text-accent"></i>Task Completion by Member</h3>
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
                                style="background: var(--accent); opacity: <?php echo $opacities[$level]; ?>;"
                                title="<?php echo $heatmapPattern[$w * 7 + $day] ?? 0; ?> tasks checked off"></div>
                            <?php endfor; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="card p-5 flex-1 flex flex-col">
                <h3 class="font-semibold text-sm mb-4 font-head"><i class="fas fa-satellite-dish me-2 text-accent"></i>Live Group Activity</h3>
                <div id="activity-feed" class="space-y-4 overflow-hidden relative flex-1 text-sm"></div>
            </div>
        </div>
    </div>

    <script>
        // Contribution chart
        const contribCanvas = document.getElementById('contributionChart');
        if (contribCanvas) {
            new Chart(contribCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($teamRoster, 'name')); ?>,
                    datasets: [{ 
                        data: <?php echo json_encode(array_column($teamRoster, 'percent')); ?>, 
                        backgroundColor: <?php
$colors = [];
foreach ($teamRoster as $m) {
    if ($m['status'] === 'red') $colors[] = '#a62639';
    elseif ($m['status'] === 'amber') $colors[] = '#8a7f3f';
    else $colors[] = '#3f6c51';
}
echo json_encode($colors);
?>, 
                        borderColor: '#eae7db', 
                        borderWidth: 2 
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '70%',
                    plugins: { legend: { position: 'bottom', labels: { color: '#6b6a5a', usePointStyle: true, boxWidth: 6 } } }
                }
            });
        }

        // Simulated live feed
                const feedEvents = <?php echo json_encode($feedEvents); ?>;
        const feedContainer = document.getElementById('activity-feed');
        feedEvents.forEach((evt) => {
            const el = document.createElement('div');
            el.className = "flex gap-3";
            el.innerHTML = `<div class="mt-1"><i class="fas ${evt.icon}"></i></div><div><p>${evt.text}</p><span class="text-xs text-muted-ui">${evt.time}</span></div>`;
            feedContainer.appendChild(el);
        });
    </script>
</body>
</html>

