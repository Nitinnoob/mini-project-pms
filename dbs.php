<?php
// dbs.php - Clean PDO MySQL Connection
// Host: localhost | DB: pms | User: root | Password: ""

$host = 'localhost';
$db   = 'pms';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Setup Required - PMS</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-6 font-sans">
        <div class="max-w-lg w-full bg-white rounded-xl shadow-lg border border-red-200 p-8">
            <div class="flex items-center space-x-3 mb-4 text-red-600">
                <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h2 class="text-xl font-bold">Database Setup Required</h2>
            </div>
            <p class="text-slate-600 text-sm mb-4">
                The application could not connect to database <strong class="text-slate-800">pms</strong> on <code class="text-red-700 bg-red-50 px-1 py-0.5 rounded">localhost:3306</code>.
            </p>
            <div class="bg-red-50 border border-red-200 rounded p-3 text-xs text-red-800 font-mono mb-6 overflow-x-auto">
                <?= htmlspecialchars($e->getMessage()) ?>
            </div>
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-xs text-slate-700 space-y-2">
                <p class="font-semibold text-slate-900">How to resolve:</p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Start MySQL in your XAMPP Control Panel.</li>
                    <li>Open phpMyAdmin (<a href="http://localhost/phpmyadmin" target="_blank" class="text-indigo-600 underline">localhost/phpmyadmin</a>).</li>
                    <li>Import <code class="bg-white border px-1 rounded">schema.sql</code> and <code class="bg-white border px-1 rounded">demo_seed.sql</code>.</li>
                    <li>Refresh this page.</li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
