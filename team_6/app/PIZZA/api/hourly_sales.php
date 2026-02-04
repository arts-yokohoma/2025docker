<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Tokyo');

include '../db/connect.php';

$today = date('Y-m-d');
$start_h = 10;
$end_h   = 22;

$hourly_sales  = array_fill($start_h, ($end_h - $start_h + 1), 0.0);
$hourly_orders = array_fill($start_h, ($end_h - $start_h + 1), 0);

try {
    $stmt = $db->prepare("
        SELECT
            EXTRACT(HOUR FROM order_date AT TIME ZONE 'UTC' AT TIME ZONE 'Asia/Tokyo') AS h,
            SUM(total_amount) AS amt,
            SUM(quantity) AS cnt
        FROM sales
        WHERE order_date::date = ?
        GROUP BY h
        ORDER BY h
    ");
    
    // FIXED: Only one $today is needed here
    $stmt->execute([$today]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $h = (int)$row['h'];
        if (isset($hourly_sales[$h])) {
            $hourly_sales[$h]  = (float)$row['amt'];
            $hourly_orders[$h] = (int)$row['cnt'];
        }
    }

    echo json_encode([
        'sales'  => array_values($hourly_sales),
        'orders' => array_values($hourly_orders)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $db->errorInfo()]);
}