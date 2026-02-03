<?php
session_start();

$currentUserId = $_SESSION['user_id'] ?? 2; 
$currentUserRole = $_SESSION['role'] ?? 'user'; 

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

<header><h1>ユーザー管理画面</h1></header>

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
