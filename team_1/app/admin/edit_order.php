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

// Fetch order items
$itemsQuery = "
SELECT oi.id, oi.menu_id, oi.quantity, m.name
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

$orderToEdit = [
    'id' => $orderData['id'],
    'date' => date('Y-m-d H:i', strtotime($orderData['date'])),
    'name' => $orderData['name'] ?? 'Unknown',
    'phone' => $orderData['phone'] ?? 'N/A',
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
  
  <label>注文詳細:</label>
  <div id="items-container">
    <?php foreach ($orderItems as $index => $item): ?>
      <div class="item-row" style="margin-bottom: 10px; padding: 10px; border: 1px solid #ddd;">
        <input type="hidden" name="item_ids[<?= $index ?>]" value="<?= $item['id'] ?>">
        <label>メニュー名:</label>
        <input type="text" name="item_names[<?= $index ?>]" value="<?= htmlspecialchars($item['name']) ?>" required>
        <label>数量:</label>
        <input type="number" name="item_quantities[<?= $index ?>]" value="<?= $item['quantity'] ?>" min="1" required>
      </div>
    <?php endforeach; ?>
  </div>
  
  <label>ステータス:</label>
  <select name="status" required>
    <option value="New" <?= $orderToEdit['status'] === 'New' ? 'selected' : '' ?>>新規</option>
    <option value="In Progress" <?= $orderToEdit['status'] === 'In Progress' ? 'selected' : '' ?>>調理中</option>
    <option value="Completed" <?= $orderToEdit['status'] === 'Completed' ? 'selected' : '' ?>>完了</option>
  </select>
  
  <button type="submit" class="btn blue">保存</button>
  <a href="orders.php" class="btn">キャンセル</a>
</form>

</body>
</html>
