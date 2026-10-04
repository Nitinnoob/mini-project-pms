<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$username = htmlspecialchars($_SESSION['username'] ?? 'User');
$initial = strtoupper(substr($username, 0, 1));

// Simulating form submission
$successMessage = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In production, insert into SQLite DB here.
    $successMessage = true;
}
?>
<!DOCTYPE html>
<html lang="en" data-mode="student">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS — Create Workspace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #12151d;
            --bg-raised: #171b26;
            --panel: #1a1f2b;
            --border: #2a3040;
            --text: #e7e9ee;
            --muted: #838ba0;
            --accent: #f2a93b;
            --accent-2: #45d0c3;
            --font-head: 'IBM Plex Sans', sans-serif;
            --font-body: 'IBM Plex Sans', sans-serif;
            --font-mono: 'IBM Plex Mono', monospace;
            --radius: 8px;
        }
        body { background: var(--bg); color: var(--text); font-family: var(--font-body); }
        .bg-panel { background: var(--panel); }
        .bg-raised { background: var(--bg-raised); }
        .border-ui { border-color: var(--border); }
        .card { background: var(--panel); border: 1px solid var(--border); border-radius: var(--radius); }
        
        .form-input {
            width: 100%;
            background: var(--bg-raised);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: var(--radius);
            padding: 0.75rem 1rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            border-color: var(--accent-2);
        }
        
        /* Success Animation */
        .success-overlay {
            position: fixed; inset: 0; background: var(--bg); z-index: 100;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .success-overlay.show { opacity: 1; pointer-events: auto; }
        
        @keyframes scaleIn {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-icon { animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
    </style>
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
                <h2 class="text-2xl font-bold" style="font-family: var(--font-head);">Create New Workspace</h2>
                <p class="text-sm" style="color: var(--muted);">Setup a new mini-project group for Section B.</p>
            </div>

            <form id="createForm" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold mb-2">Project Title</label>
                    <input type="text" name="project_title" class="form-input" placeholder="e.g. AI-Powered Terminal" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Assign Guide</label>
                        <select name="guide_name" class="form-input" required>
                            <option value="">-- Select Section B Guide --</option>
                            <option value="Prof Nanda Kumar">Prof Nanda Kumar</option>
                            <option value="Prof Roopa">Prof Roopa</option>
                            <option value="Prof Nagalakshmi">Prof Nagalakshmi</option>
                            <option value="Prof Meghashree">Prof Meghashree</option>
                            <option value="Prof Soniya Komal">Prof Soniya Komal</option>
                            <option value="Dr Latha P H">Dr Latha P H</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Role in Project</label>
                        <select name="user_role" class="form-input" required>
                            <option value="Project Leader">Project Leader</option>
                            <option value="Member">Team Member</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="block text-sm font-semibold mb-2">Invite Team Members (USNs)</label>
                    <p class="text-xs mb-2" style="color: var(--muted);">Type comma-separated USNs (e.g. 1RG24CS109, 1RG24CS097)</p>
                    <textarea name="team_invites" class="form-input h-24" placeholder="1RG24CS..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-6 border-t border-ui mt-8">
                    <a href="dashboard.php" class="px-6 py-2 text-sm font-semibold transition" style="color: var(--muted);">Cancel</a>
                    <button type="submit" class="px-6 py-2 text-sm font-semibold rounded hover:opacity-90 transition" style="background: var(--accent-2); color: var(--bg); border-radius: var(--radius);">
                        Create Workspace
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Overlay Animation -->
    <div id="successOverlay" class="success-overlay <?php echo $successMessage ? 'show' : ''; ?>">
        <i class="fas fa-check-circle text-6xl mb-6 success-icon" style="color: var(--accent-2);"></i>
        <h2 class="text-3xl font-bold mb-2 font-head success-icon" style="animation-delay: 0.1s;">Workspace Created!</h2>
        <p class="text-muted-ui success-icon" style="animation-delay: 0.2s;">Redirecting to your new dashboard...</p>
    </div>

    <script>
        <?php if ($successMessage): ?>
        // Simulate redirect after success animation
        setTimeout(() => {
            window.location.href = 'dashboard.php?demo_view=Project+Leader';
        }, 2500);
        <?php endif; ?>
    </script>
</body>
</html>
