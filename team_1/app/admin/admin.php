<?php
require_once __DIR__ . '/auth.php';
requireAuth(); // All logged-in users can see admin panel

// Get current user role for menu filtering
$userRole = $_SESSION['admin_role'] ?? 'user';
$canManage = in_array($userRole, ['admin', 'manager']); // Can access user.php and kanri.php

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理パネル</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <header>
        <div class="header-content" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #fff; border-bottom: 1px solid #e1e8ed;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <img src="../assets/image/logo.png" alt="Pizza Mach" style="height: 40px; width: auto;">
                <h1 style="margin: 0; font-size: 1.25rem;">Pizza Mach</h1>
            </div>
            <div class="header-logout">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="logout-btn">ログアウト</button>
                </form>
            </div>
        </div>
    </header>
<div class="container">
   
    <h1>管理パネル</h1>
    <p class="sub">管理メニューを選択してください。</p>

    <div class="menu">
        <!-- All roles can access orders -->
        <a href="orders.php" class="card">
            <div class="icon">📦</div>
            <p class="title">注文ページ</p>
        </a>

        <!-- Only admin and manager can access users -->
        <?php if ($canManage): ?>
        <a href="user.php" class="card">
            <div class="icon">👤</div>
            <p class="title">ユーザーページ</p>
        </a>
        <?php endif; ?>

        <!-- Only admin and manager can access store settings -->
        <?php if ($canManage): ?>
        <a href="kanri.php" class="card">
            <div class="icon">🏪</div>
            <p class="title">店舗設定ページ</p>
        </a>
        <?php endif; ?>

        <!-- All roles can access shifts -->
        <a href="shift.php" class="card">
            <div class="icon">📅</div>
            <p class="title">シフトページ</p>
        </a>
    </div>
</div>
</body>
</html>
