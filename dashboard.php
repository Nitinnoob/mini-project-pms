<?php
// dashboard.php - Role-Based Router Gateway
require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

require_login();
$user = current_user();

if ($user['role'] === 'teacher') {
    include __DIR__ . '/views/teacher.php';
    exit;
} elseif ($user['role'] === 'student') {
    include __DIR__ . '/views/student.php';
    exit;
} else {
    // Unexpected role
    session_destroy();
    header('Location: login.php');
    exit;
}
