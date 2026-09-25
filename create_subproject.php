<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';

$classroom_id = $_GET['classroom_id'] ?? null;
if (!$classroom_id) {
    header("Location: hub.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['project_name'] ?? '');
    $desc = trim($_POST['project_desc'] ?? '');
    
    if ($name) {
        try {
            $pdo->beginTransaction();
            
            // Insert project
            $stmt = $pdo->prepare("INSERT INTO projects (classroom_id, name, description, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$classroom_id, $name, $desc, $_SESSION['user_id']]);
            
            // Fetch the ID of the project just created
            $project_id = $pdo->lastInsertId();
            
            // Insert creator as Leader
            $stmt3 = $pdo->prepare("INSERT INTO project_members (project_id, user_id, is_leader, join_status) VALUES (?, ?, 1, 'Active')");
            $stmt3->execute([$project_id, $_SESSION['user_id']]);
            
            $pdo->commit();
            header("Location: dashboard.php?classroom_id=" . urlencode($classroom_id));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "An error occurred while creating the project.";
        }
    } else {
        $error = "Project name is required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mode="student">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Start a Project</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="syncspace.css">
</head>
<body class="min-h-screen flex flex-col">

    <!-- Navbar -->
    <nav class="border-b border-ui bg-raised py-3 px-6 flex justify-between items-center sticky top-0 z-40">
        <a href="dashboard.php?classroom_id=<?php echo urlencode($classroom_id); ?>" class="flex items-center gap-4 transition hover:opacity-80">
            <div class="p-2 rounded bg-accent text-bg">
                <i class="fas fa-layer-group"></i>
            </div>
            <h1 class="font-bold text-xl tracking-tight font-head">PMS</h1>
        </a>
        <div class="flex items-center gap-5 text-sm">
            <span class="font-semibold"><?php echo $username; ?></span>
            <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold text-bg bg-accent-2"><?php echo $initial; ?></div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="card w-full max-w-xl p-8">
            <div class="mb-8 border-b border-ui pb-4">
                <h2 class="text-2xl font-bold font-head">Start a Project Team</h2>
                <p class="text-sm text-muted-ui mt-1">Initialize a new project in this classroom. You will automatically become the Project Leader.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-900/20 border border-red-500 text-red-500 px-4 py-3 rounded mb-6 text-sm">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold mb-2">Project Name</label>
                    <input type="text" name="project_name" class="form-input w-full" placeholder="e.g. AI-Powered Analytics" required>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2">Project Description (Optional)</label>
                    <textarea name="project_desc" class="form-input w-full h-24" placeholder="Briefly describe what your team is building..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-ui mt-8">
                    <a href="dashboard.php?classroom_id=<?php echo urlencode($classroom_id); ?>" class="px-6 py-2 text-sm font-semibold text-muted-ui hover:text-white transition">Cancel</a>
                    <button type="submit" class="btn-ui px-6 py-2 text-sm font-semibold bg-accent-2 text-bg hover:opacity-90">
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
