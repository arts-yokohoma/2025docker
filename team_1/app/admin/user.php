<?php
require_once __DIR__ . '/auth.php';
// Users page: admin, manager can view/add
requireRoles(['admin', 'manager']);

$currentUserId = $_SESSION['admin_id'] ?? null;
$currentUserRole = $_SESSION['admin_role'] ?? 'user'; 

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

$users = [
];
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

<header style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #fff; border-bottom: 1px solid #e1e8ed; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <img src="../assets/image/logo.png" alt="Pizza Mach" style="height: 40px; width: auto;">
        <h1 style="margin: 0;">Pizza Mach</h1>
        <h1 style="margin: 0; font-size: 1.1rem;">ユーザー管理画面</h1>
    </div>
    <div class="header-logout">
        <form method="post" style="display: inline;">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="logout-btn">ログアウト</button>
        </form>
    </div>
</header>

<div class="container">
    <div class="searchBox">
        <input type="text" id="searchBox" placeholder="ユーザー検索">
        <button class="link-btn" onclick="resetSearch()">検索</button>
    </div>

    <a href="add_user.php" class="link-btn">＋ 追加</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Login</th>
                <th>メール</th>
                <th>ロール</th>
                <th>電話</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody id="userTable">
        <?php foreach($users as $u): 
            // Hiển thị phone
            if($currentUserRole === 'admin'){
                $showPhone = $u['phone'];
            } else {
                $showPhone = ($u['id'] === $currentUserId) ? $u['phone'] : '****';
            }
          
            $editBtn = ($currentUserRole==='admin' || $u['id']===$currentUserId) ? 
                        '<a href="user_complete.php?id='.$u['id'].'" class="link-btn">編集</a>' : '―';
        ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['login']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] ?></td>
                <td><?= $showPhone ?></td>
                <td><?= $editBtn ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchBox = document.getElementById('searchBox');
    const rows = document.querySelectorAll('#userTable tr');

    searchBox.addEventListener('input', function () {
        const filter = this.value.toLowerCase();

        rows.forEach(row => {
            const login = row.cells[1].textContent.toLowerCase();
            const email = row.cells[2].textContent.toLowerCase();

            row.style.display =
                login.includes(filter) || email.includes(filter)
                    ? ''
                    : 'none';
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
