<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));
$groupId = $_GET['group'] ?? 23; // Default to Group 23 for demo

// Dummy Data for Group 23 (Section B)
$projectName = "Project management system";
$guideName = "Prof Nagalakshmi";
$teamRoster = [
    ['name' => 'Nithin gowda',   'role' => 'Backend / Database', 'status' => 'green', 'task' => 'Schema Design & User Auth', 'percent' => 95],
    ['name' => 'Ranjith kumar',  'role' => 'Frontend UI',        'status' => 'green', 'task' => 'Multi-tenant Dashboard',    'percent' => 88],
    ['name' => 'Vinutha H K',    'role' => 'QA / Testing',       'status' => 'amber', 'task' => 'Integration test scripts',  'percent' => 50],
    ['name' => 'Vinaya kumar',   'role' => 'DevOps',             'status' => 'red',   'task' => 'Server deployment config',  'percent' => 15],
];

$escalations = [
    ['who' => 'Vinaya kumar',  'issue' => 'Blocked on AWS credits, 2 days now', 'severity' => 'red'],
    ['who' => 'Vinutha H K',   'issue' => 'Needs review on test branch',        'severity' => 'amber'],
];

$blockerCount = count(array_filter($teamRoster, fn($m) => $m['status'] === 'red'));
$onTrackCount = count(array_filter($teamRoster, fn($m) => $m['status'] !== 'red'));
$avgVelocity = (int) round(array_sum(array_column($teamRoster, 'percent')) / count($teamRoster));
$daysToDeadline = 5;

$heatmapWeeks = 10;
$heatmapPattern = [0, 1, 3, 2, 4, 1, 0, 2, 3, 1, 0, 0, 2, 4, 3, 1, 0, 1, 2, 3, 4, 2, 0, 1, 3, 2, 1, 0, 0, 2, 3, 4, 1, 2, 0, 1, 3, 2, 4, 1, 0, 0, 2, 3, 1, 2, 4, 3, 1, 0, 2, 1, 0, 3, 2, 4, 1, 0, 2, 3, 1, 0, 4, 2, 3, 1, 0, 2, 1, 0];

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
    <style>
        :root {
            --bg: #e4e2d6;
            --bg-raised: #eae7db;
            --panel: #eae7db;
            --panel-alt: #ded9c9;
            --border: #c7c2ac;
            --text: #23261f;
            --muted: #6b6a5a;
            --accent: #3f6c51;
            --accent-2: #a62639;
            --danger: #a62639;
            --font-head: 'IBM Plex Serif', serif;
            --font-body: 'IBM Plex Sans', sans-serif;
            --font-mono: 'IBM Plex Mono', monospace;
            --radius: 2px;
        }

        body { background: var(--bg); color: var(--text); font-family: var(--font-body); }
        .font-head { font-family: var(--font-head); }
        .font-mono-ui { font-family: var(--font-mono); }
        .text-accent { color: var(--accent); }
        .text-accent-2 { color: var(--accent-2); }
        .text-danger { color: var(--danger); }
        .text-muted-ui { color: var(--muted); }
        .bg-panel { background: var(--panel); }
        .bg-raised { background: var(--bg-raised); }
        .border-ui { border-color: var(--border); }

        .card { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); }
        
        .status-dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex: none; }
        .status-dot.green { background: #3f6c51; }
        .status-dot.amber { background: #8a7f3f; }
        .status-dot.red   { background: #a62639; }

        .stat-tile { border-top: 2px solid var(--border); }
        .stat-tile.tone-red { border-top-color: var(--danger); }
        .stat-tile.tone-amber { border-top-color: #8a7f3f; }
        .stat-tile.tone-green { border-top-color: var(--accent); }
        .stat-num { font-family: var(--font-head); font-weight: 600; letter-spacing: -0.01em; }

        .roster-bar-track { background: rgba(0,0,0,.08); border-radius: 3px; overflow: hidden; }
        .roster-bar-fill { border-radius: inherit; }

        .heat-square { width: 11px; height: 11px; border-radius: 2px; }
        .heat-glow { animation: heatGlow 3s ease-in-out infinite; }
        @keyframes heatGlow { 0%, 100% { box-shadow: 0 0 2px rgba(166,38,57,.4); } 50% { box-shadow: 0 0 6px rgba(166,38,57,.9); } }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="border-b border-ui bg-raised py-3 px-6 flex justify-between items-center sticky top-0 z-40">
        <div class="flex items-center gap-4">
            <a href="dashboard.php?demo_view=Teacher" class="text-muted-ui hover:text-accent transition mr-2" title="Back to Gradebook">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div class="p-2 rounded" style="background: var(--accent); border-radius: var(--radius);">
                <i class="fas fa-layer-group" style="color: #fff;"></i>
            </div>
            <h1 class="font-bold text-xl tracking-tight font-head">PMS</h1>
        </div>

        <div class="flex items-center gap-5 text-sm">
            <span class="text-xs font-semibold px-2 py-1 border border-ui rounded" style="color: var(--accent);">Teacher Mode (Read-Only)</span>
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
                    <div class="stat-num text-3xl"><?php echo $blockerCount; ?></div>
                    <div class="text-xs text-muted-ui mt-1">blockers open</div>
                </div>
                <div class="card stat-tile tone-green p-4">
                    <div class="stat-num text-3xl"><?php echo $onTrackCount; ?>/<?php echo count($teamRoster); ?></div>
                    <div class="text-xs text-muted-ui mt-1">students on track</div>
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
                <h3 class="font-head font-semibold mb-4">Student Progress Roster</h3>
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
                        <button class="ml-4 text-xs font-semibold px-3 py-1 border border-ui rounded hover:bg-black/5 transition">View Logs</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card p-5">
                <h3 class="font-head font-semibold mb-3 text-danger"><i class="fas fa-triangle-exclamation mr-2"></i>Active Escalations</h3>
                <ul class="text-sm space-y-2">
                    <?php foreach ($escalations as $e): ?>
                        <li class="flex items-center gap-2">
                            <span class="font-semibold"><?php echo htmlspecialchars($e['who']); ?>:</span>
                            <span class="text-muted-ui"><?php echo htmlspecialchars($e['issue']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Right: Analytics Sidebar -->
        <div class="flex flex-col gap-6">
            <div class="card p-5" style="border-top: 2px solid var(--accent);">
                <h3 class="font-semibold text-sm mb-4 font-head"><i class="fas fa-chart-pie me-2 text-accent"></i>Contribution Balance</h3>
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
                    labels: ['Nithin', 'Ranjith', 'Vinutha', 'Vinaya'],
                    datasets: [{ 
                        data: [45, 30, 15, 10], 
                        backgroundColor: ['#3f6c51', '#5a8b6f', '#8a7f3f', '#a62639'], 
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
        const feedEvents = [
            { time: "1 hr ago", icon: "fa-upload text-accent", text: "<span class='font-semibold'>Nithin gowda</span> pushed 3 commits to <span class='font-mono'>main</span>" },
            { time: "3 hrs ago", icon: "fa-check text-accent", text: "<span class='font-semibold'>Ranjith kumar</span> completed task: Dashboard UI" },
            { time: "Yesterday", icon: "fa-triangle-exclamation text-danger", text: "<span class='font-semibold'>Vinaya kumar</span> opened a blocker on AWS hosting." }
        ];
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
