<?php
/**
 * Shared request bootstrap for every root endpoint.
 *
 *   require_once 'bootstrap.php';
 *   require_login();
 *
 * Provides: session, $pdo (via dbs.php), login guard.
 * Authorization (membership / role checks) lives in auth_guard.php.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

/**
 * Halt unless a user is signed in.
 * HTML endpoints redirect to login; JSON endpoints get a 401 payload.
 */
function require_login(bool $json = false): void
{
    if (!empty($_SESSION['user_id'])) {
        return;
    }
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not signed in']);
        exit;
    }
    header("Location: login.php");
    exit;
}

/** True when the current request is a POST. */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** No-op CSRF helpers kept so existing form templates don't break. */
function csrf_token(): string  { return ''; }
function csrf_field(): string  { return ''; }
function csrf_verify(bool $json = false): void { /* no-op */ }
