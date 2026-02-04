<?php
/**
 * Admin Panel Header - Navigation & User Info
 * Include at the top of all admin pages
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$adminUsername = $_SESSION['admin_username'] ?? 'User';
$adminRole = $_SESSION['admin_role'] ?? 'unknown';

// Role labels in Japanese
$roleLabels = [
    'admin' => '管理者',
    'manager' => 'マネージャー',
    'kitchen' => 'キッチン',
    'delivery' => 'デリバリー'
];
$roleName = $roleLabels[$adminRole] ?? $adminRole;

// Define menu items based on role
$menuItems = [
    'admin' => [
        ['title' => '注文', 'icon' => '📦', 'page' => 'orders'],
        ['title' => 'ユーザー', 'icon' => '👥', 'page' => 'user'],
        ['title' => '店舗設定', 'icon' => '⚙️', 'page' => 'kanri'],
        ['title' => 'シフト', 'icon' => '📅', 'page' => 'shift']
    ],
    'manager' => [
        ['title' => '注文', 'icon' => '📦', 'page' => 'orders'],
        ['title' => 'ユーザー', 'icon' => '👥', 'page' => 'user'],
        ['title' => '店舗設定', 'icon' => '⚙️', 'page' => 'kanri'],
        ['title' => 'シフト', 'icon' => '📅', 'page' => 'shift']
    ],
    'kitchen' => [
        ['title' => '注文', 'icon' => '📦', 'page' => 'orders'],
        ['title' => 'シフト', 'icon' => '📅', 'page' => 'shift']
    ],
    'delivery' => [
        ['title' => '注文', 'icon' => '📦', 'page' => 'orders'],
        ['title' => 'シフト', 'icon' => '📅', 'page' => 'shift']
    ]
];

$items = $menuItems[$adminRole] ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
    background: #f5f7fa;
    color: #2c3e50;
}

.admin-header {
    background: #ffffff;
    border-bottom: 1px solid #e1e8ed;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    max-width: 1400px;
    margin: 0 auto;
}

.logo-section {
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    font-size: 18px;
    color: #2c3e50;
}

.logo-icon {
    font-size: 24px;
}

.user-section {
    display: flex;
    align-items: center;
    gap: 24px;
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.username {
    font-weight: 500;
    color: #2c3e50;
}

.role-badge {
    display: inline-block;
    background: #e3f2fd;
    color: #1976d2;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-top: 2px;
}

.logout-btn {
    background: #ef5350;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s;
}

.logout-btn:hover {
    background: #e53935;
}

.nav-bar {
    background: #fafbfc;
    border-bottom: 1px solid #e1e8ed;
    padding: 0;
    max-width: 1400px;
    margin: 0 auto;
}

.nav-items {
    display: flex;
    list-style: none;
    gap: 0;
    padding: 0 24px;
}

.nav-item {
    margin: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 16px 16px;
    color: #666;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    transition: all 0.2s;
    font-size: 14px;
    font-weight: 500;
}

.nav-link:hover {
    color: #2c3e50;
    border-bottom-color: #3498db;
}

.nav-link.active {
    color: #3498db;
    border-bottom-color: #3498db;
}

.admin-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.page-header {
    background: white;
    padding: 24px;
    border-radius: 8px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.page-title {
    font-size: 24px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
}

.page-subtitle {
    font-size: 14px;
    color: #7f8c8d;
}

.page-content {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}
</style>
</head>
<body>

<header class="admin-header">
    <div class="header-content">
        <div class="logo-section">
            <span class="logo-icon">🍕</span>
            <span>PIZZA-MACH Admin</span>
        </div>
        <div class="user-section">
            <div class="user-info">
                <div class="username"><?= htmlspecialchars($adminUsername, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="role-badge"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="logout-btn">ログアウト</button>
            </form>
        </div>
    </div>
</header>

<nav class="nav-bar">
    <ul class="nav-items">
        <?php foreach ($items as $item): ?>
            <li class="nav-item">
                <a href="<?= $item['page'] ?>.php" class="nav-link <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                    <span><?= $item['icon'] ?></span>
                    <span><?= $item['title'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

<?php
// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
