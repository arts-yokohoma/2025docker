<?php
// process_order.php
require_once 'db/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $instructions = $_POST['instructions'] ?? '';
    
    $small_qty = intval($_POST['small_qty'] ?? 0);
    $medium_qty = intval($_POST['medium_qty'] ?? 0);
    $large_qty = intval($_POST['large_qty'] ?? 0);
    
    $small_price = floatval($_POST['small_price'] ?? 0);
    $medium_price = floatval($_POST['medium_price'] ?? 0);
    $large_price = floatval($_POST['large_price'] ?? 0);
    
    // Calculate totals
    $small_total = $small_qty * $small_price;
    $medium_total = $medium_qty * $medium_price;
    $large_total = $large_qty * $large_price;
    $total_amount = $small_total + $medium_total + $large_total;
    
    // Validate
    if (empty($name) || empty($phone) || empty($address)) {
        die('必要な情報を入力してください。');
    }
    
    if ($total_amount <= 0) {
        die('数量を選択してください。');
    }
    
    // Save to database
    if (isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    customer_name, customer_phone, customer_email, customer_address,
                    small_quantity, medium_quantity, large_quantity,
                    small_price, medium_price, large_price,
                    total_amount, special_instructions, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                RETURNING id
            ");
            
            $stmt->execute([
                $name, $phone, $email, $address,
                $small_qty, $medium_qty, $large_qty,
                $small_price, $medium_price, $large_price,
                $total_amount, $instructions
            ]);
            
            $order = $stmt->fetch();
            $order_id = $order['id'];
            
            // Redirect to receipt page
            header("Location: receipt.php?order_id=" . $order_id);
            exit;
            
        } catch (Exception $e) {
            die("注文処理中にエラーが発生しました: " . $e->getMessage());
        }
    } else {
        // If no database, redirect with data in URL (temporary)
        $data = http_build_query([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'address' => $address,
            'small_qty' => $small_qty,
            'medium_qty' => $medium_qty,
            'large_qty' => $large_qty,
            'small_price' => $small_price,
            'medium_price' => $medium_price,
            'large_price' => $large_price,
            'total_amount' => $total_amount,
            'instructions' => $instructions
        ]);
        header("Location: receipt.php?" . $data);
        exit;
    }
} else {
    header("Location: order.php");
    exit;
}
?>