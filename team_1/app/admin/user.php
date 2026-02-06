<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
// Users page: admin, manager can view/add
requireRoles(['admin', 'manager']);

$currentUserId = $_SESSION['admin_id'] ?? null;
$currentUserRole = $_SESSION['admin_role'] ?? 'user';

// Role key → Japanese label (same as in add_user.php)
$role_labels = [
    'admin'    => '管理者',
    'manager'  => 'マネージャー',
    'kitchen'  => 'キッチン',
    'delivery' => '配達',
];

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Load users (username, email, name, surname, phone, role)
$users = [];
$res = $mysqli->query("
  SELECT u.id, u.username, u.email,
         COALESCE(u.name,'') AS name, COALESCE(u.surname,'') AS surname, COALESCE(u.phone,'') AS phone,
         r.name AS role
  FROM users u
  JOIN roles r ON u.role_id = r.id
  ORDER BY u.id
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $name = trim($row['name'] . ' ' . $row['surname']);
        $roleKey = $row['role'];
        $users[] = [
            'id' => (int)$row['id'],
            'login' => $row['username'],
            'email' => $row['email'],
            'name' => $name !== '' ? $name : '—',
            'phone' => $row['phone'] !== '' ? $row['phone'] : '—',
            'role' => $roleKey,
            'role_label' => $role_labels[$roleKey] ?? $roleKey,
        ];
    }
    $res->free();
}

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
if ($flashSuccess) unset($_SESSION['flash_success']);
if ($flashError) unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ユーザー管理</title>
<link rel="stylesheet" href="css/user.css">
</head>
<body>

<header class="header">
    <div class="header-inner">
        <img src="../assets/image/logo.png" alt="Pizza Mach" class="header-logo">
        <h1 class="h1">ユーザー管理</h1>
        <a href="admin.php" class="back-btn">戻る</a>
        <form method="post" style="margin: 0;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="logout-btn">ログアウト</button>
        </form>
    </div>
</header>

<div class="container">
    <?php if ($flashError): ?>
    <div class="flash-error" role="alert">
        <span class="flash-error-icon">⚠</span>
        <?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
    <div class="flash-success" role="status">
        <span class="flash-success-icon">✓</span>
        <?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <div class="searchBox">
        <input type="text" id="searchBox" placeholder="ユーザー検索">
        <button class="link-btn" onclick="resetSearch()">検索</button>
    </div>

    <a href="add_user.php" class="link-btn add-btn">＋ 追加</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>名前</th>
                <th>Login</th>
                <th>メール</th>
                <th>ロール</th>
                <th>電話</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="userTable">
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= (int)$user['id'] ?></td>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['login']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['role_label']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td>
                    <a href="add_user.php?edit=<?= (int)$user['id'] ?>" class="link-btn">編集</a>
                    <?php if ($currentUserRole === 'admin' && $currentUserId != $user['id'] && $user['role'] !== 'admin'): ?>
                        <a href="user_delete.php?id=<?= (int)$user['id'] ?>" class="link-btn link-btn-danger" onclick="return confirm('本当に削除しますか？');">削除</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchBox = document.getElementById('searchBox');
    const rows = document.querySelectorAll('#userTable tr');

    searchBox.addEventListener('input', () => {
        const filter = searchBox.value.toLowerCase();
        rows.forEach(row => {
            const name = row.cells[1].textContent.toLowerCase();
            const login = row.cells[2].textContent.toLowerCase();
            const email = row.cells[3].textContent.toLowerCase();
            const phone = row.cells[5].textContent.toLowerCase();
            row.style.display =
                name.includes(filter) || login.includes(filter) || email.includes(filter) || phone.includes(filter)
                    ? '' : 'none';
        });
    });
});

function resetSearch() {
    const searchBox = document.getElementById('searchBox');
    searchBox.value = '';
    searchBox.dispatchEvent(new Event('input'));
}
</script>

</body>
</html>
