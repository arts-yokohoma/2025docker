<?php
session_start();

$user = $_SESSION['order']['user'] ?? [
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

<form method="post" action="address.php">
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
