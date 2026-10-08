<!DOCTYPE html>
<html lang="en" data-mode="<?php echo $modeSlug; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>PMS - <?php echo e($viewData['actualView']); ?> view</title>
    <script>
        // Apply saved theme before anything paints, so there's no flash
        // of light mode for users who've chosen dark. Manual toggle only —
        // no system-preference auto-detect.
        (function () {
            try {
                if (localStorage.getItem('pms-theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
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
                <div class="p-2 pms-brand-mark">
                    <i class="fas fa-layer-group" style="color: var(--bg);"></i>
                </div>
                <h1 class="font-bold text-xl tracking-tight font-head">PMS</h1>
            </a>
        </div>

        <div class="flex items-center gap-5 text-sm">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="theme-toggle-btn" aria-label="Toggle dark mode">
                <i id="themeToggleIcon" class="fas fa-moon"></i>
            </button>
            <div class="relative" id="notifWrap">
                <button id="notifBellBtn" type="button" class="relative text-muted-ui hover:text-accent transition" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span id="notifBadge" class="hidden absolute -top-2 -right-2 min-w-[16px] h-4 px-1 rounded-full text-[10px] font-bold flex items-center justify-center" style="background: var(--danger); color: #fff;">0</span>
                </button>
                <div id="notifPanel" class="hidden absolute right-0 mt-3 w-80 max-w-[90vw] card shadow-xl z-50" style="background: var(--bg-raised, var(--bg));">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-ui">
                        <span class="font-semibold text-sm font-head">Notifications</span>
                        <button id="notifReadAll" type="button" class="text-xs text-accent hover:underline">Mark all read</button>
                    </div>
                    <div id="notifList" class="max-h-96 overflow-y-auto">
                        <div class="p-4 text-sm text-muted-ui text-center">Loading...</div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo $username; ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent-2); color: var(--bg);"><?php echo $initial; ?></div>
            </div>
            <a href="logout.php" class="text-danger hover:opacity-80 transition ml-1" title="Log out"><i class="fas fa-sign-out-alt text-lg"></i></a>
        </div>
    </nav>

    <script src="assets/js/theme.js"></script>
    <script src="assets/js/notifications.js" defer></script>