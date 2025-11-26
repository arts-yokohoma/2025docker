<?php
// ==========================================
// Pizza Mach Backend API
// For Member 1 (Database & Logic)
// ==========================================

// 1. Headers (Browser တွင် Error မတက်စေရန်)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. Database Connection (XAMPP Default)
$host = 'localhost';
$db_name = 'pizza_db';
$username = 'root';
$password = ''; // XAMPP မှာ Password မရှိပါ

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

// Action ကို GET method မှ ယူမည် (ဥပမာ: api.php?action=get_orders)
$action = $_GET['action'] ?? '';


// ==========================================
// LOGIC 1: ZIP CODE CHECK (Customer)
// ==========================================
if (isset($_GET['check_zip'])) {
    $zip = $_GET['check_zip'];
    
    // Whitelist ထဲမှာ ရှိမရှိ စစ်မယ်
    $stmt = $pdo->prepare("SELECT area_name FROM allowed_zipcodes WHERE code = ?");
    $stmt->execute([$zip]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        // ရှိရင် Success ပြန်ပို့
        echo json_encode([
            'status' => 'ok', 
            'msg' => '30分以内にお届け可能です (' . $result['area_name'] . ')'
        ]);
    } else {
        // မရှိရင် Fail ပြန်ပို့
        echo json_encode([
            'status' => 'fail', 
            'msg' => '配達エリア外です (Delivery not available)'
        ]);
    }
    exit();
}


// ==========================================
// LOGIC 2: CREATE ORDER (Customer)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_order') {
    // JSON Data ကို ဖတ်မည်
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['name']) || !isset($data['phone'])) {
        echo json_encode(['success' => false, 'message' => 'Incomplete Data']);
        exit();
    }

    // A. Driver Capacity (Shift) ကို စစ်ဆေးခြင်း
    // Manager သတ်မှတ်ထားသော အရေအတွက် (Settings Table)
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'total_drivers'");
    $max_drivers = $stmt->fetchColumn(); 
    if (!$max_drivers) $max_drivers = 2; // Default 2 ယောက်

    // B. လက်ရှိ ပို့ဆောင်နေဆဲ (Status 2) အရေအတွက်ကို ရေတွက်ခြင်း
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 2");
    $busy_drivers = $stmt->fetchColumn();

    // C. နှိုင်းယှဉ်ခြင်း (Capacity Check)
    if ($busy_drivers >= $max_drivers) {
        // ပြည့်နေရင် လက်မခံပါ
        echo json_encode(['success' => false, 'message' => 'Sorry, Drivers are full right now.']);
        exit();
    }

    // D. အားနေရင် Database ထဲ ထည့်မည်
    $sql = "INSERT INTO orders (customer_name, phone, address, pizza_size, zip_code, status) VALUES (?, ?, ?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([
            $data['name'], 
            $data['phone'], 
            $data['address'], 
            $data['size'], 
            $data['zip']
        ]);
        echo json_encode(['success' => true, 'message' => 'Order Created']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $e->getMessage()]);
    }
    exit();
}


// ==========================================
// LOGIC 3: GET ORDERS (Manager)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_orders') {
    // ပြီးဆုံးသွားသော (Status 3) အော်ဒါများကို မပြတော့ပါ
    // Status: 0=New, 1=Cooking, 2=Delivering
    $stmt = $pdo->query("SELECT * FROM orders WHERE status < 3 ORDER BY id DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($orders);
    exit();
}


// ==========================================
// LOGIC 4: UPDATE STATUS (Manager)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['id']]);

    echo json_encode(['success' => true]);
    exit();
}


// ==========================================
// LOGIC 5: UPDATE CAPACITY (Manager Shift)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_capacity') {
    $data = json_decode(file_get_contents("php://input"), true);

    // settings table ကို update လုပ်မည်
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('total_drivers', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$data['count'], $data['count']]);

    echo json_encode(['success' => true]);
    exit();
}
?>