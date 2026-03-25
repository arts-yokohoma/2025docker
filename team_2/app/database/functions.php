<?php
// database/functions.php
require_once __DIR__ . '/db_conn.php'; 

// Main Shop Config
if (!defined('SHOP_LAT')) define('SHOP_LAT', 35.46373); // Yokohama Station
if (!defined('SHOP_LNG')) define('SHOP_LNG', 139.60975);
if (!defined('MAX_DELIVERY_KM')) define('MAX_DELIVERY_KM', 5); 
if (!defined('AVG_SPEED_KMPH')) define('AVG_SPEED_KMPH', 30); // 30 km/h

// 1. Calculate Distance
if (!function_exists('calculateDistance')) {
    function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

// 2. Travel Time Helper
if (!function_exists('calculateTravelTime')) {
    function calculateTravelTime($km) {
        $minutes = ceil($km / (AVG_SPEED_KMPH / 60));
        return $minutes + 5; // Buffer 5 mins
    }
}

// 3. New Order Acceptance Logic
if (!function_exists('canAcceptNewOrder')) {
    function canAcceptNewOrder($new_dist_km) {
        global $conn;
        
        $max_allowable_time = 40; // Total Max Wait Time
        $cooking_time = 10;       // Kitchen Time
        
        $outbound_time = calculateTravelTime($new_dist_km);

        // A. Free Rider?
        $sql_free = "SELECT count(*) as c FROM delivery_slots WHERE status = 'Free'";
        $res_free = $conn->query($sql_free);
        if ($res_free && $res_free->fetch_assoc()['c'] > 0) {
            $total_time = $cooking_time + $outbound_time;
            return ($total_time <= $max_allowable_time);
        }

        // B. Busy Rider Returning Soon?
        $sql_busy = "SELECT next_available_time FROM delivery_slots WHERE status = 'Busy'";
        $res_busy = $conn->query($sql_busy);

        if ($res_busy) {
            while ($row = $res_busy->fetch_assoc()) {
                $return_time_str = $row['next_available_time'];
                $now = time();
                $return_timestamp = strtotime($return_time_str);
                
                $mins_until_return = max(0, ceil(($return_timestamp - $now) / 60));
                $departure_delay = max($mins_until_return, $cooking_time);
                $total_estimated_time = $departure_delay + $outbound_time;

                if ($total_estimated_time <= $max_allowable_time) {
                    return true; 
                }
            }
        }
        return false;
    }
}

// 4. Partner Shops
if (!function_exists('getPartnerShops')) {
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
}

// 5. API Function
if (!function_exists('getLatLngFromPostal')) {
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
}

// 6. Area Check
if (!function_exists('checkDeliveryArea')) {
    function checkDeliveryArea($postal_code) {
        $zip = preg_replace('/[^0-9]/', '', $postal_code);
        if (strlen($zip) !== 7) return ['status' => 'error', 'msg' => 'Invalid Postal Code'];

        $location = getLatLngFromPostal($zip);
        if ($location === false) return ['status' => 'error', 'msg' => 'Postal Code Not Found'];

        $dist_main = calculateDistance(SHOP_LAT, SHOP_LNG, $location['lat'], $location['lng']);

        if ($dist_main <= MAX_DELIVERY_KM) {
            return [
                'status' => 'success',
                'km' => round($dist_main, 2),
                'address' => $location['address'],
                'lat' => $location['lat'],
                'lng' => $location['lng']
            ];
        }

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
            'msg' => 'Outside Delivery Area',
            'suggestions' => $suggestions 
        ];
    }
}

// 7. Time Check Logic
if (!function_exists('checkEstimatedDeliveryTime')) {
    function checkEstimatedDeliveryTime($lat, $lng) {
        global $conn;
        $cooking_time = 10; 
        $avg_speed_km_min = 0.5; 

        $distance_km = calculateDistance(SHOP_LAT, SHOP_LNG, $lat, $lng);
        $travel_time = ceil($distance_km / $avg_speed_km_min); 
        
        $sql_free = "SELECT count(*) as free_riders FROM delivery_slots WHERE status = 'Free'";
        $res_free = $conn->query($sql_free);
        $row_free = $res_free->fetch_assoc();

        $wait_for_rider = 0;

        if ($row_free['free_riders'] == 0) {
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

        $preparation_delay = max($cooking_time, $wait_for_rider);
        $total_estimated_minutes = $preparation_delay + $travel_time;

        return [
            'total_minutes' => $total_estimated_minutes,
            'rider_wait' => $wait_for_rider,
            'travel_time' => $travel_time
        ];
    }
}

// 8. Assign Rider Smartly
if (!function_exists('assignRiderSmart')) {
    function assignRiderSmart($order_id, $one_way_minutes) {
        global $conn;
        if (!$conn) return false;
        $now = date('Y-m-d H:i:s');

        // Clean expired
        $conn->query("UPDATE delivery_slots SET status = 'Free' WHERE status = 'Busy' AND next_available_time <= '$now'");

        // A. Free
        $sql_find = "SELECT slot_id FROM delivery_slots WHERE status = 'Free' LIMIT 1";
        $res = $conn->query($sql_find);
        $slot_id = 0;
        $start_from = $now;

        if ($res && $res->num_rows > 0) {
            $slot = $res->fetch_assoc();
            $slot_id = $slot['slot_id'];
        } else {
            // B. Busy Returning Soonest
            $sql_busy = "SELECT slot_id, next_available_time FROM delivery_slots ORDER BY next_available_time ASC LIMIT 1";
            $res_busy = $conn->query($sql_busy);
            if ($res_busy && $res_busy->num_rows > 0) {
                $slot = $res_busy->fetch_assoc();
                $slot_id = $slot['slot_id'];
                $start_from = $slot['next_available_time']; 
            }
        }

        if ($slot_id > 0) {
            $round_trip = (int)$one_way_minutes * 2;
            $new_return_time = date('Y-m-d H:i:s', strtotime($start_from . " + $round_trip minutes"));

            $conn->query("UPDATE delivery_slots SET status = 'Busy', next_available_time = '$new_return_time' WHERE slot_id = $slot_id");
            $conn->query("UPDATE orders SET assigned_slot_id = $slot_id WHERE id = $order_id");
            return true;
        }
        return false; 
    }
}
?>