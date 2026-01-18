<?php
session_start();
require __DIR__ . '/../config/db.php';

/* ===== guard: cart ===== */
$cart = json_decode($_POST['cart_json'] ?? '{}', true);
if (empty($cart)) {
    header('Location: index.php');
    exit;
}

/* ===== guard: session ===== */
$user = $_SESSION['order']['user'] ?? null;
$address = $_SESSION['order']['address'] ?? null;

if (!$user || !$address) {
    header('Location: index.php');
    exit;
}

/* ===== build delivery address string ===== */
$deliveryAddress = trim(implode(' ', array_filter([
    !empty($address['zip']) ? '〒' . $address['zip'] : '',
    $address['pref'] ?? '',
    $address['city'] ?? '',
    $address['street'] ?? '',
    $address['comment'] ?? ''
])));


$totalPrice = 0;
foreach ($cart as $item) {
    $price = (int)($item['price'] ?? 0);
    $qty   = (int)($item['qty'] ?? 0);
    $totalPrice += $price * $qty;
}
if ($totalPrice <= 0) {
    header('Location: index.php');
    exit;
}

/* ===== meta ===== */
// Получаем время доставки из сессии или используем ASAP
$deliveryTime = $_SESSION['delivery_time'] ?? 'ASAP';
$status = 'NEW';

/* ===== transaction ===== */
$mysqli->begin_transaction();

try {
    /* 1) customer */
    $stmt = $mysqli->prepare("
        INSERT INTO customer (name, email, phone, address, active)
        VALUES (?, ?, ?, ?, 1)
    ");
    if (!$stmt) throw new Exception($mysqli->error);

    $name  = (string)($user['name'] ?? '');
    $email = (string)($user['email'] ?? '');
    $phone = (string)($user['phone'] ?? '');

    $stmt->bind_param("ssss", $name, $email, $phone, $deliveryAddress);
    $stmt->execute();
    $customerId = $stmt->insert_id;
    $stmt->close();

    /* 2) orders */
    $stmt = $mysqli->prepare("
        INSERT INTO orders (customer_id, delivery_address, delivery_time, total_price, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception($mysqli->error);

    $stmt->bind_param("issis", $customerId, $deliveryAddress, $deliveryTime, $totalPrice, $status);
    $stmt->execute();
    $orderId = $stmt->insert_id;
    $stmt->close();

    /* 3) order_items */
    $itemStmt = $mysqli->prepare("
        INSERT INTO order_items (order_id, menu_id, size, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$itemStmt) throw new Exception($mysqli->error);

    foreach ($cart as $key => $item) {
        // Извлекаем menu_id (оригинальный ID из БД, без суффикса _S/_M/_L)
        $menuId = (int)($item['menu_id'] ?? $item['id'] ?? 0);
        // Если id содержит "_S", "_M", "_L", извлекаем число
        if (preg_match('/^(\d+)_[SML]$/', $item['id'] ?? $key, $matches)) {
            $menuId = (int)$matches[1];
        }
        
        $size   = (string)($item['size'] ?? 'M'); // Размер по умолчанию M
        $qty    = (int)($item['qty'] ?? 0);
        $price  = (int)($item['price'] ?? 0);

        if ($menuId <= 0 || $qty <= 0 || $price <= 0) {
            throw new Exception('Invalid cart item');
        }

        $itemStmt->bind_param("issii", $orderId, $menuId, $size, $qty, $price);
        $itemStmt->execute();
    }

    $itemStmt->close();
    $mysqli->commit();

} catch (Throwable $e) {
    $mysqli->rollback();
    exit('注文処理に失敗しました');
}

/* ===== PRG: store id once, redirect to complete ===== */
$_SESSION['last_order_id'] = $orderId;   // чтобы complete знала, что показывать
unset($_SESSION['order']);               // очищаем промежуточные данные
header('Location: order_complete.php');  // финальная страница
exit;
