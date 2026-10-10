<?php
// views/header.php - shared <head>, helpers, navigation and flash messages
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../dbs.php';

// ---------- Small helpers shared by all views ----------
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('safe_url')) {
    // Only plain http(s) links are ever rendered as clickable
    function safe_url($u) {
        $u = trim((string)$u);
        return (preg_match('#^https?://#i', $u) && filter_var($u, FILTER_VALIDATE_URL)) ? $u : '';
    }
}
if (!function_exists('status_tag')) {
    function status_tag($status) {
        return ['done' => 'tag-ok', 'acknowledged' => 'tag-info'][$status] ?? 'tag-warn';
    }
}
if (!function_exists('asset_type_label')) {
    function asset_type_label($type) {
        return ['screenshot' => 'Screenshot', 'photo' => 'Prototype photo', 'codebase' => 'Repository', 'demo_link' => 'Demo'][$type] ?? 'Asset';
    }
}
if (!function_exists('cie_total')) {
    function cie_total($report, $presentation, $qa) {
        if ($report === null && $presentation === null && $qa === null) return null;
        return (float)$report + (float)$presentation + (float)$qa;
    }
}
if (!function_exists('fmt_mark')) {
    function fmt_mark($v) { return $v === null ? '–' : number_format((float)$v, 1); }
}

// ---------- Current user & classroom ----------
$user = current_user();
$activeClassroom = null;
if (!empty($_SESSION['active_classroom_id']) && $user) {
    $cStmt = $pdo->prepare("SELECT name FROM classrooms WHERE id = ?");
    $cStmt->execute([$_SESSION['active_classroom_id']]);
    $activeClassroom = $cStmt->fetch();
}
$currentPage = basename($_SERVER['PHP_SELF']);
$navLinks = ['dashboard.php' => 'Dashboard', 'hub.php' => 'Classrooms'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'PMS - Academic Project Evaluation') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['"Public Sans"', 'system-ui', 'sans-serif'] } } } };
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .panel   { @apply bg-white border border-zinc-200 rounded-lg; }
            .btn     { @apply inline-flex items-center justify-center rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50; }
            .btn-primary { @apply border-teal-700 bg-teal-700 text-white hover:bg-teal-800 hover:border-teal-800; }
            .field   { @apply w-full rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm focus:outline-none focus:border-teal-600 focus:ring-1 focus:ring-teal-600; }
            .label   { @apply block mb-1 text-sm font-medium text-zinc-700; }
            .muted   { @apply text-sm text-zinc-500; }
            .tag     { @apply inline-block rounded px-1.5 py-0.5 text-xs font-medium; }
            .tag-ok   { @apply bg-emerald-100 text-emerald-800; }
            .tag-warn { @apply bg-amber-100 text-amber-800; }
            .tag-bad  { @apply bg-red-100 text-red-800; }
            .tag-info { @apply bg-teal-100 text-teal-800; }
            .tag-mute { @apply bg-zinc-100 text-zinc-600; }
            .tab     { @apply px-4 py-2 text-sm font-medium border-b-2 border-transparent text-zinc-500 hover:text-zinc-800; }
            .tab.active { @apply border-teal-700 text-teal-800; }
            .chip    { @apply rounded-full border border-zinc-300 bg-white px-3 py-1 text-xs font-medium text-zinc-600 hover:border-zinc-500; }
            .chip.active { @apply border-zinc-900 bg-zinc-900 text-white; }
        }
    </style>
    <style>
        .modal { display: none; position: fixed; inset: 0; z-index: 50; align-items: center; justify-content: center; padding: 1rem; background: rgba(24, 24, 27, .6); }
        .modal.open { display: flex; }
        @media print { .no-print { display: none !important; } }
    </style>
    <script>
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const openModal  = id => document.getElementById(id).classList.add('open');
        const closeModal = id => document.getElementById(id).classList.remove('open');
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(m => m.classList.remove('open'));
        });
    </script>
</head>
<body class="min-h-screen flex flex-col bg-zinc-50 font-sans text-zinc-800 antialiased">
    <header class="no-print sticky top-0 z-40 bg-white border-b border-zinc-200">
        <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-lg font-bold text-teal-800">PMS</a>
                <?php if ($activeClassroom): ?>
                    <span class="hidden md:inline text-sm text-zinc-500 border-l border-zinc-200 pl-4"><?= h($activeClassroom['name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-5">
                <nav class="flex items-center gap-4 text-sm font-medium">
                    <?php foreach ($navLinks as $href => $label): ?>
                        <a href="<?= $href ?>" class="<?= $currentPage === $href ? 'text-teal-800' : 'text-zinc-500 hover:text-zinc-800' ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </nav>
                <div class="flex items-center gap-3 border-l border-zinc-200 pl-5 text-sm">
                    <span class="hidden sm:block text-right leading-tight">
                        <span class="block font-medium"><?= h($user['name'] ?? 'User') ?></span>
                        <span class="block text-xs text-zinc-500"><?= h($user['identifier'] ?? '') ?> · <?= h(ucfirst($user['role'] ?? '')) ?></span>
                    </span>
                    <a href="logout.php" class="text-zinc-500 hover:text-zinc-800">Sign out</a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow">
        <?php
        $flashStyles = ['success' => 'bg-emerald-50 border-emerald-200 text-emerald-800', 'error' => 'bg-red-50 border-red-200 text-red-800'];
        foreach ($flashStyles as $type => $style):
            if ($message = get_flash($type)): ?>
                <div class="max-w-6xl mx-auto px-4 mt-4 no-print">
                    <div class="px-4 py-2.5 border rounded-md text-sm <?= $style ?>"><?= h($message) ?></div>
                </div>
            <?php endif;
        endforeach; ?>
