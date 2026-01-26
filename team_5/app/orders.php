<?php
session_start();

$errorMessage = '';
$orders = [];

require_once __DIR__ . '/db_config.php';

try {
    // time_slot format: "HH:MM-HH:MM". We treat it in Asia/Tokyo timezone.
    $pdo->exec(
        "UPDATE orders
         SET status = 'delivered'
         WHERE status <> 'delivered'
           AND time_slot ~ '^[0-9]{2}:[0-9]{2}-[0-9]{2}:[0-9]{2}$'
           AND ((date_trunc('day', created_at AT TIME ZONE 'Asia/Tokyo')::date + (split_part(time_slot, '-', 2))::time)
                <= (now() AT TIME ZONE 'Asia/Tokyo'))"
    );

    $stmt = $pdo->query(
        "SELECT order_number, time_slot, qty_s, qty_m, qty_l, total_yen, customer_name, customer_phone, zipcode, address, building, room, status,
                to_char(created_at AT TIME ZONE 'Asia/Tokyo', 'YYYY-MM-DD HH24:MI') AS created_at_jst
         FROM orders
         ORDER BY created_at DESC"
    );
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = '注文履歴を取得できませんでした。（DB未準備の可能性）';
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注文履歴</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Reduce table cell padding for denser order history view */
        .order-history-table td,
        .order-history-table th {
            padding: 0.35rem 0.5rem;
        }
    </style>
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
                        <a class="nav-link btn btn-contact rounded-pill px-4 m-2" href="admin_panel.php">管理メニュー</a>
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
            <h3 class="text-center fw-bold mb-4">注文履歴</h3>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage === '' && empty($orders)): ?>
                <div class="alert alert-info" role="alert">注文履歴がありません。</div>
            <?php endif; ?>

            <?php if ($errorMessage === '' && !empty($orders)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm align-middle order-history-table">
                        <thead>
                            <tr>
                                <th>注文番号</th>
                                <th>日時</th>
                                <th>時間帯</th>
                                <th>S</th>
                                <th>M</th>
                                <th>L</th>
                                <th>合計(¥)</th>
                                <th>状態</th>
                                <th>お客様</th>
                                <th>配達先</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $row): ?>
                                <?php
                                $addressText = (string)($row['address'] ?? '');
                                $buildingText = (string)($row['building'] ?? '');
                                $roomText = (string)($row['room'] ?? '');
                                $zipcodeText = (string)($row['zipcode'] ?? '');
                                if ($buildingText !== '') {
                                    $addressText .= ' ' . $buildingText;
                                }
                                if ($roomText !== '') {
                                    $addressText .= ' ' . $roomText;
                                }


                                $status = (string)($row['status'] ?? '');
                                $statusClass = 'bg-secondary';
                                if ($status === 'pending') {
                                    $statusClass = 'bg-warning text-dark';
                                } elseif ($status === 'delivered') {
                                    $statusClass = 'bg-success';
                                }
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars((string)$row['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['created_at_jst']); ?></td>
                                    <td><?php echo htmlspecialchars((string)($row['time_slot'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['qty_s']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['qty_m']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['qty_l']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$row['total_yen']); ?></td>
                                    <td><span class="badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars((string)$row['customer_name']); ?></div>
                                        <div class="small">TEL: <?php echo htmlspecialchars((string)$row['customer_phone']); ?></div>
                                    </td>
                                    <td>
                                        <div class="small">〒<?php echo htmlspecialchars($zipcodeText); ?></div>
                                        <div><?php echo htmlspecialchars($addressText); ?></div>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary order-details-btn fw-bold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#orderDetailsModal"
                                            data-order-number="<?php echo htmlspecialchars((string)$row['order_number'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-created-at="<?php echo htmlspecialchars((string)$row['created_at_jst'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-time-slot="<?php echo htmlspecialchars((string)($row['time_slot'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-qty-s="<?php echo htmlspecialchars((string)$row['qty_s'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-qty-m="<?php echo htmlspecialchars((string)$row['qty_m'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-qty-l="<?php echo htmlspecialchars((string)$row['qty_l'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-total-yen="<?php echo htmlspecialchars((string)$row['total_yen'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-status="<?php echo htmlspecialchars($status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-customer-name="<?php echo htmlspecialchars((string)$row['customer_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-customer-phone="<?php echo htmlspecialchars((string)$row['customer_phone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-zipcode="<?php echo htmlspecialchars($zipcodeText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                            data-address="<?php echo htmlspecialchars($addressText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                            詳細
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">注文詳細</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="fw-bold">注文番号</div>
                            <div id="od_order_number" class="fs-1 fw-bold"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="fw-bold">状態</div>
                            <div id="od_status" class="fs-3"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="fw-bold">日時</div>
                            <div id="od_created_at" class="fs-3"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="fw-bold">時間帯</div>
                            <div id="od_time_slot" class="fs-3"></div>
                        </div>

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="fw-bold">S</div>
                            <div id="od_qty_s" class="fs-3"></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="fw-bold">M</div>
                            <div id="od_qty_m" class="fs-3"></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="fw-bold">L</div>
                            <div id="od_qty_l" class="fs-3"></div>
                        </div>
                        <div class="col-12">
                            <div class="fw-bold">合計(¥)</div>
                            <div id="od_total_yen" class="fs-2 fw-bold"></div>
                        </div>

                        <div class="col-12">
                            <hr>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="fw-bold">お客様</div>
                            <div id="od_customer_name" class="fs-3"></div>
                            <div id="od_customer_phone" class="fs-3"></div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="fw-bold">配達先</div>
                            <div id="od_zipcode" class="fs-3"></div>
                            <div id="od_address" class="fs-3"></div>
                            <div class="mt-3 d-flex gap-2 flex-wrap">
                                <a id="od_maps_link" class="btn btn-lg btn-outline-primary" href="#" target="_blank" rel="noopener noreferrer">地図</a>
                                <a id="od_directions_link" class="btn btn-lg btn-outline-success" href="#" target="_blank" rel="noopener noreferrer">現在地から経路</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">閉じる</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        const orderDetailsModal = document.getElementById('orderDetailsModal');
        if (orderDetailsModal) {
            orderDetailsModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const getData = (key) => button.getAttribute('data-' + key) || '';

                document.getElementById('od_order_number').textContent = getData('order-number');
                document.getElementById('od_status').textContent = getData('status');
                document.getElementById('od_created_at').textContent = getData('created-at');
                document.getElementById('od_time_slot').textContent = getData('time-slot');

                document.getElementById('od_qty_s').textContent = getData('qty-s');
                document.getElementById('od_qty_m').textContent = getData('qty-m');
                document.getElementById('od_qty_l').textContent = getData('qty-l');
                document.getElementById('od_total_yen').textContent = getData('total-yen');

                document.getElementById('od_customer_name').textContent = getData('customer-name');
                document.getElementById('od_customer_phone').textContent = 'TEL: ' + getData('customer-phone');

                document.getElementById('od_zipcode').textContent = '〒' + getData('zipcode');
                document.getElementById('od_address').textContent = getData('address');

                const destination = (getData('zipcode') + ' ' + getData('address')).trim();
                const mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(destination);
                const dirUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(destination) + '&travelmode=driving';

                const mapsLink = document.getElementById('od_maps_link');
                const directionsLink = document.getElementById('od_directions_link');
                if (mapsLink) mapsLink.href = mapsUrl;
                if (directionsLink) directionsLink.href = dirUrl;
            });
        }
    </script>

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
</body>

</html>