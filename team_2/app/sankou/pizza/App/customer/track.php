<?php
// customer/track.php
require_once 'track_logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f4f4f4; }
        .order-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 400px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; color: #333; }
        p { border-bottom: 1px solid #eee; padding: 10px 0; margin: 0; }
        .label { font-weight: bold; color: #555; }
        .status { font-weight: bold; color: #007bff; }
        .error { text-align:center; color:red; font-weight:bold; }
    </style>
</head>
<body>

<?php if (!empty($error_message)): ?>

    <p class="error">❌ <?= htmlspecialchars($error_message) ?></p>

<?php elseif ($order): ?>

    <div class="order-info">
        <h2>Order Details</h2>

        <p><span class="label">Order ID:</span> #<?= $order['id'] ?></p>
        <p><span class="label">Customer:</span> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p><span class="label">Phone:</span> <?= htmlspecialchars($order['phonenumber']) ?></p>
        <p><span class="label">Pizza:</span> <?= htmlspecialchars($order['pizza_type']) ?></p>
        <p><span class="label">Quantity:</span> <?= $order['quantity'] ?></p>
        <p><span class="label">Unit Price:</span> ¥<?= number_format($order['unit_price']) ?></p>
        <p><span class="label">Total:</span> ¥<?= number_format($order['total_price']) ?></p>
        <p>
            <span class="label">Status:</span>
            <span class="status"><?= htmlspecialchars($order['status']) ?></span>
        </p>
    </div>

<?php endif; ?>

</body>
</html>
