<?php
session_start();

$orderId = $_SESSION['last_order_id'] ?? null;
$time_slot = (string)($_SESSION['time_slot'] ?? '');
$order = $_SESSION['order'] ?? null;
$customer = $_SESSION['customer'] ?? null;

$qty_s = is_array($order) ? (int)($order['qty_s'] ?? 0) : 0;
$qty_m = is_array($order) ? (int)($order['qty_m'] ?? 0) : 0;
$qty_l = is_array($order) ? (int)($order['qty_l'] ?? 0) : 0;
$total = is_array($order) ? (int)($order['total'] ?? 0) : 0;

$name = is_array($customer) ? (string)($customer['name'] ?? '') : '';
$phone = is_array($customer) ? (string)($customer['phone'] ?? '') : '';
$zipcode = is_array($customer) ? (string)($customer['zipcode'] ?? '') : '';
$address = is_array($customer) ? (string)($customer['address'] ?? '') : '';
$building = is_array($customer) ? (string)($customer['building'] ?? '') : '';
$room = is_array($customer) ? (string)($customer['room'] ?? '') : '';

$fullAddress = $address;
if ($building !== '') {
    $fullAddress .= ' ' . $building;
}
if ($room !== '') {
    $fullAddress .= ' ' . $room;
}

// Clear order session data after capturing it for display.
unset($_SESSION['order'], $_SESSION['customer'], $_SESSION['time_slot']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注文完了</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container my-4">
    <div class="container_def">
        <h3 class="text-center fw-bold mb-3">ご注文ありがとうございました！</h3>

        <?php if ($orderId !== null && $orderId !== ''): ?>
            <div class="alert alert-success text-center fs-5" role="alert">
                注文ID：<?php echo htmlspecialchars((string)$orderId); ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                注文IDを取得できませんでした。
            </div>
        <?php endif; ?>

        <?php if ($time_slot !== ''): ?>
            <div class="mb-2">時間帯：<?php echo htmlspecialchars($time_slot); ?></div>
        <?php endif; ?>

        <div class="row mt-3">
            <div class="col-12 col-lg-6 mb-3">
                <h5 class="fw-bold">メニュー</h5>
                <div>Sサイズ：<?php echo htmlspecialchars((string)$qty_s); ?></div>
                <div>Mサイズ：<?php echo htmlspecialchars((string)$qty_m); ?></div>
                <div>Lサイズ：<?php echo htmlspecialchars((string)$qty_l); ?></div>
                <hr>
                <div class="fw-bold">合計：<?php echo htmlspecialchars((string)$total); ?>¥</div>
            </div>
            <div class="col-12 col-lg-6 mb-3">
                <h5 class="fw-bold">お届け先</h5>
                <div>名前：<?php echo htmlspecialchars($name); ?></div>
                <div>電話：<?php echo htmlspecialchars($phone); ?></div>
                <div>郵便番号：<?php echo htmlspecialchars($zipcode); ?></div>
                <div>住所：<?php echo htmlspecialchars($fullAddress); ?></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center gap-3 mt-3">
        <a class="btn btn-outline-secondary fs-4" href="index.php">ホームへ</a>
        <a class="btn btn-success fs-4" href="time.php">もう一度注文</a>
    </div>
</div>

<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
