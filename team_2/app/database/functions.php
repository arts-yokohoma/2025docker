<?php
// database/functions.php
require_once __DIR__ . '/db_conn.php'; // Database ချိတ်ဆက်မှု

// Main Shop Config
define('SHOP_LAT', 35.46373); // Yokohama Station
define('SHOP_LNG', 139.60975);
define('MAX_DELIVERY_KM', 5); // 5km အတွင်းပို့မည်

// အကွာအဝေးတွက်သည့် Function (Haversine Formula)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

// Database မှ ဆိုင်ခွဲများ ဆွဲထုတ်ခြင်း
function getPartnerShops() {
    global $conn;
    $shops = [];
    $sql = "SELECT * FROM partner_shops"; // manage_shops.php နဲ့ ထည့်ထားတဲ့ table
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $shops[] = $row;
        }
    }
    return $shops;
}

// အဓိက စစ်ဆေးသည့် Function
function checkDeliveryArea($postal_code) {
    $zip = preg_replace('/[^0-9]/', '', $postal_code);
    
    // (က) ဇစ်ကုဒ် Format စစ်ခြင်း
    if (strlen($zip) !== 7) {
        return ['status' => 'error', 'msg' => 'ဇစ်ကုဒ် ဂဏန်း ၇ လုံး ဖြစ်ရပါမည်။'];
    }

    // (ခ) API ခေါ်ခြင်း
    $location = getLatLngFromPostal($zip);
    if ($location === false) {
        return ['status' => 'error', 'msg' => 'ဇစ်ကုဒ် ရှာမတွေ့ပါ။'];
    }

    // (ဂ) Main Shop နဲ့ အကွာအဝေး စစ်ခြင်း
    $dist_main = calculateDistance(SHOP_LAT, SHOP_LNG, $location['lat'], $location['lng']);

    if ($dist_main <= MAX_DELIVERY_KM) {
        // Main Shop နဲ့ နီးရင် Success
        return [
            'status' => 'success',
            'km' => round($dist_main, 2),
            'address' => $location['address']
        ];
    }

    // (ဃ) Main Shop နဲ့ ဝေးနေရင် Partner Shops တွေနဲ့ လိုက်တိုက်စစ်မယ်
    $partners = getPartnerShops();
    $suggestions = [];

    foreach ($partners as $shop) {
        $dist_partner = calculateDistance($shop['latitude'], $shop['longitude'], $location['lat'], $location['lng']);
        
        // Partner ဆိုင်နဲ့ 5km အတွင်း ရှိလား?
        if ($dist_partner <= 5) { 
            $suggestions[] = [
                'name' => $shop['shop_name'],
                'url'  => $shop['website_url'],
                'dist' => round($dist_partner, 2)
            ];
        }
    }

    // Partner ဆိုင်တွေ ပြန်ထည့်ပေးလိုက်မယ်
    return [
        'status' => 'out_of_area',
        'msg' => 'မိတ်ဆွေနေရာသည် ကျွန်တော်တို့ Main ဆိုင်နှင့် ဝေးကွာနေပါသည်။',
        'suggestions' => $suggestions // နီးစပ်ရာ ဆိုင်စာရင်း (Array)
    ];
}

// API Function (အပြောင်းအလဲမရှိ)
function getLatLngFromPostal($zip) {
    $url = "https://geoapi.heartrails.com/api/json?method=searchByPostal&postal=" . $zip;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch); // Close curl resource to free up system resources
    if (!$response) return false;
    $data = json_decode($response, true);
    if (!isset($data['response']['location'][0])) return false;
    return [
        'lat' => $data['response']['location'][0]['y'],
        'lng' => $data['response']['location'][0]['x'],
        'address' => $data['response']['location'][0]['prefecture'] . $data['response']['location'][0]['city'] . $data['response']['location'][0]['town']
    ];
}
?>