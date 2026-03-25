<?php
// customer/track_logic.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db_conn.php';

$order = null;
$error_message = '';

// Order ID (GET or POST)
$order_id = isset($_GET['order_id'])
    ? intval($_GET['order_id'])
    : (isset($_POST['order_id']) ? intval($_POST['order_id']) : 0);

if ($order_id <= 0) {
    $error_message = 'Order ID မရှိပါ။';
    return;
}

// Fetch order
$sql = "SELECT * FROM orders WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error_message = 'Order မတွေ့ပါ။';
    } else {
        $order = $result->fetch_assoc();

        /* =====================
           Price Calculation
        ===================== */
        $size = $order['pizza_type'];
        $qty  = (int)$order['quantity'];

        switch ($size) {
            case 'L':
                $unit_price = 3000;
                break;
            case 'M':
                $unit_price = 2000;
                break;
            default:
                $unit_price = 1000;
        }

        $order['unit_price']  = $unit_price;
        $order['total_price'] = $unit_price * $qty;
    }

    $stmt->close();
} else {
    $error_message = 'Database Error';
}
