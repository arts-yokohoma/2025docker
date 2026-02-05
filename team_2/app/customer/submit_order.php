<?php
// customer/submit_order.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo'); 

require_once '../database/db_conn.php';
require_once '../database/functions.php';

// POST Method ဖြင့် လာမှသာ အလုပ်လုပ်မည်
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // (၁) Form Data လက်ခံခြင်း
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // လိပ်စာပေါင်းစပ်ခြင်း
    $city = $_POST['address_city'] ?? '';
    $detail = $_POST['address_detail'] ?? '';
    $full_address = trim($city . " " . $detail);

    $size = $_POST['size'] ?? 'M'; // Pizza Size (or Type)
    $qty = intval($_POST['quantity'] ?? 1);
    
    // Postal Code (ကိန်းဂဏန်းသီးသန့်ယူမည်)
    $postal_code = preg_replace('/[^0-9]/', '', $_POST['postal_code'] ?? '');

    // (၂) Validation (အချက်အလက်မစုံလျှင် ပြန်လွှတ်မည်)
    if (empty($name) || empty($phone) || empty($full_address)) {
        echo "<script>alert('ကျေးဇူးပြု၍ အချက်အလက်များ ပြည့်စုံစွာ ဖြည့်သွင်းပေးပါ။'); window.history.back();</script>";
        exit;
    }

    // (၃) Lat/Lng နှင့် ကြာချိန် တွက်ချက်ခြင်း
    $lat = null;
    $lng = null;
    $one_way_minutes = 20; // Default ကြာချိန် (တွက်မရခဲ့လျှင် သုံးရန်)

    // Postal Code ရှိလျှင် API ဖြင့် Lat/Lng ရှာမည်
    if (!empty($postal_code) && function_exists('getLatLngFromPostal')) {
        $geo = getLatLngFromPostal($postal_code);
        
        if ($geo) {
            $lat = $geo['lat'];
            $lng = $geo['lng'];

            // Shop နှင့် အကွာအဝေးတွက်ပြီး မိနစ်ပြောင်းမည်
            if (defined('SHOP_LAT') && defined('SHOP_LNG') && function_exists('calculateDistance')) {
                $dist_km = calculateDistance(SHOP_LAT, SHOP_LNG, $lat, $lng);
                $speed_per_min = 0.5; // (30km/h => 0.5 km/min)
                $one_way_minutes = ceil($dist_km / $speed_per_min);
            }
        }
    }

    // (၄) Database သို့ Order သိမ်းဆည်းခြင်း
    // အရေးကြီး: Lat, Lng က NULL ဖြစ်နိုင်သောကြောင့် Database field တွင် Allow Null ပေးထားသင့်သည်
    // မပေးထားလျှင် 0.0 ထည့်သွင်းရန် $lat ?? 0.0 ဟု ပြင်ရေးနိုင်သည်
    
    $sql = "INSERT INTO orders (customer_name, phonenumber, address, pizza_type, quantity, postal_code, latitude, longitude, status, order_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

    if ($stmt = $conn->prepare($sql)) {
        // s=string, i=int, d=double
        $stmt->bind_param("ssssisdd", $name, $phone, $full_address, $size, $qty, $postal_code, $lat, $lng);

       if ($stmt->execute()) {
            $new_order_id = $conn->insert_id;

            // ✅ အော်ဒါဝင်တာနဲ့ Rider ကို ချက်ချင်း Busy လုပ်မယ်
            if (function_exists('assignRiderBusy')) {
                // assignRiderBusy(Order ID, ကြာချိန်မိနစ်)
                // submit_order.php ထဲမှာ
                $one_way_minutes = $one_way_minutes ?? 20; // တန်ဖိုးမရှိရင် ၂၀ လို့ ပေးထားပါ
                assignRiderBusy($new_order_id, $one_way_minutes);
            }
            // ✅ အောင်မြင်ကြောင်း စာမျက်နှာသို့ Redirect လုပ်ခြင်း
            header("Location: check_order.php?id=" . $new_order_id);
            exit();

        } else {
            // SQL Insert Error
            echo "Save Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Database Connection Error
        echo "Database Error: " . $conn->error;
    }

} else {
    // POST မဟုတ်ဘဲ ဝင်လာလျှင် Form သို့ ပြန်ပို့မည်
    header("Location: index.php");
    exit();
}
?>