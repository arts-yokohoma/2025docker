<?php
session_start();

$orderId = $_SESSION['last_order_id'] ?? null;
if (!$orderId) {
    header('Location: index.php');
    exit;
}

// чтобы при обновлении страницы не было “вечного” заказа
unset($_SESSION['last_order_id']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match | 注文完了</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">PM</div>
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<h1>ご注文ありがとうございました！</h1>

<p>注文ID：<strong><?= htmlspecialchars((string)$orderId) ?></strong></p>
<p>この番号を控えてください。</p>

<p><a href="index.php">メニューへ戻る</a></p>

<script>
// ✅ clear cart on client
localStorage.removeItem('cart');
</script>

</body>
</html>
