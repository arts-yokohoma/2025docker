<?php
require __DIR__ . '/../config/db.php';

/**
 * Shopping cart page with delivery time selection
 * 
 * Business logic:
 * - Calculates available delivery time slots based on store hours
 * - Supports ASAP and scheduled delivery options
 * - Time slots are generated in 15-minute intervals
 * - Last order time = closing time - last_order_offset_min
 */

// Load store hours from database (with defaults)
$storeHours = [
    'open_time' => '11:00',
    'close_time' => '22:00',
    'last_order_offset_min' => 30,
];

$res = $mysqli->query("SELECT open_time, close_time, last_order_offset_min 
                       FROM store_hours WHERE id=1 AND active=1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $storeHours['open_time'] = substr((string)$row['open_time'], 0, 5);
    $storeHours['close_time'] = substr((string)$row['close_time'], 0, 5);
    $storeHours['last_order_offset_min'] = (int)$row['last_order_offset_min'];
    $res->free();
}

// Use Japan timezone for all time calculations
date_default_timezone_set('Asia/Tokyo');
$now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
$currentTime = $now->format('H:i');
$currentMinutes = (int)$now->format('H') * 60 + (int)$now->format('i');

// Parse store hours into minutes for comparison
[$openH, $openM] = explode(':', $storeHours['open_time']);
[$closeH, $closeM] = explode(':', $storeHours['close_time']);
$openMinutes = (int)$openH * 60 + (int)$openM;
$closeMinutes = (int)$closeH * 60 + (int)$closeM;

// Check if store is currently open
$isStoreOpen = ($currentMinutes >= $openMinutes && $currentMinutes < $closeMinutes);

// Calculate minimum delivery time (current time + 30 min preparation)
$minDeliveryTime = clone $now;
$minDeliveryTime->modify('+30 minutes');

// If store is closed, set minimum time to next opening + 30 minutes
if (!$isStoreOpen) {
    $todayOpen = clone $now;
    $todayOpen->setTime((int)$openH, (int)$openM, 0);
    if ($todayOpen < $now) {
        $todayOpen->modify('+1 day');
    }
    $minDeliveryTime = clone $todayOpen;
    $minDeliveryTime->modify('+30 minutes');
}

// Calculate last order time for today
$todayClose = clone $now;
$todayClose->setTime((int)$closeH, (int)$closeM, 0);
$lastOrderTime = clone $todayClose;
$lastOrderTime->modify('-' . $storeHours['last_order_offset_min'] . ' minutes');

// Generate available time slots for today, tomorrow, and day after tomorrow
$availableTimesByDate = [];
$dates = ['today' => clone $now, 'tomorrow' => clone $now, 'day_after' => clone $now];
$dates['tomorrow']->modify('+1 day');
$dates['day_after']->modify('+2 days');

// Minimum preparation and delivery time
$deliveryTimeMinutes = 30;

foreach ($dates as $key => $date) {
    $dayStart = clone $date;
    $dayStart->setTime((int)$openH, (int)$openM, 0);
    $dayEnd = clone $date;
    $dayEnd->setTime((int)$closeH, (int)$closeM, 0);
    
    // Last delivery slot = closing time - last_order_offset_min
    $lastDeliveryTime = clone $dayEnd;
    $lastDeliveryTime->modify('-' . $storeHours['last_order_offset_min'] . ' minutes');
    
    // For today: use calculated minimum time; for future days: use opening + prep time
    if ($key === 'today') {
        $dayMinTime = clone $minDeliveryTime;
        // If minimum time is already tomorrow, skip today
        if ($dayMinTime->format('Y-m-d') !== $date->format('Y-m-d')) {
            continue;
        }
    } else {
        $dayMinTime = clone $dayStart;
        $dayMinTime->modify('+' . $deliveryTimeMinutes . ' minutes');
    }
    
    $times = [];
    
    // Skip if minimum time exceeds last delivery slot
    if ($dayMinTime > $lastDeliveryTime) {
        continue;
    }
    
    $current = clone $dayMinTime;
    $interval = new DateInterval('PT15M');
    
    // Round up to nearest 15-minute interval
    $currentMinutes = (int)$current->format('i');
    $remainder = $currentMinutes % 15;
    if ($remainder > 0) {
        $roundUp = 15 - $remainder;
        $current->modify('+' . $roundUp . ' minutes');
    }
    
    // Generate time slots in 15-minute intervals
    if ($current <= $lastDeliveryTime) {
        while ($current <= $lastDeliveryTime) {
            $times[] = $current->format('H:i');
            $current->add($interval);
            
            // Safety check to prevent infinite loop
            if (count($times) > 100) break;
        }
    } else if ($dayMinTime <= $lastDeliveryTime) {
        // If rounding pushed us over, but original time was valid, add last slot
        $times[] = $lastDeliveryTime->format('H:i');
    }
    
    if (!empty($times)) {
        $availableTimesByDate[$key] = $times;
    }
}

