<?php
/**
 * First-time setup page - Create the first admin user
 * This page should be accessible only when no admin exists
 * After creating admin, this page will redirect to login
 */

require_once __DIR__ . '/../config/db.php';

// Check if admin already exists (staff_users + roles by code)
$adminCheck = $mysqli->query("
    SELECT COUNT(*) as count 
    FROM staff_users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.code = 'admin' AND r.active = 1 AND u.active = 1
");
$adminExists = $adminCheck && (int)$adminCheck->fetch_assoc()['count'] > 0;

// If admin exists, redirect to login
if ($adminExists) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$success = false;

// Handle form submission (staff_users: login, password_hash, first_name, last_name, role_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if (empty($login) || empty($first_name) || empty($last_name) || empty($password)) {
        $error = 'すべてのフィールドを入力してください';
    } elseif (strlen($password) < 6) {
        $error = 'パスワードは6文字以上である必要があります';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません';
    } else {
        // Get admin role (roles.code = 'admin'); create role if table is empty
        $roleResult = $mysqli->query("SELECT id FROM roles WHERE code = 'admin' AND active = 1 LIMIT 1");
        if (!$roleResult || !$role = $roleResult->fetch_assoc()) {
            $mysqli->query("INSERT IGNORE INTO roles (code, role_name, sort_order, active) VALUES ('admin', 'Administrator', 1, 1)");
            $roleResult = $mysqli->query("SELECT id FROM roles WHERE code = 'admin' LIMIT 1");
            $role = $roleResult ? $roleResult->fetch_assoc() : null;
        }
        if (!$role) {
            $error = '管理者ロールが見つかりません。roles に code=admin を追加してください。';
        } else {
            $role_id = (int)$role['id'];
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO staff_users (login, password_hash, first_name, last_name, role_id, active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssi", $login, $password_hash, $first_name, $last_name, $role_id);

            if ($stmt->execute()) {
                $success = true;
                $message = '管理者アカウントが正常に作成されました！3秒後にログインページにリダイレクトします...';
                header("refresh:3;url=login.php");
            } else {
                if ($mysqli->errno === 1062) {
                    $error = 'このログインは既に使用されています';
                } else {
                    $error = 'エラーが発生しました: ' . $mysqli->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>初期設定 - 管理者アカウント作成</title>
<link rel="stylesheet" href="css/login.css">
<style>
.setup-container {
    max-width: 500px;
    margin: 50px auto;
    padding: 40px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
}
.setup-header {
    text-align: center;
    margin-bottom: 30px;
}
.setup-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 28px;
}
.setup-header p {
    color: #7f8c8d;
    font-size: 14px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
    font-size: 14px;
}
.form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
}
.form-group input:focus {
    outline: none;
    border-color: #4816dc;
    box-shadow: 0 0 0 3px rgba(72, 22, 220, 0.1);
}
.btn-submit {
    width: 100%;
    padding: 12px;
    background: #4816dc;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-submit:hover {
    background: #353ee7;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(75, 5, 238, 0.3);
}
.msg {
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}
.msg.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.msg.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.info-box {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #004085;
}
.info-box strong {
    display: block;
    margin-bottom: 5px;
}
</style>
</head>
<body>
<div class="setup-container">
    <div class="setup-header">
        <h1>🍕 初期設定</h1>
        <p>最初の管理者アカウントを作成してください</p>
    </div>

    <?php if ($success): ?>
        <div class="msg success">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="msg error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ 重要情報</strong>
            これは初回セットアップページです。管理者アカウントを作成した後、このページは自動的にログインページにリダイレクトされます。
        </div>

        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="login">ログイン *</label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    value="<?= htmlspecialchars($_POST['login'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                    required 
                    autofocus
                    placeholder="ログイン名を入力"
                >
            </div>

            <div class="form-group">
                <label for="first_name">名 *</label>
                <input 
                    type="text" 
                    id="first_name" 
                    name="first_name" 
                    value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                    required
                    placeholder="名"
                >
            </div>

            <div class="form-group">
                <label for="last_name">姓 *</label>
                <input 
                    type="text" 
                    id="last_name" 
                    name="last_name" 
                    value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                    required
                    placeholder="姓"
                >
            </div>

            <div class="form-group">
                <label for="password">パスワード *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    minlength="6"
                    placeholder="6文字以上"
                >
            </div>

            <div class="form-group">
                <label for="password_confirm">パスワード（確認） *</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    required
                    minlength="6"
                    placeholder="パスワードを再入力"
                >
            </div>

            <button type="submit" class="btn-submit">管理者アカウントを作成</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
