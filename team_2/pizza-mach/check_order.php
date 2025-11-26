<?php
$conn = new mysqli("localhost", "root", "", "pizza_mach_db");

// ၁။ လက်ရှိ ဝန်ထမ်းအင်အား ဆွဲထုတ်
$row_staff = $conn->query("SELECT * FROM staff_config WHERE id = 1")->fetch_assoc();
$kitchen_limit = $row_staff['kitchen_staff'] * 3; // ၁ ယောက် ၃ ချပ်
$delivery_limit = $row_staff['delivery_staff'] * 1; // ၁ ယောက် ၁ ခေါက်

// ၂။ လက်ရှိ မပြီးသေးသော (Pending/Cooking) အော်ဒါများကို ရေတွက်
// 'Delivered' ဖြစ်သွားရင် Capacity ထဲ မထည့်တော့ဘူး
$sql_count = "SELECT COUNT(*) as total FROM orders WHERE status != 'delivered'";
$current_orders = $conn->query($sql_count)->fetch_assoc()['total'];

$new_total = $current_orders + 1;
$response = array();

// ၃။ Logic စစ်ဆေးခြင်း
if ($new_total > $kitchen_limit) {
    $response['status'] = 'error';
    $response['message'] = 'မီးဖိုချောင် လူပြည့်နေပါသည်! (Wait 30 mins)';
} elseif ($new_total > $delivery_limit) {
    $response['status'] = 'error';
    $response['message'] = 'ပို့မည့်လူ မရှိပါ! (Delivery Full)';
} else {
    // ၄။ အားလုံးအဆင်ပြေရင် Database ထဲ ထည့်မည်
   // ... (အပေါ်ပိုင်း ကုဒ်များ အတူတူပဲ) ...

    // ၄။ အားလုံးအဆင်ပြေရင် Database ထဲ ထည့်မည်
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $phone = $_POST['phone']; // <--- ဒီလိုင်း အသစ်တိုးပါ
        $addr = $_POST['address'];
        $size = $_POST['size'];
        
        // INSERT statement မှာ phone ပါ ထပ်ထည့်ပါ
        $sql = "INSERT INTO orders (customer_name, phone, address, pizza_size, status) VALUES ('$name', '$phone', '$addr', '$size', 'pending')";
        $conn->query($sql);
        
        $response['status'] = 'success';
        $response['message'] = 'အော်ဒါလက်ခံပါသည်! မိနစ် ၃၀ အတွင်း ရောက်ပါမည်။';
    }

// ... (အောက်ပိုင်း ကုဒ်များ အတူတူပဲ) ...
}

header('Content-Type: application/json');
echo json_encode($response);
?>