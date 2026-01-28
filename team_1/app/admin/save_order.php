<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)$_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $status = $_POST['status'];
    
    // Validate status
    $validStatuses = ['New', 'In Progress', 'Completed', 'Canceled'];
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
    
    // Update customer details (including address)
    $updateCustomerQuery = "UPDATE customer SET name = ?, phone = ?, address = ? WHERE id = ?";
    $stmt = $mysqli->prepare($updateCustomerQuery);
    $stmt->bind_param('sssi', $name, $phone, $address, $customerId);
    $stmt->execute();
    $stmt->close();
    
    // Update order status
    $updateOrderQuery = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $mysqli->prepare($updateOrderQuery);
    $stmt->bind_param('si', $status, $orderId);
    $stmt->execute();
    $stmt->close();
    
    // Update order items
    if (isset($_POST['menu_id']) && is_array($_POST['menu_id'])) {
        foreach ($_POST['menu_id'] as $index => $menuId) {
            $menuId = (int)$menuId;
            $itemId = (int)($_POST['item_id'][$index] ?? 0);
            $quantity = (int)($_POST['quantity'][$index] ?? 0);
            $price = (int)($_POST['price'][$index] ?? 0);
            
            if ($menuId > 0 && $quantity > 0 && $price > 0) {
                if ($itemId > 0) {
                    // Update existing order item
                    $updateItemQuery = "UPDATE order_items SET menu_id = ?, quantity = ?, price = ? WHERE id = ? AND order_id = ?";
                    $stmt = $mysqli->prepare($updateItemQuery);
                    $stmt->bind_param('iiiii', $menuId, $quantity, $price, $itemId, $orderId);
                } else {
                    // Insert new order item
                    $insertItemQuery = "INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)";
                    $stmt = $mysqli->prepare($insertItemQuery);
                    $stmt->bind_param('iiii', $orderId, $menuId, $quantity, $price);
                }
                $stmt->execute();
                $stmt->close();
            } elseif ($itemId > 0) {
                // Delete item if quantity is 0 or menu not selected
                $deleteItemQuery = "DELETE FROM order_items WHERE id = ? AND order_id = ?";
                $stmt = $mysqli->prepare($deleteItemQuery);
                $stmt->bind_param('ii', $itemId, $orderId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    header("Location: orders.php");
    exit;
}
?>
