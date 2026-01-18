<?php
session_start();

// ✅ CHANGED: handle POST here, save address to session, then redirect to confirm.php (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['order']['address'] = [
        'zip'     => $_POST['zip'] ?? '',
        'pref'    => $_POST['pref'] ?? '',
        'city'    => $_POST['city'] ?? '',
        'street'  => $_POST['street'] ?? '',
        'comment' => $_POST['comment'] ?? ''
    ];

    header('Location: confirm.php');
    exit;
}

$address = $_SESSION['order']['address'] ?? [
    'zip'     => '',
    'pref'    => '',
    'city'    => '',
    'street'  => '',
    'comment' => ''
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match | お届け先住所</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/pages/address.css">
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">PM</div>
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<div class="checkout-progress">
    <div class="progress-steps-text">
        <span class="progress-step">カート</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">お客様情報</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step active">配送先住所</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">注文内容確認</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">注文完了</span>
    </div>
    <div class="progress-bar-container">
        <div class="progress-bar-fill" style="width: 75%;"></div>
    </div>
</div>

<div class="address-page">
    <h2>お届け先住所</h2>
    
    <form method="post" action="address.php" class="address-form" id="addressForm">
        <label>
            郵便番号 <span class="required">*</span>
            <input type="text" 
                   name="zip" 
                   id="zip-input"
                   required
                   maxlength="8"
                   pattern="[0-9]{3}-?[0-9]{4}"
                   value="<?= htmlspecialchars($address['zip']) ?>"
                   placeholder="例：123-4567">
            <div class="zip-lookup-status" id="zip-status"></div>
        </label>

        <label>
            都道府県 <span class="required">*</span>
            <input type="text" 
                   name="pref" 
                   id="pref-input"
                   required
                   value="<?= htmlspecialchars($address['pref']) ?>"
                   placeholder="例：東京都">
        </label>

        <label>
            市区町村 <span class="required">*</span>
            <input type="text" 
                   name="city" 
                   id="city-input"
                   required
                   value="<?= htmlspecialchars($address['city']) ?>"
                   placeholder="例：千代田区">
        </label>

        <label>
            番地・建物名 <span class="required">*</span>
            <input type="text" 
                   name="street" 
                   id="street-input"
                   required
                   value="<?= htmlspecialchars($address['street']) ?>"
                   placeholder="例：1-1-1 サンプルビル 101号室">
        </label>

        <label>
            備考（任意）
            <textarea name="comment" 
                      id="comment-input"
                      rows="3"
                      placeholder="配達に関するご要望があればご記入ください"><?= htmlspecialchars($address['comment']) ?></textarea>
        </label>
        
        <div class="form-actions">
            <a href="user_info.php" class="btn-back-cart">お客様情報に戻る</a>
            <button type="submit" class="btn-proceed">確認画面へ進む</button>
        </div>
    </form>
</div>

<script>
// Японский API для автозаполнения адреса по почтовому индексу
// Используем zipcloud API: https://zipcloud.ibsnet.co.jp/api/search

const zipInput = document.getElementById('zip-input');
const prefInput = document.getElementById('pref-input');
const cityInput = document.getElementById('city-input');
const streetInput = document.getElementById('street-input');
const zipStatus = document.getElementById('zip-status');

let lookupTimeout = null;

// Форматирование почтового индекса: 1234567 -> 123-4567
function formatZipCode(value) {
    // Удаляем все нецифровые символы
    const digits = value.replace(/\D/g, '');
    
    if (digits.length <= 3) {
        return digits;
    } else if (digits.length <= 7) {
        return digits.slice(0, 3) + '-' + digits.slice(3);
    } else {
        return digits.slice(0, 3) + '-' + digits.slice(3, 7);
    }
}

// Автоматическое форматирование при вводе
zipInput.addEventListener('input', function(e) {
    const formatted = formatZipCode(e.target.value);
    if (formatted !== e.target.value) {
        e.target.value = formatted;
    }
    
    // Очищаем статус при изменении
    zipStatus.textContent = '';
    zipStatus.className = 'zip-lookup-status';
    
    // Очищаем предыдущий таймаут
    if (lookupTimeout) {
        clearTimeout(lookupTimeout);
    }
    
    // Запускаем поиск через 500ms после остановки ввода
    const zipCode = e.target.value.replace(/\D/g, '');
    if (zipCode.length === 7) {
        lookupTimeout = setTimeout(() => {
            lookupAddress(zipCode);
        }, 500);
    } else if (zipCode.length > 0) {
        // Если индекс неполный, очищаем поля
        if (zipCode.length < 7) {
            prefInput.value = '';
            cityInput.value = '';
        }
    }
});

// Функция поиска адреса по почтовому индексу
async function lookupAddress(zipCode) {
    if (zipCode.length !== 7) {
        return;
    }
    
    zipStatus.textContent = '検索中...';
    zipStatus.className = 'zip-lookup-status loading';
    
    try {
        const response = await fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${zipCode}`);
        const data = await response.json();
        
        if (data.status === 200 && data.results && data.results.length > 0) {
            const result = data.results[0];
            
            // Заполняем поля адреса
            prefInput.value = result.address1 || '';
            cityInput.value = result.address2 || '';
            
            // Если есть address3, добавляем его к city
            if (result.address3) {
                cityInput.value += (cityInput.value ? ' ' : '') + result.address3;
            }
            
            zipStatus.textContent = '✓ 住所が見つかりました';
            zipStatus.className = 'zip-lookup-status success';
            
            // Фокус на поле street для ввода номера дома
            setTimeout(() => {
                streetInput.focus();
            }, 100);
            
        } else {
            zipStatus.textContent = '郵便番号が見つかりませんでした';
            zipStatus.className = 'zip-lookup-status error';
            prefInput.value = '';
            cityInput.value = '';
        }
    } catch (error) {
        console.error('Address lookup error:', error);
        zipStatus.textContent = '検索に失敗しました。手動で入力してください。';
        zipStatus.className = 'zip-lookup-status error';
    }
}

// Обработка Enter в поле почтового индекса
zipInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const zipCode = zipInput.value.replace(/\D/g, '');
        if (zipCode.length === 7) {
            lookupAddress(zipCode);
        }
    }
});
</script>

</body>
</html>
