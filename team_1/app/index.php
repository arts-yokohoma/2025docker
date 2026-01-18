<?php
require __DIR__ . '/../config/db.php';

// Загрузка меню из базы данных
$menu = [];
$query = "SELECT id, name, photo_path, description, price_s, price_m, price_l 
          FROM menu 
          WHERE active = 1 
          ORDER BY id ASC";

$result = $mysqli->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $menuId = (int)$row['id'];
        $name = $row['name'];
        $desc = $row['description'] ?? '';
        $image = $row['photo_path'];
        
        $priceS = (int)$row['price_s'];
        $priceM = (int)$row['price_m'];
        $priceL = (int)$row['price_l'];
        
        // Создаем отдельную карточку для каждого размера, если цена > 0
        // Важно: все цены не обязательны, но должна быть заполнена хотя бы одна
        // Если указана только одна цена (например, только S), выведется только одна карточка
        if ($priceS > 0) {
            $menu[] = [
                'id' => $menuId . '_S',  // Уникальный ID: menu_id + размер
                'menu_id' => $menuId,    // Оригинальный ID меню
                'name' => $name . ' (S)',
                'desc' => $desc,
                'price' => $priceS,
                'image' => $image,
                'size' => 'S',
            ];
        }
        
        if ($priceM > 0) {
            $menu[] = [
                'id' => $menuId . '_M',
                'menu_id' => $menuId,
                'name' => $name . ' (M)',
                'desc' => $desc,
                'price' => $priceM,
                'image' => $image,
                'size' => 'M',
            ];
        }
        
        if ($priceL > 0) {
            $menu[] = [
                'id' => $menuId . '_L',
                'menu_id' => $menuId,
                'name' => $name . ' (L)',
                'desc' => $desc,
                'price' => $priceL,
                'image' => $image,
                'size' => 'L',
            ];
        }
    }
    $result->free();
}

// Если база пуста, можно использовать заглушку (опционально)
// if (empty($menu)) {
//     $menu = require __DIR__ . '/../data/menu_stub.php';
// }
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
    <div class="header-content">
        <div class="logo">PM</div>
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<div class="welcome-section">
    <h1 class="welcome-title">ピーザマッハへ<span class="no-wrap">ようこそ！</span></h1>
</div>

<main class="menu<?= count($menu) <= 3 ? ' menu--few-items' : '' ?>">
<?php foreach ($menu as $pizza): ?>
    <div
        class="pizza-card"
        data-id="<?= $pizza['id'] ?>"
        data-menu-id="<?= $pizza['menu_id'] ?? $pizza['id'] ?>"
        data-name="<?= htmlspecialchars($pizza['name']) ?>"
        data-price="<?= $pizza['price'] ?>"
        data-size="<?= $pizza['size'] ?? 'M' ?>"
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
    <div class="cart-bar-content">
        <div class="total">
            <span class="total-label">合計金額：</span>
            <span class="total-amount" id="totalPrice">¥0</span>
        </div>
        <a href="./cart.php" class="go-cart">
            カートに進む
        </a>
    </div>
</div>

<script>
const CART_KEY = 'cart';
let cart = {};

/* ---------- cart load/загрузка корзины ---------- */
const savedCart = localStorage.getItem(CART_KEY);
if (savedCart) {
    try {
        const parsed = JSON.parse(savedCart);
        // Проверяем, что корзина не пустая и имеет правильный формат
        if (parsed && typeof parsed === 'object') {
            cart = parsed;
            // Очищаем записи с qty = 0 или отсутствующими данными
            for (const id in cart) {
                if (!cart[id] || !cart[id].qty || cart[id].qty <= 0) {
                    delete cart[id];
                }
            }
            // Если корзина очистилась полностью, обновляем localStorage
            if (Object.keys(cart).length === 0) {
                localStorage.removeItem(CART_KEY);
            } else {
                saveCart(); // Сохраняем очищенную корзину
            }
        }
    } catch (e) {
        // Если ошибка парсинга, очищаем
        localStorage.removeItem(CART_KEY);
        cart = {};
    }
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
    const menuId = parseInt(card.dataset.menuId || card.dataset.id, 10);
    const name = card.dataset.name;
    const price = parseInt(card.dataset.price, 10);
    const size = card.dataset.size || 'M';

    const countEl = card.querySelector('.count');

    card.querySelector('.plus').addEventListener('click', () => {
        if (!cart[id]) {
            // Сохраняем menu_id и size для будущей отправки на сервер
            cart[id] = { 
                id, 
                menu_id: menuId,  // Оригинальный ID меню из БД
                name, 
                price, 
                size,             // Размер S/M/L
                qty: 0 
            };
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
    // корзина уже сохранена через saveCart(), дополнительное сохранение не нужно
});

</script>

</body>
</html>
