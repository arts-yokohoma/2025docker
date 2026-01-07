<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match | カート</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

<header class="header">
    <h1>🛒 カート</h1>
    <a href="index.php" class="btn-link">← メニューへ戻る</a>
</header>

<main class="container">
    <div id="cart-empty" class="hidden">
        <p>カートは空です 🍃</p>
    </div>

    <table id="cart-table" class="cart-table hidden">
        <thead>
            <tr>
                <th>商品</th>
                <th>価格</th>
                <th>数量</th>
                <th>小計</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="cart-items"></tbody>
    </table>

    <div id="cart-summary" class="cart-summary hidden">
        <p>合計: <span id="cart-total">0</span> 円</p>
        <a href="user_info.php">注文へ進む</a>
    </div>
</main>

<script src="./assets/js/cart.js"></script>
</body>
</html>
