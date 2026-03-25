<?php
// customer/check_availability.php
require_once '../database/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postal_code = $_POST['postal_code'] ?? '';
    
    // (က) ဧရိယာ စစ်ဆေးခြင်း
    $area_check = checkDeliveryArea($postal_code);
    
    if ($area_check['status'] !== 'success') {
        echo json_encode([
            'status' => 'error',
            'msg' => 'ပို့ဆောင်မှု ဧရိယာပြင်ပ ဖြစ်နေပါသည်။ (Main Shop နှင့် 5km ကျော်လွန်နေသည်)'
        ]);
        exit;
    }

    // (ခ) အချိန်နှင့် Rider အားမအား စစ်ဆေးခြင်း
    $lat = $area_check['lat'];
    $lng = $area_check['lng'];
    
    // ⚠️ FIXED: Passing Lat/Lng/Qty
    $smart_status = canAcceptNewOrder($lat, $lng, 2); 
    
    $minutes = 30;
    if ($smart_status === 'WAIT_AVAILABLE') $minutes = 45;
    if ($smart_status === 'FULL') $minutes = 60;

    // Traffic Check
    global $conn;
    $traffic = $conn->query("SELECT setting_value FROM system_config WHERE setting_key='traffic_mode'")->fetch_assoc()['setting_value'] ?? '0';
    if ($traffic == '1') $minutes += 15;

    // (ဂ) အားလုံးအောင်မြင်ရင်
    echo json_encode([
        'status' => 'success',
        'msg' => ($minutes > 30) ? "Busy ($minutes mins)" : "Available (30 mins)",
        'minutes' => $minutes,
        'city_info' => $area_check['address']
    ]);
    exit;
}
?>