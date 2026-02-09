<?php
// admin/admin_logic.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
date_default_timezone_set('Asia/Tokyo');
require_once '../database/db_conn.php';

// ==========================================
// 1. UPDATE SETTINGS (Staff & Riders)
// ==========================================
if (isset($_POST['update_settings'])) {
    $k_staff = intval($_POST['kitchen_staff']);
    $r_staff = intval($_POST['rider_staff']);

    // Update Kitchen Staff Count
    $conn->query("UPDATE system_config SET setting_value='$k_staff' WHERE setting_key='kitchen_staff'");
    
    // Update Rider Slots in Database
    $res = $conn->query("SELECT COUNT(*) as c FROM delivery_slots");
    $current_slots = $res->fetch_assoc()['c'];

    if ($r_staff > $current_slots) {
        // အသစ်တိုးမည်
        $diff = $r_staff - $current_slots;
        for ($i = 0; $i < $diff; $i++) {
            $conn->query("INSERT INTO delivery_slots (status) VALUES ('Free')");
        }
    } elseif ($r_staff < $current_slots) {
        // လျှော့ချမည် (Free ဖြစ်နေသော Slot များကို ဦးစားပေးဖျက်မည်)
        $diff = $current_slots - $r_staff;
        $conn->query("DELETE FROM delivery_slots WHERE status='Free' ORDER BY slot_id DESC LIMIT $diff");
        
        // Free တွေဖျက်လို့ မလောက်သေးရင် ကျန်တာဆက်ဖျက်မည် (Active Rider မဟုတ်တာကို ရွေးဖျက်မည်)
        $res2 = $conn->query("SELECT COUNT(*) as c FROM delivery_slots");
        $rem_slots = $res2->fetch_assoc()['c'];
        
        if ($rem_slots > $r_staff) {
            $diff2 = $rem_slots - $r_staff;
            // 🛑 SAFETY FIX: အော်ဒါပို့နေသော (Delivering) Rider များကို မဖျက်မိအောင် ကာကွယ်ခြင်း
            $sql_safe_delete = "DELETE FROM delivery_slots 
                                WHERE slot_id NOT IN (SELECT assigned_slot_id FROM orders WHERE status='Delivering' AND assigned_slot_id IS NOT NULL) 
                                ORDER BY slot_id DESC LIMIT $diff2";
            $conn->query($sql_safe_delete);
        }
    }
    header("Location: admin.php");
    exit();
}

// ==========================================
// 2. TOGGLE TRAFFIC MODE
// ==========================================
if (isset($_POST['toggle_traffic'])) {
    $res = $conn->query("SELECT setting_value FROM system_config WHERE setting_key='traffic_mode'");
    $current = $res->fetch_assoc()['setting_value'] ?? '0';
    $new = ($current == '1') ? '0' : '1';
    $conn->query("UPDATE system_config SET setting_value='$new' WHERE setting_key='traffic_mode'");
    header("Location: admin.php");
    exit();
}

