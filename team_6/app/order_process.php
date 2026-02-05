<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db/connect.php';

function generateOrderID($length = 7) {
    $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZ'; 
    return substr(str_shuffle($chars), 0, $length);
}

if(!isset($_SESSION['order_data'])){
    echo "<p>注文データが見つかりません。<a href='index.php'>最初からやり直してください。</a></p>"; exit;
}

$orderData = $_SESSION['order_data'];

if(!isset($_POST['otp_input']) || $_POST['otp_input'] != $orderData['otp']){
    echo "<script>alert('確認コードが正しくありません。'); window.history.back();</script>"; exit;
}

try {
    $db->beginTransaction();

    // --- 1. FINAL CAPACITY RE-CHECK (Synced with DB ENUM) ---
    $db_time = date("H:i:00", strtotime($orderData['delivery_time']));
    $delivery_date = $orderData['delivery_date'];

    $stmtCap = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM staff_shift WHERE shift_date = ? AND shift_start <= ? AND shift_end > ?) as staff,
            (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id 
             WHERE o.delivery_date = ? AND o.delivery_time = ? 
             AND o.status IN ('Pending', 'Cooking', 'Ready', 'Out for Delivery', 'Delivered')) as booked
    ");
    $stmtCap->execute([$delivery_date, $db_time, $db_time, $delivery_date, $db_time]);
    $capData = $stmtCap->fetch(PDO::FETCH_ASSOC);

    $max_pizzas = (int)$capData['staff'] * (30 / 5); 
    $remaining = $max_pizzas - (int)$capData['booked'];

    $current_qty = 0;
    foreach($orderData['cart'] as $item) { $current_qty += (int)$item['qty']; }

    if ($current_qty > $remaining) {
        $db->rollBack();
        echo "<script>alert('申し訳ありません！入力中に予約がいっぱいになりました。別の時間を選択してください。'); window.location.href='order_confirm.php';</script>";
        exit;
    }

    // --- 2. INSERT ORDER (Using 'Pending') ---
    $customer_id = $orderData['customer_id'];
    $cart = $orderData['cart'];
    $total_price = $orderData['total_price'];
    $order_id = generateOrderID(); 

    $stmt = $db->prepare("
        INSERT INTO orders (id, customer_id, total_price, status, order_time, delivery_date, delivery_time) 
        VALUES (?, ?, ?, 'Pending', NOW(), ?, ?)
    ");
    $stmt->execute([$order_id, $customer_id, $total_price, $delivery_date, $db_time]);

    // --- 3. INSERT ITEMS & UPDATE SALES ---
    foreach($cart as $item){
        // Note: Using 'Pending' for item status as well to avoid ENUM errors
        $stmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, pizza_size, quantity, item_price, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([$order_id, $item['id'], $item['size'], $item['qty'], $item['price']]);

        $order_datetime = date('Y-m-d H:i:s');
        $stmtCheck = $db->prepare("SELECT id FROM sales WHERE menu_item_id = ? AND date_trunc('hour', order_date) = date_trunc('hour', ?::timestamp)");
        $stmtCheck->execute([$item['id'], $order_datetime]);
        $sale = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($sale) {
            $stmtUpdate = $db->prepare("UPDATE sales SET quantity = quantity + ?, total_amount = total_amount + ? WHERE id = ?");
            $stmtUpdate->execute([$item['qty'], $item['price'] * $item['qty'], $sale['id']]);
        } else {
            $stmtInsert = $db->prepare("INSERT INTO sales (menu_item_id, order_date, quantity, total_amount) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$item['id'], $order_datetime, $item['qty'], $item['price'] * $item['qty']]);
        }
    }

    $db->commit();
    unset($_SESSION['cart']);
    unset($_SESSION['order_data']);

} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    die("エラーが発生しました: " . $e->getMessage());
}
?>

<style>
/* Reset and Base Styles */
body {
    background-color: #fdfaf0;
    font-family: "Yu Gothic", "Meiryo", sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}

.order-complete {
    max-width: 500px;
    width: 90%;
    background: white;
    padding: 40px 20px;
    border-radius: 24px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(180, 95, 4, 0.1);
    border: 2px solid #e6b422;
    position: relative;
}

.success-icon {
    font-size: 50px;
    background: #fdf2d2;
    width: 80px;
    height: 80px;
    line-height: 80px;
    border-radius: 50%;
    margin: 0 auto 20px;
    display: block;
}

.order-complete h2 {
    color: #b45f04;
    margin-bottom: 10px;
    font-size: 1.8rem;
}

.order-complete p {
    font-size: 1.1rem;
    color: #555;
    line-height: 1.6;
    margin: 10px 0;
}

.order-id-badge {
    display: inline-block;
    background: #fdf2d2;
    padding: 15px 30px;
    border-radius: 12px;
    border: 2px dashed #e6b422;
    margin: 20px 0;
}

.order-id-badge span {
    display: block;
    font-size: 0.9rem;
    color: #b45f04;
    font-weight: bold;
}

.order-id-badge strong {
    font-size: 2.2rem;
    color: #333;
    letter-spacing: 2px;
    font-family: 'Courier New', Courier, monospace;
}

.payment-notice {
    background: #fff5f5;
    border-left: 5px solid #c62828;
    padding: 15px;
    margin: 20px 0;
    text-align: left;
    border-radius: 4px;
}

.payment-notice h4 {
    margin: 0 0 5px 0;
    color: #c62828;
    font-size: 1rem;
}

.payment-notice p {
    font-size: 0.95rem;
    margin: 0;
    color: #333;
}

.back-btn {
    display: inline-block;
    background: #e6b422;
    color: white;
    text-decoration: none;
    padding: 15px 40px;
    border-radius: 50px;
    font-weight: bold;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.back-btn:hover {
    background: #b45f04;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>

<div class='order-complete'>
    <span class="success-icon">🍕</span>
    <h2>ご注文ありがとうございます！</h2>
    <p>キッチンで調理を開始しました。</p>

    <div class="order-id-badge">
        <span>注文番号（配達員に提示）</span>
        <strong><?php echo $order_id; ?></strong>
    </div>

    <div class="payment-notice">
        <h4>⚠️ お支払いについて</h4>
        <p>代金は商品お届け時に<strong>現金のみ（現金払い）</strong>でお受けいたします。</p>
        <p style="font-size: 0.8rem; color: #666; margin-top:5px;">※クレジットカード、電子マネーはご利用いただけません。</p>
    </div>

    <p>配達状況はマイページから確認できます。</p>
    
    <a href='index.php' class="back-btn">メニューに戻る</a>
</div>