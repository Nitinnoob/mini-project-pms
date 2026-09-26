<!DOCTYPE html>
<html lang="en" data-mode="<?php echo $modeSlug; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PMS - <?php echo htmlspecialchars($viewData['actualView']); ?> view</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Sans+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Serif:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="syncspace.css">
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
                <div class="p-2 rounded" style="background: var(--accent); border-radius: var(--radius);">
                    <i class="fas fa-layer-group" style="color: var(--bg);"></i>
                </div>
                <h1 class="font-bold text-xl tracking-tight font-head">PMS</h1>
            </a>
        </div>

        <div class="flex items-center gap-5 text-sm">
            <button class="text-muted-ui hover:text-accent transition" aria-label="Notifications"><i class="fas fa-bell"></i></button>
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo $username; ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent-2); color: var(--bg);"><?php echo $initial; ?></div>
            </div>
            <a href="logout.php" class="text-danger hover:opacity-80 transition ml-1" title="Log out"><i class="fas fa-sign-out-alt text-lg"></i></a>
        </div>
    </nav>

