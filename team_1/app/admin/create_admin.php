<?php
/**
 * User creation page
 * - First-time setup: anyone can create the first admin
 * - After setup: only admin users can create new users
 */

require_once __DIR__ . '/../config/db.php';

// Check if admin exists
$adminCheck = $mysqli->query("
    SELECT COUNT(*) as count 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.name = 'admin'
");
$adminExists = $adminCheck && (int)$adminCheck->fetch_assoc()['count'] > 0;

// If admin exists, check permission
if ($adminExists) {
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['admin_role'] !== 'admin') {
        header('Location: login.php');
        exit;
    }
}

$message = '';
$error = '';
$success = false;

// Load available roles
$roles = [];
$roleResult = $mysqli->query("SELECT id, name FROM roles ORDER BY id");
if ($roleResult) {
    while ($role = $roleResult->fetch_assoc()) {
        $roles[] = $role;
    }
    $roleResult->free();
} else {
    $error = 'ロールを読み込めません: ' . $mysqli->error;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);

    if (empty($username) || empty($email) || empty($password) || $role_id === 0) {
        $error = 'すべてのフィールドを入力してください';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '有効なメールアドレスを入力してください';
    } elseif (strlen($password) < 6) {
        $error = 'パスワードは6文字以上である必要があります';
    } elseif ($password !== $password_confirm) {
        $error = 'パスワードが一致しません';
    } elseif (strlen($username) < 3) {
        $error = 'ユーザー名は3文字以上である必要があります';
    } else {
        // Verify role exists
        $roleCheck = $mysqli->prepare("SELECT id FROM roles WHERE id = ?");
        if (!$roleCheck) {
            $error = 'データベースエラー: ' . $mysqli->error;
        } else {
            $roleCheck->bind_param("i", $role_id);
            $roleCheck->execute();
            $roleCheckResult = $roleCheck->get_result();
            
            if (!$roleCheckResult->fetch_assoc()) {
                $error = 'ロールが見つかりません';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                
                if (!$stmt) {
                    $error = 'データベースエラー: ' . $mysqli->error;
                } else {
                    $stmt->bind_param("sssi", $username, $email, $password_hash, $role_id);
                    
                    if ($stmt->execute()) {
                        $success = true;
                        $message = 'ユーザー「' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '」を作成しました';
                        $_POST = [];
                    } else {
                        if ($mysqli->errno === 1062) {
                            $error = 'このユーザー名またはメールアドレスは既に使用されています';
                        } else {
                            $error = 'エラー: ' . $mysqli->error;
                        }
                    }
                    $stmt->close();
                }
            }
            $roleCheck->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $adminExists ? 'ユーザー作成' : '初期設定' ?></title>
<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    margin: 0;
    padding: 20px;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.container {
    max-width: 500px;
    width: 100%;
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    padding: 40px;
}
h1 {
    text-align: center;
    color: #333;
    margin-bottom: 10px;
}
.subtitle {
    text-align: center;
    color: #666;
    font-size: 14px;
    margin-bottom: 30px;
}
.info-box {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #1565c0;
}
.form-group {
    margin-bottom: 18px;
}
label {
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 6px;
    font-size: 14px;
}
input, select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
    font-family: inherit;
}
input:focus, select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.msg {
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}
.msg.success {
    background: #c8e6c9;
    color: #2e7d32;
    border-left: 4px solid #4caf50;
}
.msg.error {
    background: #ffcdd2;
    color: #c62828;
    border-left: 4px solid #f44336;
}
button {
    width: 100%;
    padding: 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
button:hover {
    background: #5568d3;
}
</style>
</head>
<body>
<header style="position: fixed; top: 0; left: 0; right: 0; display: flex; align-items: center; gap: 12px; padding: 12px 20px; background: #fff; border-bottom: 1px solid #e1e8ed; box-shadow: 0 1px 3px rgba(0,0,0,0.08); z-index: 10;">
  <img src="../assets/image/logo.png" alt="Pizza Mach" style="height: 40px; width: auto;">
  <span style="font-size: 1.25rem; font-weight: 600;">Pizza Mach</span>
</header>
<div class="container" style="margin-top: 60px;">
    <h1>🍕 <?= $adminExists ? 'ユーザー作成' : 'セットアップ' ?></h1>
    <p class="subtitle"><?= $adminExists ? '新しいユーザーを追加してください' : '最初のアカウントを作成してください' ?></p>

    <?php if ($success): ?>
        <div class="msg success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="msg error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if (!$adminExists): ?>
        <div class="info-box">初回セットアップです。最初の管理者アカウントを作成してください。</div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="username">ユーザー名</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required minlength="3" placeholder="3文字以上">
        </div>

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required placeholder="user@example.com">
        </div>

        <div class="form-group">
            <label for="role_id">ロール</label>
            <select id="role_id" name="role_id" required>
                <option value="">-- ロールを選択 --</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>" <?= ((int)($_POST['role_id'] ?? 0) === (int)$role['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" required minlength="6" placeholder="6文字以上">
        </div>

        <div class="form-group">
            <label for="password_confirm">パスワード（確認）</label>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="6" placeholder="パスワードを再入力">
        </div>

        <button type="submit">ユーザーを作成</button>
    </form>
</div>
</body>
</html>
