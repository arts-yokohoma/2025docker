<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// host=localhost ではなく host=db に変更
// パスワードは docker-compose.yml で設定した 'root' を指定
$pdo = new PDO('mysql:host=db;dbname=pizza_db;charset=utf8', 'root', 'root');

$action = $_GET['action'] ?? '';

// 1. 郵便番号チェック
if (isset($_GET['check_zip'])) {
    $stmt = $pdo->prepare("SELECT area_name FROM allowed_zipcodes WHERE code = ?");
    $stmt->execute([$_GET['check_zip']]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['status' => $res ? 'ok' : 'fail', 'area' => $res['area_name'] ?? '']);
    exit;
}

// 2. 注文作成 (シフト容量チェック付き)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create_order') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // 設定されたドライバー人数を取得
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'total_drivers'");
    $max_drivers = $stmt->fetchColumn() ?: 2;

    // 現在配達中の数を取得
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 2");
    $busy = $stmt->fetchColumn();

    if ($busy >= $max_drivers) {
        echo json_encode(['success' => false, 'message' => '現在大変混み合っており、注文を受けられません。']);
        exit;
    }

    // 注文保存
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, phone, address, pizza_size, zip_code) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['name'], $data['phone'], $data['address'], $data['size'], $data['zip']]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

// 3. 注文取得 (完了以外)
if ($action === 'get_orders') {
    $stmt = $pdo->query("SELECT * FROM orders WHERE status < 3 ORDER BY created_at DESC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// 4. ステータス更新
if ($action === 'update_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$data['status'], $data['id']]);
    echo json_encode(['success' => true]);
    exit;
}

// 5. シフト人数更新
if ($action === 'update_capacity') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'total_drivers'");
    $stmt->execute([$data['count']]);
    echo json_encode(['success' => true]);
    exit;
}
?>