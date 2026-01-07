<<<<<<< HEAD
<?php
// Docker Compose မှာ သတ်မှတ်ထားတဲ့ အချက်အလက်များ
$host = "team_2_db"; // container_name ကို host အဖြစ်သုံးရပါမယ်
$user = "team_2";       // MYSQL_USER
$pass = "team2pass";    // MYSQL_PASSWORD
$dbname = "team_2_db";  // MYSQL_DATABASE

// Connection ဆောက်ခြင်း
$conn = new mysqli($host, $user, $pass, $dbname);

// Connection စစ်ဆေးခြင်း
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else {
    // echo "Connected successfully";
    echo"connected db successfully";
}

// စာသားတွေ မှန်အောင် UTF-8 သတ်မှတ်ခြင်း
$conn->set_charset("utf8mb4");
?>
=======
<?php
$db = new PDO('sqlite:../database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    phone TEXT,
    address TEXT,
    size TEXT,
    time TEXT,
    driver_name TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
?>
>>>>>>> 3093e2e (kin)
