<?php
// customer/submit_order.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo'); 

require_once '../database/db_conn.php';
require_once '../database/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = $_POST['address_city'] ?? '';
    $detail = $_POST['address_detail'] ?? '';
    $full_address = trim($city . " " . $detail);
    $size = $_POST['size'] ?? 'M';
    $qty = intval($_POST['quantity'] ?? 1);
    $postal_code = preg_replace('/[^0-9]/', '', $_POST['postal_code'] ?? '');

    if (empty($name) || empty($phone) || empty($full_address)) {
        echo "<script>alert('Please fill all fields'); window.history.back();</script>";
        exit;
    }

    $lat = 0.0;
    $lng = 0.0;
    $one_way_minutes = 20; // Default

    if (!empty($postal_code) && function_exists('getLatLngFromPostal')) {
        $geo = getLatLngFromPostal($postal_code);
        if ($geo) {
            $lat = $geo['lat'];
            $lng = $geo['lng'];
            if (function_exists('calculateDistance')) {
                $dist_km = calculateDistance(SHOP_LAT, SHOP_LNG, $lat, $lng);
                $speed_per_min = 0.5; 
                $one_way_minutes = ceil($dist_km / $speed_per_min);
            }
        }
    }

    $sql = "INSERT INTO orders (customer_name, phonenumber, address, pizza_type, quantity, postal_code, latitude, longitude, status, order_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssisdd", $name, $phone, $full_address, $size, $qty, $postal_code, $lat, $lng);

        if ($stmt->execute()) {
            $new_order_id = $conn->insert_id;

            // ✅ Use assignRiderSmart
            if (function_exists('assignRiderSmart')) {
                assignRiderSmart($new_order_id, $one_way_minutes);
            }
            
            header("Location: check_order.php?id=" . $new_order_id);
            exit();

        } else {
            echo "Save Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Database Error: " . $conn->error;
    }

} else {
    header("Location: index.php");
    exit();
}
?>