<?php
require_once __DIR__ . '/auth.php';
requireAdmin(); // Require admin authentication

require_once __DIR__ . '/../config/db.php';

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$perPage = 10;
$maxPage = 99;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $maxPage));

// Search functionality
$search = trim($_GET['search'] ?? '');
$searchCondition = '';
$searchParams = [];

if (!empty($search)) {
    $searchCondition = " AND (u.username LIKE ? OR u.email LIKE ?)";
    $searchPattern = '%' . $search . '%';
    $searchParams = [$searchPattern, $searchPattern];
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.id WHERE 1=1" . $searchCondition;
$countStmt = $mysqli->prepare($countQuery);
if (!empty($searchParams)) {
    $countStmt->bind_param("ss", ...$searchParams);
}
$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$countStmt->close();

$totalPages = min(ceil($totalUsers / $perPage), $maxPage);
$offset = ($page - 1) * $perPage;

// Get users with pagination
$query = "
    SELECT u.id, u.username, u.email, u.active, u.created_at, r.name as role_name
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE 1=1" . $searchCondition . "
    ORDER BY u.created_at DESC 
    LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($query);
if (!empty($searchParams)) {
    $stmt->bind_param("ssii", ...array_merge($searchParams, [$perPage, $offset]));
} else {
    $stmt->bind_param("ii", $perPage, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();
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
  <div>
    <a href="admin.php" style="margin-right: 12px; text-decoration: none; color: #555;">管理パネル</a>
    <a href="users.php" style="margin-right: 12px; text-decoration: none; color: #555;">ユーザー管理</a>
    <a href="logout.php" class="logout">ログアウト</a>
  </div>
</header>

<div class="main">
  <h1>ユーザーリスト</h1>
  <div class="sub">登録済みユーザー一覧（検索・管理機能）</div>

  <div class="search-area">
    <form method="get" class="search-box" style="display: flex; gap: 8px;">
      <input type="text" name="search" value="<?= h($search) ?>" placeholder="名前またはメールアドレスで検索...">
      <button type="submit">検索</button>
      <?php if (!empty($search)): ?>
        <a href="user.php" style="padding: 10px 18px; background: #f3f4f6; color: #333; text-decoration: none; border-radius: 10px;">クリア</a>
      <?php endif; ?>
    </form>
    <div class="total">Total: <?= $totalUsers ?></div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>名前</th>
          <th>メールアドレス</th>
          <th>ロール</th>
          <th>ステータス</th>
          <th>登録日</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 32px; color: #999;">
              <?= !empty($search) ? '検索結果が見つかりませんでした' : 'ユーザーが登録されていません' ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <?php
              $initial = strtoupper(substr($u['username'], 0, 1));
              $roleColors = [
                'admin' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                'manager' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                'driver' => ['bg' => '#d1fae5', 'color' => '#065f46'],
                'kitchen' => ['bg' => '#fef3c7', 'color' => '#92400e']
              ];
              $roleColor = $roleColors[$u['role_name']] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
            ?>
            <tr>
              <td class="name">
                <div class="avatar" style="background: <?= $roleColor['bg'] ?>; color: <?= $roleColor['color'] ?>;"><?= $initial ?></div>
                <?= h($u['username']) ?>
              </td>
              <td><?= h($u['email']) ?></td>
              <td>
                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; background: <?= $roleColor['bg'] ?>; color: <?= $roleColor['color'] ?>;">
                  <?= h($u['role_name']) ?>
                </span>
              </td>
              <td>
                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; <?= $u['active'] ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;' ?>">
                  <?= $u['active'] ? '有効' : '無効' ?>
                </span>
              </td>
              <td><?= date('Y/m/d H:i', strtotime($u['created_at'])) ?></td>
              <td class="action">
                <a href="users.php" style="background: #f3f4f6; color: #333; text-decoration: none; padding: 6px 12px; border-radius: 8px; font-size: 14px;">管理</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><</a>
      <?php endif; ?>

      <?php
        $start = max(1, $page - 2);
        $end   = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
      ?>
        <a class="<?= $i === $page ? 'active' : '' ?>" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">></a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
