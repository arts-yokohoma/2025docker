<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$orderId = (int)$_GET['id'];

// Fetch order with customer and items details (including delivery_time for editing)
$query = "
SELECT 
  o.id,
  o.create_time as date,
  o.delivery_time,
  c.name,
  c.phone,
  c.address,
  o.status,
  c.id as customer_id
FROM orders o
LEFT JOIN customer c ON o.customer_id = c.id
WHERE o.id = ?
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$result = $stmt->get_result();
$orderData = $result->fetch_assoc();
$stmt->close();

if (!$orderData) {
    header("Location: orders.php");
    exit;
}

// Fetch order items with price
$itemsQuery = "
SELECT oi.id, oi.menu_id, oi.quantity, oi.price, m.name
FROM order_items oi
LEFT JOIN menu m ON oi.menu_id = m.id
WHERE oi.order_id = ?
";

$stmt = $mysqli->prepare($itemsQuery);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$itemsResult = $stmt->get_result();
$orderItems = [];
while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}
$stmt->close();

// Fetch all menu items for selection dropdown
$menuQuery = "SELECT id, name, price_s FROM menu WHERE active = 1 AND deleted = 0 ORDER BY name";
$menuResult = $mysqli->query($menuQuery);
$menuItems = [];
while ($menuItem = $menuResult->fetch_assoc()) {
    $menuItems[] = $menuItem;
}

// Load store hours and build delivery time slots (same logic as cart)
$storeHours = [
    'open_time' => '11:00',
    'close_time' => '22:00',
    'last_order_offset_min' => 30,
];
$res = $mysqli->query("SELECT open_time, close_time, last_order_offset_min 
                       FROM store_hours WHERE id=1 AND active=1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $storeHours['open_time'] = substr((string)$row['open_time'], 0, 5);
    $storeHours['close_time'] = substr((string)$row['close_time'], 0, 5);
    $storeHours['last_order_offset_min'] = (int)$row['last_order_offset_min'];
    $res->free();
}

date_default_timezone_set('Asia/Tokyo');
$now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
$currentTime = $now->format('H:i');
$currentMinutes = (int)$now->format('H') * 60 + (int)$now->format('i');
[$openH, $openM] = explode(':', $storeHours['open_time']);
[$closeH, $closeM] = explode(':', $storeHours['close_time']);
$openMinutes = (int)$openH * 60 + (int)$openM;
$closeMinutes = (int)$closeH * 60 + (int)$closeM;
$isStoreOpen = ($currentMinutes >= $openMinutes && $currentMinutes < $closeMinutes);
$todayClose = clone $now;
$todayClose->setTime((int)$closeH, (int)$closeM, 0);
$lastOrderTime = clone $todayClose;
$lastOrderTime->modify('-' . $storeHours['last_order_offset_min'] . ' minutes');
$canOrderToday = ($now < $lastOrderTime);
$minDeliveryTime = clone $now;
$minDeliveryTime->modify('+30 minutes');
if (!$canOrderToday || !$isStoreOpen) {
    $todayOpen = clone $now;
    $todayOpen->setTime((int)$openH, (int)$openM, 0);
    if ($todayOpen < $now) {
        $todayOpen->modify('+1 day');
    }
    $minDeliveryTime = clone $todayOpen;
    $minDeliveryTime->modify('+30 minutes');
}

$availableTimesByDate = [];
$dates = ['today' => clone $now, 'tomorrow' => clone $now, 'day_after' => clone $now];
$dates['tomorrow']->modify('+1 day');
$dates['day_after']->modify('+2 days');
$deliveryTimeMinutes = 30;

