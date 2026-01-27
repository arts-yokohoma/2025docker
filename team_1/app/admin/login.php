<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to admin panel
if (isset($_SESSION['user_id'])) {
    header('Location: admin.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$error = '';

if ($submitted) {
    if (empty($username) || empty($password)) {
        $error = 'ユーザー名とパスワードを入力してください';
    } else {
        // Check user credentials - allow admin, manager, driver, kitchen roles
        $stmt = $mysqli->prepare("
            SELECT u.id, u.username, u.password, u.active, r.name as role_name
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE (u.username = ? OR u.email = ?) AND r.name IN ('admin', 'manager', 'driver', 'kitchen')
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Check if user is active
            if (!$user['active']) {
                $error = 'このアカウントは無効です';
            } elseif (password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role_name'];
                
                header('Location: admin.php');
                exit;
            } else {
                $error = 'ユーザー名またはパスワードが正しくありません';
            }
        } else {
            $error = 'ユーザー名またはパスワードが正しくありません';
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
<label>ユーザー名またはメール：</label>
<input type="text" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required autofocus>
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