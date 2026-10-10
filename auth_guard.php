<?php
// auth_guard.php - Session Management & Role Verification

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user details
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'         => $_SESSION['user_id'],
        'role'       => $_SESSION['role'] ?? '',
        'name'       => $_SESSION['name'] ?? '',
        'username'   => $_SESSION['username'] ?? '',
        'identifier' => $_SESSION['identifier'] ?? '',
    ];
}

/**
 * Check if the logged in user is a teacher
 */
function is_teacher(): bool {
    return is_logged_in() && ($_SESSION['role'] === 'teacher');
}

/**
 * Check if the logged in user is a student
 */
function is_student(): bool {
    return is_logged_in() && ($_SESSION['role'] === 'student');
}

/**
 * Require user to be logged in
 */
function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: login.php');
        exit;
    }
}

/**
 * Require teacher privileges
 */
function require_teacher(): void {
    require_login();
    if (!is_teacher()) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>403 Access Denied</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
            <div class="bg-white p-8 rounded-xl shadow border border-red-200 max-w-md w-full text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-red-100 text-red-600 rounded-full mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h1 class="text-xl font-bold text-slate-800 mb-2">Access Denied</h1>
                <p class="text-sm text-slate-600 mb-6">Teacher privileges are required to perform this action.</p>
                <a href="dashboard.php" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Return to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Require student privileges
 */
function require_student(): void {
    require_login();
    if (!is_student()) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>403 Access Denied</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
            <div class="bg-white p-8 rounded-xl shadow border border-red-200 max-w-md w-full text-center">
                <h1 class="text-xl font-bold text-slate-800 mb-2">Student Access Only</h1>
                <p class="text-sm text-slate-600 mb-6">This section is designated for registered students.</p>
                <a href="dashboard.php" class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Return to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * CSRF Protection
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Flash messaging helpers
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_' . $type] = $message;
}

function get_flash(string $type): ?string {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}
