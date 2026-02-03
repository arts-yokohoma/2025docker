<?php
session_start();

$orderId = $_SESSION['last_order_number'] ?? null;
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
unset($_SESSION['order'], $_SESSION['customer'], $_SESSION['time_slot'], $_SESSION['last_order_number']);
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

    <!-- navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="img/nav_bar_logo.png" height="60" class="me-2" alt="Team 5 logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link btn btn-contact rounded-pill px-4 m-2" href="location.php">店舗情報</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-filled-custom rounded-pill px-4 m-2" href="time.php">今すぐ注文</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->


    <div class="container my-4">
        <div class="container_def">
            <h3 class="text-center fw-bold mb-3">ご注文ありがとうございました！</h3>

            <?php if ($orderId !== null && $orderId !== ''): ?>
                <div class="alert alert-success text-center fs-3 fw-bold" role="alert">
                    注文番号：<?php echo htmlspecialchars((string)$orderId); ?>
                </div>
                <div class="text-center fs-2 fw-bold mb-3">注文番号をメモしておいてください。</div>
            <?php else: ?>
                <div class="alert alert-warning fs-3 fw-bold" role="alert">
                    注文番号を取得できませんでした。
                </div>
            <?php endif; ?>

            <?php if ($time_slot !== ''): ?>
                <div class="mb-2">時間帯：<?php echo htmlspecialchars($time_slot); ?></div>
            <?php endif; ?>

            <div class="row mt-3">
                <div class="col-12 col-lg-6 mb-3">
                    <h5 class="fw-bold fs-3">メニュー</h5>
                    <div>Sサイズ：<?php echo htmlspecialchars((string)$qty_s); ?></div>
                    <div>Mサイズ：<?php echo htmlspecialchars((string)$qty_m); ?></div>
                    <div>Lサイズ：<?php echo htmlspecialchars((string)$qty_l); ?></div>
                    <hr>
                    <div class="fw-bold fs-3">合計：<?php echo htmlspecialchars((string)$total); ?>¥</div>
                </div>
                <div class="col-12 col-lg-6 mb-3">
                    <h5 class="fw-bold fs-3">お届け先</h5>
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

    <!-- Site footer -->
    <footer class="site-footer mt-5">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <a class="navbar-brand d-flex align-items-center" href="index.php">
                        <img src="img/nav_bar_logo.png" height="40" class="me-2" alt="Team 5 logo">
                    </a>
                    <small class="d-block">&copy; <span id="year"></span> CYBER EDGE. All rights reserved.</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0 footer-links">
                        <li class="list-inline-item"><a href="/index.php">ホーム</a></li>
                        <li class="list-inline-item"><a href="/admin_login.php">Login</a></li>
                        <li class="list-inline-item"><a href="contact.php">お問い合わせ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>