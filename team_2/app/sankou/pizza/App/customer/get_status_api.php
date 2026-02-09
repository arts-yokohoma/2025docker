<?php
// customer/get_status_api.php
require_once '../database/db_conn.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT status, start_time, order_date, reject_reason FROM orders WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if ($order) {
    // Timer တွက်ချက်ခြင်း
    $remaining = 0;
    if ($order['status'] == 'Cooking' || $order['status'] == 'Delivering') {
        $time_string = !empty($order['start_time']) ? $order['start_time'] : $order['order_date'];
        $target = strtotime($time_string) + (30 * 60); // 30 Minutes Target
        $remaining = max(0, $target - time());
    }

    echo json_encode([
        'status' => $order['status'],
        'remaining_seconds' => $remaining,
        'reject_reason' => $order['reject_reason'] ?? ''
    ]);
} else {
    echo json_encode(['error' => 'Not Found']);
}
?>