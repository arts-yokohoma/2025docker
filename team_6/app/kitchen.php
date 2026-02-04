<?php
// kitchen.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db/connect.php';

// =============================
// LOGIN HANDLING
// =============================
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $db->prepare("SELECT * FROM staff WHERE user_id = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['kitchen_staff_id'] = $user['id'];
        $_SESSION['kitchen_staff_name'] = $user['name'];
        header("Location: kitchen.php");
        exit;
    } else {
        $login_error = "ID またはパスワードが間違っています。";
    }
}

if (isset($_GET['logout'])) {
    session_unset(); session_destroy();
    header("Location: kitchen.php"); exit;
}

// ログインチェック
if (!isset($_SESSION['kitchen_staff_id'])):
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>キッチンログイン - To Pizza Mach</title>
    <style>
        body { background: #222; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #fff; padding: 40px; border-radius: 15px; border-top: 10px solid #ff3b3b; width: 320px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h1 { color: #ff3b3b; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #ff3b3b; color: #fff; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1rem; }
        button:hover { background: #e03030; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>👨‍🍳 Kitchen Login</h1>
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
// KITCHEN LOGIC
// =============================

// 1. 注文完了 (Readyにする)
if (isset($_POST['finish_order'])) {
    $order_id = $_POST['order_id'];
    $staff_id = $_SESSION['kitchen_staff_id'];

    // トランザクション開始
    $db->beginTransaction();
    try {
        // ステータス更新と調理担当者の記録 (調理担当カラムを仮定: cooked_by)
        $stmt = $db->prepare("UPDATE orders SET status = 'Ready', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$order_id]);

        $db->commit();
        $msg = "注文 #$order_id を完了にしました！";
    } catch (Exception $e) {
        $db->rollBack();
        $msg = "エラー: " . $e->getMessage();
    }
}

// 2. 注文削除 (キャンセル)
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $msg = "注文 #$order_id を削除しました。";
}

// 3. データの取得
// 新着数カウント
$new_count = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();

// 現在の注文 (Pending or Cooking)
$stmt = $db->prepare("
    SELECT o.*, c.first_name, c.last_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.status IN ('Pending', 'Cooking') 
    ORDER BY o.order_time ASC
");
$stmt->execute();
$current_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 完了した注文 (直近10件)
$stmt = $db->prepare("
    SELECT o.*, c.first_name, c.last_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.status IN ('Ready', 'Out for Delivery', 'Delivered') 
    ORDER BY o.updated_at DESC LIMIT 10
");
$stmt->execute();
$history_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 商品取得関数
function getItems($db, $id) {
    $stmt = $db->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Kitchen Dashboard - To Pizza Mach</title>
    <style>
        :root { --red: #ff3b3b; --dark: #1a1a1a; --gray: #f4f4f4; --green: #2ecc71; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: var(--gray); margin: 0; display: flex; flex-direction: column; height: 100vh; }
        
        header { background: var(--red); color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .badge { background: white; color: var(--red); padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.9rem; }

        .container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; padding: 20px; flex: 1; overflow: hidden; }
        
        /* 列の設定 */
        .column { background: #ddd; border-radius: 10px; display: flex; flex-direction: column; overflow: hidden; }
        .column h2 { background: var(--dark); color: white; margin: 0; padding: 15px; font-size: 1.2rem; display: flex; justify-content: space-between; }
        .order-list { padding: 15px; overflow-y: auto; flex: 1; }

        /* 注文カード */
        .order-card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 10px solid var(--red); position: relative; }
        .order-card h3 { margin: 0 0 10px 0; color: var(--dark); border-bottom: 2px solid var(--gray); padding-bottom: 5px; }
        
        .item-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-weight: bold; font-size: 1.1rem; }
        .item-row span.qty { background: var(--red); color: white; padding: 2px 8px; border-radius: 5px; }

        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        .btn { flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; color: white; }
        .btn-done { background: var(--green); font-size: 1.1rem; }
        .btn-del { background: #999; font-size: 0.9rem; max-width: 80px; }
        .btn:hover { opacity: 0.8; transform: scale(0.98); }

        /* 履歴用 */
        .history-item { background: #eee; padding: 10px; border-radius: 5px; margin-bottom: 10px; font-size: 0.85rem; border-left: 5px solid #555; }
        
        .footer-info { font-size: 0.8rem; color: #666; margin-top: 5px; }
    </style>
</head>
<body>

<header>
    <div style="font-size: 1.5rem; font-weight: bold;">🍕 TO PIZZA MACH <span style="font-size: 1rem; font-weight: normal; margin-left:10px;">- Kitchen Mode</span></div>
    <div style="display: flex; align-items: center; gap: 20px;">
        <div class="badge">未調理: <?= $new_count ?> 件</div>
        <div style="color: white; font-weight: bold;">Staff: <?= htmlspecialchars($_SESSION['kitchen_staff_name']) ?></div>
        <a href="?logout=1" style="color: white; text-decoration: none; border: 1px solid white; padding: 5px 10px; border-radius: 5px; font-size: 0.8rem;">ログアウト</a>
    </div>
</header>

<div class="container">
    <section class="column">
        <h2>🔥 進行中の注文 (Current Orders) <span>FIFO方式</span></h2>
        <div class="order-list">
            <?php if (empty($current_orders)): ?>
                <div style="text-align:center; color:#888; margin-top:50px;">
                    <h1 style="font-size: 4rem;">💤</h1>
                    <p>現在、調理待ちの注文はありません。</p>
                </div>
            <?php else: ?>
                <?php foreach ($current_orders as $o): ?>
                    <div class="order-card">
                        <h3>注文 #<?= $o['id'] ?> <small style="float:right; color:#888;"><?= date('H:i', strtotime($o['order_time'])) ?></small></h3>
                        <div style="margin-bottom: 15px;">👤 <?= htmlspecialchars($o['first_name'].' '.$o['last_name']) ?> 様</div>
                        
                        <div class="items-container">
                            <?php foreach (getItems($db, $o['id']) as $item): ?>
                                <div class="item-row">
                                    <span>● <?= htmlspecialchars($item['name']) ?> (<?= $item['pizza_size'] ?>)</span>
                                    <span class="qty">x <?= $item['quantity'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="btn-group">
                            <form method="post" style="flex:1;">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button type="submit" name="finish_order" class="btn btn-done">✅ 調理完了 (Ready)</button>
                            </form>
                            <form method="post" onsubmit="return confirm('本当に削除しますか？');">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <button type="submit" name="delete_order" class="btn btn-del">削除</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="column" style="background: #ccc;">
        <h2>📋 完了済み (Recent History)</h2>
        <div class="order-list">
            <?php foreach ($history_orders as $h): ?>
                <div class="history-item">
                    <strong>#<?= $h['id'] ?></strong> - <?= htmlspecialchars($h['first_name']) ?> 様
                    <div class="footer-info">
                        完了: <?= date('H:i', strtotime($h['updated_at'])) ?><br>
                        ステータス: <span style="color:blue;"><?= $h['status'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
    // 30秒ごとにページをリロードして新着を確認
    setTimeout(function(){
       location.reload();
    }, 30000);
</script>

</body>
</html>