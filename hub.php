<?php
require_once 'bootstrap.php';
require_login();

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));

// Fetch classrooms this user is a part of
$stmt = $pdo->prepare("
    SELECT c.id, c.name, cm.role,
           (SELECT COUNT(*) FROM classroom_members WHERE classroom_id = c.id) as member_count
    FROM classrooms c
    JOIN classroom_members cm ON c.id = cm.classroom_id
    WHERE cm.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$workspaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

$myWorkspaces = [];
foreach ($workspaces as $row) {
    $myWorkspaces[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'desc' => $row['role'] === 'Admin' ? 'Coordinator / Faculty Guide' : 'Student Classroom',
        'members' => $row['member_count'],
        'role' => $row['role'],
        'link' => 'dashboard.php?classroom_id=' . urlencode($row['id'])
    ];
}

?>
<!DOCTYPE html>
<html lang="en" data-mode="student">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Hub</title>
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="pms.css">
    <script>
        (function () {
            try {
                if (localStorage.getItem('pms-theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="border-b border-ui bg-raised py-3 px-6 flex justify-between items-center sticky top-0 z-40" style="border-color: var(--border); background: var(--bg-raised);">
        <div class="flex items-center gap-4">
            <div class="p-2 rounded" style="background: var(--accent); border-radius: var(--radius);">
                <i class="fas fa-layer-group" style="color: var(--bg);"></i>
            </div>
            <h1 class="font-bold text-xl tracking-tight">PMS</h1>
        </div>
        <div class="flex items-center gap-5 text-sm">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="theme-toggle-btn" aria-label="Toggle dark mode">
                <i id="themeToggleIcon" class="fas fa-moon"></i>
            </button>
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo $username; ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent-2); color: var(--bg);"><?php echo $initial; ?></div>
            </div>
            <a href="logout.php" class="text-muted-ui hover:text-white transition" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-1 p-8 max-w-6xl mx-auto w-full">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h2 class="text-3xl font-bold mb-1">Welcome back, <?php echo $username; ?></h2>
                <p style="color: var(--muted);">Select a classroom or create a new one to get started.</p>
            </div>
            <div class="flex gap-3">
                <a href="join.php" class="px-5 py-2 font-semibold text-sm transition" style="background: var(--bg-raised); border: 1px solid var(--border); border-radius: var(--radius); color: var(--text);">
                    <i class="fas fa-key mr-1"></i> Join Code
                </a>
                <a href="create_classroom.php" class="px-5 py-2 font-semibold text-sm transition" style="background: var(--accent-2); border-radius: var(--radius); color: var(--bg);">
                    <i class="fas fa-plus mr-1"></i> New Classroom
                </a>
            </div>
        </div>

        <h3 class="text-lg font-semibold mb-4 border-b pb-2" style="border-color: var(--border);">Your Classrooms</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $bannerGradients = [
                'linear-gradient(135deg, #1f6f5c, #134e40)',
                'linear-gradient(135deg, #c17817, #854f0a)',
                'linear-gradient(135deg, #2563eb, #1d4ed8)',
                'linear-gradient(135deg, #059669, #047857)',
                'linear-gradient(135deg, #7c3aed, #5b21b6)',
                'linear-gradient(135deg, #db2777, #9d174d)',
            ];
            foreach ($myWorkspaces as $ws):
                $gradient = $bannerGradients[abs(crc32((string)($ws['id'] ?? $ws['name']))) % count($bannerGradients)];
            ?>
            <a href="<?php echo $ws['link']; ?>" class="card block relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:shadow-xl flex flex-col">
                <div class="h-24 p-5 flex justify-between items-start text-white relative" style="background: <?php echo $gradient; ?>;">
                    <div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded uppercase tracking-wider" style="background: rgba(0,0,0,0.25);">
                            <?php echo htmlspecialchars($ws['role']); ?>
                        </span>
                        <h4 class="text-lg font-bold font-head mt-2 line-clamp-1 drop-shadow-sm text-white"><?php echo htmlspecialchars($ws['name']); ?></h4>
                    </div>
                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-graduation-cap text-sm"></i>
                    </div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <p class="text-xs mb-4 text-muted-ui"><?php echo htmlspecialchars($ws['desc']); ?></p>
                    <div class="text-xs font-semibold flex items-center gap-2 text-muted-ui pt-3 border-t border-ui">
                        <i class="fas fa-users text-accent"></i> <?php echo $ws['members']; ?> member<?php echo $ws['members'] != 1 ? 's' : ''; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <a href="join.php" class="card block p-6 opacity-75 hover:opacity-100 flex flex-col items-center justify-center text-center min-h-[180px] transition-all duration-200 hover:-translate-y-1 hover:shadow-md" style="border-style: dashed;">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3" style="background: var(--bg-raised);">
                    <i class="fas fa-plus text-xl text-accent"></i>
                </div>
                <h4 class="font-bold mb-1 font-head">Join or Create</h4>
                <p class="text-xs text-muted-ui">Enroll with code or launch a section</p>
            </a>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 px-6 py-4 rounded text-sm font-semibold shadow-lg text-white pointer-events-none transition-all duration-300 transform translate-y-8 opacity-0 z-50 flex items-center gap-3" style="background: var(--panel); border: 1px solid var(--border);">
        <i class="fas fa-info-circle" style="color: var(--accent-2);"></i>
        <span id="toast-msg"></span>
    </div>

    <script>
        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = msg;
            toast.classList.remove('translate-y-8', 'opacity-0');
            setTimeout(() => toast.classList.add('translate-y-8', 'opacity-0'), 3000);
        }
    </script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
