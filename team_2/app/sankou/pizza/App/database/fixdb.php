<?php
// fix_db.php
require_once 'database/db_conn.php';

// Check columns
$check = $conn->query("SHOW COLUMNS FROM `delivery_slots` LIKE 'status'");
if($check->num_rows == 0) {
    $conn->query("ALTER TABLE `delivery_slots` ADD COLUMN `status` VARCHAR(20) DEFAULT 'Free'");
    echo "✅ Added 'status' column.<br>";
}

$check2 = $conn->query("SHOW COLUMNS FROM `delivery_slots` LIKE 'next_available_time'");
if($check2->num_rows == 0) {
    $conn->query("ALTER TABLE `delivery_slots` ADD COLUMN `next_available_time` DATETIME DEFAULT NULL");
    echo "✅ Added 'next_available_time' column.<br>";
}

echo "🎉 Database Fixed! You can try ordering now.";
?>