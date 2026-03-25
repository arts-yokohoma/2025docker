<?php
/**
 * User deletion handler
 * 
 * Security rules:
 * - Only admin role can access this page
 * - Cannot delete users with admin role (protects all admins)
 * - Cannot delete yourself (double protection)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

// Only admin can delete users
requireAdmin();

$currentUserId = $_SESSION['admin_id'] ?? null;
$deleteId = (int)($_GET['id'] ?? 0);

// Validate delete ID
if ($deleteId <= 0) {
    $_SESSION['flash_error'] = '無効なユーザーIDです。';
    header('Location: user.php');
    exit;
}

// Prevent self-deletion
if ($deleteId === $currentUserId) {
    $_SESSION['flash_error'] = '自分自身を削除することはできません。';
    header('Location: user.php');
    exit;
}

// Get user info with role
$stmt = $mysqli->prepare("
    SELECT u.id, u.username, r.name AS role
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
");

if (!$stmt) {
    $_SESSION['flash_error'] = 'データベースエラー: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
    header('Location: user.php');
    exit;
}

$stmt->bind_param('i', $deleteId);
$stmt->execute();
$result = $stmt->get_result();
$userToDelete = $result->fetch_assoc();
$stmt->close();

// Check if user exists
if (!$userToDelete) {
    $_SESSION['flash_error'] = '指定されたユーザーが見つかりません。';
    header('Location: user.php');
    exit;
}

// CRITICAL: Prevent deletion of admin role users
if ($userToDelete['role'] === 'admin') {
    $_SESSION['flash_error'] = '管理者ユーザーは削除できません。セキュリティ保護のため、管理者の削除は禁止されています。';
    header('Location: user.php');
    exit;
}

// Check if user has any shift assignments
$checkShifts = $mysqli->prepare("SELECT COUNT(*) as shift_count FROM shifts WHERE user_id = ?");
if ($checkShifts) {
    $checkShifts->bind_param('i', $deleteId);
    $checkShifts->execute();
    $shiftResult = $checkShifts->get_result();
    $shiftData = $shiftResult->fetch_assoc();
    $checkShifts->close();
    
    if ($shiftData && $shiftData['shift_count'] > 0) {
        // Delete associated shifts first (CASCADE should handle this, but being explicit)
        $deleteShifts = $mysqli->prepare("DELETE FROM shifts WHERE user_id = ?");
        if ($deleteShifts) {
            $deleteShifts->bind_param('i', $deleteId);
            $deleteShifts->execute();
            $deleteShifts->close();
        }
    }
}

// Delete the user
$deleteStmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
if (!$deleteStmt) {
    $_SESSION['flash_error'] = 'データベースエラー: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
    header('Location: user.php');
    exit;
}

$deleteStmt->bind_param('i', $deleteId);

if ($deleteStmt->execute()) {
    if ($deleteStmt->affected_rows > 0) {
        $_SESSION['flash_success'] = 'ユーザー「' . htmlspecialchars($userToDelete['username'], ENT_QUOTES, 'UTF-8') . '」を削除しました。';
    } else {
        $_SESSION['flash_error'] = 'ユーザーの削除に失敗しました。';
    }
} else {
    $_SESSION['flash_error'] = '削除エラー: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
}

$deleteStmt->close();
header('Location: user.php');
exit;
