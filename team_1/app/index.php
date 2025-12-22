<?php
$menu = require __DIR__ . '/../data/menu_stub.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
</head>
<body>

<header class="header">
    <div class="logo">PM</div>
</header>

<main class="menu">
    <?php foreach ($menu as $pizza): ?>
        <div
            class="pizza-card"
            data-id="<?= $pizza['id'] ?>"
            data-price="<?= $pizza['price'] ?>"
        >
            <img src="<?= $pizza['image'] ?>" alt="<?= htmlspecialchars($pizza['name']) ?>">

            <h3><?= htmlspecialchars($pizza['name']) ?></h3>
            <p><?= htmlspecialchars($pizza['desc']) ?></p>

            <div class="card-bottom">
                <span class="price">¥<?= number_format($pizza['price']) ?></span>

                <div class="qty">
                    <button type="button" class="minus">−</button>
                    <span class="count">0</span>
                    <button type="button" class="plus">＋</button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<!-- Нижняя панель корзины -->
<div class="cart-bar">
    <div class="total">
        合計金額：<span id="totalPrice">¥0</span>
    </div>
    <a href="/cart.php" class="go-cart">
        カートに進む
    </a>
</div>

<script>
let total = 0;

document.querySelectorAll('.pizza-card').forEach(card => {
    const price = parseInt(card.dataset.price, 10);
    const countEl = card.querySelector('.count');

    card.querySelector('.plus').addEventListener('click', () => {
        let count = parseInt(countEl.textContent, 10);
        count++;
        countEl.textContent = count;
        total += price;
        updateTotal();
    });

    card.querySelector('.minus').addEventListener('click', () => {
        let count = parseInt(countEl.textContent, 10);
        if (count > 0) {
            count--;
            countEl.textContent = count;
            total -= price;
            updateTotal();
        }
    });
});

function updateTotal() {
    document.getElementById('totalPrice').textContent =
        '¥' + total.toLocaleString();
}
</script>

</body>
</html>
