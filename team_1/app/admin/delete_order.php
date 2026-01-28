<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$orderId = (int)$_GET['id'];

// Cancel order by updating status to 'Canceled'
$query = "UPDATE orders SET status = 'Canceled' WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param('i', $orderId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order cancelled', 'status' => 'Canceled']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order: ' . $stmt->error]);
}

$stmt->close();
?>
