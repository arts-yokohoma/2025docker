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

// Helper to generate a cryptographically-secure order number (3 letters + 3 numbers).
$generateOrderNumber = static function (): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $digits = '0123456789';

    $result = '';
    for ($i = 0; $i < 3; $i++) {
        $result .= $alphabet[random_int(0, 25)];
    }
    for ($i = 0; $i < 3; $i++) {
        $result .= $digits[random_int(0, 9)];
    }

    return $result;
};

$normalizePhone = static function (string $raw): string {
    // Keep digits only (e.g., "080-1234-5678" -> "08012345678")
    return preg_replace('/\D+/', '', $raw) ?? '';
};

$tz = new DateTimeZone('Asia/Tokyo');
$today = (new DateTime('now', $tz))->format('Y-m-d');

$parseTimeSlot = static function (string $timeSlot): ?array {
    if (!preg_match('/^\d{2}:\d{2}-\d{2}:\d{2}$/', $timeSlot)) {
        return null;
    }
    [$start, $end] = explode('-', $timeSlot, 2);
    return ['start' => $start, 'end' => $end];
};

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
            $slotParts = $parseTimeSlot($time_slot);
            if ($slotParts === null) {
                throw new Exception('選択した時間帯が不正です。もう一度選択してください。');
            }

            $slotStart = $slotParts['start'];
            $slotEnd = $slotParts['end'];

            $pdo->beginTransaction();

            // Lock the selected time slot row so capacity checks are race-safe.
            $lockStmt = $pdo->prepare(
                "SELECT capacity, available
                 FROM time_slots
                 WHERE shift_date = :shift_date AND slot_start = :slot_start::time AND slot_end = :slot_end::time
                 FOR UPDATE"
            );
            $lockStmt->execute([
                ':shift_date' => $today,
                ':slot_start' => $slotStart,
                ':slot_end' => $slotEnd,
            ]);
            $timeSlotRow = $lockStmt->fetch();

            if (!$timeSlotRow) {
                throw new Exception('選択した時間帯は利用できません。（シフト未登録）');
            }

            $capacity = (int)($timeSlotRow['capacity'] ?? 0);
            $isAvailable = filter_var($timeSlotRow['available'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$isAvailable || $capacity <= 0) {
                throw new Exception('選択した時間帯は満席です。別の時間帯を選択してください。');
            }

            $countStmt = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM orders
                 WHERE (created_at AT TIME ZONE 'Asia/Tokyo')::date = :shift_date
                   AND time_slot = :time_slot"
            );
            $countStmt->execute([
                ':shift_date' => $today,
                ':time_slot' => $time_slot,
            ]);
            $used = (int)$countStmt->fetchColumn();
            if ($used >= $capacity) {
                // Ensure this slot is marked inactive when full.
                $pdo->prepare(
                    "UPDATE time_slots
                     SET available = FALSE
                     WHERE shift_date = :shift_date AND slot_start = :slot_start::time AND slot_end = :slot_end::time"
                )->execute([
                    ':shift_date' => $today,
                    ':slot_start' => $slotStart,
                    ':slot_end' => $slotEnd,
                ]);
                throw new Exception('選択した時間帯は満席です。別の時間帯を選択してください。');
            }

            // Attempt insert; if a collision on primary key occurs, retry a few times.
            $attempts = 0;
            $maxAttempts = 5;
            $insertedOrderNumber = null;

            while ($attempts < $maxAttempts && $insertedOrderNumber === null) {
                $attempts++;
                $orderNumber = $generateOrderNumber();

                $sql = "INSERT INTO orders (order_number, time_slot, qty_s, qty_m, qty_l, total_yen, customer_name, customer_phone, zipcode, address, building, room)
                        VALUES (:order_number, :time_slot, :qty_s, :qty_m, :qty_l, :total_yen, :customer_name, :customer_phone, :zipcode, :address, :building, :room)
                        RETURNING order_number";
                $stmt = $pdo->prepare($sql);

                try {
                    $stmt->execute([
                        ':order_number' => $orderNumber,
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

                    $insertedOrderNumber = $stmt->fetchColumn();
                } catch (PDOException $e) {
                    // If unique violation, loop and retry a new order number. Otherwise rethrow.
                    $sqlState = $e->getCode();
                    if (!is_string($sqlState) || ($sqlState !== '23505' && strpos($e->getMessage(), 'duplicate') === false)) {
                        throw $e;
                    }
                    // else continue to retry
                }
            }

            if ($insertedOrderNumber === null) {
                throw new Exception('注文番号の生成に失敗しました。再度お試しください。');
            }

            // Save/update customer contact for promotions (best-effort).
            // Do not fail the order if this insert fails or the table isn't created yet.
            try {
                $normalizedPhone = $normalizePhone($phone);
                if ($normalizedPhone !== '') {
                    $contactSql = "INSERT INTO customer_contacts (phone, name, zipcode, address, building, room, last_seen_at)
                                  VALUES (:phone, :name, :zipcode, :address, :building, :room, NOW())
                                  ON CONFLICT (phone) DO UPDATE
                                    SET name = EXCLUDED.name,
                                        zipcode = EXCLUDED.zipcode,
                                        address = EXCLUDED.address,
                                        building = EXCLUDED.building,
                                        room = EXCLUDED.room,
                                        last_seen_at = NOW()";
                    $pdo->prepare($contactSql)->execute([
                        ':phone' => $normalizedPhone,
                        ':name' => $name,
                        ':zipcode' => $zipcode !== '' ? $zipcode : null,
                        ':address' => $address !== '' ? $address : null,
                        ':building' => $building !== '' ? $building : null,
                        ':room' => $room !== '' ? $room : null,
                    ]);
                }
            } catch (PDOException $e) {
                error_log('customer_contacts upsert failed: ' . $e->getMessage());
            }

            // If this order fills the slot, mark it inactive.
            $newUsed = $used + 1;
            if ($newUsed >= $capacity) {
                $pdo->prepare(
                    "UPDATE time_slots
                     SET available = FALSE
                     WHERE shift_date = :shift_date AND slot_start = :slot_start::time AND slot_end = :slot_end::time"
                )->execute([
                    ':shift_date' => $today,
                    ':slot_start' => $slotStart,
                    ':slot_end' => $slotEnd,
                ]);
            }

            $pdo->commit();

            $_SESSION['last_order_number'] = $insertedOrderNumber;

            // Rotate nonce to prevent accidental double-submit.
            $_SESSION['confirm_nonce'] = bin2hex(random_bytes(16));

            header('Location: order_complete.php');
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Common case: table not created yet.
            $errorMessage = '注文の保存に失敗しました。（DB未準備の可能性） create_orders.sql を実行してから再度お試しください。';
        } catch (Exception $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMessage = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注文確認</title>
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

    <div class="container my-4">
        <div class="container_def">
            <h3 class="text-center fw-bold mb-4 fs-2">注文内容の確認</h3>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($time_slot !== ''): ?>
                <div class="alert alert-info text-center fs-5" role="alert">
                    選択した時間帯：<span class="fw-bold"><?php echo htmlspecialchars($time_slot); ?></span>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-12 col-lg-6 mb-3">
                    <h5 class="fw-bolder fs-2">メニュー</h5>
                    <div class="fs-3">Sサイズ：<?php echo htmlspecialchars((string)$qty_s); ?></div>
                    <div class="fs-3">Mサイズ：<?php echo htmlspecialchars((string)$qty_m); ?></div>
                    <div class="fs-3">Lサイズ：<?php echo htmlspecialchars((string)$qty_l); ?></div>
                    <hr>
                    <div class="fs-2 fw-bolder">合計：<?php echo htmlspecialchars((string)$total); ?>¥</div>
                </div>
                <div class="col-12 col-lg-6 mb-3">
                    <h5 class="fw-bolder fs-2">お届け先</h5>
                    <div class="fs-3">名前：<?php echo htmlspecialchars($name); ?></div>
                    <div class="fs-3">電話：<?php echo htmlspecialchars($phone); ?></div>
                    <div class="fs-3">郵便番号：<?php echo htmlspecialchars($zipcode); ?></div>
                    <div class="fs-3">住所：<?php echo htmlspecialchars($fullAddress); ?></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-3">
            <a class="btn btn-outline-secondary fs-4" href="address.php">住所修正</a>
            <a class="btn btn-outline-secondary fs-4" href="order_select.php">メニュー修正</a>
            <form method="post" action="" style="margin:0;">
                <input type="hidden" name="confirm_nonce" value="<?php echo htmlspecialchars((string)$_SESSION['confirm_nonce']); ?>">
                <button type="submit" class="btn btn-success fs-4">確定</button>
            </form>
        </div>
    </div>

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
                        <li class="list-inline-item"><a href="contact.php">お問い合わせ</a></li>
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