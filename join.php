<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require 'dbs.php';

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));
$error = '';
$successMessage = false;
$classroom_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['invite_code'] ?? '');
    $usn = trim(strtoupper($_POST['usn'] ?? ''));
    
    // Fetch classroom details including the requires_usn flag
    $stmt = $pdo->prepare("SELECT id, name, requires_usn FROM classrooms WHERE invite_code = ?");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $classroom_id = $row['id'];
        $requires_usn = $row['requires_usn'];
        
        // 1. Check USN Requirement
        if ($requires_usn == 1 && empty($usn)) {
            $error = 'This is an official classroom. You must enter your VTU USN to join.';
        } 
        // 2. Validate VTU USN Format if provided
        elseif (!empty($usn) && !preg_match('/^\d[A-Z]{2}\d{2}[A-Z]{2}\d{3}$/i', $usn)) {
            $error = 'Invalid USN format. Please use standard VTU format (e.g., 1RG24CS015).';
        } 
        else {
            // Check if already a member
            $stmtCheck = $pdo->prepare("SELECT role FROM classroom_members WHERE classroom_id = ? AND user_id = ?");
            $stmtCheck->execute([$classroom_id, $_SESSION['user_id']]);
            
            if (!$stmtCheck->fetch()) {
                // Insert into classroom_members WITH the scoped USN
                $stmtJoin = $pdo->prepare("INSERT INTO classroom_members (classroom_id, user_id, role, usn) VALUES (?, ?, 'Team Member', ?)");
                $stmtJoin->execute([$classroom_id, $_SESSION['user_id'], empty($usn) ? null : $usn]);
            }
            $successMessage = true;
        }
    } else {
        $error = 'Invalid invite code. Please check with your teacher.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-mode="teacher">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Join Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="syncspace.css">
</head>
<body class="min-h-screen flex flex-col">

    <nav class="border-b border-ui py-3 px-6 flex justify-between items-center" style="border-color: var(--border); background: var(--bg-raised);">
        <a href="hub.php" class="flex items-center gap-4 hover:opacity-80 transition">
            <div class="p-2 rounded" style="background: var(--accent); border-radius: var(--radius);">
                <i class="fas fa-layer-group" style="color: var(--bg);"></i>
            </div>
            <h1 class="font-bold text-xl tracking-tight">PMS</h1>
        </a>
    </nav>

    <div class="flex-1 flex items-center justify-center p-6">
        <div class="card w-full max-w-md p-8 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-raised" style="border: 1px solid var(--border);">
                <i class="fas fa-key text-2xl" style="color: var(--accent-2);"></i>
            </div>
            <h2 class="text-2xl font-bold mb-2">Join a Workspace</h2>
            <p class="text-sm mb-6" style="color: var(--muted);">Enter the invite code provided by your teacher or project leader to collaborate.</p>

            <style>@keyframes shake { 0%, 100% {transform: translateX(0);} 25% {transform: translateX(-5px);} 75% {transform: translateX(5px);} } .shake { animation: shake 0.3s ease-in-out; border-color: var(--danger) !important; }</style>
            <?php if ($error): ?>
                <div class="mb-4 p-3 rounded text-sm text-center" style="background: rgba(240, 68, 56, 0.1); border: 1px solid var(--danger); color: var(--danger);">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--muted);">Classroom Invite Code</label>
                    <input type="text" name="invite_code" value="<?php echo htmlspecialchars($code ?? ''); ?>" class="form-input code-input <?php echo $error ? 'shake' : ''; ?>" placeholder="XXXXXX" required maxlength="12">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--muted);">Your USN <span class="text-xs font-normal opacity-70">(Required for Official Classrooms)</span></label>
                    <input type="text" name="usn" class="form-input" placeholder="e.g. 1RG24CS015" style="width: 100%; padding: 0.75rem; border-radius: var(--radius); background: var(--bg); border: 1px solid var(--border); color: var(--text);">
                </div>
                
                <button type="submit" class="w-full py-3 font-semibold rounded hover:opacity-90 transition" style="background: var(--accent-2); color: var(--bg); border-radius: var(--radius);">
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
        <h2 class="text-3xl font-bold mb-2 font-head success-icon" style="animation-delay: 0.1s;">Joined Successfully!</h2>
        <p class="text-muted-ui success-icon" style="animation-delay: 0.2s;">Taking you to the workspace...</p>
    </div>

    <script>
        <?php if ($successMessage): ?>
        setTimeout(() => {
            window.location.href = 'dashboard.php?classroom_id=<?php echo urlencode($classroom_id); ?>';
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>

