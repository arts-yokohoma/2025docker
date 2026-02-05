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
 * Require one of the specified roles - redirects to login if user doesn't have any of them
 * @param array $allowedRoles Array of role names (e.g., ['admin', 'manager', 'delivery'])
 */
function requireRoles(array $allowedRoles): void {
    requireAuth();
    $userRole = getUserRole();
    if (!in_array($userRole, $allowedRoles)) {
        clearSessionAndRedirectToLogin();
    }
}

/**
 * Require admin role - redirects to login if not admin
 */
function requireAdmin(): void {
    requireRoles(['admin']);
}
