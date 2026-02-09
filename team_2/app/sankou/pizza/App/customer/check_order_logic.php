<?php
// customer/check_order_logic.php
ob_start(); 
session_start();
date_default_timezone_set('Asia/Tokyo');

// ၁။ Database နှင့် Functions များ ချိတ်ဆက်ခြင်း
require_once '../database/db_conn.php';
if (file_exists('../database/functions.php')) {
    require_once '../database/functions.php';
}

$order = null;
$error_msg = "";

// ၂။ POST Request များ ကိုင်တွယ်ခြင်း

// (A) Customer က ပစ္စည်းရကြောင်း အတည်ပြုလျှင် (Confirm Receive)
if (isset($_POST['confirm_receive'])) {
    $order_id = intval($_POST['order_id']);

    // Order Info ယူမယ် (Rider Slot & GPS)
    $qry = $conn->query("SELECT assigned_slot_id, latitude, longitude FROM orders WHERE id = $order_id");
    
    if ($qry && $qry->num_rows > 0) {
        $row = $qry->fetch_assoc();
        $slot_id = $row['assigned_slot_id'] ?? 0;
        
        // Rider ပြန်ချိန် တွက်ချက်ခြင်း
        $return_minutes = 15; // Default
        if (!empty($row['latitude']) && !empty($row['longitude']) && function_exists('calculateDistance')) {
            $dist = calculateDistance(SHOP_LAT, SHOP_LNG, $row['latitude'], $row['longitude']);
            $return_minutes = ceil($dist * 3) + 5; // 1km = 3mins + 5mins Buffer
        }
        
        $back_time = date('Y-m-d H:i:s', strtotime("+$return_minutes minutes"));

        // Order ကို Completed ပြောင်း
        $stmt = $conn->prepare("UPDATE orders SET status = 'Completed', return_time = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Rider ကို Busy (Returning) ပြောင်း
        if ($slot_id > 0) {
            $conn->query("UPDATE delivery_slots SET status = 'Busy', next_available_time = '$back_time' WHERE slot_id = $slot_id");
        }
    }

    // Refresh Page
    header("Location: check_order.php?id=" . $order_id); 
    exit();
}

// (B) ဖုန်းနံပါတ်ဖြင့် အော်ဒါရှာလျှင်
if (isset($_POST['checkphonenumber'])) {
    $phone = $_POST['checkphonenumber'];
    
    $stmt = $conn->prepare("SELECT id FROM orders WHERE phonenumber = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        header("Location: check_order.php?id=" . $row['id']);
        exit();
    } else {
        echo "<script>alert('❌ ဤဖုန်းနံပါတ်ဖြင့် အော်ဒါမရှိပါ'); window.location.href='index.php';</script>";
        exit();
    }
}

// ၃။ GET Request (အော်ဒါ ID ဖြင့် Data ဆွဲထုတ်ခြင်း)
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
} else {
    // ID မပါရင် Home ကိုပြန်ပို့
    header("Location: index.php");
    exit();
}

if (!$order) {
    die("❌ Error: Order Not Found");
}

// ၄။ View အတွက် Variable များ ပြင်ဆင်ခြင်း
$c_name = htmlspecialchars($order['customer_name'] ?? $order['name'] ?? '-');
$c_phone = htmlspecialchars($order['phonenumber'] ?? $order['phone'] ?? '-');
$c_address = htmlspecialchars($order['address'] ?? ($order['address_city'] . ' ' . $order['address_detail']) ?? '-');
$c_size = $order['pizza_type'] ?? 'M';
$c_qty = intval($order['quantity'] ?? 1);

// ဈေးနှုန်း
$unit_price = ($c_size == 'S') ? 1000 : (($c_size == 'M') ? 2000 : 3000);
$total_price = $unit_price * $c_qty;

// Status အရောင်နှင့် စာသားများ
$status_text = "";
$status_color = "";
$show_timer = false;

switch ($order['status']) {
    case 'Pending':
        $status_text = "⏳ အော်ဒါ လက်ခံရရှိထားပါသည် (Waiting)";
        $status_color = "#f39c12"; // Orange
        $show_timer = false;
        break;
    case 'Cooking':
        $status_text = "👨‍🍳 စားဖိုမှူး ချက်ပြုတ်နေပါသည် (Cooking)";
        $status_color = "#d35400"; // Dark Orange
        $show_timer = true;
        break;
    case 'Delivering':
        $status_text = "🛵 လူကြီးမင်းထံ လာပို့နေပါပြီ (On the way)";
        $status_color = "#2980b9"; // Blue
        $show_timer = true;
        break;
    case 'Completed':
        $status_text = "✅ ပို့ဆောင်မှု ပြီးစီးပါပြီ (Completed)";
        $status_color = "#27ae60"; // Green
        $show_timer = false; 
        break;
    case 'Rejected':
        $status_text = "❌ အော်ဒါ ပယ်ဖျက်ခံလိုက်ရပါသည်";
        $status_color = "#c0392b"; // Red
        $show_timer = false;
        break;
    default:
        $status_text = "Processing...";
        $status_color = "grey";
}

// ==========================================
// 🔴 TIMER LOGIC FIXED
// ==========================================
$remaining_seconds = 0;
if ($show_timer) {
    // 1. Database မှ estimated_mins ကို ယူမည် (မရှိရင် ၃၀ ထားမည်)
    $duration_mins = intval($order['estimated_mins'] ?? 30);
    
    // 2. စချက်သည့်အချိန် (Start Time) ကို မူတည်တွက်မည်
    // Cooking စဖြစ်ကတည်းက start_time ဝင်နေမှာဖြစ်လို့ အချိန်မရွေ့တော့ပါ
    $time_string = !empty($order['start_time']) ? $order['start_time'] : $order['order_date'];
    
    // 3. ပြီးဆုံးမည့်အချိန် = Start Time + Duration
    $target_time = strtotime($time_string) + ($duration_mins * 60); 
    
    // 4. ကျန်ချိန်တွက်ခြင်း
    $remaining_seconds = max(0, $target_time - time());
}
?>