<?php
$perPage = 10;
$maxPage = 99;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $maxPage));

$allUsers = [];
for ($i = 1; $i <= 990; $i++) {
    $allUsers[] = [
        'initial' => 'U' . ($i % 10),
        'name' => "ユーザー{$i}",
        'email' => "user{$i}@example.com",
        'address' => "東京都サンプル{$i}-1-1",
        'phone' => "090-0000-" . str_pad($i, 5, '0', STR_PAD_LEFT),
        'date' => '2023/10/01'
    ];
}
$totalUsers = count($allUsers);
$totalPages = min(ceil($totalUsers / $perPage), $maxPage);

$offset = ($page - 1) * $perPage;
$users = array_slice($allUsers, $offset, $perPage);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ユーザーリスト</title>

<style>
*{box-sizing:border-box}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#f6f7f9;color:#333}
header{background:#fff;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 32px;border-bottom:1px solid #eee}
.logo{display:flex;align-items:center;gap:10px;font-weight:600}
.logout{background:#f1f1f1;border:none;padding:6px 14px;border-radius:8px}
.main{max-width:1200px;margin:32px auto}
h1{font-size:28px;margin:0}
.sub{color:#777;margin:6px 0 24px}
.search-area{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.search-box{display:flex;gap:8px}
.search-box input{width:360px;padding:10px 14px;border-radius:10px;border:1px solid #ddd}
.search-box button{background:#4f6ef7;color:#fff;border:none;padding:10px 18px;border-radius:10px}
.total{background:#eef2ff;color:#4f6ef7;padding:6px 14px;border-radius:20px;font-size:14px}
.table-wrap{background:#fff;border-radius:16px;overflow:hidden;border:1px solid #eee}
table{width:100%;border-collapse:collapse}
th{background:#fafafa;text-align:left;font-weight:600;padding:14px 16px;font-size:14px;border-bottom:1px solid #eee}
td{padding:16px;border-bottom:1px solid #f0f0f0;font-size:14px}
.name{display:flex;align-items:center;gap:12px}
.avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:600;background:#e8edff;color:#4f6ef7}
.action{display:flex;gap:8px}
.edit{background:#f3f4f6;border:none;padding:6px 12px;border-radius:8px}
.delete{background:#fee2e2;color:#dc2626;border:none;padding:6px 12px;border-radius:8px}
.pagination{margin:24px 0;text-align:center}
.pagination a{margin:0 6px;text-decoration:none;color:#555}
.pagination .active{background:#4f6ef7;color:#fff;padding:8px 14px;border-radius:50%}
</style>
</head>
<body>
<header>
  <div class="logo">🍕 Pizza Admin</div>
  <button class="logout">ログアウト</button>
</header>

<div class="main">
  <h1>ユーザーリスト</h1>
  <div class="sub">登録済みユーザー一覧（検索・管理機能）</div>

  <div class="search-area">
    <div class="search-box">
      <input placeholder="名前またはメールアドレスで検索...">
      <button>検索</button>
    </div>
    <div class="total">Total: <?= $totalUsers ?></div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>名前</th>
          <th>メールアドレス</th>
          <th>住所</th>
          <th>電話番号</th>
          <th>登録日</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td class="name">
            <div class="avatar"><?= $u['initial'] ?></div>
            <?= $u['name'] ?>
          </td>
          <td><?= $u['email'] ?></td>
          <td><?= $u['address'] ?></td>
          <td><?= $u['phone'] ?></td>
          <td><?= $u['date'] ?></td>
          <td class="action">
            <button class="edit">編集</button>
            <button class="delete">削除</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page-1 ?>"><</a>
    <?php endif; ?>

    <?php
      $start = max(1, $page - 2);
      $end   = min($totalPages, $page + 1);
      for ($i = $start; $i <= $end; $i++):
    ?>
      <a class="<?= $i === $page ? 'active' : '' ?>" href="?page=<?= $i ?>">
        <?= $i ?>
      </a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
      <a href="?page=<?= $page+1 ?>">></a>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
