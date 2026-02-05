<?php
// database/functions.php
require_once __DIR__ . '/db_conn.php'; 

// Main Shop Config
define('SHOP_LAT', 35.46373); // Yokohama Station
define('SHOP_LNG', 139.60975);
define('MAX_DELIVERY_KM', 5); // 5km

// ၁။ အကွာအဝေးတွက်သည့် Function
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

// ၂။ Partner Shops
function getPartnerShops() {
    global $conn;
    $shops = [];
    if (!$conn) return [];
    $sql = "SELECT * FROM partner_shops"; 
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $shops[] = $row;
        }
    }
    return $shops;
}

// ၃။ API Function (Lat/Lng ရှာပေးသည်)
function getLatLngFromPostal($zip) {
    $url = "https://geoapi.heartrails.com/api/json?method=searchByPostal&postal=" . $zip;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch); 

    if (!$response || $http_code !== 200) return false;
    $data = json_decode($response, true);
    if (!isset($data['response']['location'][0])) return false;
    $loc = $data['response']['location'][0];
    return [
        'lat' => $loc['y'],
        'lng' => $loc['x'],
        'address' => $loc['prefecture'] . $loc['city'] . $loc['town']
    ];
}

// ၄။ Area Check (Lat/Lng ပါ Return ပြန်အောင် ပြင်ထားသည်)
function checkDeliveryArea($postal_code) {
    $zip = preg_replace('/[^0-9]/', '', $postal_code);
    if (strlen($zip) !== 7) {
        return ['status' => 'error', 'msg' => 'ဇစ်ကုဒ် ဂဏန်း ၇ လုံး ဖြစ်ရပါမည်။'];
    }

    $location = getLatLngFromPostal($zip);
    if ($location === false) {
        return ['status' => 'error', 'msg' => 'ဇစ်ကုဒ် ရှာမတွေ့ပါ။'];
    }

    $dist_main = calculateDistance(SHOP_LAT, SHOP_LNG, $location['lat'], $location['lng']);

    if ($dist_main <= MAX_DELIVERY_KM) {
        return [
            'status' => 'success',
            'km' => round($dist_main, 2),
            'address' => $location['address'],
            'lat' => $location['lat'], // (New) Rider တွက်ဖို့ ထည့်ပေးလိုက်တာ
            'lng' => $location['lng']  // (New)
        ];
    }

    // Partner Shop Logic
    $partners = getPartnerShops();
    $suggestions = [];
    foreach ($partners as $shop) {
        if(empty($shop['latitude']) || empty($shop['longitude'])) continue;
        $dist_partner = calculateDistance($shop['latitude'], $shop['longitude'], $location['lat'], $location['lng']);
        if ($dist_partner <= 5) { 
            $suggestions[] = [
                'name' => $shop['shop_name'],
                'url'  => $shop['website_url'],
                'dist' => round($dist_partner, 2)
            ];
        }
    }

    return [
        'status' => 'out_of_area',
        'msg' => 'မိတ်ဆွေနေရာသည် ကျွန်တော်တို့ Main ဆိုင်နှင့် ဝေးကွာနေပါသည်။',
        'suggestions' => $suggestions 
    ];
}

// ၅။ (NEW) Rider Availability & Time Check Logic
function checkEstimatedDeliveryTime($lat, $lng) {
    global $conn;

    // --- Config ---
    $cooking_time = 10; // ချက်ပြုတ်ချိန် ၁၀ မိနစ် (ပြင်ဆင်ထားပြီး)
    $avg_speed_km_min = 0.5; // 30km/h => 0.5 km/min

    // --- Calculation ---
    $distance_km = calculateDistance(SHOP_LAT, SHOP_LNG, $lat, $lng);
    $travel_time = ceil($distance_km / $avg_speed_km_min); 
    
    // Check Free Riders
    $sql_free = "SELECT count(*) as free_riders FROM delivery_slots WHERE status = 'Free'";
    $res_free = $conn->query($sql_free);
    $row_free = $res_free->fetch_assoc();

    $wait_for_rider = 0;

    if ($row_free['free_riders'] == 0) {
        // No free riders, find earliest return time
        $sql_busy = "SELECT MIN(next_available_time) as earliest_return FROM delivery_slots WHERE status = 'Busy'";
        $res_busy = $conn->query($sql_busy);
        $row_busy = $res_busy->fetch_assoc();

        if ($row_busy['earliest_return']) {
            $return_time = strtotime($row_busy['earliest_return']);
            $now = time();
            if ($return_time > $now) {
                $wait_for_rider = ceil(($return_time - $now) / 60);
            }
        }
    }

    // Logic: ချက်ပြုတ်ချိန်အတွင်း Rider ပြန်ရောက်ရင် စောင့်စရာမလို
    $preparation_delay = max($cooking_time, $wait_for_rider);
    $total_estimated_minutes = $preparation_delay + $travel_time;

    return [
        'total_minutes' => $total_estimated_minutes,
        'rider_wait' => $wait_for_rider,
        'travel_time' => $travel_time
    ];
}
/// database/functions.php ရဲ့ အောက်ဆုံးမှာ ထည့်ပါ

function assignRiderBusy($order_id, $one_way_minutes) {
    global $conn;
    if (!$conn) return false;

    // ၁။ အချိန်စေ့သွားတဲ့ Rider တွေကို အရင် Free ပြန်လုပ်မယ်
    $now = date('Y-m-d H:i:s');
    $conn->query("UPDATE delivery_slots SET status = 'Free' WHERE status = 'Busy' AND next_available_time <= '$now'");

    // ၂။ Free ဖြစ်နေတဲ့ Rider တစ်ယောက်ကို ရှာမယ်
    $sql_find = "SELECT slot_id FROM delivery_slots WHERE status = 'Free' LIMIT 1";
    $res = $conn->query($sql_find);
    
    if ($res && $res->num_rows > 0) {
        $slot = $res->fetch_assoc();
        $slot_id = $slot['slot_id'];

        // ၃။ Busy ပြောင်းမယ့် အချိန်တွက်မယ် (အသွားအပြန်)
        $total_busy_minutes = (int)$one_way_minutes * 2;
        $return_time = date('Y-m-d H:i:s', strtotime("+$total_busy_minutes minutes"));

        // ၄။ Slot ကို Busy ပေးပြီး Order နဲ့ ချိတ်လိုက်မယ်
        $conn->query("UPDATE delivery_slots SET status = 'Busy', next_available_time = '$return_time' WHERE slot_id = $slot_id");
        $conn->query("UPDATE orders SET assigned_slot_id = $slot_id WHERE id = $order_id");
        
        return true;
    }
    return false; // Rider မအားရင် False
}
?>