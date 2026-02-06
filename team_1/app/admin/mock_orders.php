<?php
require_once dirname(dirname(__FILE__)) . '/config/db.php';

date_default_timezone_set('Asia/Tokyo');

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
$nowTs = time();
$todayStr = date('Y-m-d', $nowTs);
$tomorrowStr = date('Y-m-d', strtotime('+1 day', $nowTs));

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
    $createTs = strtotime($row['date']);
    if ($deliveryRaw && strtotime($deliveryRaw) !== false) {
        $delivery = date('Y-m-d H:i', strtotime($deliveryRaw));
    }
    $createTime = date('Y-m-d H:i', $createTs);

    // Expected delivery: delivery_time if set, else create_time + 40 minutes
    $expectedDeliveryTs = $deliveryRaw ? strtotime($deliveryRaw) : ($createTs + 40 * 60);
    $expectedDeliveryFormatted = date('m/d H:i', $expectedDeliveryTs);
    $expectedDate = date('Y-m-d', $expectedDeliveryTs);
    if ($expectedDate === $tomorrowStr) {
        $expectedDeliveryFormatted = '明日 ' . date('H:i', $expectedDeliveryTs);
    } elseif ($expectedDate === $todayStr) {
        $expectedDeliveryFormatted = '今日 ' . date('H:i', $expectedDeliveryTs);
    }

    // For incomplete orders: remaining minutes until expected delivery (negative = overdue)
    $nokoriMinutes = null;
    $nokoriLabel = '';
    if ($status !== 'Completed' && $status !== 'Canceled') {
        $nokoriMinutes = (int) round(($expectedDeliveryTs - $nowTs) / 60);
        if ($nokoriMinutes > 0) {
            $nokoriLabel = 'のこり ' . $nokoriMinutes . '分';
        } else {
            $nokoriLabel = abs($nokoriMinutes) . '分すぎ';
        }
    }

    $orders[] = [
        "id" => $row["id"],
        "date" => $createTime,
        "delivery_time" => $delivery,
        "expected_delivery" => $expectedDeliveryFormatted,
        "expected_delivery_ts" => $expectedDeliveryTs,
        "nokori_label" => $nokoriLabel,
        "name" => $row["name"] ?? "Unknown",
        "phone" => $row["phone"] ?? "N/A",
        "address" => $row["address"] ?? "N/A",
        "item" => $row["item"] ?? "No items",
        "status" => $status,
        "total_amount" => $row["total_amount"] ?? 0
    ];
}
