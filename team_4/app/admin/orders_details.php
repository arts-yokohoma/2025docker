<?php
// admin/order_details.php
session_start();
require_once '../db/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get order ID
$orderId = $_GET['id'] ?? 0;

try {
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        die("Order not found");
    }
    
    // Calculate item totals
    $small_total = $order['small_quantity'] * $order['small_price'];
    $medium_total = $order['medium_quantity'] * $order['medium_price'];
    $large_total = $order['large_quantity'] * $order['large_price'];
    
} catch (Exception $e) {
    die("Error loading order: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Order Details - <?php echo $order['order_number']; ?></title>
    <link rel="stylesheet" href="css/adminstyle.css">
    <style>
        .order-details-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-header {
            background: #d19758;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            margin: -20px -20px 20px -20px;
        }
        
        .order-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items-table th {
            background: #e9ecef;
            padding: 10px;
            text-align: left;
        }
        
        .order-items-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="order-details-container">
        <div class="order-header">
            <h1>Order Details: <?php echo $order['order_number']; ?></h1>
            <a href="dashboard.php" style="color: white; text-decoration: underline;">← Back to Dashboard</a>
        </div>
        
        <div class="order-section">
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>
        </div>
        
        <div class="order-section">
            <h3>Order Items</h3>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($order['small_quantity'] > 0): ?>
                    <tr>
                        <td>スモールピザ (20cm)</td>
                        <td><?php echo $order['small_quantity']; ?></td>
                        <td>¥<?php echo number_format($order['small_price']); ?></td>
                        <td>¥<?php echo number_format($small_total); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($order['medium_quantity'] > 0): ?>
                    <tr>
                        <td>ミディアムピザ (30cm)</td>
                        <td><?php echo $order['medium_quantity']; ?></td>
                        <td>¥<?php echo number_format($order['medium_price']); ?></td>
                        <td>¥<?php echo number_format($medium_total); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if ($order['large_quantity'] > 0): ?>
                    <tr>
                        <td>ラージピザ (40cm)</td>
                        <td><?php echo $order['large_quantity']; ?></td>
                        <td>¥<?php echo number_format($order['large_price']); ?></td>
                        <td>¥<?php echo number_format($large_total); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                        <td style="font-weight: bold; color: #d19758; font-size: 18px;">
                            ¥<?php echo number_format($order['total_amount']); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="order-section">
            <h3>Order Status & Information</h3>
            <p><strong>Status:</strong> 
                <span class="status-badge" style="background: 
                    <?php 
                    $statusColors = [
                        'pending' => '#ffc107',
                        'confirmed' => '#17a2b8',
                        'preparing' => '#007bff',
                        'out_for_delivery' => '#6f42c1',
                        'delivered' => '#28a745',
                        'cancelled' => '#dc3545'
                    ];
                    echo $statusColors[$order['status']] ?? '#6c757d';
                    ?>
                ">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </p>
            <p><strong>Order Date:</strong> <?php echo date('Y/m/d H:i', strtotime($order['order_date'])); ?></p>
            
            <?php if (!empty($order['special_instructions'])): ?>
            <p><strong>Special Instructions:</strong><br>
                <?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?>
            </p>
            <?php endif; ?>
        </div>
        
        <div class="order-section">
            <h3>Update Status</h3>
            <select id="statusSelect" onchange="updateStatus(this.value)">
                <option value="">Select New Status</option>
                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="out_for_delivery" <?php echo $order['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button onclick="printOrder()" style="margin-left: 10px;">Print Order</button>
        </div>
    </div>

    <script>
    function updateStatus(newStatus) {
        if (!newStatus) return;
        
        if (confirm('Change order status to ' + newStatus + '?')) {
            fetch('orders_data.php?action=update_status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: <?php echo $orderId; ?>,
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order status updated successfully!');
                    location.reload();
                } else {
                    alert('Error updating status: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error updating order status');
            });
        }
    }
    
    function printOrder() {
        window.print();
    }
    </script>
</body>
</html>