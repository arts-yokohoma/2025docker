<?php
// App/admin/print_ticket.php
require_once '../database/db_conn.php';

if (!isset($_GET['id'])) die("No ID");
$id = intval($_GET['id']);

$sql = "SELECT * FROM orders WHERE id = $id";
$res = $conn->query($sql);
if ($res->num_rows == 0) die("Order Not Found");
$order = $res->fetch_assoc();

// Price Calculation
$unit_price = ($order['pizza_type'] == 'S') ? 1000 : (($order['pizza_type'] == 'M') ? 2000 : 3000);
$total = $unit_price * $order['quantity'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Ticket #<?= $id ?></title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 0; padding: 10px; width: 300px; font-size: 14px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .big { font-size: 1.5em; }
        hr { border: 1px dashed #000; }
        .right { text-align: right; }
        @media print { @page { margin: 0; } }
    </style>
</head>
<body onload="window.print()"> <div class="center">
        <h2 style="margin:0;">FAST PIZZA</h2>
        <p>Kitchen Ticket (Denpyou)</p>
        <p><?= date('Y-m-d H:i') ?></p>
    </div>
    
    <hr>
    
    <div style="font-size: 1.2em; font-weight: bold;">
        Order No: #<?= $order['id'] ?>
    </div>
    
    <div style="margin: 10px 0; border: 2px solid black; padding: 5px;">
        <span style="background: black; color: white; padding: 2px;">ITEM:</span><br>
        <div class="big bold center">
            <?= $order['pizza_type'] ?> Pizza <br>
            x <?= $order['quantity'] ?>
        </div>
    </div>

    <div>
        <strong>Customer:</strong> <?= htmlspecialchars($order['customer_name']) ?><br>
        <strong>Phone:</strong> <?= htmlspecialchars($order['phonenumber']) ?><br>
        <strong>Addr:</strong> <?= htmlspecialchars($order['address']) ?>
    </div>

    <hr>
    
    <div class="right bold big">
        Total: ¥<?= number_format($total) ?>
    </div>

</body>
</html>