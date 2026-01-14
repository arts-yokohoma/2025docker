<?php
session_start();

// ✅ CHANGED: handle POST here and save user into session
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // 
    $_SESSION['order']['user'] = [            // 
        'name'  => $_POST['name'] ?? '',      // 
        'email' => $_POST['email'] ?? '',     // 
        'phone' => $_POST['phone'] ?? ''      // 
    ];                                        // 

    header('Location: address.php');          
    exit;                                     //
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
    <title>お客様情報</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<h2>お客様情報</h2>

<!-- ✅ CHANGED: post back to user_info.php so the code above runs -->
<form method="post" action="user_info.php"> <!-- ✅ CHANGED -->

    <label>
        お名前<br>
        <input type="text" name="name" required
               value="<?= htmlspecialchars($user['name']) ?>">
    </label>
    <br><br>

    <label>
        メールアドレス<br>
        <input type="email" name="email" required
               value="<?= htmlspecialchars($user['email']) ?>">
    </label>
    <br><br>

    <label>
        電話番号<br>
        <input type="tel" name="phone" required
               value="<?= htmlspecialchars($user['phone']) ?>">
    </label>
    <br><br>

    <button type="submit">次へ</button>
</form>

<p><a href="cart.php">← カートへ戻る</a></p>

</body>
</html>
