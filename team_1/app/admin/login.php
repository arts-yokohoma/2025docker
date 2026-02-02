<?php
/**
 * Admin login — validates credentials against staff_users + roles (DB), starts session.
 * Only staff with roles code admin, manager, driver, kitchen can log in.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to admin panel
if (!empty($_SESSION['user_id']) && !empty($_SESSION['username'])) {
    header('Location: admin.php');
    exit;
}

$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$error = '';

if ($submitted) {
    if (empty($login) || empty($password)) {
        $error = 'ログインとパスワードを入力してください';
    } else {
        $stmt = $mysqli->prepare("
            SELECT u.id, u.login, u.password_hash, u.active, r.code as role_code, r.role_name
            FROM staff_users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.login = ? AND r.active = 1 AND r.code IN ('admin', 'manager', 'driver', 'kitchen')
        ");
        $stmt->bind_param("s", $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (!$user['active']) {
                $error = 'このアカウントは無効です';
            } elseif (password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['login'];
                $_SESSION['role'] = $user['role_code'];

                header('Location: admin.php');
                exit;
            } else {
                $error = 'ログインまたはパスワードが正しくありません';
            }
        } else {
            $error = 'ログインまたはパスワードが正しくありません';
        }
    }
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
<label>ログイン：</label>
<input type="text" name="login" value="<?= htmlspecialchars($login, ENT_QUOTES, 'UTF-8') ?>" required autofocus>
<label>パスワード：</label>
<input type="password" name="password" required>
<button type="submit">ログイン</button>
</form>
<?php if ($error): ?>
<div class="msg err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
</div>
</body>
</html>