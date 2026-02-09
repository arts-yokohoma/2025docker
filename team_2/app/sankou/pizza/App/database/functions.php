<?php
// database/functions.php
require_once __DIR__ . '/db_conn.php'; 

// ==========================================
// 🛠️ AUTO-FIX DATABASE COLUMNS (Run Once)
// ==========================================
if ($conn) {
    // 1. Fix Delivery Slots Table
    $check_status = $conn->query("SHOW COLUMNS FROM delivery_slots LIKE 'status'");
    if ($check_status && $check_status->num_rows == 0) {
        $conn->query("ALTER TABLE delivery_slots ADD COLUMN status VARCHAR(20) DEFAULT 'Free'");
    }
    $check_time = $conn->query("SHOW COLUMNS FROM delivery_slots LIKE 'next_available_time'");
    if ($check_time && $check_time->num_rows == 0) {
        $conn->query("ALTER TABLE delivery_slots ADD COLUMN next_available_time DATETIME DEFAULT NULL");
    }

    // 2. Fix Orders Table (Needed for Smart Batching)
    $check_lat = $conn->query("SHOW COLUMNS FROM orders LIKE 'latitude'");
    if ($check_lat && $check_lat->num_rows == 0) {
        $conn->query("ALTER TABLE orders ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL");
        $conn->query("ALTER TABLE orders ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL");
    }
}

// ==========================================
// CONFIGURATION
// ==========================================
if (!defined('SHOP_LAT')) define('SHOP_LAT', 35.46373); 
if (!defined('SHOP_LNG')) define('SHOP_LNG', 139.60975);
if (!defined('MAX_DELIVERY_KM')) define('MAX_DELIVERY_KM', 5); 
if (!defined('AVG_SPEED_KMPH')) define('AVG_SPEED_KMPH', 30); 

// 1. Calculate Distance (Haversine Formula)
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

// 2. Travel Time Helper (Minutes)
if (!function_exists('calculateTravelTime')) {
    function calculateTravelTime($km) {
        // Speed: 30 km/h => 0.5 km/min => 2 mins per km + 5 mins buffer
        $minutes = ceil($km / (AVG_SPEED_KMPH / 60));
        return $minutes + 5; 
    }
}

// ============================================================
// 🧠 SMART BATCHING LOGIC
// ============================================================
if (!function_exists('canAcceptNewOrder')) {
    function canAcceptNewOrder($new_lat, $new_lng, $new_qty) {
        global $conn;
        
        $max_allowable_time = 40; 
        $cooking_time = 10;
        
        // 1. FREE RIDER CHECK
        // If a rider is explicitly 'Free', return AVAILABLE immediately.
        $sql_free = "SELECT count(*) as c FROM delivery_slots WHERE TRIM(LOWER(status)) = 'free'";
        $res_free = $conn->query($sql_free);
        $free_count = ($res_free) ? intval($res_free->fetch_assoc()['c']) : 0;

        if ($free_count > 0) {
            return "AVAILABLE"; 
        }

        // 2. SMART BATCHING CHECK (Pending + Cooking)
        // Check orders that have reserved a rider but haven't left yet.
        // We include 'Pending' because submit_order_logic now reserves riders immediately.
        $sql_pending = "SELECT latitude, longitude, quantity, start_time, order_date 
                        FROM orders 
                        WHERE status IN ('Pending', 'Cooking')";
        $res_pending = $conn->query($sql_pending);

        if ($res_pending && $res_pending->num_rows > 0) {
            while ($row = $res_pending->fetch_assoc()) {
                
                // Skip if GPS data is missing
                if(empty($row['latitude']) || empty($row['longitude'])) continue;

                // (A) Location Check: Is new order within 3km of existing order?
                $dist_between_orders = calculateDistance($new_lat, $new_lng, $row['latitude'], $row['longitude']);
                
                // (B) Capacity Check: Total quantity <= 10?
                $total_qty = intval($row['quantity']) + intval($new_qty);
                
                // (C) Time Check: Has existing order been waiting < 15 mins?
                // Use start_time for Cooking, order_date for Pending
                $ref_time = !empty($row['start_time']) ? $row['start_time'] : $row['order_date'];
                $minutes_since_start = (time() - strtotime($ref_time)) / 60;

                // ✅ If all conditions met, we can batch!
                if ($dist_between_orders <= 3.0 && $total_qty <= 10 && $minutes_since_start <= 15) {
                    return "AVAILABLE"; // Return 30 mins (Shared Rider)
                }
            }
        }

        // 3. BUSY CHECK (Calculate Return Time)
        // If no free rider and no batching possible, calculate when a rider returns
        $new_dist_km = calculateDistance(SHOP_LAT, SHOP_LNG, $new_lat, $new_lng);
        $outbound_time = calculateTravelTime($new_dist_km);
        
        $sql_busy = "SELECT next_available_time FROM delivery_slots WHERE status = 'Busy'";
        $res_busy = $conn->query($sql_busy);

        if ($res_busy && $res_busy->num_rows > 0) {
            while ($row = $res_busy->fetch_assoc()) {
                $return_timestamp = !empty($row['next_available_time']) ? strtotime($row['next_available_time']) : time();
                
                // Calculate minutes until rider is back at shop
                $mins_until_return = max(0, ceil(($return_timestamp - time()) / 60));
                
                // Total wait = Rider Return Time + Delivery Time
                $total_estimated_time = max($mins_until_return, $cooking_time) + $outbound_time;

                if ($total_estimated_time <= $max_allowable_time) {
                    return "WAIT_AVAILABLE"; // Return 45 mins
                }
            }
        }
        
        return "FULL"; // Return 60 mins
    }
}

// 4. API Function (Postal to LatLng)
if (!function_exists('getLatLngFromPostal')) {
    function getLatLngFromPostal($zip) {
        $url = "https://geoapi.heartrails.com/api/json?method=searchByPostal&postal=" . $zip;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch); 

        if (!$response) return false;
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

// 5. Check Delivery Area
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
        return ['status' => 'out_of_area', 'msg' => 'Outside Delivery Area', 'suggestions' => []];
    }
}

// 6. Partner Shops
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

// 7. Assign Rider Smartly
if (!function_exists('assignRiderSmart')) {
    function assignRiderSmart($order_id, $one_way_minutes) {
        global $conn;
        if (!$conn) return false;
        $now = date('Y-m-d H:i:s');

        // Clean expired busy slots
        $conn->query("UPDATE delivery_slots SET status = 'Free' WHERE status = 'Busy' AND next_available_time <= '$now'");

        // A. Find Free Slot
        $sql_find = "SELECT slot_id FROM delivery_slots WHERE status = 'Free' LIMIT 1";
        $res = $conn->query($sql_find);
        $slot_id = 0;
        $start_from = $now;

        if ($res && $res->num_rows > 0) {
            $slot = $res->fetch_assoc();
            $slot_id = $slot['slot_id'];
        } else {
            // B. Find Busy Slot Returning Soonest
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

            // Update Slot
            $conn->query("UPDATE delivery_slots SET status = 'Busy', next_available_time = '$new_return_time' WHERE slot_id = $slot_id");
            
            // Assign to Order
            $conn->query("UPDATE orders SET assigned_slot_id = $slot_id WHERE id = $order_id");
            return true;
        }
        return false; 
    }
}
?>