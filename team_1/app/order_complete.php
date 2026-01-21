<?php
session_start();
require __DIR__ . '/../config/db.php';

$orderId = $_SESSION['last_order_id'] ?? null;
if (!$orderId) {
    header('Location: index.php');
    exit;
}

// Get delivery time from the order
$deliveryTimeDisplay = 'お届け予定時間：約30分後';
$res = $mysqli->query("SELECT delivery_time FROM orders WHERE id=" . (int)$orderId . " LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $deliveryTime = $row['delivery_time'];
    if ($deliveryTime) {
        try {
            date_default_timezone_set('Asia/Tokyo');
            $dt = new DateTime($deliveryTime, new DateTimeZone('Asia/Tokyo'));
            $now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
            
            // Check if delivery is today, tomorrow, or day after
            $deliveryDate = $dt->format('Y-m-d');
            $todayDate = $now->format('Y-m-d');
            $tomorrowDate = (clone $now)->modify('+1 day')->format('Y-m-d');
            $dayAfterDate = (clone $now)->modify('+2 days')->format('Y-m-d');
            
            $dateLabel = '';
            if ($deliveryDate === $todayDate) {
                $dateLabel = '本日';
            } elseif ($deliveryDate === $tomorrowDate) {
                $dateLabel = '明日';
            } elseif ($deliveryDate === $dayAfterDate) {
                $dateLabel = '明後日';
            } else {
                $dateLabel = $dt->format('m月d日');
            }
            
            $timeLabel = $dt->format('H:i');
            $deliveryTimeDisplay = "お届け予定時間：{$dateLabel} {$timeLabel}";
        } catch (Exception $e) {
            // Keep default message if parsing fails
        }
    }
    $res->free();
}

// чтобы при обновлении страницы не было "вечного" заказа
unset($_SESSION['last_order_id']);
unset($_SESSION['delivery_time']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match | 注文完了</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/components.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-content">
        <div class="logo">PM</div>
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<!-- Main Content -->
<div class="order-complete-container">
    <div class="success-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="80" height="80" fill="#27ae60">
            <path d="M422-297.33 704.67-580l-49.34-48.67L422-395.33l-118-118-48.67 48.66L422-297.33ZM480-80q-82.33 0-155.33-31.5-73-31.5-127.34-85.83Q143-251.67 111.5-324.67T80-480q0-83 31.5-156t85.83-127q54.34-54 127.34-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 82.33-31.5 155.33-31.5 73-85.5 127.34Q709-143 636-111.5T480-80Z"/>
        </svg>
    </div>

    <h1 class="success-title">ご注文ありがとうございます！</h1>
    <p class="success-message">ご注文を受け付けました。ただいま調理中です。</p>

    <div class="order-info-card">
        <h2>ご注文内容</h2>
        <div class="info-row">
            <span class="info-label">ご注文番号</span>
            <span class="info-value"><?= htmlspecialchars((string)$orderId) ?></span>
        </div>
        <div class="info-row">
            <span class="info-label"><?= htmlspecialchars(explode('：', $deliveryTimeDisplay)[0]) ?></span>
            <span class="info-value"><?= htmlspecialchars(explode('：', $deliveryTimeDisplay)[1]) ?></span>
        </div>
    </div>

    <p class="note-text">
        お問い合わせの際は、上記の注文番号をお伝えください。
    </p>

    <a href="index.php" class="btn-proceed" style="display: inline-block;">トップページに戻る</a>
</div>

<script>
// Clear cart from localStorage
localStorage.removeItem('cart');
localStorage.removeItem('delivery_time');
</script>

</body>
</html>
