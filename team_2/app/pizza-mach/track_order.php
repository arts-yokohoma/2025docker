<?php
$conn = new mysqli("localhost", "root", "", "pizza_mach_db");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];

    // အဲ့ဒီဖုန်းနံပါတ်နဲ့ မှာထားတဲ့ "နောက်ဆုံးအော်ဒါ" ကို ရှာမယ်
    $sql = "SELECT * FROM orders WHERE phone = '$phone' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Status ကို မြန်မာလို ပြန်ပြောင်းပေးမယ်
        $status_msg = "";
        if($row['status'] == 'pending') $status_msg = "⏳ စောင့်ဆိုင်းဆဲ (မီးဖိုချောင်သို့ ပို့ပြီး)";
        if($row['status'] == 'delivered') $status_msg = "✅ ပို့ဆောင်ပြီးပါပြီ";

        echo json_encode([
            "status" => "found",
            "order_id" => $row['id'],
            "pizza_size" => $row['pizza_size'],
            "state" => $status_msg,
            "time" => $row['order_time']
        ]);
    } else {
        echo json_encode(["status" => "not_found"]);
    }
}
?>