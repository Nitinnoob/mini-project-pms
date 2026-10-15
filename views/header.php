<!DOCTYPE html>
<html lang="en" class="dark" data-mode="<?php echo e($modeSlug ?? ''); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>PMS</title>
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="pms.css">
</head>

<body class="min-h-screen flex flex-col overflow-x-hidden">

    <!-- Success Toast Notification -->
    <div id="toast" class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 card border-l-4 px-6 py-3 shadow-2xl flex items-center gap-3" style="border-left-color: var(--accent);">
        <i class="fas fa-check-circle text-accent text-xl"></i>
        <div>
            <h4 class="font-bold text-sm font-head">Success</h4>
            <p class="text-xs text-muted-ui">Action completed.</p>
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
            <div class="flex items-center gap-2">
                <span class="font-semibold"><?php echo e($username ?? ''); ?></span>
                <div class="h-8 w-8 rounded-full flex items-center justify-center font-bold" style="background: var(--accent-2); color: var(--bg);"><?php echo e($initial ?? ''); ?></div>
            </div>
            <a href="logout.php" class="text-danger hover:opacity-80 transition ml-1" title="Log out"><i class="fas fa-sign-out-alt text-lg"></i></a>
        </div>
    </nav>