foreach ($dates as $key => $date) {
    $dayStart = clone $date;
    $dayStart->setTime((int)$openH, (int)$openM, 0);
    $dayEnd = clone $date;
    $dayEnd->setTime((int)$closeH, (int)$closeM, 0);
    $lastDeliveryTime = clone $dayEnd;
    $lastDeliveryTime->modify('-' . $storeHours['last_order_offset_min'] . ' minutes');
    if ($key === 'today') {
        $dayMinTime = clone $minDeliveryTime;
        if ($dayMinTime->format('Y-m-d') !== $date->format('Y-m-d')) {
            continue;
        }
    } else {
        $dayMinTime = clone $dayStart;
        $dayMinTime->modify('+' . $deliveryTimeMinutes . ' minutes');
    }
    $times = [];
    if ($dayMinTime > $lastDeliveryTime) {
        continue;
    }
    $current = clone $dayMinTime;
    $interval = new DateInterval('PT15M');
    $currentMinutesVal = (int)$current->format('i');
    $remainder = $currentMinutesVal % 15;
    if ($remainder > 0) {
        $roundUp = 15 - $remainder;
        $current->modify('+' . $roundUp . ' minutes');
    }
    if ($current <= $lastDeliveryTime) {
        while ($current <= $lastDeliveryTime) {
            $times[] = $current->format('H:i');
            $current->add($interval);
            if (count($times) > 100) break;
        }
    } elseif ($dayMinTime <= $lastDeliveryTime) {
        $times[] = $lastDeliveryTime->format('H:i');
    }
    if (!empty($times)) {
        $availableTimesByDate[$key] = $times;
    }
}

$dateLabels = ['today' => '今日', 'tomorrow' => '明日', 'day_after' => '明後日'];
$dateOptions = [];
foreach ($dateLabels as $key => $label) {
    if (isset($availableTimesByDate[$key])) {
        $dateOptions[] = [
            'key' => $key,
            'label' => $label,
            'date' => $dates[$key]->format('Y-m-d'),
        ];
    }
}

$deliveryTime = $orderData['delivery_time'] ?? null;
$deliveryTimeDb = $deliveryTime ? date('Y-m-d H:i:s', strtotime($deliveryTime)) : null;
$initialDateKey = null;
$initialTimeSlot = null;
if ($deliveryTime && strtotime($deliveryTime) !== false) {
    $dt = new DateTime($deliveryTime, new DateTimeZone('Asia/Tokyo'));
    $deliveryDateStr = $dt->format('Y-m-d');
    $deliveryTimeStr = $dt->format('H:i');
    foreach ($dateOptions as $opt) {
        if ($opt['date'] === $deliveryDateStr && isset($availableTimesByDate[$opt['key']])) {
            $slots = $availableTimesByDate[$opt['key']];
            if (in_array($deliveryTimeStr, $slots)) {
                $initialDateKey = $opt['key'];
                $initialTimeSlot = $deliveryTimeStr;
                break;
            }
            $initialDateKey = $opt['key'];
            $initialTimeSlot = $slots[0];
            break;
        }
    }
}
if ($initialDateKey === null && !empty($dateOptions)) {
    $initialDateKey = $dateOptions[0]['key'];
    $initialTimeSlot = $availableTimesByDate[$initialDateKey][0] ?? null;
}
$initialDeliveryValue = '';
if ($initialDateKey && $initialTimeSlot) {
    $d = $dates[$initialDateKey] ?? null;
    if ($d) {
        $d->setTime((int)substr($initialTimeSlot, 0, 2), (int)substr($initialTimeSlot, 3, 2), 0);
        $initialDeliveryValue = $d->format('Y-m-d H:i:s');
    }
}
if ($initialDeliveryValue === '' && !empty($dateOptions)) {
    $firstOpt = $dateOptions[0];
    $firstSlots = $availableTimesByDate[$firstOpt['key']];
    $t = $firstSlots[0] ?? null;
    if ($t) {
        $d = clone $dates[$firstOpt['key']];
        $d->setTime((int)substr($t, 0, 2), (int)substr($t, 3, 2), 0);
        $initialDeliveryValue = $d->format('Y-m-d H:i:s');
    }
}

$orderToEdit = [
    'id' => $orderData['id'],
    'date' => date('Y-m-d H:i', strtotime($orderData['date'])),
    'delivery_time' => $deliveryTime ? date('Y-m-d H:i', strtotime($deliveryTime)) : null,
    'name' => $orderData['name'] ?? 'Unknown',
    'phone' => $orderData['phone'] ?? 'N/A',
    'address' => $orderData['address'] ?? 'N/A',
    'status' => $orderData['status'] ?? 'New',
    'customer_id' => $orderData['customer_id'],
];

