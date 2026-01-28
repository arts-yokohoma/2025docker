<?php
include "../database/db_conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form ထဲက Data များကို လက်ခံခြင်း
    $name         = $_POST['name'] ?? '';
    $phonenumber  = $_POST['phone'] ?? '';
    $pizza_type   = $_POST['size'] ?? '';
    $quantity     = $_POST['quantity'] ?? '';
    $postal_code  = $_POST['postal_code'] ?? '';
    
    // လိပ်စာနှစ်ခုကို တစ်ခုတည်းဖြစ်အောင် ပေါင်းခြင်း
    $address_city   = $_POST['address'] ?? '';
    $address_detail = $_POST['address_detail'] ?? '';
    $address   = trim($address_city . " " . $address_detail);

    // Data ပြည့်စုံမှု ရှိမရှိ စစ်ဆေးခြင်း
    if (!empty($name) && !empty($address) && !empty($phonenumber) && !empty($pizza_type) && !empty($quantity)) {
        
        // SQL query (Parameter ၆ ခု ပါဝင်သည် - name, address, phone, type, qty, postal)
        $sql = "INSERT INTO orders (customer_name, address, phonenumber, pizza_type, quantity, postal_code, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // bind_param ထဲမှာ type string ၆ ခုပဲ ဖြစ်ရပါမယ် (ssssss)
            $stmt->bind_param("ssssss", $name, $address, $phonenumber, $pizza_type, $quantity, $postal_code);
            
            if ($stmt->execute()) {
                $new_order_id = $stmt->insert_id; 
                
                $stmt->close();
                $conn->close();

                // Order အောင်မြင်ရင် Status page ကို ID နဲ့တကွ ပို့ပေးမယ်
                header("Location: check_order.php?id=" . $new_order_id);
                exit();
            } else {
                die("Error executing statement: " . $stmt->error);
            }
        }
    } else {
        echo "<script>alert('အချက်အလက်များ အားလုံးဖြည့်စွက်ပေးပါ'); window.history.back();</script>";
    }
}
?>