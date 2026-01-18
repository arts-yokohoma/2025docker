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

/* ===== build delivery address string (без comment) ===== */
$deliveryAddress = trim(implode(' ', array_filter([
    !empty($address['zip']) ? '〒' . $address['zip'] : '',
    $address['pref'] ?? '',
    $address['city'] ?? '',
    $address['street'] ?? ''
])));

// Сохраняем comment отдельно для использования в адресе доставки
$deliveryComment = trim($address['comment'] ?? '');

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

/* ===== Вычисляем время доставки ===== */
date_default_timezone_set('Asia/Tokyo');
$now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
$deliveryTimeRaw = $_SESSION['delivery_time'] ?? 'ASAP';
$deliveryTime = null; // Будет в формате DATETIME или строки

// Получаем время работы магазина для вычисления ASAP
$storeHours = null;
$res = $mysqli->query("SELECT open_time, close_time FROM store_hours WHERE id=1 AND active=1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $storeHours = [
        'open_time' => substr((string)$row['open_time'], 0, 5),
        'close_time' => substr((string)$row['close_time'], 0, 5)
    ];
    $res->free();
}

if ($deliveryTimeRaw === 'ASAP') {
    if ($storeHours) {
        // Есть данные о времени работы - вычисляем нормально
        [$openH, $openM] = explode(':', $storeHours['open_time']);
        [$closeH, $closeM] = explode(':', $storeHours['close_time']);
        
        // Вычисляем ASAP: текущее время + 30-45 минут (берем среднее 37 минут)
        $asapTime = clone $now;
        $asapTime->modify('+37 minutes');
        
        // Проверяем, работает ли магазин сейчас
        $currentMinutes = (int)$now->format('H') * 60 + (int)$now->format('i');
        $openMinutes = (int)$openH * 60 + (int)$openM;
        $closeMinutes = (int)$closeH * 60 + (int)$closeM;
        $isStoreOpen = ($currentMinutes >= $openMinutes && $currentMinutes < $closeMinutes);
        
        if (!$isStoreOpen) {
            // Если магазин закрыт, доставка будет после открытия + 30 минут
            $todayOpen = clone $now;
            $todayOpen->setTime((int)$openH, (int)$openM, 0);
            if ($todayOpen < $now) {
                $todayOpen->modify('+1 day');
            }
            $asapTime = clone $todayOpen;
            $asapTime->modify('+30 minutes');
        }
        
        $deliveryTime = $asapTime->format('Y-m-d H:i:s');
    } else {
        // Нет данных о времени работы - магазин считается закрытым
        // Для ASAP используем завтра 11:30 (дефолтное время открытия + 30 минут)
        $asapTime = clone $now;
        $asapTime->modify('+1 day');
        $asapTime->setTime(11, 30, 0);
        $deliveryTime = $asapTime->format('Y-m-d H:i:s');
    }
    
} else if (preg_match('/^(today|tomorrow|day_after)_(\d{2}):(\d{2})$/', $deliveryTimeRaw, $matches)) {
    // Формат: "today_14:30" или "tomorrow_18:00"
    $dateKey = $matches[1];
    $hour = (int)$matches[2];
    $minute = (int)$matches[3];
    
    $targetDate = clone $now;
    if ($dateKey === 'tomorrow') {
        $targetDate->modify('+1 day');
    } else if ($dateKey === 'day_after') {
        $targetDate->modify('+2 days');
    }
    
    $targetDate->setTime($hour, $minute, 0);
    $deliveryTime = $targetDate->format('Y-m-d H:i:s');
    
} else {
    // Fallback: сохраняем как есть (строка)
    $deliveryTime = $deliveryTimeRaw;
}

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
        INSERT INTO orders (customer_id, delivery_address, delivery_comment, delivery_time, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception($mysqli->error);

    $stmt->bind_param("isssis", $customerId, $deliveryAddress, $deliveryComment, $deliveryTime, $totalPrice, $status);
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
