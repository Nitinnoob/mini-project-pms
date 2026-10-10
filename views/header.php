<?php
// views/header.php - Global Header & Navigation
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../dbs.php';

$user = current_user();
$activeClassroomId = $_SESSION['active_classroom_id'] ?? null;
$activeClassroom = null;

if ($activeClassroomId && $user) {
    $cStmt = $pdo->prepare("SELECT * FROM classrooms WHERE id = ?");
    $cStmt->execute([$activeClassroomId]);
    $activeClassroom = $cStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'PMS - Academic Project Evaluation') ?></title>
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col antialiased">
    <!-- Top Navigation Bar -->
    <header class="bg-indigo-800 text-white shadow-md border-b border-indigo-900 no-print sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand / Logo -->
                <div class="flex items-center space-x-3">
                    <a href="dashboard.php" class="flex items-center space-x-2 font-bold text-lg tracking-tight group">
                        <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-inner group-hover:bg-indigo-500 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div>
                            <span class="block leading-none text-white font-extrabold tracking-wide">PMS</span>
                            <span class="text-[10px] text-indigo-300 font-medium tracking-normal">Academic Review Hub</span>
                        </div>
                    </a>

                    <?php if ($activeClassroom): ?>
                        <div class="hidden md:flex items-center ml-4 pl-4 border-l border-indigo-700 space-x-2">
                            <span class="text-xs bg-indigo-900/60 border border-indigo-600/40 px-2.5 py-1 rounded-md text-indigo-200">
                                <span class="font-semibold text-white"><?= htmlspecialchars($activeClassroom['name']) ?></span>
                                <span class="mx-1 text-indigo-400">&bull;</span>
                                <span class="font-mono text-indigo-300"><?= htmlspecialchars($activeClassroom['invite_code']) ?></span>
                            </span>
                            <a href="hub.php" class="text-xs text-indigo-300 hover:text-white underline ml-1">Switch</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navigation Links & User Menu -->
                <div class="flex items-center space-x-4">
                    <nav class="flex items-center space-x-2">
                        <a href="dashboard.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-indigo-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'bg-indigo-700 text-white' : 'text-indigo-100' ?>">
                            Dashboard
                        </a>
                        <a href="hub.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-indigo-700 transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'hub.php') ? 'bg-indigo-700 text-white' : 'text-indigo-100' ?>">
                            Classrooms
                        </a>
                    </nav>

                    <!-- User Profile Dropdown Badge -->
                    <div class="flex items-center pl-3 border-l border-indigo-700 space-x-3">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold leading-tight"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                            <div class="text-[10px] text-indigo-300 uppercase tracking-wider font-mono">
                                <?= htmlspecialchars($user['identifier'] ?? '') ?> 
                                (<?= ucfirst(htmlspecialchars($user['role'] ?? '')) ?>)
                            </div>
                        </div>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-500 text-white text-xs font-bold ring-2 ring-indigo-400/50">
                            <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                        </span>
                        <a href="logout.php" title="Sign out" class="text-indigo-300 hover:text-white p-1 rounded-md hover:bg-indigo-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="flex-grow">
        <?php if ($flashSuccess = get_flash('success')): ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 no-print">
                <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg flex items-center space-x-2">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($flashError = get_flash('error')): ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 no-print">
                <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg flex items-center space-x-2">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span><?= htmlspecialchars($flashError) ?></span>
                </div>
            </div>
        <?php endif; ?>
