<?php
session_start();

// Сохраняем время доставки из localStorage в сессию (если есть)
if (isset($_GET['delivery_time'])) {
    $_SESSION['delivery_time'] = $_GET['delivery_time'];
}

// ✅ CHANGED: handle POST here and save user into session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['order']['user'] = [
        'name'  => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];

    header('Location: address.php');
    exit;
}

$user = $_SESSION['order']['user'] ?? [
    'name'  => '',
    'email' => '',
    'phone' => ''
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Pizza Match | お客様情報</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./assets/css/style.css">
    <link rel="stylesheet" href="./assets/css/components.css">
    <link rel="stylesheet" href="./assets/css/pages/user-info.css">
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
        <span class="progress-step active">お客様情報</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">配送先住所</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">注文内容確認</span>
        <span class="progress-step-separator">/</span>
        <span class="progress-step">注文完了</span>
    </div>
    <div class="progress-bar-container">
        <div class="progress-bar-fill" style="width: 50%;"></div>
    </div>
</div>

<div class="user-info-page">
    <h2>お客様情報</h2>
    
    <form method="post" action="user_info.php" class="user-info-form" id="userInfoForm">
        <label>
            お名前 <span class="required">*</span>
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($user['name']) ?>"
                   placeholder="例: 山田 太郎">
        </label>

        <label>
            メールアドレス <span class="required">*</span>
            <input type="email" name="email" required
                   value="<?= htmlspecialchars($user['email']) ?>"
                   placeholder="例: taro.yamada@example.com">
        </label>

        <label>
            電話番号 <span class="required">*</span>
            <input type="tel" name="phone" required
                   value="<?= htmlspecialchars($user['phone']) ?>"
                   placeholder="例: 090-1234-5678">
        </label>
        
        <div class="privacy-section">
            <div class="privacy-checkbox">
                <input type="checkbox" id="privacy-agree" name="privacy_agree" required checked>
                <label for="privacy-agree">
                    個人情報の取り扱いについて同意します (注文処理の目的でのみ利用します)
                </label>
            </div>
            <a href="#" class="privacy-link" onclick="alert('個人情報保護方針ページへ移動します'); return false;">
                <span class="privacy-icon">i</span>
                <span>個人情報の管理について確認する</span>
            </a>
        </div>
        
        <div class="form-actions">
            <a href="cart.php" class="btn-back-cart">カートに戻る</a>
            <button type="submit" class="btn-proceed">住所確認へ進む</button>
        </div>
    </form>
</div>

<script>
const form = document.getElementById('userInfoForm');
const privacyCheckbox = document.getElementById('privacy-agree');

// Проверка чекбокса перед отправкой
form.addEventListener('submit', function(e) {
    if (!privacyCheckbox.checked) {
        e.preventDefault();
        alert('個人情報の取り扱いについて同意してください。');
        return false;
    }
});
</script>

</body>
</html>
