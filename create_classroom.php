<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';

$modeSlug = 'teacher'; // Teacher theme for classroom creation
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));

$successMessage = false;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['classroom_name'] ?? 'New Classroom');
    $user_role = 'Admin'; // Creators of classrooms are automatically Admins (HOD/Teacher)
    
    // Check if a classroom with this name already exists for this user
    $stmtCheck = $pdo->prepare("SELECT id FROM classrooms WHERE name = ? AND created_by = ?");
    $stmtCheck->execute([$title, $_SESSION['user_id']]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    if ($existing) {
        $errorMessage = "You have already created a classroom named '" . htmlspecialchars($title) . "'.";
        $existingClassroomId = $existing['id'];
    } elseif ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
        $errorMessage = "The project end date cannot be earlier than the start date.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $requires_usn = isset($_POST['requires_usn']) ? 1 : 0;
            $min_size = isset($_POST['min_team_size']) ? max(1, (int)$_POST['min_team_size']) : 1;
            $max_size = isset($_POST['max_team_size']) ? max($min_size, (int)$_POST['max_team_size']) : 10;
            
            $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charsLen = strlen($chars);
            $inserted = false;
            $maxRetries = 5;

            for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
                $invite_code = '';
                for ($i = 0; $i < 6; $i++) {
                    $invite_code .= $chars[random_int(0, $charsLen - 1)];
                }

                // Verify uniqueness before insert
                $stmtCheckCode = $pdo->prepare("SELECT 1 FROM classrooms WHERE invite_code = ?");
                $stmtCheckCode->execute([$invite_code]);
                if (!$stmtCheckCode->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO classrooms (name, created_by, invite_code, requires_usn, min_team_size, max_team_size, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $_SESSION['user_id'], $invite_code, $requires_usn, $min_size, $max_size, $start_date, $end_date]);
                    $inserted = true;
                    break;
                }
            }

            if (!$inserted) {
                throw new Exception("Unable to generate unique invite code.");
            }
            
            $classroom_id = $pdo->lastInsertId();
            
            // Insert creator into classroom_members as Admin
            $stmt3 = $pdo->prepare("INSERT INTO classroom_members (classroom_id, user_id, role) VALUES (?, ?, ?)");
            $stmt3->execute([$classroom_id, $_SESSION['user_id'], $user_role]);
            
            $pdo->commit();
            $successMessage = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMessage = "Database error: Could not create classroom.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mode="<?php echo $modeSlug; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Create Classroom</title>
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
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
    <nav class="border-b border-ui bg-raised py-3 px-6 flex justify-between items-center sticky top-0 z-40">
        <a href="dashboard.php" class="flex items-center gap-4 hover:opacity-80 transition">
            <div class="p-2 rounded" style="background: var(--accent); border-radius: var(--radius);">
                <i class="fas fa-layer-group" style="color: var(--bg);"></i>
            </div>
            <h1 class="font-bold text-xl tracking-tight" style="font-family: var(--font-head);">PMS</h1>
        </a>
        <div class="flex items-center gap-5 text-sm">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="theme-toggle-btn" aria-label="Toggle dark mode">
                <i id="themeToggleIcon" class="fas fa-moon"></i>
            </button>
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo $username; ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent-2); color: var(--bg);"><?php echo $initial; ?></div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="card w-full max-w-2xl p-8">
            <div class="mb-8 border-b border-ui pb-4">
                <h2 class="text-2xl font-bold" style="font-family: var(--font-head);">Create New Classroom</h2>
                <p class="text-sm" style="color: var(--muted);">Set up a workspace for your entire class (e.g., Section B).</p>
            </div>

            <?php if ($errorMessage): ?>
            <div class="mb-6 p-4 rounded text-sm font-semibold border" style="background: rgba(240, 68, 56, 0.1); color: var(--danger); border-color: var(--danger);">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $errorMessage; ?>
            </div>
            <?php endif; ?>

            <form id="createForm" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold mb-2">Classroom Name</label>
                    <input type="text" name="classroom_name" class="form-input" placeholder="e.g. 5th Sem CS Mini-Projects" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Min Team Size</label>
                        <input type="number" name="min_team_size" class="form-input" value="1" min="1" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Max Team Size</label>
                        <input type="number" name="max_team_size" class="form-input" value="10" min="1" required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Project Start Date</label>
                        <input type="date" name="start_date" class="form-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Project End Date</label>
                        <input type="date" name="end_date" class="form-input" required>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="requires_usn" name="requires_usn" class="w-4 h-4" style="accent-color: var(--accent-2);">
                    <label for="requires_usn" class="text-sm font-semibold cursor-pointer">Require students to provide a valid VTU USN to join</label>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-ui mt-8">
                    <a href="dashboard.php" class="px-6 py-2 text-sm font-semibold transition" style="color: var(--muted);">Cancel</a>
                    <button type="submit" class="px-6 py-2 text-sm font-semibold rounded hover:opacity-90 transition" style="background: var(--accent-2); color: var(--bg); border-radius: var(--radius);">
                        Create Classroom
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Overlay Animation -->
    <div id="successOverlay" class="success-overlay <?php echo $successMessage ? 'show' : ''; ?>">
        <i class="fas fa-check-circle text-6xl mb-6 success-icon" style="color: var(--accent-2);"></i>
        <h2 class="text-3xl font-bold mb-2 font-head success-icon" style="animation-delay: 0.1s;">Classroom Created!</h2>
        <p class="text-xl font-mono-ui success-icon mb-4" style="color: var(--accent); animation-delay: 0.15s;">Invite Code: <?php echo htmlspecialchars($invite_code ?? ''); ?></p>
        <p class="text-muted-ui success-icon" style="animation-delay: 0.2s;">Redirecting to your new dashboard...</p>
    </div>

    <script>
        <?php if ($successMessage): ?>
        // Simulate redirect after success animation
        setTimeout(() => {
            window.location.href = 'dashboard.php?classroom_id=<?php echo urlencode($classroom_id); ?>';
        }, 5000);
        <?php endif; ?>
    </script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
