<?php
session_start();

$user = $_SESSION['order']['user'] ?? null;
$address = $_SESSION['order']['address'] ?? null;

if (!$user || !$address) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match | ご注文内容の確認</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
    <style>
        .item { margin: 10px 0; display:flex; gap:10px; align-items:center; }
        .item .name { flex: 1; }
        .empty { color:#999; }
        button[disabled]{ opacity:.5; cursor:not-allowed; }
        .muted { color:#666; font-size: 14px; }
    </style>
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">PM</div>
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<h2>ご注文内容の確認</h2>

<h3>商品（編集可）</h3>
<div id="items"></div>
<p class="muted">※ 数量変更・削除はこの画面でできます。</p>

<hr>

<h3>お客様情報</h3>
<p>お名前：<?= htmlspecialchars($user['name'] ?? '') ?></p>
<p>メール：<?= htmlspecialchars($user['email'] ?? '') ?></p>
<p>電話：<?= htmlspecialchars($user['phone'] ?? '') ?></p>

<hr>

<h3>配送先住所</h3>
<p>
    〒<?= htmlspecialchars($address['zip'] ?? '') ?><br>
    <?= htmlspecialchars($address['pref'] ?? '') ?>
    <?= htmlspecialchars($address['city'] ?? '') ?>
    <?= htmlspecialchars($address['street'] ?? '') ?><br>
    <?= htmlspecialchars($address['comment'] ?? '') ?>
</p>
<p><a href="address.php">住所を変更</a></p>

<hr>

<h3>配達時間</h3>
<p id="delivery-time-display">
    <?php
    $deliveryTime = $_SESSION['delivery_time'] ?? 'ASAP';
    if ($deliveryTime === 'ASAP') {
        echo 'できるだけ早く（最短30分後）';
    } else {
        echo '指定時間: ' . htmlspecialchars($deliveryTime);
    }
    ?>
</p>
<p><a href="cart.php">配達時間を変更</a></p>

<hr>

<form method="post" action="order_create.php" id="orderForm">
    <input type="hidden" name="cart_json" id="cartJson">
    <button type="submit" id="submitBtn">注文を確定する</button>
</form>

<p><a href="cart.php">← カートへ戻る</a> / <a href="index.php">← メニューへ戻る</a></p>

<script>
const CART_KEY = 'cart';
let cart = JSON.parse(localStorage.getItem(CART_KEY) || '{}');

const itemsEl = document.getElementById('items');
const cartJsonEl = document.getElementById('cartJson');
const submitBtn = document.getElementById('submitBtn');
const orderForm = document.getElementById('orderForm');

function saveCart() {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function render() {
    itemsEl.innerHTML = '';
    const keys = Object.keys(cart);

    if (keys.length === 0) {
        itemsEl.innerHTML = '<p class="empty">カートに商品がありません。</p>';
        submitBtn.disabled = true;
        cartJsonEl.value = '{}';
        return;
    }

    submitBtn.disabled = false;

    keys.forEach(key => {
        const item = cart[key];

        // safety: ensure required fields exist
        if (item.qty === undefined) item.qty = 0;
        if (item.price === undefined) item.price = 0;

        const div = document.createElement('div');
        div.className = 'item';
        div.innerHTML = `
            <span class="name">${item.name}</span>
            <span>¥${Number(item.price).toLocaleString()}</span>
            <button type="button" onclick="changeQty('${key}', -1)">−</button>
            <span>${item.qty}</span>
            <button type="button" onclick="changeQty('${key}', 1)">＋</button>
            <button type="button" onclick="removeItem('${key}')">削除</button>
        `;
        itemsEl.appendChild(div);
    });

    cartJsonEl.value = JSON.stringify(cart);
}

function changeQty(key, diff) {
    if (!cart[key]) return;
    cart[key].qty = Number(cart[key].qty) + diff;
    if (cart[key].qty <= 0) delete cart[key];
    saveCart();
    render();
}

function removeItem(key) {
    delete cart[key];
    saveCart();
    render();
}

// ensure hidden input is up-to-date at submit time
orderForm.addEventListener('submit', () => {
    cartJsonEl.value = JSON.stringify(cart);
});

render();
</script>

</body>
</html>
