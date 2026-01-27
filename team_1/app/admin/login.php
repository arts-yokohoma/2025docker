<?php
$user = trim($_POST['user'] ?? '');
$pass = trim($_POST['pass'] ?? '');
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$ok = $submitted && $user === 'pizza' && $pass === 'maha';
$msg = '';
if ($submitted) {
    $msg = $ok ? 'ログイン成功' : 'ログイン失敗';
}
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PIZZA-MACH - Login</title>
<link rel="stylesheet" href="css/login.css">
</head>
<body>
<div class="box">
<h1>管理パネル</h1>
<div style="display: flex; justify-content: center; margin-bottom: 20px;">
<img src="../assets/image/logo.png" alt="Pizza Mach logo: stylized delivery person in red cap running with pizza, yellow and red circular pizza design, tagline Fast Fresh Pizza on cream background" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
</div>
<form method="post" autocomplete="off">
<label>ユーザー名：</label>
<input type="text" name="user" value="<?= htmlspecialchars($user, ENT_QUOTES, 'UTF-8') ?>" required>
<label>パスワード：</label>
<input type="password" name="pass" required>
<a href="admin.php">
<button type="submit">ログイン</button>
</form>
<?php if ($submitted): ?>
<div class="msg <?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
</div>
</body>
</html>