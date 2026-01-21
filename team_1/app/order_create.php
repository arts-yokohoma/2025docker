<?php
session_start();
require __DIR__ . '/../config/db.php';

/**
 * Order creation endpoint
 * 
 * Processes order submission using database transaction:
 * 1. Creates customer record
 * 2. Creates order with delivery time and comment
 * 3. Creates order items with menu_id and size
 * 
 * Uses PRG (Post-Redirect-Get) pattern to prevent duplicate submissions
 */

// Validate cart data
$cart = json_decode($_POST['cart_json'] ?? '{}', true);
if (empty($cart)) {
    header('Location: index.php');
    exit;
}

// Validate session data (user info and address)
$user = $_SESSION['order']['user'] ?? null;
$address = $_SESSION['order']['address'] ?? null;

if (!$user || !$address) {
    header('Location: index.php');
    exit;
}

// Build delivery address string (without comment - stored separately)
$deliveryAddress = trim(implode(' ', array_filter([
    !empty($address['zip']) ? '〒' . $address['zip'] : '',
    $address['pref'] ?? '',
    $address['city'] ?? '',
    $address['street'] ?? ''
])));

// Store delivery comment separately in database
$deliveryComment = trim($address['comment'] ?? '');

// Calculate total price
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

/**
 * Calculate delivery time
 * 
 * Supports two formats:
 * - "ASAP": Calculates based on current time and store hours
 * - "today_14:30" / "tomorrow_18:00" / "day_after_12:00": Scheduled delivery
 */
date_default_timezone_set('Asia/Tokyo');
$now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
$deliveryTimeRaw = $_SESSION['delivery_time'] ?? 'ASAP';
$deliveryTime = null;

// Load store hours for ASAP calculation
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
        // Calculate ASAP based on store hours
        [$openH, $openM] = explode(':', $storeHours['open_time']);
        [$closeH, $closeM] = explode(':', $storeHours['close_time']);
        
        // ASAP = current time + 37 minutes (average prep + delivery time)
        $asapTime = clone $now;
        $asapTime->modify('+37 minutes');
        
        // Check if store is currently open
        $currentMinutes = (int)$now->format('H') * 60 + (int)$now->format('i');
        $openMinutes = (int)$openH * 60 + (int)$openM;
        $closeMinutes = (int)$closeH * 60 + (int)$closeM;
        $isStoreOpen = ($currentMinutes >= $openMinutes && $currentMinutes < $closeMinutes);
        
        if (!$isStoreOpen) {
            // If closed, delivery = next opening + 30 minutes
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
        // No store hours data - default to tomorrow 11:30
        $asapTime = clone $now;
        $asapTime->modify('+1 day');
        $asapTime->setTime(11, 30, 0);
        $deliveryTime = $asapTime->format('Y-m-d H:i:s');
    }
    
} else if (preg_match('/^(today|tomorrow|day_after)_(\d{2}):(\d{2})$/', $deliveryTimeRaw, $matches)) {
    // Parse scheduled delivery time: "today_14:30", "tomorrow_18:00", etc.
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
    // Fallback: store as-is (string)
    $deliveryTime = $deliveryTimeRaw;
}

$status = 'New';

/**
 * Validate data before transaction
 */
// Check if consent fields exist in customer table
$checkConsent = $mysqli->query("SHOW COLUMNS FROM customer LIKE 'consent'");
$hasConsentFields = $checkConsent && $checkConsent->num_rows > 0;
if ($checkConsent) $checkConsent->free();

// DEBUG: Uncomment to check consent data (remove after testing)
// echo "DEBUG consent data:<br>";
// echo "consent: " . ($user['privacy_consent'] ?? 'NOT SET') . "<br>";
// echo "consent_time: " . ($user['consent_time'] ?? 'NOT SET') . "<br>";
// exit;

// Validate delivery_time format for DATETIME
if ($deliveryTime && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $deliveryTime)) {
    exit('配達時間の形式が正しくありません: ' . htmlspecialchars($deliveryTime) . '<br><a href="cart.php">← カートに戻る</a>');
}

