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
        data-name="<?= htmlspecialchars($pizza['name']) ?>"
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

<!--lower panel/ Нижняя панель корзины -->
<div class="cart-bar">
    <div class="total">
        合計金額：<span id="totalPrice">¥0</span>
    </div>
    <a href="./cart.php" class="go-cart">
        カートに進む
    </a>
</div>

<script>
const CART_KEY = 'cart';
let cart = {};

/* ---------- cart load/загрузка корзины ---------- */
const savedCart = localStorage.getItem(CART_KEY);
if (savedCart) {
    cart = JSON.parse(savedCart);
}

/* ---------- helpers ---------- */
function saveCart() {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function calcTotal() {
    let sum = 0;
    for (const id in cart) {
        sum += cart[id].price * cart[id].qty;
    }
    return sum;
}

function updateTotal() {
    document.getElementById('totalPrice').textContent =
        '¥' + calcTotal().toLocaleString();
}

/* ---------- synchron UI ---------- */
function syncUI() {
    document.querySelectorAll('.pizza-card').forEach(card => {
        const id = card.dataset.id;
        const countEl = card.querySelector('.count');
        countEl.textContent = cart[id]?.qty ?? 0;
    });
    updateTotal();
}

/* ---------- обработчики ---------- */
document.querySelectorAll('.pizza-card').forEach(card => {
    const id = card.dataset.id;
    const name = card.dataset.name;
    const price = parseInt(card.dataset.price, 10);

    const countEl = card.querySelector('.count');

    card.querySelector('.plus').addEventListener('click', () => {
        if (!cart[id]) {
            cart[id] = { id, name, price, qty: 0 };
        }
        cart[id].qty++;
        countEl.textContent = cart[id].qty;
        saveCart();
        updateTotal();
    });

    card.querySelector('.minus').addEventListener('click', () => {
        if (!cart[id]) return;

        cart[id].qty--;
        if (cart[id].qty <= 0) {
            delete cart[id];
            countEl.textContent = 0;
        } else {
            countEl.textContent = cart[id].qty;
        }
        saveCart();
        updateTotal();
    });
});

/* ---------- старт ---------- */
syncUI();
/* ---------- переход в корзину ---------- */
document.querySelector('.go-cart').addEventListener('click', (e) => {
    if (Object.keys(cart).length === 0) {
        e.preventDefault();
        alert('カートは空です 🍃');
        return;
    }

    // подготовка данных для cart.php
    const cartArray = Object.values(cart);
    localStorage.setItem('pizza_cart', JSON.stringify(cartArray));
});

</script>

</body>
</html>
