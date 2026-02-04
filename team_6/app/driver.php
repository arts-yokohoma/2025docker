<?php
// driver.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// データベース接続 (PostgreSQL)
include 'db/connect.php'; 

// =============================
// AJAX 通知ハンドラー
// =============================
if (isset($_GET['action']) && $_GET['action'] === 'notification') {
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE status = 'Ready'");
    $stmt->execute();
    echo $stmt->fetchColumn();
    exit;
}

// driver.php - Add this near your notification handler
if (isset($_GET['action']) && $_GET['action'] === 'update_location') {
    $lat = $_GET['lat'];
    $lng = $_GET['lng'];
    $did = $_SESSION['delivery_id'];
    $stmt = $db->prepare("UPDATE staff SET current_lat = ?, current_lng = ? WHERE id = ?");
    $stmt->execute([$lat, $lng, $did]);
    exit;
}

// =============================
// ログイン・ログアウト処理
// =============================
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // staffテーブルを使用
    $stmt = $db->prepare("SELECT * FROM staff WHERE user_id = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['delivery_id'] = $user['id'];
        $_SESSION['delivery_name'] = $user['name'];
        header("Location: driver.php"); 
        exit;
    } else {
        $login_error = "IDまたはパスワードが正しくありません。";
    }
}

if (isset($_POST['logout']) || isset($_GET['logout'])) {
    session_unset(); 
    session_destroy();
    header("Location: driver.php"); 
    exit;
}

