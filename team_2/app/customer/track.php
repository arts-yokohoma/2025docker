<?php
include '../database/db_conn.php';

// Get order ID from URL parameter or POST
$order_id = htmlspecialchars($_GET['order_id'] ?? $_POST['order_id'] ?? '');

if ($order_id) {
    // Fetch order from database
    $query = "SELECT * FROM orders WHERE order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        echo "<div class='order-info'>";
        echo "<h2>Order Details</h2>";
        echo "<p><strong>Order ID:</strong> " . htmlspecialchars($order['order_id']) . "</p>";
        echo "<p><strong>Customer Name:</strong> " . htmlspecialchars($order['customer_name']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($order['email']) . "</p>";
        echo "<p><strong>Product:</strong> " . htmlspecialchars($order['product']) . "</p>";
        echo "<p><strong>Quantity:</strong> " . htmlspecialchars($order['quantity']) . "</p>";
        echo "<p><strong>Total Price:</strong> $" . htmlspecialchars($order['total_price']) . "</p>";
        echo "</div>";
    } else {
        echo "Order not found.";
    }
    $stmt->close();
} else {
    echo "No order ID provided.";
}
?>