// For backward compatibility: today's slots
$availableTimes = $availableTimesByDate['today'] ?? [];

// Load menu data for displaying item images and descriptions
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
    <title>Pizza Match | カート</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/pages/cart.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-content">
        <div class="logo">PM</div>
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<!-- Progress Bar -->
<div class="checkout-progress">
    <div class="progress-steps-text">
        <span class="progress-step active">カート確認</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">お客様情報</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">配送先住所</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">注文確認</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">完了</span>
    </div>
    <div class="progress-bar-container">
        <div class="progress-bar-fill" style="width: 25%;"></div>
    </div>
</div>

<div class="cart-page-wrapper">
    <div class="cart-layout">
        <!-- Main Content: Cart Items -->
        <div class="cart-main">
            <h1>カート内容の確認と配達時間の選択</h1>
            <div id="cart-items-container"></div>
        </div>
        
        <!-- Sidebar: Delivery Time & Summary -->
        <div class="cart-sidebar">
            <div class="sidebar-card">
                <h2>お届け希望時間</h2>
                <div class="delivery-option selected" id="option-asap">
                    <input type="radio" name="delivery_time" id="delivery_asap" value="ASAP" checked>
                    <label for="delivery_asap">最短でお届け</label>
                    <div class="delivery-time-estimate">約30分～45分</div>
                </div>
                
                <div class="delivery-option" id="option-scheduled">
                    <input type="radio" name="delivery_time" id="delivery_scheduled" value="SCHEDULED">
                    <label for="delivery_scheduled">配達時間を指定する</label>
                    <div class="time-select-wrapper" id="scheduled-time-wrapper" style="display: none;">
                        <select id="delivery-date" style="margin-bottom: 12px;">
                            <?php
                            $dateLabels = [
                                'today' => '今日',
                                'tomorrow' => '明日',
                                'day_after' => '明後日'
                            ];
                            foreach ($dateLabels as $key => $label):
                                if (isset($availableTimesByDate[$key])):
                            ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                        <select id="scheduled-time"></select>
                    </div>
                </div>
            </div>
            
            <div class="sidebar-card">
                <h2>ご注文内容</h2>
                <div class="order-summary">
                    <div class="summary-row">
                        <span>商品合計(<span id="item-count">0</span>点)</span>
                        <span>¥<span id="subtotal">0</span></span>
                    </div>
                    <div class="summary-row">
                        <span>配送料</span>
                        <span>無料</span>
                    </div>
                    <div class="summary-total">
                        合計 ¥<span id="cart-total">0</span>
                    </div>
                </div>
                <a href="user_info.php" id="go-to-order" class="btn-proceed">お客様情報の入力へ進む →</a>
                <p style="font-size: 12px; color: #999; margin-top: 12px; text-align: center;">
                    ※注文を確定するまで、料金は発生しません。ゲスト購入として手続きを継続します。
                </p>
            </div>
        </div>
    </div>
</div>

<script>
// Данные меню из PHP
const menuData = <?= json_encode($menuData) ?>;

const CART_KEY = 'cart';
const storeHours = {
    openTime: '<?= $storeHours['open_time'] ?>',
    closeTime: '<?= $storeHours['close_time'] ?>',
    availableTimesByDate: <?= json_encode($availableTimesByDate) ?>
};