if (!isset($_SESSION['delivery_id'])):
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ログイン - To Pizza Mach</title>
    <style>
        body { background: #fff0f0; font-family: "Helvetica Neue", Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 350px; text-align: center; }
        h1 { color: #ff3b3b; margin-bottom: 30px; font-size: 1.5rem; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ff3b3b; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #e03030; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>🍕 ドライバーログイン</h1>
        <?php if (isset($login_error)) echo "<p style='color:red;'>$login_error</p>"; ?>
        <form method="post">
            <input type="text" name="username" placeholder="ユーザーID" required>
            <input type="password" name="password" placeholder="パスワード" required>
            <button type="submit" name="login">ログイン</button>
        </form>
    </div>
</body>
</html>
<?php exit; endif; ?>

<?php
// =============================
// ダッシュボード ロジック
// =============================
$driver_id = $_SESSION['delivery_id'];
$driver_name = $_SESSION['delivery_name'];
$success_msg = "";

// 1. 配達引き受け処理
if (isset($_POST['pickup_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $db->prepare("UPDATE orders SET status = 'Out for Delivery', delivery_person_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'Ready'");
    $stmt->execute([$driver_id, $order_id]);
}

// 2. 配達完了処理
if (isset($_POST['delivered_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $db->prepare("UPDATE orders SET status = 'Delivered', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND delivery_person_id = ?");
    $stmt->execute([$order_id, $driver_id]);
    $success_msg = "お疲れ様でした、{$driver_name}さん！代金の受け取りを確認しました。";
}

// =============================
// データ取得関数
// =============================

// 商品詳細を menu_items テーブルから取得
function getOrderItems($db, $order_id) {
    $sql = "SELECT m.name as item_name, oi.quantity 
            FROM order_items oi 
            JOIN menu_items m ON oi.menu_item_id = m.id 
            WHERE oi.order_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$order_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 注文一覧の取得
$sql = "SELECT o.*, c.first_name, c.last_name, c.phone, c.address 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        WHERE (o.status IN ('Ready', 'Out for Delivery') OR (o.status = 'Delivered' AND o.delivery_person_id = ?))
        ORDER BY o.order_time DESC";
$stmt = $db->prepare($sql);
$stmt->execute([$driver_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ドライバーダッシュボード</title>
    <style>
        :root { --red: #ff3b3b; --bg: #f8f9fa; --blue: #3b82f6; --green: #10b981; }
        body { font-family: "Hiragino Kaku Gothic ProN", "Meiryo", sans-serif; background: var(--bg); margin: 0; color: #333; }
        header { background: var(--red); color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .main-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; padding: 20px; height: calc(100vh - 80px); box-sizing: border-box; }
        .column { background: #ebedef; border-radius: 12px; display: flex; flex-direction: column; padding: 15px; overflow: hidden; }
        .column h2 { font-size: 1.1rem; margin: 0 0 15px 0; display: flex; justify-content: space-between; align-items: center; }
        .scroll-box { flex: 1; overflow-y: auto; }
        
        .card { background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #ccc; }
        .card-ready { border-left-color: var(--blue); }
        .card-delivering { border-left-color: #f59e0b; }
        .card-history { border-left-color: var(--green); }

        .card h3 { margin: 0 0 8px 0; font-size: 1rem; color: #555; }
        .info { font-size: 0.9rem; margin-bottom: 4px; }
        .items { background: #fffcf0; border: 1px dashed #ffd43b; padding: 8px; border-radius: 4px; font-size: 0.85rem; margin: 10px 0; }
        .order-total {
    font-size: 1.1rem;
    font-weight: bold;
    color: var(--red);
    text-align: right;
    margin-top: 5px;
    border-top: 1px solid #eee;
    padding-top: 5px;
}
        .btn { border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.2s; }
        .btn-pickup { background: var(--blue); color: white; }
        .btn-finish { background: var(--green); color: white; }
        
        .badge { background: white; color: var(--red); padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; }
        
        summary { cursor: pointer; font-weight: bold; outline: none; }
        details[open] summary { margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }

        @media (max-width: 900px) { .main-grid { grid-template-columns: 1fr; overflow-y: auto; height: auto; } }
    </style>
</head>
<body>

<header>
    <div><strong>🍕 To Pizza Mach</strong> | ドライバー: <?= htmlspecialchars($driver_name) ?></div>
    <div>
        <span style="margin-right:15px;">🔔 新着: <span id="notif-count">0</span></span>
        <a href="?logout=1" style="color:white; text-decoration:none; font-size:0.9rem;">ログアウト</a>
    </div>
</header>

<?php if ($success_msg): ?>
    <script>alert("<?= $success_msg ?>");</script>
<?php endif; ?>

<div class="main-grid">
    <section class="column">
        <h2>新着注文 <span class="badge" style="background:var(--blue); color:white;">準備完了</span></h2>
        <div class="scroll-box">
            <?php foreach ($orders as $o): if ($o['status'] === 'Ready'): ?>
                <div class="card card-ready">
                    <h3>注文 #<?= $o['id'] ?></h3>
                    <div class="info">👤 <strong><?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?> 様</strong></div>
                    <div class="info">📍 <?= htmlspecialchars($o['address']) ?></div>
                    <div class="items">
                        <?php foreach (getOrderItems($db, $o['id']) as $item): ?>
                            • <?= htmlspecialchars($item['item_name']) ?> (x<?= $item['quantity'] ?>)<br>
                        <?php endforeach; ?>
                        <div class="order-total">合計: ¥<?= number_format($o['total_price']) ?></div>
                    </div>
                    <form method="post">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" name="pickup_order" class="btn btn-pickup">📦 配達を開始する</button>
                    </form>
                </div>
            <?php endif; endforeach; ?>
        </div>
    </section>

    <section class="column">
        <h2>配達中 <span class="badge" style="background:#f59e0b; color:white;">担当分</span></h2>
        <div class="scroll-box">
            <?php foreach ($orders as $o): if ($o['status'] === 'Out for Delivery' && $o['delivery_person_id'] == $driver_id): ?>
                <div class="card card-delivering">
                    <h3>注文 #<?= $o['id'] ?></h3>
                    <div class="info">📞 <a href="tel:<?= $o['phone'] ?>"><?= htmlspecialchars($o['phone']) ?></a></div>
                    <div class="info">📍 <?= htmlspecialchars($o['address']) ?></div>
                    <div class="items">
                        <?php foreach (getOrderItems($db, $o['id']) as $item): ?>
                            • <?= htmlspecialchars($item['item_name']) ?> (x<?= $item['quantity'] ?>)<br>
                        <?php endforeach; ?>
                    </div>
                    <div class="order-total">合計: ¥<?= number_format($o['total_price']) ?></div>
                    <form method="post" onsubmit="return confirm('配達完了を確認しましたか？集金を忘れないでください。');">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <button type="submit" name="delivered_order" class="btn btn-finish">✅ 配達完了・集金確認</button>
                    </form>
                </div>
            <?php endif; endforeach; ?>
        </div>
    </section>

    <section class="column">
        <h2>配達履歴 <span class="badge" style="background:var(--green); color:white;">完了</span></h2>
        <div class="scroll-box">
            <?php foreach ($orders as $o): if ($o['status'] === 'Delivered'): ?>
                <div class="card card-history">
                    <details>
                        <summary>注文 #<?= $o['id'] ?> - <?= date('H:i', strtotime($o['updated_at'])) ?> 完了</summary>
                        <div style="font-size: 0.85rem; padding-top:10px;">
                            <p><strong>顧客:</strong> <?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?> 様</p>
                            <p><strong>住所:</strong> <?= htmlspecialchars($o['address']) ?></p>
                            <div class="items" style="background:#f0f0f0; border:none;">
                                <?php foreach (getOrderItems($db, $o['id']) as $item): ?>
                                    • <?= htmlspecialchars($item['item_name']) ?> (x<?= $item['quantity'] ?>)<br>
                                <?php endforeach; ?>
                                <div class="order-total">受取金額: ¥<?= number_format($o['total_price']) ?></div>
                            </div>
                        </div>
                    </details>
                </div>
            <?php endif; endforeach; ?>
        </div>
    </section>
</div>

<script>
// Automatically send GPS location to server if driver is logged in
if (navigator.geolocation) {
    setInterval(() => {
        navigator.geolocation.getCurrentPosition(position => {
            fetch(`driver.php?action=update_location&lat=${position.coords.latitude}&lng=${position.coords.longitude}`);
        });
    }, 10000); // Update every 10 seconds
}

// 通知バッジの更新
function updateNotif() {
    fetch('driver.php?action=notification')
        .then(res => res.text())
        .then(count => { document.getElementById('notif-count').innerText = count; });
}
setInterval(updateNotif, 10000);
updateNotif();

// 自動ログアウト (10分)
let timer;
function resetTimer() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        alert("セッションが終了しました。");
        window.location.href = 'driver.php?logout=1';
    }, 600000);
}
document.onmousemove = resetTimer;
document.onclick = resetTimer;
</script>
</body>
</html>