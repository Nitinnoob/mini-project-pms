<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'dbs.php';

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
    // Oracle fetches column names in uppercase by default
    $myWorkspaces[] = [
        'name' => $row['NAME'],
        'desc' => 'Workspace context',
        'members' => $row['MEMBER_COUNT'],
        'role' => $row['ROLE'],
        'link' => 'dashboard.php?classroom_id=' . urlencode($row['ID'])
    ];
}

?>
<!DOCTYPE html>
<html lang="en" data-mode="student">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="syncspace.css">
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

        <h3 class="text-lg font-semibold mb-4 border-b pb-2" style="border-color: var(--border);">Preview the UI</h3>
        <p class="text-sm mb-4" style="color: var(--muted);">For demo/presentation purposes — jump straight into either role's dashboard with sample data, no classroom or project setup required.</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <a href="dashboard.php?classroom_id=demo&demo_view=Student" class="card block p-6" style="border-style: dashed;">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 rounded-lg" style="background: var(--bg-raised);">
                        <i class="fas fa-user text-xl" style="color: var(--accent);"></i>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded" style="background: var(--bg-raised); color: var(--muted); border: 1px solid var(--border);">
                        Demo
                    </span>
                </div>
                <h4 class="text-xl font-bold mb-1">Student View</h4>
                <p class="text-sm" style="color: var(--muted);">Kanban board, calendar, and contribution tracker as a regular team member.</p>
            </a>
            <a href="dashboard.php?classroom_id=demo&demo_view=Project+Leader" class="card block p-6" style="border-style: dashed;">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 rounded-lg" style="background: var(--bg-raised);">
                        <i class="fas fa-user-tie text-xl" style="color: var(--accent);"></i>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded" style="background: var(--bg-raised); color: var(--muted); border: 1px solid var(--border);">
                        Demo
                    </span>
                </div>
                <h4 class="text-xl font-bold mb-1">Project Leader View</h4>
                <p class="text-sm" style="color: var(--muted);">Same board, plus pending requests, team roster, and invite management.</p>
            </a>
        </div>

        <h3 class="text-lg font-semibold mb-4 border-b pb-2" style="border-color: var(--border);">Your Classrooms</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($myWorkspaces as $ws): ?>
            <a href="<?php echo $ws['link']; ?>" class="card block p-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 rounded-lg" style="background: var(--bg-raised);">
                        <i class="fas fa-server text-xl" style="color: var(--accent);"></i>
                    </div>
                    <span class="text-xs font-semibold px-2 py-1 rounded" style="background: var(--bg-raised); color: var(--muted); border: 1px solid var(--border);">
                        <?php echo htmlspecialchars($ws['role']); ?>
                    </span>
                </div>
                <h4 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($ws['name']); ?></h4>
                <p class="text-sm mb-4" style="color: var(--muted);"><?php echo htmlspecialchars($ws['desc']); ?></p>
                <div class="text-xs font-semibold flex items-center gap-2" style="color: var(--muted);">
                    <i class="fas fa-users"></i> <?php echo $ws['members']; ?> members
                </div>
            </a>
            <?php endforeach; ?>
            <a href="join.php" class="card block p-6 opacity-70 hover:opacity-100 flex flex-col items-center justify-center text-center min-h-[200px]" style="border-style: dashed;">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3" style="background: var(--bg-raised);">
                    <i class="fas fa-plus text-xl" style="color: var(--muted);"></i>
                </div>
                <h4 class="font-bold mb-1">Join or Create</h4>
                <p class="text-sm" style="color: var(--muted);">Start a new workspace</p>
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
</body>
</html>