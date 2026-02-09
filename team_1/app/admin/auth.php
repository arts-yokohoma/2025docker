<?php
/**
 * Authentication helper functions
 * 
 * Provides functions for checking user authentication and role-based access.
 * All protected admin pages must require this file and call requireAdmin() or requireRoles().
 * Session is validated against DB (users + roles by name); inactive users are logged out.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

/**
 * Clear session and redirect to login (e.g. when user is inactive or access denied)
 */
function clearSessionAndRedirectToLogin(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Check if user is logged in (session has admin_id)
 * Does not validate against DB — use requireAuth() / requireRoles() for that
 */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['admin_id']);
}

/**
 * Get current user's role
 */
function getUserRole(): ?string {
    return $_SESSION['admin_role'] ?? null;
}

/**
 * Check if logged in user has admin role
 * Returns true if user is admin, false otherwise
 */
function isAdmin(): bool {
    return isLoggedIn() && getUserRole() === 'admin';
}

/**
 * Require authentication - redirects to login if not logged in
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require one of the specified roles - shows access denied if user doesn't have any of them
 * @param array $allowedRoles Array of role names (e.g., ['admin', 'manager', 'driver'])
 */
function requireRoles(array $allowedRoles): void {
    requireAuth();
    $userRole = getUserRole();
    if (!in_array($userRole, $allowedRoles)) {
        showAccessDenied();
    }
}

/**
 * Show access denied page and exit
 */
function showAccessDenied(): void {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>アクセス拒否</title>
        <style>
            body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
            .access-denied { text-align: center; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .access-denied h1 { color: #e53e3e; margin-bottom: 20px; }
            .access-denied p { color: #666; margin-bottom: 30px; }
            .access-denied a { display: inline-block; padding: 10px 20px; background: #3182ce; color: white; text-decoration: none; border-radius: 4px; }
            .access-denied a:hover { background: #2c5aa0; }
        </style>
    </head>
    <body>
        <div class="access-denied">
            <h1>🚫 アクセス拒否</h1>
            <p>このページにアクセスする権限がありません。</p>
            <a href="admin.php">管理パネルに戻る</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Require admin role - redirects to login if not admin
 */
function requireAdmin(): void {
    requireRoles(['admin']);
}
