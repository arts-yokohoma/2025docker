<?php
// App/admin/kitchen_logic.php
session_start();
require_once '../database/db_conn.php';

// (A) နောက်ဆုံးဝင်ထားတဲ့ Order ID ကို ပြန်ပေးမယ် (Auto Print အတွက်)
if (isset($_GET['check_latest_order'])) {
    $sql = "SELECT id FROM orders ORDER BY id DESC LIMIT 1";
    $res = $conn->query($sql);
    $last_id = ($res->num_rows > 0) ? $res->fetch_assoc()['id'] : 0;
    
    echo json_encode(['latest_id' => $last_id]);
    exit();
}

// (B) ချက်မယ် / Rider ခေါ်မယ် Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $act = $_GET['action'];
    $now = date('Y-m-d H:i:s');

    if ($act == 'start_cook') {
        $conn->query("UPDATE orders SET status='Cooking', start_time='$now' WHERE id=$id");
    } elseif ($act == 'call_rider') {
        // Rider Assignment Logic (Simplified)
        $sql_free = "SELECT slot_id FROM delivery_slots WHERE status='Free' LIMIT 1";
        $free_res = $conn->query($sql_free);
        if ($free_res->num_rows > 0) {
            $slot_id = $free_res->fetch_assoc()['slot_id'];
            $conn->query("UPDATE orders SET status='Delivering', departure_time='$now', assigned_slot_id=$slot_id WHERE id=$id");
            $conn->query("UPDATE delivery_slots SET status='Busy' WHERE slot_id=$slot_id");
        } else {
             $conn->query("UPDATE orders SET status='Delivering', departure_time='$now' WHERE id=$id");
        }
    }
    // Action ပြီးရင် ပြန်မောင်းမယ်
    header("Location: kitchen.php"); exit();
}

// (C) ပုံမှန် Data ဆွဲထုတ်ခြင်း (Pending & Cooking)
$sql = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking') ORDER BY id DESC";
$result = $conn->query($sql);
?>