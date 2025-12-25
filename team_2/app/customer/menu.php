<?php
$host = "127.0.0.1";
$dbname = "pizza_db";
$user = "root";
$pass = "";

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT * FROM menu WHERE available = 1");
    $stmt->execute();
    $pizzas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi DB: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>🍕 Pizza Menu</title>
<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
.menu { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:20px; }
.pizza { background:#fff; border-radius:8px; box-shadow:0 2px 6px #ccc; overflow:hidden; }
.pizza img { width:100%; height:180px; object-fit:cover; }
.pizza div { padding:15px; }
.price { color:red; font-weight:bold; }
.cart-btn {
    background:#ff9800;
    border:none;
    padding:8px 12px;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}

.cart-btn:hover {
    background:#e68900;
}

.buy-btn {
    margin-left:8px;
    padding:8px 12px;
    background:#e53935;
    color:white;
    text-decoration:none;
    border-radius:6px;
    font-weight:bold;
}

.buy-btn:hover {
    background:#c62828;
}

</style>
</head>
<body>

<h1>🍕 ピザメニュー</h1>

<div class="menu">
<?php foreach ($pizzas as $p): ?>
    <div class="pizza">
        <img src="<?= htmlspecialchars($p['image_url']) ?>">
        <div>
            <h3><?= htmlspecialchars($p['pizza_name']) ?></h3>
            <p><?= htmlspecialchars($p['description']) ?></p>
            <p>Size: <?= htmlspecialchars($p['size']) ?></p>
            <p class="price">¥<?= number_format($p['price']) ?></p>
                <!-- FORM -->
        <form method="post" style="margin-top:10px;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <input type="hidden" name="name" value="<?= $p['pizza_name'] ?>">
            <input type="hidden" name="price" value="<?= $p['price'] ?>">

            <!-- Xe đẩy -->
            <button type="submit" name="add_to_cart" class="cart-btn">
                🛒 カート
            </button>

            <!-- Mua ngay -->
            <a href="checkout.php?id=<?= $p['id'] ?>" class="buy-btn">
                💴 購入
            </a>
        </form>
 
        </div>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
