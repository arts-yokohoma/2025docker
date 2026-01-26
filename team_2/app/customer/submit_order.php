<?php
include "../database/db_conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST"){
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $phonenumber = $_POST['phone'] ?? '';
    $pizza_type = $_POST['size'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    
    if(!empty($name) && !empty($address) && !empty($phonenumber) && !empty($pizza_type) && !empty($quantity)){
        $sql = "INSERT INTO orders (customer_name, address, phonenumber, pizza_type, quantity, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($sql);
        
        if($stmt){
            $stmt->bind_param("sssss", $name, $address, $phonenumber, $pizza_type, $quantity);
            if($stmt->execute()){
                // (၁) အခုလေးတင် ဝင်သွားတဲ့ Order ID ကို ယူမယ်
                $new_order_id = $stmt->insert_id; 
                
                $stmt->close();
                $conn->close();

                // (၂) ID ကို ယူပြီး Status စာမျက်နှာကို ပို့လိုက်မယ်
                header("Location: order_status.php?id=" . $new_order_id);
                exit();
            } else {
                die("Error executing statement: " . $stmt->error);
            }
        }
    }
}
//header('location:check_order.php');
?>

