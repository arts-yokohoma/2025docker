<?php include "mock_orders.php"; 

// Count orders by status
$statusCounts = ['New' => 0, 'In Progress' => 0, 'Completed' => 0, 'Canceled' => 0];
foreach ($orders as $order) {
    $status = $order["status"];
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>注文ページ</title>
  <link rel="stylesheet" href="css/orders.css">
 
</head>
<body>

<h2>注文ページ</h2>

<div class="filter-tabs">
  <button class="filter-tab active" data-filter="all">
    すべて
    <span class="filter-badge"><?= count($orders) ?></span>
  </button>
  <button class="filter-tab" data-filter="New">
    <span class="status-icon">🔵</span> 新規
    <span class="filter-badge"><?= $statusCounts['New'] ?></span>
  </button>
  <button class="filter-tab" data-filter="In Progress">
    <span class="status-icon"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#0000F5"><path d="M177-560q14-36 4.5-64T149-680q-33-40-43.5-75.5T102-840h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78Zm160 0q14-36 5-64t-32-56q-33-40-44-75.5t-4-84.5h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78Zm160 0q14-36 5-64t-32-56q-33-40-44-75.5t-4-84.5h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78ZM200-160q-50 0-85-35t-35-85v-200h561q5-34 27-59.5t54-36.5l185-62 25 76-185 62q-12 4-19.5 14.5T720-462v182q0 50-35 85t-85 35H200Zm0-80h400q17 0 28.5-11.5T640-280v-120H160v120q0 17 11.5 28.5T200-240Zm200-80Z"/></svg>
  </span> 調理中
    <span class="filter-badge"><?= $statusCounts['In Progress'] ?></span>
  </button>
  <button class="filter-tab" data-filter="Completed">
    <span class="status-icon">✅</span> 完了
    <span class="filter-badge"><?= $statusCounts['Completed'] ?></span>
  </button>
  <button class="filter-tab" data-filter="Canceled">
    <span class="status-icon">❌</span> キャンセル
    <span class="filter-badge"><?= $statusCounts['Canceled'] ?></span>
  </button>
</div>

<table class="orders">
  <thead>
    <tr>
      <th>注文番号</th>
      <th>日時</th>
      <th>顧客名</th>
      <th>注文詳細</th>
      <th>ステータス</th>
      <th>操作</th>
      <th>編集</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($orders as $order): ?>
      <tr class="visible" data-status="<?= htmlspecialchars($order["status"]) ?>">
        <td>#<?= $order["id"] ?></td>
        <td><?= $order["date"] ?></td>
        <td>
          <?= $order["name"] ?><br>
          <small><?= $order["phone"] ?></small>
        </td>
        <td><?= $order["item"] ?></td>
        <td>
          <span class="status <?= strtolower(str_replace(' ', '-', $order["status"])) ?>">
            <?= $order["status"] ?>
          </span>
        </td>
        <td>
          <?php if ($order["status"] !== "Completed"): ?>
            <button class="btn red delete-btn" data-id="<?= $order["id"] ?>"title="削除">🗑</button>

            <?php if ($order["status"] === "New"): ?>
              <button class="btn blue status-btn" data-id="<?= $order["id"] ?>" data-next="In Progress">調理開始</button>
            <?php elseif ($order["status"] === "In Progress"): ?>
              <button class="btn yellow status-btn" data-id="<?= $order["id"] ?>" data-next="Completed">完了にする</button>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($order["status"] !== "Completed"): ?>
            <button class="btn edit-btn" data-id="<?= $order["id"] ?>">編集</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script src="../assets/js/ordersfunction.js"></script>
</body>
</html>
