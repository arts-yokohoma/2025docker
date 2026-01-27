<?php
/**
 * Authentication helper functions
 * 
 * Provides functions for checking user authentication and role-based access
 */

session_start();
require_once __DIR__ . '/../config/db.php';

/**
 * Check if user is logged in
 * Returns true if user is authenticated, false otherwise
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
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin role - redirects to login if not admin
 */
function requireAdmin(): void {
    requireAuth();
    if (!isAdmin()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Check if logged in user has one of the specified roles
 * @param array $allowedRoles Array of role names (e.g., ['admin', 'manager'])
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
 * Require one of the specified roles - redirects to login if user doesn't have any of them
 * @param array $allowedRoles Array of role names (e.g., ['admin', 'manager', 'driver'])
 */
function requireRoles(array $allowedRoles): void {
    requireAuth();
    if (!hasRole($allowedRoles)) {
        header('Location: login.php');
        exit;
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
