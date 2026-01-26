<?php
session_start();

$errorMessage = '';

$time_slot = (string)($_SESSION['time_slot'] ?? '');
$order = $_SESSION['order'] ?? null;
$customer = $_SESSION['customer'] ?? null;

if (!is_array($order) || !is_array($customer)) {
    // Missing required steps; send user back
    header('Location: time.php');
    exit;
}

$qty_s = (int)($order['qty_s'] ?? 0);
$qty_m = (int)($order['qty_m'] ?? 0);
$qty_l = (int)($order['qty_l'] ?? 0);
$total = (int)($order['total'] ?? 0);

$name = (string)($customer['name'] ?? '');
$phone = (string)($customer['phone'] ?? '');
$zipcode = (string)($customer['zipcode'] ?? '');
$address = (string)($customer['address'] ?? '');
$building = (string)($customer['building'] ?? '');
$room = (string)($customer['room'] ?? '');

$fullAddress = $address;
if ($building !== '') {
    $fullAddress .= ' ' . $building;
}
if ($room !== '') {
    $fullAddress .= ' ' . $room;
}

if (empty($_SESSION['confirm_nonce'])) {
    $_SESSION['confirm_nonce'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedNonce = (string)($_POST['confirm_nonce'] ?? '');
    $sessionNonce = (string)($_SESSION['confirm_nonce'] ?? '');

    if ($postedNonce === '' || $sessionNonce === '' || !hash_equals($sessionNonce, $postedNonce)) {
        $errorMessage = '送信の検証に失敗しました。もう一度お試しください。';
    } elseif ($name === '' || $phone === '' || $zipcode === '' || $address === '') {
        $errorMessage = '住所情報が不足しています。前の画面に戻って入力してください。';
    } elseif (($qty_s + $qty_m + $qty_l) <= 0) {
        $errorMessage = 'メニューが選択されていません。';
    } else {
        require_once __DIR__ . '/db_config.php';

        try {
            $sql = "INSERT INTO orders (time_slot, qty_s, qty_m, qty_l, total_yen, customer_name, customer_phone, zipcode, address, building, room)
                    VALUES (:time_slot, :qty_s, :qty_m, :qty_l, :total_yen, :customer_name, :customer_phone, :zipcode, :address, :building, :room)
                    RETURNING id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':time_slot' => $time_slot !== '' ? $time_slot : null,
                ':qty_s' => $qty_s,
                ':qty_m' => $qty_m,
                ':qty_l' => $qty_l,
                ':total_yen' => $total,
                ':customer_name' => $name,
                ':customer_phone' => $phone,
                ':zipcode' => $zipcode,
                ':address' => $address,
                ':building' => $building !== '' ? $building : null,
                ':room' => $room !== '' ? $room : null,
            ]);

            $newId = $stmt->fetchColumn();
            $_SESSION['last_order_id'] = $newId;

            // Rotate nonce to prevent accidental double-submit.
            $_SESSION['confirm_nonce'] = bin2hex(random_bytes(16));

            header('Location: order_complete.php');
            exit;
        } catch (PDOException $e) {
            // Common case: table not created yet.
            $errorMessage = '注文の保存に失敗しました。（DB未準備の可能性） create_orders.sql を実行してから再度お試しください。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注文</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- navbar -->

    <div class="container my-4">
        <div class="container_def">
            <h3 class="text-center fw-bold mb-4">注文内容の確認</h3>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($time_slot !== ''): ?>
                <div class="alert alert-info text-center fs-5" role="alert">
                    選択した時間帯：<?php echo htmlspecialchars($time_slot); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12 col-lg-6 mb-3">
                    <h5 class="fw-bold">メニュー</h5>
                    <div class="fs-5">Sサイズ：<?php echo htmlspecialchars((string)$qty_s); ?></div>
                    <div class="fs-5">Mサイズ：<?php echo htmlspecialchars((string)$qty_m); ?></div>
                    <div class="fs-5">Lサイズ：<?php echo htmlspecialchars((string)$qty_l); ?></div>
                    <hr>
                    <div class="fs-4 fw-bold">合計：<?php echo htmlspecialchars((string)$total); ?>¥</div>
                </div>
                <div class="col-12 col-lg-6 mb-3">
                    <h5 class="fw-bold">お届け先</h5>
                    <div class="fs-5">名前：<?php echo htmlspecialchars($name); ?></div>
                    <div class="fs-5">電話：<?php echo htmlspecialchars($phone); ?></div>
                    <div class="fs-5">郵便番号：<?php echo htmlspecialchars($zipcode); ?></div>
                    <div class="fs-5">住所：<?php echo htmlspecialchars($fullAddress); ?></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-3">
            <a class="btn btn-outline-secondary fs-4" href="address.php">戻る</a>
            <a class="btn btn-outline-secondary fs-4" href="order_select.php">メニュー修正</a>
            <form method="post" action="" style="margin:0;">
                <input type="hidden" name="confirm_nonce" value="<?php echo htmlspecialchars((string)$_SESSION['confirm_nonce']); ?>">
                <button type="submit" class="btn btn-success fs-4">確定</button>
            </form>
        </div>
    </div>
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

<script src="js/bootstrap.bundle.min.js"></script>

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
                        <li class="list-inline-item"><a href="#">お問い合わせ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</body>

</html>

