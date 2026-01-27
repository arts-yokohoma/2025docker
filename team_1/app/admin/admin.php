<?php
require_once __DIR__ . '/auth.php';
requireAdmin(); // Require admin authentication

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>管理パネル</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="container">
    <h1>管理パネル</h1>
    <p class="sub">管理メニューを選択してください。</p>
    <?php if ($currentUser): ?>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            ログイン中: <?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?> 
            (<?= htmlspecialchars($currentUser['role_name'], ENT_QUOTES, 'UTF-8') ?>)
            | <a href="logout.php" style="color: #4f6ef7;">ログアウト</a>
        </p>
    <?php endif; ?>

    <div class="menu">
        <a href="orders.php" class="card">
            <div class="icon">📦</div>
            <p class="title">注文ページ</p>
        </a>

        <a href="users.php" class="card">
            <div class="icon">👥</div>
            <p class="title">ユーザー管理</p>
        </a>

        <a href="user.php" class="card">
            <div class="icon">👤</div>
            <p class="title">ユーザーページ</p>
        </a>

        <a href="kanri.php" class="card">
            <div class="icon">🏪</div>
            <p class="title">店舗設定ページ</p>
        </a>

        <a href="shift.php" class="card">
            <div class="icon">📅</div>
            <p class="title">シフトページ</p>
        </a>
    </div>
</div>
</body>
</html>
