<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['order']['user'] = [
         'name'  => '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? ''
        
    ];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>住所入力</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<h2>住所入力（仮）</h2>
<p>お名前: <?= htmlspecialchars($_SESSION['order']['user']['name'] ?? '') ?></p>
<p>メール: <?= htmlspecialchars($_SESSION['order']['user']['email'] ?? '') ?></p>
<p>電話: <?= htmlspecialchars($_SESSION['order']['user']['phone'] ?? '') ?></p>

<p>※ 次はここに住所フォームを追加します</p>

<p><a href="user_info.php">← 戻る</a></p>

</body>
</html>
