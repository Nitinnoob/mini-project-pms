<?php
// index.php - Main Application Entrypoint
require_once __DIR__ . '/auth_guard.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