$ORDER_STATUSES = [
    ['value' => 'New', 'label' => '新規', 'color' => '#F59E0B'],
    ['value' => 'In Progress', 'label' => '調理中', 'color' => '#3B82F6'],
    ['value' => 'Completed', 'label' => '完了', 'color' => '#10B981'],
    ['value' => 'Canceled', 'label' => 'キャンセル', 'color' => '#EF4444'],
];

$currentStatus = ['value' => 'New', 'label' => '新規', 'color' => '#F59E0B'];
foreach ($ORDER_STATUSES as $status) {
    if ($status['value'] === $orderToEdit['status']) {
        $currentStatus = $status;
        break;
    }
}

$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

function formatPrice($price) {
    return '¥' . number_format($price);
}

$totalItems = 0;
foreach ($orderItems as $item) {
    $totalItems += $item['quantity'];
}

// Prepare menu data for JavaScript
$menuDataForJs = [];
foreach ($menuItems as $m) {
    $menuDataForJs[] = [
        'id' => $m['id'],
        'name' => $m['name'],
        'price' => $m['price_s']
    ];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文編集 - <?= htmlspecialchars($orderToEdit['id']) ?></title>
    <!-- Material Symbols for icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="css/order_edit.css">
    <style>
        /* Ensure back button visible and positioned correctly */
        .btn-back {
            position: fixed !important;
            top: 16px !important;
            right: 16px !important;
            z-index: 9999 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            padding: 6px 10px !important;
            border: 1px solid #ddd !important;
            border-radius: 6px !important;
            background: #fff !important;
            color: #000 !important;
            text-decoration: none !important;
            font-size: 14px !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1) !important;
        }
        .btn-back svg { fill: #0000F5 !important; }
    </style>
</head>
<body>
<header class="edit-order-header">
    <img src="../assets/image/logo.png" alt="Pizza Mach" class="edit-order-logo">
    <span class="edit-order-title">注文編集</span>
    <a href="orders.php" class="edit-order-back">戻る</a>
    <a href="logout.php" class="edit-order-logout">ログアウト</a>
</header>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1 class="title">注文 #<?= htmlspecialchars($orderToEdit['id']) ?></h1>
                <p class="subtitle">
                    作成日時: <?= htmlspecialchars($orderToEdit['date']) ?>
                </p>
            </div>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'delivery_after_close'): ?>
            <div class="alert alert-error">
                配達時間は営業終了後には設定できません。営業時間内の枠を選んでください。
            </div>
        <?php endif; ?>

        <form method="POST" action="save_order.php" id="orderForm">
            <input type="hidden" name="id" value="<?= $orderToEdit['id'] ?>">
            <input type="hidden" name="customer_id" value="<?= $orderToEdit['customer_id'] ?>">

            <!-- Delivery time: date + time blocks (within store hours) -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span class="icon">🕐</span>
                        配達時間
                    </h2>
                </div>
                <div class="delivery-time-edit">
                    <input type="hidden" name="delivery_time" id="deliveryTimeHidden" value="<?= htmlspecialchars($initialDeliveryValue) ?>">
                    <label class="input-label">お届け予定日時（営業時間内の枠から選択）</label>
                    <div class="delivery-time-blocks">
                        <div class="delivery-date-row">
                            <label class="block-label">日付</label>
                            <select id="delivery-date" class="input-field delivery-date-select">
                                <?php foreach ($dateOptions as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt['key']) ?>"
                                            data-date="<?= htmlspecialchars($opt['date']) ?>"
                                            <?= $initialDateKey === $opt['key'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($opt['label']) ?> (<?= $opt['date'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="delivery-time-row">
                            <label class="block-label">時間</label>
                            <select id="delivery-time-slot" class="input-field delivery-time-select">
                                <?php
                                $slotsForInitial = $initialDateKey ? ($availableTimesByDate[$initialDateKey] ?? []) : [];
                                foreach ($slotsForInitial as $slot):
                                    $sel = ($slot === $initialTimeSlot) ? ' selected' : '';
                                ?>
                                    <option value="<?= htmlspecialchars($slot) ?>"<?= $sel ?>><?= htmlspecialchars($slot) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p class="delivery-time-note">※営業終了後の時間は選択できません。</p>
                </div>
            </div>

            <!-- Status Block -->
            <div class="card">
                <div class="status-bar">
                    <span class="status-label">注文ステータス:</span>
                    <span class="status-badge" id="statusBadge" style="background: <?= $currentStatus['color'] ?>20; color: <?= $currentStatus['color'] ?>">
                        <span class="status-dot" id="statusDot" style="background: <?= $currentStatus['color'] ?>"></span>
                        <span id="statusText"><?= htmlspecialchars($currentStatus['label']) ?></span>
                    </span>
                    <div class="status-change">
                        <span class="status-change-label">変更:</span>
                        <select name="status" id="statusSelect" class="status-select">
                            <?php foreach ($ORDER_STATUSES as $status): ?>
                                <option value="<?= $status['value'] ?>" 
                                        data-color="<?= $status['color'] ?>"
                                        data-label="<?= $status['label'] ?>"
                                        <?= $status['value'] === $orderToEdit['status'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Customer Data Block -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span class="icon">👤</span>
                        顧客情報
                    </h2>
                    <button type="button" class="btn-edit" id="editCustomerBtn"><span class="material-symbols-outlined">edit</span> 編集</button>
                </div>

                <div id="customerView" class="customer-view">
                    <div class="customer-grid">
                        <div>
                            <span class="field-label">氏名</span>
                            <p class="field-value" id="displayName">
                                <?= htmlspecialchars($orderToEdit['name']) ?>
                            </p>
                        </div>
                        <div>
                            <span class="field-label">電話番号</span>
                            <p class="field-value" id="displayPhone"><?= htmlspecialchars($orderToEdit['phone']) ?></p>
                        </div>
                        <div class="full-width">
                            <span class="field-label">配送先住所</span>
                            <p class="field-value" id="displayAddress"><?= htmlspecialchars($orderToEdit['address']) ?></p>
                        </div>
                    </div>
                </div>

                <div id="customerEdit" class="customer-edit" style="display: none;">
                    <div class="name-grid">
                        <div class="full-width-input">
                            <label class="input-label">顧客名</label>
                            <input type="text" class="input-field" name="name" id="customerName" 
                                   value="<?= htmlspecialchars($orderToEdit['name']) ?>" required>
                        </div>
                    </div>
                    <div class="contact-grid">
                        <div>
                            <label class="input-label">電話番号</label>
                            <input type="text" class="input-field" name="phone" id="customerPhone" 
                                   value="<?= htmlspecialchars($orderToEdit['phone']) ?>" required>
                        </div>
                        <div>
                            <label class="input-label">配送先住所</label>
                            <input type="text" class="input-field" name="address" id="customerAddress" 
                                   value="<?= htmlspecialchars($orderToEdit['address']) ?>" required>
                        </div>
                    </div>
                    <div class="button-group">
                        <button type="button" class="btn-secondary" id="cancelCustomerBtn">キャンセル</button>
                        <button type="button" class="btn-primary" id="saveCustomerBtn">💾 保存表示</button>
                    </div>
                </div>
            </div>

            <!-- Products Block -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span class="icon">📦</span>
                        商品
                    </h2>
                    <button type="button" class="btn-add-product" id="addProductBtn">➕ 商品を追加</button>
                </div>

                <!-- Product Search Modal -->
                <div id="productSearch" class="product-search" style="display: none;">
                    <input type="text" class="search-input" id="searchInput" placeholder="商品名で検索..." autocomplete="off">
                    <div class="product-list" id="productList">
                        <?php foreach ($menuItems as $menu): ?>
                            <div class="product-item" 
                                 data-id="<?= $menu['id'] ?>" 
                                 data-name="<?= htmlspecialchars($menu['name']) ?>" 
                                 data-price="<?= $menu['price_s'] ?>">
                                <span><?= htmlspecialchars($menu['name']) ?></span>
                                <span class="product-price"><?= formatPrice($menu['price_s']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-close-search" id="closeSearchBtn">閉じる</button>
                </div>

                <!-- Items Table -->
                <div class="table-container">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>商品</th>
                                <th class="text-right">単価</th>
                                <th class="text-center">数量</th>
                                <th class="text-right">小計</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <?php if (empty($orderItems)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px; color: #94A3B8;">
                                        商品がありません
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orderItems as $item): ?>
                                    <tr data-item-id="<?= $item['id'] ?>">
                                        <td>
                                            <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
                                            <input type="hidden" name="menu_id[]" value="<?= $item['menu_id'] ?>">
                                            <span class="item-name"><?= htmlspecialchars($item['name']) ?></span>
                                        </td>
                                        <td class="text-right price-cell">
                                            <input type="hidden" name="price[]" class="price-input" value="<?= $item['price'] ?>">
                                            <span class="price-display"><?= formatPrice($item['price']) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="quantity-control">
                                                <button type="button" class="qty-btn qty-minus">−</button>
                                                <input type="number" name="quantity[]" class="qty-input" 
                                                       value="<?= $item['quantity'] ?>" 
                                                       data-price="<?= $item['price'] ?>" min="1">
                                                <button type="button" class="qty-btn qty-plus">+</button>
                                            </div>
                                        </td>
                                        <td class="text-right subtotal-cell"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn-delete">🗑️</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="totals-section">
                    <div class="total-row">
                        <span>商品 (<span id="totalItemsCount"><?= $totalItems ?></span> 点)</span>
                        <span id="subtotalAmount"><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="total-final">
                        <span>合計</span>
                        <span id="totalAmount"><?= formatPrice($subtotal) ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
                <a href="orders.php" class="btn-secondary-large">キャンセル</a>
                <button type="submit" class="btn-save-large">💾 注文を保存</button>
            </div>
        </form>
    </div>

    <script>
        // Menu items data for JavaScript
        var menuData = <?= json_encode($menuDataForJs) ?>;
        var ORDER_STATUSES = <?= json_encode($ORDER_STATUSES) ?>;
        // Delivery time blocks: date key -> list of time slots (HH:MM)
        var deliverySlotsByDate = <?= json_encode($availableTimesByDate) ?>;
        var deliveryDateOptions = <?= json_encode(array_map(function ($o) { return ['key' => $o['key'], 'date' => $o['date']]; }, $dateOptions)) ?>;
    </script>
    <script src="./order_edit.js"></script>
    <script>
        (function() {
            var dateSelect = document.getElementById('delivery-date');
            var timeSelect = document.getElementById('delivery-time-slot');
            var hidden = document.getElementById('deliveryTimeHidden');
            function getSelectedDate() {
                var opt = dateSelect.options[dateSelect.selectedIndex];
                return opt ? opt.getAttribute('data-date') : '';
            }
            function setHiddenValue(dateStr, timeStr) {
                if (dateStr && timeStr) {
                    hidden.value = dateStr + ' ' + timeStr + ':00';
                }
            }
            function fillTimeSlots(dateKey) {
                var slots = deliverySlotsByDate[dateKey] || [];
                var current = timeSelect.value;
                timeSelect.innerHTML = '';
                slots.forEach(function(t) {
                    var o = document.createElement('option');
                    o.value = t;
                    o.textContent = t;
                    timeSelect.appendChild(o);
                });
                if (slots.length && current && slots.indexOf(current) !== -1) {
                    timeSelect.value = current;
                } else if (slots.length) {
                    timeSelect.value = slots[0];
                }
                setHiddenValue(getSelectedDate(), timeSelect.value);
            }
            dateSelect.addEventListener('change', function() {
                var key = dateSelect.value;
                fillTimeSlots(key);
            });
            timeSelect.addEventListener('change', function() {
                setHiddenValue(getSelectedDate(), timeSelect.value);
            });
        })();
    </script>
</body>
</html>