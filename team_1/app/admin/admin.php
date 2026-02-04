<?php
require_once __DIR__ . '/auth.php';
requireAuth(); // All logged-in users can see admin panel
?>
<!DOCTYPE html>>>
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

    <div class="menu">
        <a href="orders.php" class="card">
            <div class="icon">📦</div>
            <p class="title">注文ページ</p>
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
