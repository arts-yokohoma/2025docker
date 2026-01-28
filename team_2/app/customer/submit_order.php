<?php
// customer/submit_order.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo'); // Timezone မှန်ဖို့လိုပါတယ်

require_once '../database/db_conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // (၁) Form မှ Data များကို လက်ခံယူခြင်း
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // လိပ်စာ ၂ ခုကို ပေါင်းလိုက်ခြင်း (City + Detail)
    $city = $_POST['address_city'] ?? '';
    $detail = $_POST['address_detail'] ?? '';
    $full_address = trim($city . " " . $detail);

    $size = $_POST['size'] ?? 'M';
    $qty = intval($_POST['quantity'] ?? 1);
    $postal_code = $_POST['postal_code'] ?? '';

    // (၂) လိုအပ်သည်များ ပါမပါ စစ်ဆေးခြင်း
    if (empty($name) || empty($phone) || empty($full_address)) {
        echo "<script>alert('အချက်အလက်များ ပြည့်စုံစွာ ဖြည့်သွင်းပါ'); window.history.back();</script>";
        exit;
    }

    // (၃) Database သို့ သိမ်းဆည်းခြင်း (Prepared Statement)
    // SQL Injection ကာကွယ်ရန် bind_param သုံးထားပါသည်
    $sql = "INSERT INTO orders (customer_name, phonenumber, address, pizza_type, quantity, postal_code, status, order_date) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";

    if ($stmt = $conn->prepare($sql)) {
        // s = string, i = integer
        // types: name(s), phone(s), address(s), size(s), qty(i), postal(s)
        $stmt->bind_param("ssssis", $name, $phone, $full_address, $size, $qty, $postal_code);

        if ($stmt->execute()) {
            // ✅ အောင်မြင်ရင် Order ID ယူပြီး Status Page ကို ပို့မယ်
            $new_order_id = $conn->insert_id;
            header("Location: check_order.php?id=" . $new_order_id);
            exit();
        } else {
            echo "SQL Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Database Error: " . $conn->error;
    }
} else {
    // POST မဟုတ်ဘဲ တိုက်ရိုက်ဝင်လာရင် index ကို ပြန်ပို့မယ်
    header("Location: index.php");
    exit();
}
?>