<?php
// customer/track.php
include '../database/db_conn.php';

// Get order ID from URL parameter or POST
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : (isset($_POST['order_id']) ? intval($_POST['order_id']) : 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .order-info { background: white; padding: 20px; border-radius: 8px; max-width: 400px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        p { border-bottom: 1px solid #eee; padding: 10px 0; margin: 0; }
        .label { font-weight: bold; color: #555; }
        .status { font-weight: bold; color: #007bff; }
    </style>
</head>
<body>

<?php
if ($order_id > 0) {
    // FIX: Changed 'order_id' to 'id'
    $query = "SELECT * FROM orders WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Price Calculation
        $size = $order['pizza_type']; 
        $qty = $order['quantity'];
        $unit_price = ($size == 'L') ? 3000 : (($size == 'M') ? 2000 : 1000); 
        $total_price = $unit_price * $qty;

        echo "<div class='order-info'>";
        echo "<h2>Order Details</h2>";
        echo "<p><span class='label'>Order ID:</span> #" . $order['id'] . "</p>";
        echo "<p><span class='label'>Customer Name:</span> " . htmlspecialchars($order['customer_name']) . "</p>";
        // FIX: Changed 'email' to 'phonenumber'
        echo "<p><span class='label'>Phone:</span> " . htmlspecialchars($order['phonenumber']) . "</p>";
        // FIX: Changed 'product' to 'pizza_type'
        echo "<p><span class='label'>Pizza:</span> " . htmlspecialchars($order['pizza_type']) . "</p>";
        echo "<p><span class='label'>Quantity:</span> " . $order['quantity'] . "</p>";
        echo "<p><span class='label'>Total Price:</span> ¥" . number_format($total_price) . "</p>";
        echo "<p><span class='label'>Status:</span> <span class='status'>" . $order['status'] . "</span></p>";
        echo "</div>";
    } else {
        echo "<h3 style='text-align:center; color:red;'>❌ Order not found.</h3>";
    }
    $stmt->close();
} else {
    echo "<h3 style='text-align:center;'>Please provide an Order ID.</h3>";
}
?>
</body>
</html>