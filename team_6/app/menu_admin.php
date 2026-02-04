<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db/connect.php';

// =============================
// メニュー一覧取得
// =============================
$menuItems = $db
    ->query("SELECT * FROM menu_items ORDER BY created_at DESC")
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>メニュー管理 - To Pizza Mach</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
  <h1>🍕 メニュー管理</h1>
</header>

<main class="menu-admin-container">
  <a href="menu_edit.php?action=add" class="add-btn">➕ 新しいピザを追加</a>

  <table>
    <thead>
      <tr>
        <th>画像</th>
        <th>ピザ名</th>
        <th>小サイズ</th>
        <th>中サイズ</th>
        <th>大サイズ</th>
        <th>売り切れ</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($menuItems as $item): ?>
      <tr>
        <td>
          <img src="assets/images/<?php echo $item['image']; ?>" width="80">
        </td>
        <td>
          <?php echo htmlspecialchars($item['name']); ?>
        </td>
        <td>
          ¥<?php echo $item['price_s']; ?>
        </td>
        <td>
          ¥<?php echo $item['price_m']; ?>
        </td>
        <td>
          ¥<?php echo $item['price_l']; ?>
        </td>
        <td>
          <?php echo $item['is_sold_out'] ? "✅" : "ー"; ?>
        </td>
        <td>
          <a href="menu_edit.php?action=edit&id=<?php echo $item['id']; ?>">✏ 編集</a> |
          <a href="menu_edit.php?action=delete&id=<?php echo $item['id']; ?>"
            onclick="return confirm('削除しますか？')">🗑 削除</a> |
          <a href="menu_edit.php?action=soldout&id=<?php echo $item['id']; ?>">
            <?php echo $item['is_sold_out'] ? "解除" : "売り切れ"; ?>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

</body>
</html>
