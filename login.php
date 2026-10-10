<?php
// login.php - Authentication Entrypoint
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$flashSuccess = get_flash('success');
$flashError = get_flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables per GEMINI.md section 2.B
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['role']       = $user['role']; // 'student' or 'teacher'
            $_SESSION['name']       = $user['name'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['identifier'] = $user['identifier']; // USN or Staff ID

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PMS Academic Review System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <!-- Brand Header -->
        <div class="bg-indigo-700 px-6 py-6 text-white text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-600 rounded-xl mb-2 shadow-inner">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold tracking-tight">Academic PMS</h1>
            <p class="text-xs text-indigo-200 mt-1">Continuous Project Evaluation & Review System</p>
        </div>

        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 text-center">Sign In to Your Workspace</h2>

            <?php if ($flashSuccess): ?>
                <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg flex items-center space-x-2">
                    <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span><?= htmlspecialchars($flashSuccess) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error || $flashError): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg flex items-center space-x-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span><?= htmlspecialchars($error ?: $flashError) ?></span>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                    <input type="text" id="usernameInput" name="username" required placeholder="Enter username" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" id="passwordInput" name="password" required placeholder="••••••••" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow transition-all">
                    Sign In
                </button>
            </form>

            <!-- Quick Demo Credentials Box for Instant Testing -->
            <div class="mt-6 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2 flex items-center justify-between">
                    <span>1-Click Demo Logins</span>
                    <span class="text-[10px] text-indigo-600 font-normal">Pre-seeded accounts</span>
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="fillLogin('roopa', 'password123')" class="px-2.5 py-1.5 bg-white border border-slate-300 hover:border-indigo-500 rounded-lg text-left text-xs transition-colors group">
                        <span class="block font-semibold text-slate-800 group-hover:text-indigo-600">Prof. Roopa</span>
                        <span class="block text-[10px] text-slate-500">Teacher (Staff ID)</span>
                    </button>
                    <button type="button" onclick="fillLogin('keerthana', 'password123')" class="px-2.5 py-1.5 bg-white border border-slate-300 hover:border-indigo-500 rounded-lg text-left text-xs transition-colors group">
                        <span class="block font-semibold text-slate-800 group-hover:text-indigo-600">Keerthana R</span>
                        <span class="block text-[10px] text-slate-500">Student (Leader)</span>
                    </button>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-200 text-center">
                <p class="text-xs text-slate-600">
                    New user? 
                    <a href="registers.php" class="text-indigo-600 font-semibold hover:underline">Register as Student or Teacher</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function fillLogin(username, password) {
            document.getElementById('usernameInput').value = username;
            document.getElementById('passwordInput').value = password;
        }
    </script>
</body>
</html>
