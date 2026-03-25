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
    $location = getLatLngFromPostal($postal_code); // Lat/Lng ယူ
    $time_check = checkEstimatedDeliveryTime($location['lat'], $location['lng']); // တွက်ချက်
    
    // မိနစ် ၃၀ ကျော်ရင် လက်မခံ
    if ($time_check['total_minutes'] > 30) {
        echo json_encode([
            'status' => 'error',
            'msg' => "Rider များ မအားလပ်သဖြင့် ပို့ဆောင်ချိန် " . $time_check['total_minutes'] . " မိနစ် ခန့် ကြာနိုင်ပါသည်။ (မိနစ် ၃၀ အာမခံချက်မပေးနိုင်သဖြင့် မှာယူ၍ မရနိုင်ပါ)"
        ]);
        exit;
    }

    // (ဂ) အားလုံးအောင်မြင်ရင်
    echo json_encode([
        'status' => 'success',
        'msg' => 'မှာယူနိုင်ပါသည်။ (ခန့်မှန်းကြာချိန်: ' . $time_check['total_minutes'] . ' မိနစ်)',
        'city_info' => $area_check['address']
    ]);
    exit;
}
?>