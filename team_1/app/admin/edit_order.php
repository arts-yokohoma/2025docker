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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>注文編集</title>
  <link rel="stylesheet" href="css/orders.css">
</head>
<body>

<h2>注文編集 - #<?= $orderToEdit['id'] ?></h2>

<form method="POST" action="save_order.php">
  <input type="hidden" name="id" value="<?= $orderToEdit['id'] ?>">
  
  <label>顧客名:</label>
  <input type="text" name="name" value="<?= htmlspecialchars($orderToEdit['name']) ?>" required>
  
  <label>電話:</label>
  <input type="text" name="phone" value="<?= htmlspecialchars($orderToEdit['phone']) ?>" required>
  
  <label>住所:</label>
  <input type="text" name="address" value="<?= htmlspecialchars($orderToEdit['address']) ?>" required>
  
  <label>ステータス:</label>
  <select name="status" required>
    <option value="New" <?= $orderToEdit['status'] === 'New' ? 'selected' : '' ?>>新規</option>
    <option value="In Progress" <?= $orderToEdit['status'] === 'In Progress' ? 'selected' : '' ?>>調理中</option>
    <option value="Completed" <?= $orderToEdit['status'] === 'Completed' ? 'selected' : '' ?>>完了</option>
    <option value="Canceled" <?= $orderToEdit['status'] === 'Canceled' ? 'selected' : '' ?>>キャンセル</option>
  </select>

  <h3>注文アイテム</h3>
  <table id="items-table" style="width: 100%; border-collapse: collapse;">
    <thead>
      <tr style="border-bottom: 2px solid #ddd;">
        <th style="text-align: left; padding: 8px;">メニュー</th>
        <th style="text-align: left; padding: 8px;">数量</th>
        <th style="text-align: left; padding: 8px;">価格</th>
        <th style="text-align: left; padding: 8px;">小計</th>
        <th style="text-align: left; padding: 8px;">操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orderItems as $item): ?>
        <tr class="item-row" style="border-bottom: 1px solid #ddd;">
          <td style="padding: 8px;">
            <select name="menu_id[]" class="menu-select" data-item-id="<?= $item['id'] ?>" onchange="updatePrice(this)">
              <option value="">メニューを選択</option>
              <?php foreach ($menuItems as $menu): ?>
                <option value="<?= $menu['id'] ?>" data-price="<?= $menu['price_s'] ?>" <?= $item['menu_id'] == $menu['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($menu['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </td>
          <td style="padding: 8px;">
            <input type="number" name="quantity[]" class="quantity-input" value="<?= $item['quantity'] ?>" min="1" onchange="updatePrice(this)" style="width: 60px;">
          </td>
          <td style="padding: 8px;">
            <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
            <input type="hidden" name="price[]" class="price-input" value="<?= $item['price'] ?>">
            <span class="price-display">¥<?= number_format($item['price']) ?></span>
          </td>
          <td style="padding: 8px;">
            <span class="subtotal-display">¥<?= number_format($item['price'] * $item['quantity']) ?></span>
          </td>
          <td style="padding: 8px;">
            <button type="button" onclick="removeItem(this)" class="btn red" style="padding: 5px 10px;">削除</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div style="margin-top: 15px;">
    <button type="button" onclick="addItem()" class="btn blue">アイテムを追加</button>
  </div>

  <div style="margin-top: 20px; font-size: 18px; font-weight: bold;">
    合計金額: <span id="total-amount">¥0</span>
  </div>
  
  <button type="submit" class="btn blue">保存</button>
  <a href="orders.php" class="btn">キャンセル</a>
</form>

<script>
  // Menu items data for JavaScript
  const menuData = <?php echo json_encode(array_map(fn($m) => ['id' => $m['id'], 'name' => $m['name'], 'price' => $m['price_s']], $menuItems)); ?>;

  function updatePrice(element) {
    const row = element.closest('tr');
    const menuSelect = row.querySelector('.menu-select');
    const quantityInput = row.querySelector('.quantity-input');
    const priceInput = row.querySelector('.price-input');
    const priceDisplay = row.querySelector('.price-display');
    const subtotalDisplay = row.querySelector('.subtotal-display');

    const selectedOption = menuSelect.options[menuSelect.selectedIndex];
    const price = selectedOption.dataset.price || 0;
    const quantity = quantityInput.value || 0;

    priceInput.value = price;
    priceDisplay.textContent = '¥' + parseInt(price).toLocaleString('ja-JP');
    subtotalDisplay.textContent = '¥' + (parseInt(price) * parseInt(quantity)).toLocaleString('ja-JP');

    calculateTotal();
  }

  function removeItem(button) {
    button.closest('tr').remove();
    calculateTotal();
  }

  function addItem() {
    const tbody = document.querySelector('#items-table tbody');
    const newRow = document.createElement('tr');
    newRow.className = 'item-row';
    newRow.style.borderBottom = '1px solid #ddd';
    newRow.innerHTML = `
      <td style="padding: 8px;">
        <select name="menu_id[]" class="menu-select" onchange="updatePrice(this)">
          <option value="">メニューを選択</option>
          <?php foreach ($menuItems as $menu): ?>
            <option value="<?= $menu['id'] ?>" data-price="<?= $menu['price_s'] ?>">
              <?= htmlspecialchars($menu['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </td>
      <td style="padding: 8px;">
        <input type="number" name="quantity[]" class="quantity-input" value="1" min="1" onchange="updatePrice(this)" style="width: 60px;">
      </td>
      <td style="padding: 8px;">
        <input type="hidden" name="item_id[]" value="">
        <input type="hidden" name="price[]" class="price-input" value="0">
        <span class="price-display">¥0</span>
      </td>
      <td style="padding: 8px;">
        <span class="subtotal-display">¥0</span>
      </td>
      <td style="padding: 8px;">
        <button type="button" onclick="removeItem(this)" class="btn red" style="padding: 5px 10px;">削除</button>
      </td>
    `;
    tbody.appendChild(newRow);
    calculateTotal();
  }

  function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
      const priceInput = row.querySelector('.price-input');
      const quantityInput = row.querySelector('.quantity-input');
      const price = parseInt(priceInput.value) || 0;
      const quantity = parseInt(quantityInput.value) || 0;
      total += price * quantity;
    });
    document.getElementById('total-amount').textContent = '¥' + total.toLocaleString('ja-JP');
  }

  // Calculate total on page load
  document.addEventListener('DOMContentLoaded', calculateTotal);
</script>

</body>
</html>
