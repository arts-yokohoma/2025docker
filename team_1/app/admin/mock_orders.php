<?php
require_once dirname(dirname(__FILE__)) . '/config/db.php';

// Fetch orders from database with order items and customer details
$query = "
SELECT 
  o.id,
  o.create_time as date,
  o.delivery_time,
  c.name,
  c.phone,
  c.address,
  GROUP_CONCAT(CONCAT(m.name, ' x', oi.quantity) SEPARATOR ', ') as item,
  o.status,
  COALESCE(SUM(oi.price * oi.quantity), 0) as total_amount
FROM orders o
LEFT JOIN customer c ON o.customer_id = c.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN menu m ON oi.menu_id = m.id
GROUP BY o.id, o.create_time, o.delivery_time, c.id, c.name, c.phone, c.address, o.status
ORDER BY o.create_time DESC
";

$result = $mysqli->query($query);

if (!$result) {
    die('Query failed: ' . $mysqli->error);
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    // Normalize status to proper case (New, In Progress, Completed, Canceled)
    $status = strtolower($row["status"] ?? "new");
    if (strpos($status, 'in progress') !== false) {
        $status = 'In Progress';
    } elseif ($status === 'completed') {
        $status = 'Completed';
    } elseif ($status === 'canceled') {
        $status = 'Canceled';
    } else {
        $status = 'New';
    }
    
    $deliveryRaw = $row['delivery_time'] ?? null;
    $delivery = null;
    if ($deliveryRaw && strtotime($deliveryRaw) !== false) {
        $delivery = date('Y-m-d H:i', strtotime($deliveryRaw));
    }

    $orders[] = [
        "id" => $row["id"],
        "date" => date('Y-m-d H:i', strtotime($row["date"])),
        "delivery_time" => $delivery,
        "name" => $row["name"] ?? "Unknown",
        "phone" => $row["phone"] ?? "N/A",
        "address" => $row["address"] ?? "N/A",
        "item" => $row["item"] ?? "No items",
        "status" => $status,
        "total_amount" => $row["total_amount"] ?? 0
    ];
}
