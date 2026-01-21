<?php
require_once __DIR__ . '/../../config/db.php';

// Fetch orders from database with order items and customer details
$query = "
SELECT 
  o.id,
  o.create_time as date,
  c.name,
  c.phone,
  GROUP_CONCAT(CONCAT(m.name, ' x', oi.quantity) SEPARATOR ', ') as item,
  o.status
FROM orders o
LEFT JOIN customer c ON o.customer_id = c.id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN menu m ON oi.menu_id = m.id
GROUP BY o.id, o.create_time, c.name, c.phone, o.status
ORDER BY o.create_time DESC
";

$result = $mysqli->query($query);

if (!$result) {
    die('Query failed: ' . $mysqli->error);
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    // Normalize status to proper case (New, In Progress, Completed)
    $status = strtolower($row["status"] ?? "new");
    if (strpos($status, 'in progress') !== false) {
        $status = 'In Progress';
    } elseif ($status === 'completed') {
        $status = 'Completed';
    } else {
        $status = 'New';
    }
    
    $orders[] = [
        "id" => $row["id"],
        "date" => date('Y-m-d H:i', strtotime($row["date"])),
        "name" => $row["name"] ?? "Unknown",
        "phone" => $row["phone"] ?? "N/A",
        "item" => $row["item"] ?? "No items",
        "status" => $status
    ];
}
