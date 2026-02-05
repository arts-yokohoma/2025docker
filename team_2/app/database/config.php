<?php
// database/config.php

// ၁။ HTTP လား HTTPS လား စစ်မယ်
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

// ၂။ Host (localhost:8080) ကို ယူမယ်
$host = $_SERVER['HTTP_HOST'];

// ၃။ Environment ပေါ်မူတည်ပြီး BASE_URL သတ်မှတ်မယ်
if (strpos($host, 'localhost') !== false || $host == '127.0.0.1') {
    // === LOCALHOST (Your Machine) ===
    // မိတ်ဆွေရဲ့ Link အရ /team_2/app/ သည် Project Root ဖြစ်သည်
    define('BASE_URL', $protocol . "://" . $host . "/team_2/app/"); 

} else {
    // === REAL SERVER (Hosting) ===
    // Server ပေါ်ရောက်ရင် Folder နာမည် မလိုတော့ပါ
    define('BASE_URL', $protocol . "://" . $host . "/");
}

// ၄။ PHP Include တွေအတွက် System Path
// C:\xampp\htdocs\team_2\app\database\.. -> C:\xampp\htdocs\team_2\app\
define('ROOT_PATH', realpath(__DIR__ . '/../') . '/');

?>