// ==========================================
// 3. ACTIONS (COOK, DELIVER, REJECT, ETC.)
// ==========================================
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $act = $_GET['action'];
    $now = date('Y-m-d H:i:s');

    if ($act == 'cook') {
        $conn->query("UPDATE orders SET status='Cooking', start_time='$now' WHERE id=$id");
        header("Location: admin.php"); exit();

    } elseif ($act == 'deliver') {
        // Smart Rider Logic: Busy ဖြစ်နေပြီး Delivery မလုပ်နေသော (ပြန်လာနေသော) Rider ကို ရှာမည်
        $sql_standby = "SELECT slot_id FROM delivery_slots WHERE status='Busy' AND slot_id NOT IN (SELECT assigned_slot_id FROM orders WHERE status='Delivering' AND assigned_slot_id IS NOT NULL) LIMIT 1";
        $standby_res = $conn->query($sql_standby);
        $target_slot_id = 0;

        if ($standby_res && $standby_res->num_rows > 0) {
            $target_slot_id = $standby_res->fetch_assoc()['slot_id'];
        } else {
            // မရှိလျှင် Free Rider ကို ရှာမည်
            $sql_free = "SELECT slot_id FROM delivery_slots WHERE status='Free' LIMIT 1";
            $free_res = $conn->query($sql_free);
            if ($free_res->num_rows > 0) $target_slot_id = $free_res->fetch_assoc()['slot_id'];
        }

        if ($target_slot_id > 0) {
            $conn->query("UPDATE orders SET status='Delivering', departure_time='$now', assigned_slot_id=$target_slot_id WHERE id=$id");
            $conn->query("UPDATE delivery_slots SET status='Busy' WHERE slot_id=$target_slot_id");
            header("Location: admin.php"); exit();
        } else {
            echo "<script>alert('❌ No Riders Available!'); window.location.href='admin.php';</script>"; exit();
        }

    } elseif ($act == 'rider_back') {
        $q = $conn->query("SELECT assigned_slot_id FROM orders WHERE id=$id");
        $slot_id = $q->fetch_assoc()['assigned_slot_id'] ?? 0;
        $conn->query("UPDATE orders SET status='Completed', return_time='$now' WHERE id=$id");
        if ($slot_id) $conn->query("UPDATE delivery_slots SET status='Free' WHERE slot_id=$slot_id");
        header("Location: admin.php"); exit();

    } elseif ($act == 'reject') {
        // 🛑 SECURITY FIX: SQL Injection ကာကွယ်ခြင်း
        $raw_reason = urldecode($_GET['reason'] ?? 'Busy');
        $reason = $conn->real_escape_string($raw_reason);
        
        $conn->query("UPDATE orders SET status='Rejected', reject_reason='$reason' WHERE id=$id");
        header("Location: admin.php"); exit();
    }
}

// ==========================================
// 4. FETCH DATA FOR DASHBOARD
// ==========================================

// Check New Orders (AJAX)
if (isset($_GET['check_new_orders'])) {
    $r = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='Pending'");
    echo $r->fetch_assoc()['c']; exit();
}

// Staff Settings
$res_k = $conn->query("SELECT setting_value FROM system_config WHERE setting_key = 'kitchen_staff'");
$k_staff = intval($res_k->fetch_assoc()['setting_value'] ?? 3);
$max_kitchen_capacity = $k_staff * 4;

$res_t = $conn->query("SELECT setting_value FROM system_config WHERE setting_key = 'traffic_mode'");
$traffic_mode = $res_t->fetch_assoc()['setting_value'] ?? '0';

// Kitchen Load Calculation
$active_res = $conn->query("SELECT SUM(quantity) as total_items FROM orders WHERE status IN ('Pending', 'Cooking')");
$current_kitchen_load = intval($active_res->fetch_assoc()['total_items'] ?? 0);
$capacity_percent = ($max_kitchen_capacity > 0) ? ($current_kitchen_load / $max_kitchen_capacity) * 100 : 100;
if($capacity_percent > 100) $capacity_percent = 100;

// Riders Status & Deli Load Calculation
$res_total = $conn->query("SELECT COUNT(*) as c FROM delivery_slots");
$total_riders_db = $res_total->fetch_assoc()['c'];
$res_free = $conn->query("SELECT COUNT(*) as c FROM delivery_slots WHERE status='Free'");
$free_riders = $res_free->fetch_assoc()['c'];
$busy_riders_db = $total_riders_db - $free_riders;

// 🟢 Deli Percent Logic
$deli_percent = ($total_riders_db > 0) ? ($busy_riders_db / $total_riders_db) * 100 : 0;
if($deli_percent > 100) $deli_percent = 100;

// Order List Fetching
$tab = $_GET['tab'] ?? 'active';
if ($tab == 'active') {
    $sql = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering') ORDER BY FIELD(status, 'Pending', 'Cooking', 'Delivering'), order_date ASC";
} else {
    $st = ($tab == 'rejected') ? 'Rejected' : 'Completed';
    $sql = "SELECT * FROM orders WHERE status = '$st' ORDER BY order_date DESC LIMIT 50";
}
$result = $conn->query($sql);
?>