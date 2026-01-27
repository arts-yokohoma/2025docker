<?php
require_once __DIR__ . '/auth.php';
requireRoles(['admin', 'manager', 'driver', 'kitchen']); // Require specific roles

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $status = $_POST['status'];
    
    // Validate status
    $validStatuses = ['New', 'In Progress', 'Completed'];
    if (!in_array($status, $validStatuses)) {
        die('Invalid status');
    }
    
    // Get customer ID from order
    $getCustomerQuery = "SELECT customer_id FROM orders WHERE id = ?";
    $stmt = $mysqli->prepare($getCustomerQuery);
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $customerId = $row['customer_id'];
    $stmt->close();
    
    // Update customer details
    $updateCustomerQuery = "UPDATE customer SET name = ?, phone = ? WHERE id = ?";
    $stmt = $mysqli->prepare($updateCustomerQuery);
    $stmt->bind_param('ssi', $name, $phone, $customerId);
    $stmt->execute();
    $stmt->close();
    
    // Update order status
    $updateOrderQuery = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $mysqli->prepare($updateOrderQuery);
    $stmt->bind_param('si', $status, $orderId);
    $stmt->execute();
    $stmt->close();
    
    // Update order items if they exist
    if (isset($_POST['item_ids']) && is_array($_POST['item_ids'])) {
        foreach ($_POST['item_ids'] as $index => $itemId) {
            $itemId = (int)$itemId;
            $quantity = (int)$_POST['item_quantities'][$index];
            
            if ($quantity > 0) {
                // Update order item quantity
                $updateItemQuery = "UPDATE order_items SET quantity = ? WHERE id = ? AND order_id = ?";
                $stmt = $mysqli->prepare($updateItemQuery);
                $stmt->bind_param('iii', $quantity, $itemId, $orderId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    header("Location: orders.php");
    exit;
}
?>