function getCart() {
    const cartJson = localStorage.getItem(CART_KEY);
    return cartJson ? JSON.parse(cartJson) : {};
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function renderCart() {
    const cart = getCart();
    const container = document.getElementById('cart-items-container');
    const cartKeys = Object.keys(cart);
    
    if (cartKeys.length === 0) {
        container.innerHTML = `
            <div class="empty-cart">
                <p>カートは空です 🍃</p>
                <a href="index.php" class="menu-link">← メニューに戻る</a>
            </div>
        `;
        updateSummary({}, 0, 0, 0);
        return;
    }
    
    let html = '';
    let subtotal = 0;
    let itemCount = 0;
    
    cartKeys.forEach(id => {
        const item = cart[id];
        if (!item || !item.qty || !item.price) return;
        
        const menuId = item.menu_id || parseInt(id.split('_')[0]);
        const menuInfo = menuData[menuId] || { name: item.name, image: '', description: '' };
        const itemSubtotal = item.price * item.qty;
        subtotal += itemSubtotal;
        itemCount += item.qty;
        
        html += `
            <div class="cart-item" data-id="${id}">
                <img src="${menuInfo.image || '/assets/image/menu/photopizza.jpg'}" 
                     alt="${menuInfo.name}" class="cart-item-image">
                <div class="cart-item-info">
                    <div class="cart-item-name">${menuInfo.name || item.name}</div>
                    <div class="cart-item-desc">${menuInfo.description || ''}</div>
                    <div class="cart-item-size">サイズ: ${item.size || 'M'}</div>
                    <div class="cart-item-actions">
                        <div class="cart-item-qty">
                            <button type="button" onclick="updateQty('${id}', -1)">−</button>
                            <span>${item.qty}</span>
                            <button type="button" onclick="updateQty('${id}', 1)">＋</button>
                        </div>
                        <div class="cart-item-price">¥${itemSubtotal.toLocaleString()}</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Добавляем кнопки действий
    html += `
        <div class="cart-actions-bottom">
            <a href="index.php" class="btn-back-menu">← メニューに戻る</a>
            <button type="button" class="btn-clear-cart" onclick="clearCart()">カートを空にする</button>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Налог уже включен в цену, поэтому total = subtotal
    const total = subtotal;
    updateSummary(cart, itemCount, subtotal, total);
}

function updateSummary(cart, itemCount, subtotal, total) {
    document.getElementById('item-count').textContent = itemCount;
    document.getElementById('subtotal').textContent = subtotal.toLocaleString();
    document.getElementById('cart-total').textContent = total.toLocaleString();
}

// Обновление временных слотов при выборе даты
function updateTimeSlots(dateKey) {
    const times = storeHours.availableTimesByDate[dateKey] || [];
    const select = document.getElementById('scheduled-time');
    select.innerHTML = '';
    
    const dateLabels = {
        'today': '今日',
        'tomorrow': '明日',
        'day_after': '明後日'
    };
    const dateLabel = dateLabels[dateKey] || '';
    
    times.forEach(time => {
        const option = document.createElement('option');
        const endTime = new Date('2000-01-01T' + time + ':00');
        endTime.setMinutes(endTime.getMinutes() + 15);
        const endTimeStr = String(endTime.getHours()).padStart(2, '0') + ':' + 
                          String(endTime.getMinutes()).padStart(2, '0');
        option.value = time;
        option.textContent = `${dateLabel} ${time} - ${endTimeStr}`;
        select.appendChild(option);
    });
}

function updateQty(id, diff) {
    const cart = getCart();
    if (!cart[id]) return;
    
    cart[id].qty = parseInt(cart[id].qty) + diff;
    if (cart[id].qty <= 0) {
        delete cart[id];
    } else {
        cart[id].qty = Math.max(1, cart[id].qty);
    }
    saveCart(cart);
    renderCart();
}

function clearCart() {
    if (confirm('カートを空にしますか？')) {
        localStorage.removeItem(CART_KEY);
        renderCart();
    }
}

// Обработка выбора времени доставки
document.querySelectorAll('input[name="delivery_time"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const optionAsap = document.getElementById('option-asap');
        const optionScheduled = document.getElementById('option-scheduled');
        const timeWrapper = document.getElementById('scheduled-time-wrapper');
        
        if (this.value === 'SCHEDULED') {
            optionAsap.classList.remove('selected');
            optionScheduled.classList.add('selected');
            timeWrapper.style.display = 'block';
            // Обновляем временные слоты для выбранной даты
            const selectedDate = document.getElementById('delivery-date').value;
            updateTimeSlots(selectedDate);
        } else {
            optionAsap.classList.add('selected');
            optionScheduled.classList.remove('selected');
            timeWrapper.style.display = 'none';
        }
    });
});

// Обработка изменения даты доставки
document.getElementById('delivery-date').addEventListener('change', function() {
    updateTimeSlots(this.value);
});

// Сохранение времени доставки (с датой, если выбрана)
document.getElementById('go-to-order').addEventListener('click', function(e) {
    const selectedTime = document.querySelector('input[name="delivery_time"]:checked').value;
    let deliveryTime = 'ASAP';
    
    if (selectedTime === 'SCHEDULED') {
        const selectedDate = document.getElementById('delivery-date').value;
        const selectedTimeSlot = document.getElementById('scheduled-time').value;
        // Формат: "tomorrow_14:30" или "today_18:00"
        deliveryTime = selectedDate + '_' + selectedTimeSlot;
    }
    
    localStorage.setItem('delivery_time', deliveryTime);
    const url = new URL(this.href);
    url.searchParams.set('delivery_time', deliveryTime);
    this.href = url.toString();
});

// Инициализация
document.addEventListener('DOMContentLoaded', renderCart);
</script>

</body>
</html>
