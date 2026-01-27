<?php
require_once __DIR__ . '/auth.php';
requireAdmin(); // Only admins can manage users

require_once __DIR__ . '/../config/db.php';

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Handle form submissions
$message = '';
$error = '';

// Create user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 2); // Default to 'manager' role
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'すべてのフィールドを入力してください';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '有効なメールアドレスを入力してください';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $email, $hashedPassword, $role_id);
        
        if ($stmt->execute()) {
            $message = 'ユーザーが正常に作成されました';
        } else {
            if ($mysqli->errno === 1062) {
                $error = 'ユーザー名またはメールアドレスが既に存在します';
            } else {
                $error = 'ユーザー作成に失敗しました: ' . $mysqli->error;
            }
        }
    }
}

// Delete user (soft delete - set active = 0)
if (isset($_GET['delete_id'])) {
    $deleteId = (int)$_GET['delete_id'];
    if ($deleteId !== $_SESSION['user_id']) { // Prevent self-deletion
        $stmt = $mysqli->prepare("UPDATE users SET active = 0 WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        if ($stmt->execute()) {
            $message = 'ユーザーが削除されました';
        } else {
            $error = 'ユーザー削除に失敗しました';
        }
    } else {
        $error = '自分自身を削除することはできません';
    }
}

// Get all users with roles
$users = [];
$result = $mysqli->query("
    SELECT u.id, u.username, u.email, u.active, u.created_at, r.name as role_name
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    ORDER BY u.created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get all roles for dropdown
$roles = [];
$roleResult = $mysqli->query("SELECT id, name FROM roles ORDER BY id");
if ($roleResult) {
    while ($row = $roleResult->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ユーザー管理</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    background: #f6f7f9;
    color: #333;
}
header {
    background: #fff;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    border-bottom: 1px solid #eee;
}
.logo { display: flex; align-items: center; gap: 10px; font-weight: 600; }
.logout { background: #f1f1f1; border: none; padding: 6px 14px; border-radius: 8px; cursor: pointer; text-decoration: none; color: #333; }
.logout:hover { background: #e0e0e0; }
.main { max-width: 1200px; margin: 32px auto; padding: 0 32px; }
h1 { font-size: 28px; margin: 0 0 8px; }
.sub { color: #777; margin-bottom: 24px; }
.msg { padding: 12px; border-radius: 8px; margin-bottom: 16px; }
.msg.ok { background: #d4edda; color: #155724; }
.msg.err { background: #f8d7da; color: #721c24; }
.create-form {
    background: #fff;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    border: 1px solid #eee;
}
.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; font-weight: 500; color: #555; }
.form-group input, .form-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}
.form-group input:focus, .form-group select:focus {
    outline: none;
    border-color: #4f6ef7;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; }
.btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; }
.btn-primary { background: #4f6ef7; color: #fff; }
.btn-primary:hover { background: #3d5ae5; }
.btn-danger { background: #dc2626; color: #fff; }
.btn-danger:hover { background: #b91c1c; }
.table-wrap {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #eee;
}
table { width: 100%; border-collapse: collapse; }
th {
    background: #fafafa;
    text-align: left;
    font-weight: 600;
    padding: 14px 16px;
    font-size: 14px;
    border-bottom: 1px solid #eee;
}
td { padding: 16px; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.badge-admin { background: #fee2e2; color: #991b1b; }
.badge-manager { background: #dbeafe; color: #1e40af; }
.badge-driver { background: #d1fae5; color: #065f46; }
.badge-kitchen { background: #fef3c7; color: #92400e; }
.badge-active { background: #d4edda; color: #155724; }
.badge-inactive { background: #f8d7da; color: #721c24; }
.action { display: flex; gap: 8px; }
</style>
</head>
<body>
<header>
    <div class="logo">🍕 Pizza Admin - ユーザー管理</div>
    <div>
        <a href="admin.php" style="margin-right: 12px; text-decoration: none; color: #555;">管理パネル</a>
        <a href="logout.php" class="logout">ログアウト</a>
    </div>
</header>

<div class="main">
    <h1>ユーザー管理</h1>
    <div class="sub">システムユーザーの作成・管理</div>

    <?php if ($message): ?>
        <div class="msg ok"><?= h($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="msg err"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- Create User Form -->
    <div class="create-form">
        <h2 style="margin-top: 0; font-size: 20px; margin-bottom: 16px;">新規ユーザー作成</h2>
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label>ユーザー名 *</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>メールアドレス *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>パスワード *</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>ロール *</label>
                    <select name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= $role['name'] === 'manager' ? 'selected' : '' ?>>
                                <?= h($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="create_user" class="btn btn-primary">ユーザーを作成</button>
        </form>
    </div>

    <!-- Users List -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ユーザー名</th>
                    <th>メール</th>
                    <th>ロール</th>
                    <th>ステータス</th>
                    <th>登録日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: #999;">
                            ユーザーが登録されていません
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= h($user['id']) ?></td>
                            <td><?= h($user['username']) ?></td>
                            <td><?= h($user['email']) ?></td>
                            <td>
                                <span class="badge badge-<?= h($user['role_name']) ?>">
                                    <?= h($user['role_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $user['active'] ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $user['active'] ? '有効' : '無効' ?>
                                </span>
                            </td>
                            <td><?= date('Y/m/d H:i', strtotime($user['created_at'])) ?></td>
                            <td class="action">
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="?delete_id=<?= $user['id'] ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('このユーザーを削除しますか？')">
                                        削除
                                    </a>
                                <?php else: ?>
                                    <span style="color: #999;">自分</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
