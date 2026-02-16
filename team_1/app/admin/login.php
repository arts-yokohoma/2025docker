<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to admin panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$msg = '';
$ok = false;

if ($submitted) {
    if (empty($username) || empty($password)) {
        $msg = 'ユーザー名とパスワードを入力してください';
    } else {
        // Check against users table with role
        $stmt = $mysqli->prepare("
            SELECT u.id, u.username, u.password, r.name as role 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = ?
        ");
        
        if (!$stmt) {
            $msg = 'データベースエラー: ' . $mysqli->error;
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $user_row = $result->fetch_assoc()) {
                if (password_verify($password, $user_row['password'])) {
                    // Only admin, manager, kitchen, driver can login
                    $allowed_roles = ['admin', 'manager', 'kitchen', 'driver'];
                    if (!in_array($user_row['role'], $allowed_roles)) {
                        $msg = 'このロールではログインできません';
                    } else {
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['admin_id'] = $user_row['id'];
                        $_SESSION['admin_username'] = $user_row['username'];
                        $_SESSION['admin_role'] = $user_row['role'];
                        $ok = true;
                        $msg = 'ログイン成功';
                        header("refresh:2;url=admin.php");
                    }
                } else {
                    $msg = 'ユーザー名またはパスワードが正しくありません';
                }
            } else {
                $msg = 'ユーザー名またはパスワードが正しくありません';
            }
            $stmt->close();
        }
    }
}
?>

<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>PIZZA-MACH 管理パネル - Login</title>
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
<input type="text" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" required>
<label>パスワード：</label>
<input type="password" name="password" required>
<button type="submit">ログイン</button>
</form>
<?php if ($submitted): ?>
<div class="msg <?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
</div>
</body>
</html>