/**
 * Database transaction: create customer, order, and order items
 * All operations must succeed or entire transaction is rolled back
 */
$mysqli->begin_transaction();

try {
    // 1) Create customer record
    $name  = (string)($user['name'] ?? '');
    $email = (string)($user['email'] ?? '');
    $phone = (string)($user['phone'] ?? '');
    
    if ($hasConsentFields) {
        // Customer table has consent fields
        $stmt = $mysqli->prepare("
            INSERT INTO customer (name, email, phone, address, consent, consent_time, active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        if (!$stmt) throw new Exception("Customer prepare failed: " . $mysqli->error);
        
        $consent = (int)($user['privacy_consent'] ?? 0);
        $consentTime = $user['consent_time'] ?? null;
        
        $stmt->bind_param("ssssis", $name, $email, $phone, $deliveryAddress, $consent, $consentTime);
    } else {
        // Old schema without consent fields
        $stmt = $mysqli->prepare("
            INSERT INTO customer (name, email, phone, address, active)
            VALUES (?, ?, ?, ?, 1)
        ");
        if (!$stmt) throw new Exception("Customer prepare failed: " . $mysqli->error);
        
        $stmt->bind_param("ssss", $name, $email, $phone, $deliveryAddress);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Customer insert failed: " . $stmt->error);
    }
    $customerId = $stmt->insert_id;
    $stmt->close();

    // 2) Create order record
    $stmt = $mysqli->prepare("
        INSERT INTO orders (customer_id, delivery_address, delivery_comment, delivery_time, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception("Order prepare failed: " . $mysqli->error);

    $stmt->bind_param("isssis", $customerId, $deliveryAddress, $deliveryComment, $deliveryTime, $totalPrice, $status);
    
    if (!$stmt->execute()) {
        throw new Exception("Order insert failed: " . $stmt->error . " | delivery_time: " . $deliveryTime);
    }
    $orderId = $stmt->insert_id;
    $stmt->close();

    // 3) Create order items
    $itemStmt = $mysqli->prepare("
        INSERT INTO order_items (order_id, menu_id, size, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");
    if (!$itemStmt) throw new Exception("Order items prepare failed: " . $mysqli->error);

    foreach ($cart as $key => $item) {
        // Extract menu_id from composite ID (format: "123_S" -> 123)
        $menuId = (int)($item['menu_id'] ?? $item['id'] ?? 0);
        // If ID contains size suffix, extract numeric part
        if (preg_match('/^(\d+)_[SML]$/', $item['id'] ?? $key, $matches)) {
            $menuId = (int)$matches[1];
        }
        
        $size   = (string)($item['size'] ?? 'M'); // Default size is M
        $qty    = (int)($item['qty'] ?? 0);
        $price  = (int)($item['price'] ?? 0);

        if ($menuId <= 0 || $qty <= 0 || $price <= 0) {
            throw new Exception("Invalid cart item: menuId=$menuId, qty=$qty, price=$price, key=$key");
        }

        $itemStmt->bind_param("issii", $orderId, $menuId, $size, $qty, $price);
        
        if (!$itemStmt->execute()) {
            throw new Exception("Order item insert failed: " . $itemStmt->error . " | item: $key");
        }
    }

    $itemStmt->close();
    $mysqli->commit();

} catch (Throwable $e) {
    $mysqli->rollback();
    
    // Log error for debugging
    error_log('Order creation failed: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Show detailed error in development (remove in production)
    exit('注文処理に失敗しました<br><br>エラー詳細:<br>' . htmlspecialchars($e->getMessage()) . '<br><br><a href="cart.php">← カートに戻る</a>');
}

/**
 * PRG Pattern: Store order ID in session and redirect
 * Prevents duplicate submissions on page refresh
 */
$_SESSION['last_order_id'] = $orderId;
unset($_SESSION['order']); // Clear intermediate session data
header('Location: order_complete.php');
exit;
