<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$orderId = (int)$_GET['id'];

// Fetch order with customer and items details
$query = "
SELECT 
  o.id,
  o.create_time as date,
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

$orderToEdit = [
    'id' => $orderData['id'],
    'date' => date('Y-m-d H:i', strtotime($orderData['date'])),
    'name' => $orderData['name'] ?? 'Unknown',
    'phone' => $orderData['phone'] ?? 'N/A',
    'address' => $orderData['address'] ?? 'N/A',
    'status' => $orderData['status'] ?? 'New',
    'customer_id' => $orderData['customer_id']
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
    <link rel="stylesheet" href="css/order_edit.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1 class="title">注文 #<?= htmlspecialchars($orderToEdit['id']) ?></h1>
                <p class="subtitle">
                    作成日時: <?= htmlspecialchars($orderToEdit['date']) ?>
                </p>
            </div>
            <a href="orders.php" class="back-btn" title="戻る">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#0000F5" aria-hidden="true" focusable="false"><path d="m313-440 224 224-57 56-320-320 320-320 57 56-224 224h487v80H313Z"/></svg>
                <span class="back-text">戻る</span>
            </a>
        </div>

        <form method="POST" action="save_order.php" id="orderForm">
            <input type="hidden" name="id" value="<?= $orderToEdit['id'] ?>">
            <input type="hidden" name="customer_id" value="<?= $orderToEdit['customer_id'] ?>">

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
                    <button type="button" class="btn-edit" id="editCustomerBtn">✏️ 編集</button>
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
    </script>
    <script src="./order_edit.js"></script>
</body>
</html>