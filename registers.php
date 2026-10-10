<?php
// registers.php - Student & Teacher Registration
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? 'student');
    $name = trim($_POST['name'] ?? '');
    $username = strtolower(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $identifier = strtoupper(trim($_POST['identifier'] ?? ''));

    if (!in_array($role, ['student', 'teacher'])) {
        $error = 'Invalid role selected.';
    } elseif (empty($name) || empty($username) || empty($password) || empty($identifier)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check uniqueness of username and identifier
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username is already taken. Please choose another.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE identifier = ? LIMIT 1");
            $stmt->execute([$identifier]);
            if ($stmt->fetch()) {
                $error = ($role === 'student' ? 'USN' : 'Staff ID') . ' is already registered.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insert = $pdo->prepare("INSERT INTO users (name, username, password, role, identifier) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$name, $username, $hashedPassword, $role, $identifier]);
                
                $newUserId = $pdo->lastInsertId();
                // Auto-login upon registration
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['role'] = $role;
                $_SESSION['name'] = $name;
                $_SESSION['username'] = $username;
                $_SESSION['identifier'] = $identifier;
                
                set_flash('success', 'Registration successful! Welcome to PMS.');
                header('Location: hub.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - PMS Academic Review System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-700 px-6 py-6 text-white text-center">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-600 rounded-xl mb-2 shadow-inner">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold tracking-tight">PMS Academic Portal</h1>
            <p class="text-xs text-indigo-200 mt-1">Project Review & Continuous Evaluation System</p>
        </div>

        <div class="p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 text-center">Create Your Account</h2>

            <?php if ($error): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg flex items-center space-x-2">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Role Selector Toggle -->
            <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-xl mb-5">
                <button type="button" id="tabStudent" onclick="selectRole('student')" class="py-2 text-xs font-semibold rounded-lg transition-all shadow-sm bg-white text-indigo-700">
                    Student (with USN)
                </button>
                <button type="button" id="tabTeacher" onclick="selectRole('teacher')" class="py-2 text-xs font-semibold rounded-lg transition-all text-slate-600 hover:text-slate-900">
                    Teacher (with Staff ID)
                </button>
            </div>

            <form action="registers.php" method="POST" class="space-y-4">
                <input type="hidden" name="role" id="roleInput" value="student">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Keerthana R" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div id="identifierContainer">
                    <label id="identifierLabel" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">University Seat Number (USN)</label>
                    <input type="text" name="identifier" id="identifierInput" required placeholder="e.g. 1MS21CS042" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 uppercase">
                    <p id="identifierHint" class="text-xs text-slate-500 mt-1">Official VTU / Autonomous Seat Number</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                    <input type="text" name="username" required placeholder="e.g. keerthana" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 lowercase">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                    <input type="password" name="password" required placeholder="At least 6 characters" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-md hover:shadow transition-all">
                    Register Account
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-200 text-center">
                <p class="text-xs text-slate-600">
                    Already registered? 
                    <a href="login.php" class="text-indigo-600 font-semibold hover:underline">Log in here</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function selectRole(role) {
            const roleInput = document.getElementById('roleInput');
            const tabStudent = document.getElementById('tabStudent');
            const tabTeacher = document.getElementById('tabTeacher');
            const idLabel = document.getElementById('identifierLabel');
            const idInput = document.getElementById('identifierInput');
            const idHint = document.getElementById('identifierHint');

            roleInput.value = role;

            if (role === 'student') {
                tabStudent.className = 'py-2 text-xs font-semibold rounded-lg transition-all shadow-sm bg-white text-indigo-700';
                tabTeacher.className = 'py-2 text-xs font-semibold rounded-lg transition-all text-slate-600 hover:text-slate-900';
                idLabel.textContent = 'University Seat Number (USN)';
                idInput.placeholder = 'e.g. 1MS21CS042';
                idHint.textContent = 'Official VTU / Autonomous Seat Number';
            } else {
                tabTeacher.className = 'py-2 text-xs font-semibold rounded-lg transition-all shadow-sm bg-white text-indigo-700';
                tabStudent.className = 'py-2 text-xs font-semibold rounded-lg transition-all text-slate-600 hover:text-slate-900';
                idLabel.textContent = 'Faculty Staff ID';
                idInput.placeholder = 'e.g. CS-FAC-101';
                idHint.textContent = 'Departmental Faculty ID / Employee Code';
            }
        }
    </script>
</body>
</html>
