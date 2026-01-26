<?php
session_start();
require_once 'db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    
    $small_qty = intval($_POST['small_qty'] ?? 0);
    $medium_qty = intval($_POST['medium_qty'] ?? 0);
    $large_qty = intval($_POST['large_qty'] ?? 0);
    
    $small_price = floatval($_POST['small_price'] ?? 0);
    $medium_price = floatval($_POST['medium_price'] ?? 0);
    $large_price = floatval($_POST['large_price'] ?? 0);
    
    // Validate required fields
    if (empty($name) || empty($phone) || empty($address)) {
        header("Location: order.php?error=required");
        exit;
    }
    
    // Validate quantity
    if ($small_qty + $medium_qty + $large_qty === 0) {
        header("Location: order.php?error=quantity");
        exit;
    }
    
    // Calculate total
    $total_amount = ($small_qty * $small_price) + 
                    ($medium_qty * $medium_price) + 
                    ($large_qty * $large_price);
    
    // Try to save to database
    $order_id = null;
    if (isset($pdo)) {
        try {
            // Generate unique order number
            $order_number = 'PH-' . date('YmdHis') . rand(100, 999);
            
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    order_number, customer_name, customer_phone, customer_email,
                    customer_address, small_quantity, medium_quantity, large_quantity,
                    small_price, medium_price, large_price, total_amount,
                    special_instructions, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
                RETURNING id
            ");
            
            $stmt->execute([
                $order_number, $name, $phone, $email, $address,
                $small_qty, $medium_qty, $large_qty,
                $small_price, $medium_price, $large_price,
                $total_amount, $instructions
            ]);
            
            $result = $stmt->fetch();
            $order_id = $result['id'];
            
        } catch (Exception $e) {
            // Log error but continue with session
            error_log("Order save error: " . $e->getMessage());
        }
    }
    
    // Store in session for receipt
    $_SESSION['order_data'] = [
        'order_id' => $order_id ? 'PH-' . str_pad($order_id, 6, '0', STR_PAD_LEFT) : 'PH-' . date('YmdHis') . rand(1000, 9999),
        'db_id' => $order_id,
        'order_date' => date('Y/m/d'),
        'order_time' => date('H:i'),
        'customer_name' => $name,
        'customer_phone' => $phone,
        'customer_email' => $email,
        'customer_address' => $address,
        'small_qty' => $small_qty,
        'medium_qty' => $medium_qty,
        'large_qty' => $large_qty,
        'small_price' => $small_price,
        'medium_price' => $medium_price,
        'large_price' => $large_price,
        'total_amount' => $total_amount,
        'instructions' => $instructions
    ];
    
    // Redirect to receipt
    if ($order_id) {
        header("Location: receipt.php?order_id=" . $order_id);
    } else {
        header("Location: receipt.php");
    }
    exit;
    
} else {
    header("Location: order.php");
    exit;
}
?>