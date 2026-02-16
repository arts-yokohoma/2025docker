<?php
// customer/index_logic.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db_conn.php';
require_once '../database/functions.php';

// Helper Function
function getTrafficStatus() {
    $file = __DIR__ . '/../admin/traffic_status.txt';
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '0';
}

// Variables Initialization (View အတွက်)
$msg = '';
$msg_type = '';
$postal_code = '';
$suggestions = [];
$show_traffic_warning = false;

// POST Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // (A) Delivery Area Check
    if (isset($_POST['postal_code'])) {
        $postal_code = preg_replace('/[^0-9]/', '', $_POST['postal_code']);
        
        $check = checkDeliveryArea($postal_code);

        if ($check['status'] === 'error') {
            $msg = '❌ ' . $check['msg'];
            $msg_type = 'error';

        } elseif ($check['status'] === 'out_of_area') {
            $msg = '🚫 ' . $check['msg'];
            $msg_type = 'warning';
            if (isset($check['suggestions'])) {
                $suggestions = $check['suggestions'];
            }

        } else {
            // Success
            $found_address = $check['address'];
            $distance_km = isset($check['km']) ? $check['km'] : 0; 
            
            // Traffic Check
            $traffic_status = getTrafficStatus();

            if ($traffic_status === '1' && empty($_POST['agree_late'])) {
                // Traffic ပိတ်နေရင် Warning ပြမယ် (Redirect မလုပ်သေးဘူး)
                $show_traffic_warning = true; 
            } else {
                // အားလုံး OK ရင် Order Form သို့ Redirect လုပ်မည်
                $encoded_address = urlencode($found_address);
                header("Location: order_form.php?code=$postal_code&address=$encoded_address&dist=$distance_km");
                exit();
            }
        }
    }

    // (B) Order Search Check
    if (isset($_POST['checkphonenumber'])) {
        $phone = trim($_POST['checkphonenumber']);
        if ($phone !== '') {
            $stmt = $conn->prepare("SELECT id FROM orders WHERE phonenumber = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($order = $result->fetch_assoc()) {
                header('Location: check_order.php?id=' . $order['id']);
                exit;
            } else {
                $msg = '❌ အော်ဒါရှာမတွေ့ပါ။ ဖုန်းနံပါတ် ပြန်စစ်ပါ။';
                $msg_type = 'error';
            }
        }
    }
}
?>