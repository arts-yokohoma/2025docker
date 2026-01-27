<?php
require_once __DIR__ . '/auth.php';
requireRoles(['admin', 'manager', 'driver', 'kitchen']); // Require specific roles

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID and status are required']);
    exit;
}

$orderId = (int)$_POST['id'];
$newStatus = $_POST['status'];

// Validate status
$validStatuses = ['New', 'In Progress', 'Completed'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update order status in database
$query = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = $mysqli->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param('si', $newStatus, $orderId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
}

$stmt->close();
?>
