<?php
/**
 * Shared request bootstrap for every root endpoint.
 *
 *   require_once 'bootstrap.php';
 *   require_login();            // or require_login(true) for JSON endpoints
 *   if (POST) { csrf_verify(); ... }
 *
 * Provides: hardened session, $pdo (via dbs.php), login guard, CSRF helpers.
 * Authorization (membership / role checks) lives in auth_guard.php.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly'  => true,
        // Lax (not Strict) so a shared dashboard/invite link opened from
        // WhatsApp or email still carries the session on first navigation.
        'cookie_samesite'  => 'Lax',
        'use_strict_mode'  => true,
        'use_only_cookies' => true,
    ]);
}

require_once __DIR__ . '/dbs.php';
require_once __DIR__ . '/auth_guard.php';

/**
 * Halt unless a user is signed in.
 * HTML endpoints are redirected to login; JSON endpoints get a 401 payload.
 */
function require_login(bool $json = false): void
{
    if (!empty($_SESSION['user_id'])) {
        return;
    }
    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not signed in', 'message' => 'Not signed in']);
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

/** Per-session CSRF token, generated lazily. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to drop inside every <form method="POST">. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Reject the request unless it carries the session's CSRF token, either as a
 * `csrf_token` form field or an `X-CSRF-Token` header (used by fetch()).
 */
function csrf_verify(bool $json = false): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $known = $_SESSION['csrf_token'] ?? '';

    if (is_string($sent) && $known !== '' && hash_equals($known, $sent)) {
        return;
    }

    http_response_code(403);
    $msg = 'Your session has expired or the request could not be verified. Please refresh the page and try again.';
    if ($json) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $msg, 'message' => $msg]);
    } else {
        echo htmlspecialchars($msg) . ' <a href="hub.php">Back to PMS</a>';
    }
    exit;
}
