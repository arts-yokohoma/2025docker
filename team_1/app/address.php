<?php
session_start();

// ✅ CHANGED: handle POST here, save address to session, then redirect to confirm.php (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {                         // ✅ CHANGED
    $_SESSION['order']['address'] = [                                 // ✅ CHANGED (order, not orders)
        'zip'     => $_POST['zip'] ?? '',                              // ✅ CHANGED
        'pref'    => $_POST['pref'] ?? '',                             // ✅ CHANGED
        'city'    => $_POST['city'] ?? '',                             // ✅ CHANGED
        'street'  => $_POST['street'] ?? '',                           // ✅ CHANGED
        'comment' => $_POST['comment'] ?? ''                           // ✅ CHANGED
    ];                                                                 // ✅ CHANGED

    header('Location: confirm.php');                                   // ✅ CHANGED
    exit;                                                              // ✅ CHANGED
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
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">PM</div>
        <h1 class="header-title">Pizza Match</h1>
    </div>
</header>

<h2>お届け先住所</h2>

<!-- ✅ CHANGED: post back to address.php so the code above runs -->
<form method="post" action="address.php"> <!-- ✅ CHANGED -->

    <label>
        郵便番号（任意）<br>
        <input type="text" name="zip"
               value="<?= htmlspecialchars($address['zip']) ?>"
               placeholder="例：123-4567">
    </label>
    <br><br>

    <label>
        都道府県<br>
        <input type="text" name="pref" required
               value="<?= htmlspecialchars($address['pref']) ?>">
    </label>
    <br><br>

    <label>
        市区町村<br>
        <input type="text" name="city" required
               value="<?= htmlspecialchars($address['city']) ?>">
    </label>
    <br><br>

    <label>
        番地・建物名<br>
        <input type="text" name="street" required
               value="<?= htmlspecialchars($address['street']) ?>">
    </label>
    <br><br>

    <label>
        備考（任意）<br>
        <textarea name="comment" rows="3"><?= htmlspecialchars($address['comment']) ?></textarea>
    </label>
    <br><br>

    <button type="submit">確認画面へ</button>
</form>

<p><a href="user_info.php">← 戻る</a></p>

</body>
</html>
