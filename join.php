<?php
require_once 'bootstrap.php';
require_login();

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));
$error = '';
$successMessage = false;
$classroom_id = null;

if (is_post()) {
    csrf_verify();
    $code = trim($_POST['invite_code'] ?? '');
    $usn = trim(strtoupper($_POST['usn'] ?? ''));
    
    // Fetch classroom details including the requires_usn flag
    $stmt = $pdo->prepare("SELECT id, name, requires_usn FROM classrooms WHERE invite_code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $classroom_id = $row['id'];
        $requires_usn = $row['requires_usn'];

        // Check membership FIRST — someone who already belongs to this
        // classroom (most notably the teacher who created it, already an
        // Admin member) shouldn't be forced through USN validation or shown
        // a "Joined Successfully!" as if this were a fresh join.
        $existingRole = classroom_role($pdo, $classroom_id, $_SESSION['user_id']);

        if ($existingRole !== null) {
            $alreadyMember = true;
            $successMessage = true; // send them into the workspace, just don't claim a fresh join
        }
        // 1. Check USN Requirement
        elseif ($requires_usn == 1 && empty($usn)) {
            $error = 'This is an official classroom. You must enter your VTU USN to join.';
        } 
        // 2. Validate VTU USN Format if provided
        elseif (!empty($usn) && !preg_match('/^\d[A-Z]{2}\d{2}[A-Z]{2}\d{3}$/i', $usn)) {
            $error = 'Invalid USN format. Please use standard VTU format (e.g., 1RG24CS015).';
        } 
        else {
            $stmtJoin = $pdo->prepare("INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (?, ?, 'Team Member', ?)");
            $stmtJoin->execute([$classroom_id, $_SESSION['user_id'], empty($usn) ? null : $usn]);
            $successMessage = true;
        }
    } else {
        $error = 'Invalid invite code. Please check with your teacher.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mode="student">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Join Workspace</title>
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

    <nav class="border-b border-ui bg-raised py-3 px-6 flex justify-between items-center" style="border-color: var(--border); background: var(--bg-raised);">
        <a href="hub.php" class="flex items-center gap-4 hover:opacity-80 transition">
            <div class="p-2 pms-brand-mark">
                <i class="fas fa-layer-group" style="color: var(--bg);"></i>
            </div>
            <h1 class="font-bold text-xl tracking-tight">PMS</h1>
        </a>
        <div class="flex items-center gap-5 text-sm">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="theme-toggle-btn" aria-label="Toggle dark mode">
                <i id="themeToggleIcon" class="fas fa-moon"></i>
            </button>
        </div>
    </nav>

    <div class="flex-1 flex items-center justify-center p-6">
        <div class="card w-full max-w-md p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-raised" style="border: 1px solid var(--border);">
                <i class="fas fa-key text-2xl" style="color: var(--accent-2);"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">Join a Workspace</h2>
            <p class="text-sm mb-6" style="color: var(--muted);">Enter the invite code provided by your teacher or project leader to collaborate.</p>

            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded text-sm text-center" style="background: rgba(240, 68, 56, 0.1); border: 1px solid var(--danger); color: var(--danger);">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--muted);">Classroom Invite Code</label>
                    <input type="text" name="invite_code" value="<?php echo htmlspecialchars($code ?? ''); ?>" class="form-input code-input <?php echo $error ? 'shake' : ''; ?>" placeholder="XXXXXX" required maxlength="12">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--muted);">Your USN <span class="text-xs font-normal opacity-70">(Required for Official Classrooms)</span></label>
                    <input type="text" name="usn" class="form-input pms-input" placeholder="e.g. 1RG24CS015">
                </div>
                
                <button type="submit" class="w-full py-3 font-semibold pms-btn-primary hover:opacity-90 transition">
                    Join Workspace
                </button>
            </form>
            
            <div class="mt-6 pt-4 border-t" style="border-color: var(--border);">
                <a href="hub.php" class="text-sm hover:underline" style="color: var(--muted);">Return to Hub</a>
            </div>
        </div>
    </div>

    <!-- Success Overlay -->
    <div id="successOverlay" class="success-overlay <?php echo $successMessage ? 'show' : ''; ?>">
        <i class="fas fa-check-circle text-6xl mb-6 success-icon" style="color: var(--accent-2);"></i>
        <?php if (!empty($alreadyMember)): ?>
            <h2 class="text-3xl font-bold mb-2 font-head success-icon" style="animation-delay: 0.1s;">Already in this classroom</h2>
            <p class="text-muted-ui success-icon" style="animation-delay: 0.2s;">
                You're already a member here as <?php echo htmlspecialchars($existingRole ?? 'a member'); ?>. Taking you back to it...
            </p>
        <?php else: ?>
            <h2 class="text-3xl font-bold mb-2 font-head success-icon" style="animation-delay: 0.1s;">Joined Successfully!</h2>
            <p class="text-muted-ui success-icon" style="animation-delay: 0.2s;">Taking you to the workspace...</p>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($successMessage): ?>
        setTimeout(() => {
            window.location.href = 'dashboard.php?classroom_id=<?php echo urlencode($classroom_id); ?>';
        }, 2000);
        <?php endif; ?>
    </script>
    <script src="assets/js/theme.js"></script>
</body>
</html>