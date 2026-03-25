<?php
// database/db_conn.php

// 1. PHP Timezone ကို Tokyo ပြောင်းမယ်
date_default_timezone_set('Asia/Tokyo');

// Error Reporting
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1') {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
} else {
    // Production Server အတွက် Error မပြဘဲ Log ပဲမှတ်မယ် (Security အရ ပိုကောင်း)
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// Database Credentials Setting
$configs = [
    'docker' => ['host' => 'team_2_mysql', 'user' => 'team_2', 'pass' => 'team2pass', 'db' => 'team_2_db'],
    'local'  => ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'team_2_db'],
    'real'   => ['host' => 'localhost', 'user' => 'root', 'pass' => 'amm', 'db' => 'team_2_db']
];

$conn = null;

// Connection Logic (Docker -> Local -> Real)
try {
    $c = $configs['docker'];
    $conn = @new mysqli($c['host'], $c['user'], $c['pass'], $c['db']);
} catch (mysqli_sql_exception $e1) {
    try {
        $c = $configs['local'];
        $conn = new mysqli($c['host'], $c['user'], $c['pass'], $c['db']);
    } catch (mysqli_sql_exception $e2) {
        try {
            $c = $configs['real'];
            $conn = new mysqli($c['host'], $c['user'], $c['pass'], $c['db']);
        } catch (mysqli_sql_exception $e3) {
            // Connection Failed Completely
            header('HTTP/1.1 503 Service Unavailable');
            echo "<h1>System Maintenance</h1>";
            if ($_SERVER['HTTP_HOST'] == 'localhost') {
                die("Connection Failed: " . $e3->getMessage());
            } else {
                die("<p>We are currently experiencing database issues. Please try again later.</p>");
            }
        }
    }
}

// 2. Character Set & Timezone Configuration
if ($conn) {
    $conn->set_charset("utf8mb4");
    // MySQL Timezone ကို Tokyo (+09:00) ပြောင်းမယ်
    $conn->query("SET time_zone = '+09:00'");
}

// ==========================================
// 🇯🇵 JAPANESE LANGUAGE CONFIG
// ==========================================
$lang = [
    // Customer Form
    'order_form_title' => 'ピザを注文 (Order Pizza)',
    'name' => 'お名前 (Name)',
    'phone' => '電話番号 (Phone)',
    'address' => '住所 (Address)',
    'detail' => '番地・建物名 (Detail)',
    'size' => 'サイズ (Size)',
    'qty' => '数量 (Qty)',
    'order_btn' => '注文を確定する',
    
    // Busy / Wait
    'wait_title' => '⚠️ 大変混み合っております',
    'wait_msg' => '現在注文が集中しているため、お届けにお時間がかかります。',
    'wait_btn' => '待てるので注文する',
    'cancel_btn' => 'キャンセル',
    'heavy_traffic' => '渋滞中',
    'kitchen_busy' => '調理混雑中',
    'riders_busy' => '配達員混雑中',

    // Status Page
    'status_pending' => 'ご注文を確認中です (Pending)',
    'status_cooking' => 'ただいま調理中です (Cooking)',
    'status_delivering' => '配達員が向かっています (On the Way)',
    'status_completed' => '配達が完了しました (Completed)',
    'status_rejected' => '注文がキャンセルされました',
    'arriving_soon' => 'まもなく到着します！',
    'eta' => '到着予定',
    'mins' => '分',

    // Kitchen Admin
    'kitchen_title' => 'キッチン (Kitchen)',
    'cook_btn' => '調理開始',
    'call_btn' => '配達員呼出',
    'done_btn' => '完了',
    'print_btn' => '印刷',
    'reject_btn' => '拒否',
    'riders_free' => '待機中',
];
?>