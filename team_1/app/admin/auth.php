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
 * Check if user is logged in (session has user_id)
 * Does not validate against DB — use requireAuth() / requireAdmin() for that
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Check if logged in user has admin role
 * Returns true if user is admin, false otherwise
 */
function isAdmin(): bool {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $mysqli;
    $userId = $_SESSION['user_id'];
    
    $stmt = $mysqli->prepare("
        SELECT r.name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ? AND u.active = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['name'] === 'admin';
    }
    
    return false;
}

/**
 * Require authentication - redirects to login if not logged in
 * Alias for requireLogin()
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require login - redirects to login if not logged in
 * Same as requireAuth() - more intuitive name
 */
function requireLogin(): void {
    requireAuth();
}

/**
 * Require admin role - redirects to login if not admin.
 * Clears session if user exists but is inactive or not admin (avoids redirect loop).
 */
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin()) {
        clearSessionAndRedirectToLogin();
    }
}

/**
 * Check if logged in user has one of the specified roles
 * @param array $allowedRoles Array of role names (e.g., ['admin', 'moderator'])
 * @return bool True if user has one of the allowed roles
 */
function hasRole(array $allowedRoles): bool {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $mysqli;
    $userId = $_SESSION['user_id'];
    
    // Create placeholders for IN clause
    $placeholders = str_repeat('?,', count($allowedRoles) - 1) . '?';
    
    $stmt = $mysqli->prepare("
        SELECT r.name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ? AND u.active = 1 AND r.name IN ($placeholders)
    ");
    
    $types = 'i' . str_repeat('s', count($allowedRoles));
    $params = array_merge([$userId], $allowedRoles);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * Require one of the specified roles - redirects to login if user doesn't have any of them.
 * Clears session when access denied (e.g. user deactivated) to avoid redirect loop.
 * @param array $allowedRoles Array of role names (e.g., ['admin', 'manager', 'driver'])
 */
function requireRoles(array $allowedRoles): void {
    requireAuth();
    if (!hasRole($allowedRoles)) {
        clearSessionAndRedirectToLogin();
    }
}

/**
 * Get current user info
 * Returns array with user data or null if not logged in
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $mysqli;
    $userId = $_SESSION['user_id'];
    
    $stmt = $mysqli->prepare("
        SELECT u.id, u.username, u.email, r.name as role_name
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ? AND u.active = 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc() ?: null;
}
