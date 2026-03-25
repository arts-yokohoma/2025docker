<?php
require __DIR__ . '/./config/db.php';

// track return destination (used when coming from confirm page)
$return = $_GET['return'] ?? '';

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

// Calculate last order time for today
$todayClose = clone $now;
$todayClose->setTime((int)$closeH, (int)$closeM, 0);
$lastOrderTime = clone $todayClose;
$lastOrderTime->modify('-' . $storeHours['last_order_offset_min'] . ' minutes');

// Check if we can still accept orders for today
// Orders can be accepted only BEFORE last_order_time
$canOrderToday = ($now < $lastOrderTime);

// Calculate minimum delivery time (current time + 30 min preparation)
$minDeliveryTime = clone $now;
$minDeliveryTime->modify('+30 minutes');

// If we cannot order for today (after last_order_time OR store closed), 
// set minimum time to next opening + 30 minutes
if (!$canOrderToday || !$isStoreOpen) {
    $todayOpen = clone $now;
    $todayOpen->setTime((int)$openH, (int)$openM, 0);
    if ($todayOpen < $now) {
        $todayOpen->modify('+1 day');
    }
    $minDeliveryTime = clone $todayOpen;
    $minDeliveryTime->modify('+30 minutes');
}

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
    <title>Pizza Mach | カート</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/pages/cart.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-content">
        <div class="logo"><img src="./assets/image/logo.png" alt="Pizza Mach logo featuring stylized pizza slice with restaurant name"></div>
        <h1 class="header-title">Pizza Mach</h1>
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
                <?php
                // Show ASAP option ONLY if:
                // 1. Store is currently open
                // 2. Current time is before last_order_time
                // 3. There are available slots for today
                $showAsap = $isStoreOpen && $canOrderToday && isset($availableTimesByDate['today']) && !empty($availableTimesByDate['today']);
                $asapSelected = $showAsap ? 'checked' : '';
                $scheduledSelected = !$showAsap ? 'checked' : '';
                $asapClass = $showAsap ? 'selected' : '';
                $scheduledClass = !$showAsap ? 'selected' : '';
                $scheduledDisplay = !$showAsap ? 'block' : 'none';
                ?>
                
                <?php if (!$showAsap): ?>
                <div style="margin-bottom: 20px; padding: 16px 20px; background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-radius: 12px; border: 2px solid #ff9800; box-shadow: 0 2px 8px rgba(255, 152, 0, 0.15);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#ff6f00">
                                <path d="M638-448h-23.33l-66.34-66.67H628l120.67-218.66H328.33L261.67-800h527.66q25 0 37.17 20.83 12.17 20.84-1.17 45.17L684-478q-7.58 14.29-19.29 22.14Q653-448 638-448ZM284.53-80.67q-30.86 0-52.7-21.97Q210-124.62 210-155.47q0-30.86 21.98-52.7Q253.95-230 284.81-230t52.69 21.98q21.83 21.97 21.83 52.83t-21.97 52.69q-21.98 21.83-52.83 21.83Zm556.14 48L585.33-286H286q-40 0-59.67-30.83-19.66-30.84-.33-65.84l60.67-106.66L205.33-668 40-833.33l47.33-47.34L888-80l-47.33 47.33Zm-322-320-94-96h-84l-55.34 96h233.34Zm109.33-162h-79.67H628Zm57.33 434q-30.33 0-52.5-21.97-22.16-21.98-22.16-52.83 0-30.86 22.16-52.7Q655-230 685.33-230q30.34 0 52.5 21.98Q760-186.05 760-155.19t-22.17 52.69q-22.16 21.83-52.5 21.83Z"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-size: 16px; font-weight: bold; color: #e65100; margin-bottom: 8px;">
                                <?php if (!$isStoreOpen): ?>
                                    現在営業時間外です
                                <?php elseif (!$canOrderToday): ?>
                                    本日のラストオーダー時間を過ぎています
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 15px; font-weight: 600; color: #d84315; line-height: 1.5;">
                                <?php if (!$isStoreOpen): ?>
                                    下記より配達時間をご指定ください
                                <?php elseif (!$canOrderToday): ?>
                                    明日以降の配達時間をご指定ください
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($showAsap): ?>
                <div class="delivery-option <?= $asapClass ?>" id="option-asap">
                    <input type="radio" name="delivery_time" id="delivery_asap" value="ASAP" <?= $asapSelected ?>>
                    <label for="delivery_asap">最短でお届け</label>
                    <div class="delivery-time-estimate">約30分～45分</div>
                </div>
                <?php endif; ?>
                
                <div class="delivery-option <?= $scheduledClass ?>" id="option-scheduled">
                    <input type="radio" name="delivery_time" id="delivery_scheduled" value="SCHEDULED" <?= $scheduledSelected ?>>
                    <label for="delivery_scheduled">配達時間を指定する</label>
                    <div class="time-select-wrapper" id="scheduled-time-wrapper" style="display: <?= $scheduledDisplay ?>;">
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
                <?php
                // if the cart was opened from the confirmation page we want to send
                // the user straight back after updating the delivery time instead of
                // proceeding through the normal checkout flow
                $baseHref = ($return === 'confirm') ? 'confirm.php' : 'user_info.php';
                $linkLabel = ($return === 'confirm') ? '配達時間を変更して確認へ戻る' : 'お客様情報の入力へ進む →';
                ?>
                <a href="<?= htmlspecialchars($baseHref) ?>" id="go-to-order" class="btn-proceed"><?= htmlspecialchars($linkLabel) ?></a>
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

// if the cart is opened with a delivery_time query parameter (e.g. when
// returning from confirmation) make sure we persist it in localStorage so
// the radio buttons in the UI stay in sync.
const initialParams = new URL(window.location.href).searchParams;
if (initialParams.get('delivery_time')) {
    localStorage.setItem('delivery_time', initialParams.get('delivery_time'));
}

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
    const selectedRadio = document.querySelector('input[name="delivery_time"]:checked');
    
    if (!selectedRadio) {
        e.preventDefault();
        alert('配達時間を選択してください。');
        return;
    }
    
    const selectedTime = selectedRadio.value;
    let deliveryTime = 'ASAP';
    
    if (selectedTime === 'SCHEDULED') {
        const selectedDate = document.getElementById('delivery-date').value;
        const selectedTimeSlot = document.getElementById('scheduled-time').value;
        
        if (!selectedDate || !selectedTimeSlot) {
            e.preventDefault();
            alert('配達時間を選択してください。');
            return;
        }
        
        // Формат: "tomorrow_14:30" или "today_18:00"
        deliveryTime = selectedDate + '_' + selectedTimeSlot;
    }
    
    localStorage.setItem('delivery_time', deliveryTime);

    // if the return parameter is set to 'confirm', bypass the normal
    // checkout link and redirect straight back to the confirmation page
    const params = new URL(window.location.href).searchParams;
    if (params.get('return') === 'confirm') {
        e.preventDefault();
        window.location.href = 'confirm.php?delivery_time=' + encodeURIComponent(deliveryTime);
        return;
    }

    const url = new URL(this.href);
    url.searchParams.set('delivery_time', deliveryTime);
    this.href = url.toString();
});

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    renderCart();
    
    // Если SCHEDULED выбран по умолчанию (ASAP недоступен), загрузить слоты
    const scheduledRadio = document.getElementById('delivery_scheduled');
    if (scheduledRadio && scheduledRadio.checked) {
        const selectedDate = document.getElementById('delivery-date').value;
        if (selectedDate) {
            updateTimeSlots(selectedDate);
        }
    }
});
</script>

</body>
</html>
