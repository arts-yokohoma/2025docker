<?php
// customer/order_form_logic.php
session_start();
require_once '../database/db_conn.php'; // Includes $lang & Timezone settings

if (file_exists('../database/functions.php')) {
    require_once '../database/functions.php';
}

// 1. Input Validation
if (!isset($_GET['code'])) { header("Location: index.php"); exit(); }

$postal_code = htmlspecialchars($_GET['code']);
$found_address = isset($_GET['address']) ? htmlspecialchars(urldecode($_GET['address'])) : '';

// Coordinates & Distance Setup
$lat = 0;
$lng = 0;
$distance_km = 0;

// Address Fallback & Get Lat/Lng
if (function_exists('checkDeliveryArea')) {
    $check = checkDeliveryArea($postal_code);
    if ($check['status'] === 'success') {
        if(empty($found_address)) $found_address = $check['address'];
        $distance_km = $check['km'];
        $lat = $check['lat']; 
        $lng = $check['lng']; 
    }
}

// 2. Load Config
$res_k = $conn->query("SELECT setting_value FROM system_config WHERE setting_key = 'kitchen_staff'");
$k_staff = intval($res_k->fetch_assoc()['setting_value'] ?? 3);
$max_kitchen_capacity = $k_staff * 4; 

$sql_load = "SELECT SUM(quantity) as total_items FROM orders WHERE status IN ('Pending', 'Cooking')";
$res_load = $conn->query($sql_load);
$current_kitchen_load = intval($res_load->fetch_assoc()['total_items'] ?? 0);

// ============================================================
// 🛠️ INTELLIGENT TIME CALCULATION
// ============================================================
$estimated_time = 30; // Base Time
$busy_reason = "";
$default_qty_check = 2; // Assume 2 items for check

// 1. Rider Free Check (Direct DB Check)
$sql_free = "SELECT COUNT(*) as c FROM delivery_slots WHERE TRIM(LOWER(status)) = 'free'";
$res_free = $conn->query($sql_free);
$free_riders = intval($res_free->fetch_assoc()['c']);

// A. Kitchen Check
if ($current_kitchen_load >= $max_kitchen_capacity) {
    $estimated_time = 60; 
    $busy_reason = $lang['kitchen_busy']; // 🇯🇵 Japanese Text
} 
// B. Rider Check (Direct)
elseif ($free_riders > 0) {
    $estimated_time = 30; // Rider is Free -> 30 mins
} 
else {
    // Rider not free -> Check Smart Batching
    if (function_exists('canAcceptNewOrder')) {
        
        // Pass Lat, Lng, Qty
        $smart_status = canAcceptNewOrder($lat, $lng, $default_qty_check);
        
        if ($smart_status === "AVAILABLE") {
            $estimated_time = 30; // Batching OK -> 30 mins
        } elseif ($smart_status === "WAIT_AVAILABLE") {
            $estimated_time = 45;
            $busy_reason = $lang['riders_busy']; // 🇯🇵 Japanese Text
        } else {
            $estimated_time = 60;
            $busy_reason = $lang['riders_busy']; // 🇯🇵 Japanese Text
        }
    } else {
        $estimated_time = 60;
        $busy_reason = $lang['riders_busy'];
    }
}

// C. Traffic Mode Check
$res_t = $conn->query("SELECT setting_value FROM system_config WHERE setting_key = 'traffic_mode'");
$traffic = $res_t->fetch_assoc()['setting_value'] ?? '0';
$is_heavy_traffic = ($traffic == '1'); 

if ($is_heavy_traffic) {
    $estimated_time += 15;
    if(empty($busy_reason)) $busy_reason = $lang['heavy_traffic']; // 🇯🇵 Japanese Text
}

// ============================================================
// 🛑 INTERCEPTION LOGIC (JAPANESE)
// ============================================================
if ($estimated_time > 30 && !isset($_GET['confirm_wait'])) {
    
    $params = $_GET;
    $params['confirm_wait'] = '1';
    
    // 🔥 Ensure GPS data is passed in the link
    $params['lat'] = $lat;
    $params['lng'] = $lng;
    
    if (!isset($params['address']) && !empty($found_address)) {
        $params['address'] = urlencode($found_address);
    }
    
    $wait_url = "order_form.php?" . http_build_query($params);
    
    ?>
    <!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Wait?</title>
        <style>
            body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 350px; width: 90%; }
            .time-box { font-size: 3em; font-weight: bold; color: #e67e22; margin: 10px 0; }
            .reason { color: #7f8c8d; margin-bottom: 30px; line-height: 1.5; font-size: 13px; }
            .btn { display: block; width: 100%; padding: 15px; margin: 10px 0; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s; box-sizing: border-box; }
            .btn-wait { background: #f1c40f; color: #333; border: none; }
            .btn-cancel { background: #eee; color: #555; border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2 style="margin-top:0; color:#e67e22;"><?= $lang['wait_title'] ?></h2>
            <div class="time-box"><?= $estimated_time ?> <span style="font-size:0.4em;"><?= $lang['mins'] ?></span></div>
            <p class="reason">
                <?= $lang['wait_msg'] ?><br>
                (<?= htmlspecialchars($busy_reason) ?>)
            </p>
            
            <a href="<?= $wait_url ?>" class="btn btn-wait"><?= $lang['wait_btn'] ?></a>
            <a href="index.php" class="btn btn-cancel"><?= $lang['cancel_btn'] ?></a>
        </div>
    </body>
    </html>
    <?php
    exit(); 
}
?>