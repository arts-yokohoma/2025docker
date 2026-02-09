<?php
// customer/submit_order_logic.php
session_start();
require_once '../database/db_conn.php'; // Lang & Timezone

// Ensure functions are loaded
if (file_exists('../database/functions.php')) {
    require_once '../database/functions.php';
}

$success_order_id = 0;
$error_message = "";
$is_overloaded = false; 
$overload_wait_time = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. INPUTS
    $name = trim($_POST['name'] ?? '');
    
    // 🔥 PHONE SANITIZATION & VALIDATION
    $raw_phone = $_POST['phone'] ?? '';
    // Remove all non-number characters (hyphens, spaces)
    $phone = preg_replace('/[^0-9]/', '', $raw_phone);

    // Validate Japan Phone Format (Starts with 0, 10-11 digits)
    if (!preg_match('/^0\d{9,10}$/', $phone)) {
        $error_message = "電話番号が正しくありません (Invalid Phone Number)";
    } 
    elseif (empty($name)) {
        $error_message = "お名前を入力してください (Name required)";
    }
    else {
        // ... (Validation Passed - Continue Logic) ...

        $city = $_POST['address_city'] ?? '';
        $detail = $_POST['address_detail'] ?? '';
        $postal = $_POST['postal_code'] ?? '';
        $size = $_POST['size'] ?? 'M';
        $qty = intval($_POST['quantity'] ?? 1); 
        if ($qty < 1) $qty = 1;

        // GPS Data
        $lat = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : NULL;
        $lng = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : NULL;

        // 2. CAPACITY CHECK
        $res_k = $conn->query("SELECT setting_value FROM system_config WHERE setting_key = 'kitchen_staff'");
        $k_staff = intval($res_k->fetch_assoc()['setting_value'] ?? 3);
        $max_limit = $k_staff * 4;

        $sql_load = "SELECT SUM(quantity) as total_items FROM orders WHERE status IN ('Pending', 'Cooking')";
        $res_load = $conn->query($sql_load);
        $current_load = intval($res_load->fetch_assoc()['total_items'] ?? 0);

        $final_estimated_mins = 30; 
        $res_t = $conn->query("SELECT setting_value FROM system_config WHERE setting_key = 'traffic_mode'");
        if (($res_t->fetch_assoc()['setting_value'] ?? '0') == '1') {
            $final_estimated_mins += 15;
        }

        // Overload Check
        if (($current_load + $qty) > $max_limit) {
            if (!isset($_POST['confirm_wait'])) {
                $is_overloaded = true;
                $extra_items = ($current_load + $qty) - $max_limit;
                $overload_wait_time = 45 + ($extra_items * 10);
            } else {
                $extra_items = ($current_load + $qty) - $max_limit;
                $final_estimated_mins = 45 + ($extra_items * 10);
            }
        }

        // 3. RIDER ASSIGNMENT LOGIC
        if (!$is_overloaded && empty($error_message)) {
            
            $assigned_slot_id = NULL;

            // STEP A: Try to find a FREE rider first
            $rider_sql = "SELECT slot_id FROM delivery_slots WHERE status='Free' LIMIT 1";
            $rider_res = $conn->query($rider_sql);
            
            if ($rider_res && $rider_res->num_rows > 0) {
                // ✅ FOUND FREE RIDER
                $rid = $rider_res->fetch_assoc()['slot_id'];
                $assigned_slot_id = $rid;
                
                // 🔥 RESERVE IMMEDIATELY
                $conn->query("UPDATE delivery_slots SET status='Busy' WHERE slot_id=$rid");
            
            } else {
                // STEP B: SMART BATCHING
                if ($lat && $lng) {
                    $batch_sql = "SELECT assigned_slot_id, latitude, longitude, quantity, start_time, order_date 
                                  FROM orders 
                                  WHERE status IN ('Pending', 'Cooking') AND assigned_slot_id IS NOT NULL";
                    $batch_res = $conn->query($batch_sql);

                    while ($row = $batch_res->fetch_assoc()) {
                        if(empty($row['latitude']) || empty($row['longitude'])) continue;

                        $dist = calculateDistance($lat, $lng, $row['latitude'], $row['longitude']);
                        $total_qty = intval($row['quantity']) + $qty;
                        
                        $ref_time = !empty($row['start_time']) ? $row['start_time'] : $row['order_date'];
                        $mins_waiting = (time() - strtotime($ref_time)) / 60;

                        if ($dist <= 3.0 && $total_qty <= 10 && $mins_waiting <= 15) {
                            $assigned_slot_id = $row['assigned_slot_id'];
                            break; 
                        }
                    }
                }
            }

            // 4. INSERT ORDER
            $full_address = $city . " " . $detail;
            
            $stmt = $conn->prepare("INSERT INTO orders (
                customer_name, phonenumber, address, address_city, address_detail, postal_code, 
                pizza_type, quantity, status, order_date, 
                assigned_slot_id, estimated_mins, latitude, longitude
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssiiidd", 
                $name, $phone, $full_address, $city, $detail, $postal, 
                $size, $qty, 
                $assigned_slot_id, $final_estimated_mins, $lat, $lng
            );
            
            if ($stmt->execute()) {
                $success_order_id = $conn->insert_id;
            } else {
                $error_message = "Database Error: " . $stmt->error;
            }
        }
    }
}
?>