<?php
session_start();
require __DIR__ . '/./config/db.php';

$user = $_SESSION['order']['user'] ?? null;
$address = $_SESSION['order']['address'] ?? null;

if (!$user || !$address) {
    header('Location: index.php');
    exit;
}

// Load menu data for displaying item images
$menuData = [];
$menuRes = $mysqli->query("SELECT id, name, photo_path, description FROM menu WHERE active = 1 AND deleted = 0");
if ($menuRes) {
    while ($menuRow = $menuRes->fetch_assoc()) {
        $menuData[(int)$menuRow['id']] = [
            'name' => $menuRow['name'],
            'image' => $menuRow['photo_path'],
            'description' => $menuRow['description'] ?? ''
        ];
    }
    $menuRes->free();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match | ご注文内容の確認</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/pages/confirm.css">
</head>
<body>

<header class="header">
    <div class="header-content">
         <div class="logo"><img src="./assets/image/logo.png" alt="Pizza Mach logo featuring stylized pizza slice with restaurant name" ></div>
       
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<!-- Progress Bar -->
<div class="checkout-progress">
    <div class="progress-steps-text">
        <span class="progress-step">カート確認</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">お客様情報</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">配送先住所</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step active">注文確認</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">完了</span>
    </div>
    <div class="progress-bar-container">
        <div class="progress-bar-fill" style="width: 80%;"></div>
    </div>
</div>

<div class="confirm-page-wrapper">
    <div class="confirm-container">
        <h2>ご注文内容の確認</h2>
        <p>ご注文内容をご確認の上、「注文を確定する」ボタンを押してください。</p>
        
        <div class="confirm-layout-grid">
            <div class="confirm-main-content">
                <div class="section-box">
                    <h3>ご注文商品（編集可）</h3>
                    <div id="items"></div>
                    <p class="muted">※ 数量変更・削除はこの画面でできます。</p>
                </div>
                
                <div class="section-box">
                    <h3>お客様情報</h3>
                    <p>
                        お名前：<a href="user_info.php" class="editable-field"><?= htmlspecialchars($user['name'] ?? '') ?></a>
                    </p>
                    <p>メール：<?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <p>
                        電話：<a href="user_info.php" class="editable-field"><?= htmlspecialchars($user['phone'] ?? '') ?></a>
                    </p>
                </div>
                
                <div class="section-box">
                    <h3>配送先住所</h3>
                    <p>
                        〒<?= htmlspecialchars($address['zip'] ?? '') ?><br>
                        <?= htmlspecialchars($address['pref'] ?? '') ?>
                        <?= htmlspecialchars($address['city'] ?? '') ?>
                        <?= htmlspecialchars($address['street'] ?? '') ?><br>
                        <?= htmlspecialchars($address['comment'] ?? '') ?>
                    </p>
                    <p><a href="address.php">住所を変更</a></p>
                </div>
                
                <div class="section-box">
                    <h3>配達時間</h3>
                    <p id="delivery-time-display">
                        <?php
                        $deliveryTime = $_SESSION['delivery_time'] ?? 'ASAP';
                        if ($deliveryTime === 'ASAP') {
                            echo 'できるだけ早く（最短30分後）';
                        } else if (preg_match('/^(today|tomorrow|day_after)_(\d{2}):(\d{2})$/', $deliveryTime, $matches)) {
                            // Parse scheduled delivery time: "today_14:30", "tomorrow_18:00", etc.
                            $dateKey = $matches[1];
                            $timeStr = $matches[2] . ':' . $matches[3];
                            
                            $dateLabels = [
                                'today' => '今日',
                                'tomorrow' => '明日',
                                'day_after' => '明後日'
                            ];
                            
                            $dateLabel = $dateLabels[$dateKey] ?? '';
                            echo '指定時間: ' . htmlspecialchars($dateLabel . ' ' . $timeStr);
                        } else {
                            echo '指定時間: ' . htmlspecialchars($deliveryTime);
                        }
                        ?>
                    </p>
                    <p><a href="cart.php">配達時間を変更</a></p>
                </div>
            </div>

            <div class="confirm-sidebar">
                <div class="sidebar-card payment-summary-box" id="payment-summary">
                    <h3>お支払い金額</h3>
                    <div class="payment-table">
                        <div class="payment-row">
                            <span class="payment-label">商品合計(<span id="item-count-display">0</span>点)</span>
                            <span class="payment-value" id="subtotal-display">¥0</span>
                        </div>
                        <div class="payment-row">
                            <span class="payment-label">配達料</span>
                            <span class="payment-value">無料</span>
                        </div>
                        <div class="payment-row payment-total">
                            <span class="payment-label">合計</span>
                            <span class="payment-value" id="total-display">¥0</span>
                        </div>
                    </div>
                    
                    <form method="post" action="order_create.php" id="orderForm">
                        <input type="hidden" name="cart_json" id="cartJson">
                        <button type="submit" id="submitBtn" class="btn-proceed">注文を確定する</button>
                    </form>
                    <div class="sidebar-card">
                    <a href="cart.php" class="btn-back-cart">← カートへ戻る</a>
                </div>
                </div>
                
                
            </div>
        </div>
    </div>
</div>

<script>
// Menu data from PHP
const menuData = <?= json_encode($menuData) ?>;

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
    let total = 0;
    let itemCount = 0;
    const keys = Object.keys(cart);

    if (keys.length === 0) {
        itemsEl.innerHTML = '<p class="empty">カートに商品がありません。</p>';
        submitBtn.disabled = true;
        cartJsonEl.value = '{}';
        updatePaymentSummary(0, 0);
        return;
    }

    submitBtn.disabled = false;

    keys.forEach(key => {
        const item = cart[key];

        // safety: ensure required fields exist
        if (item.qty === undefined) item.qty = 0;
        if (item.price === undefined) item.price = 0;

        // Get menu info for image (same approach as cart.php)
        const menuId = item.menu_id || parseInt(key.split('_')[0]);
        const menuInfo = menuData[menuId] || { name: item.name, image: '', description: '' };

        const div = document.createElement('div');
        div.className = 'confirm-item';

        const img = document.createElement('img');
        img.className = 'confirm-item-img';
        img.src = menuInfo.image || item.image || './assets/img/noimage.png';
        img.alt = menuInfo.name || item.name || '';

        const info = document.createElement('div');
        info.className = 'confirm-item-info';
        info.innerHTML = `
            <div class="name">${item.name || ''}</div>
            <div class="price">¥${Number(item.price).toLocaleString()}</div>
        `;

        const actions = document.createElement('div');
        actions.className = 'confirm-item-actions';

        const minus = document.createElement('button');
        minus.type = 'button';
        minus.textContent = '−';
        minus.onclick = () => changeQty(key, -1);

        const qty = document.createElement('span');
        qty.textContent = item.qty;

        const plus = document.createElement('button');
        plus.type = 'button';
        plus.textContent = '＋';
        plus.onclick = () => changeQty(key, 1);

        const del = document.createElement('button');
        del.type = 'button';
        del.textContent = '削除';
        del.onclick = () => removeItem(key);

        actions.append(minus, qty, plus, del);
        div.append(img, info, actions);
        itemsEl.appendChild(div);

        total += item.price * item.qty;
        itemCount += item.qty;
    });

    cartJsonEl.value = JSON.stringify(cart);
    
    // Update payment summary
    updatePaymentSummary(total, itemCount);
}

function updatePaymentSummary(subtotal, itemCount) {
    // Налог не включен, total = subtotal
    const total = subtotal;
    
    document.getElementById('item-count-display').textContent = itemCount;
    document.getElementById('subtotal-display').textContent = `¥${subtotal.toLocaleString()}`;
    document.getElementById('total-display').textContent = `¥${total.toLocaleString()}`;
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
