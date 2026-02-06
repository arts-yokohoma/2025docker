<?php
require_once __DIR__ . '/auth.php';
// Orders: admin, manager, kitchen, delivery can view
requireRoles(['admin', 'manager', 'kitchen', 'delivery']);

date_default_timezone_set('Asia/Tokyo');

include __DIR__ . "/mock_orders.php";

// Count orders by status and by date (今日/明日/明後日) in Asia/Tokyo
$statusCounts = ['New' => 0, 'In Progress' => 0, 'Completed' => 0, 'Canceled' => 0];
$dateCounts = ['today' => 0, 'tomorrow' => 0, 'dayafter' => 0];
$now = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
$todayStr = $now->format('Y-m-d');
$tomorrowStr = (clone $now)->modify('+1 day')->format('Y-m-d');
$dayafterStr = (clone $now)->modify('+2 days')->format('Y-m-d');

foreach ($orders as $order) {
    $status = $order["status"];
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
    // Prefer delivery_time (if scheduled) for date filters; fall back to create date
    $dateSource = $order['delivery_time'] ?? $order['date'];
    $orderDate = substr($dateSource, 0, 10);
    if ($orderDate === $todayStr) {
        $dateCounts['today']++;
    } elseif ($orderDate === $tomorrowStr) {
        $dateCounts['tomorrow']++;
    } elseif ($orderDate === $dayafterStr) {
        $dateCounts['dayafter']++;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>注文ページ</title>
  <!-- Material Symbols for icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <!-- Cache-busted stylesheet to force reload after CSS changes -->
  <link rel="stylesheet" href="css/orders.css?v=<?= filemtime(__DIR__ . '/css/orders.css') ?>">
  <!-- Temporary debug styles: force completed styles to confirm CSS is applied (remove after verification) -->
  <style>
    .status.completed { background: #e7f3ff !important; color: #0a58d4 !important; border: 1px solid #bfd9ff !important; }
    .tab-btn[data-status="Completed"].active { color: #28a745 !important; }
    .tab-btn[data-status="Completed"].active::after { background: #28a745 !important; }
  </style>
</head>
<body>
<header class="orders-page-header">
    <img src="../assets/image/logo.png" alt="Pizza Mach" class="orders-page-logo">
    <span class="orders-page-title">注文ページ</span>
    <a href="admin.php" class="orders-page-back">戻る</a>
    <form method="post" style="margin: 0;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="orders-page-logout">ログアウト</button>
    </form>
</header>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<div class="filter-container">
  <div class="filter-tabs">
    <button class="tab-btn active" data-status="all" onclick="filterByStatus(this)">すべて <span class="count"><?= count($orders) ?></span></button>
    <button class="tab-btn" data-status="New" onclick="filterByStatus(this)">🔵 New <span class="count"><?= $statusCounts['New'] ?></span></button>
    <button class="tab-btn" data-status="In Progress" onclick="filterByStatus(this)"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#0000F5"><path d="M177-560q14-36 4.5-64T149-680q-33-40-43.5-75.5T102-840h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78Zm160 0q14-36 5-64t-32-56q-33-40-44-75.5t-4-84.5h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78Zm160 0q14-36 5-64t-32-56q-33-40-44-75.5t-4-84.5h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78ZM200-160q-50 0-85-35t-35-85v-200h561q5-34 27-59.5t54-36.5l185-62 25 76-185 62q-12 4-19.5 14.5T720-462v182q0 50-35 85t-85 35H200Zm0-80h400q17 0 28.5-11.5T640-280v-120H160v120q0 17 11.5 28.5T200-240Zm200-80Z"/></svg>In Progress <span class="count"><?= $statusCounts['In Progress'] ?></span></button>
    <button class="tab-btn" data-status="Completed" onclick="filterByStatus(this)">✅ Completed <span class="count"><?= $statusCounts['Completed'] ?></span></button>
    <button class="tab-btn" data-status="Canceled" onclick="filterByStatus(this)">❌ Canceled <span class="count"><?= $statusCounts['Canceled'] ?></span></button>

    <!-- Date filters -->
    <button class="tab-btn" data-date="today" data-target-date="<?= $todayStr ?>" onclick="filterByStatus(this)">今日 <span class="count"><?= $dateCounts['today'] ?></span></button>
    <button class="tab-btn" data-date="tomorrow" data-target-date="<?= $tomorrowStr ?>" onclick="filterByStatus(this)">明日 <span class="count"><?= $dateCounts['tomorrow'] ?></span></button>
    <button class="tab-btn" data-date="dayafter" data-target-date="<?= $dayafterStr ?>" onclick="filterByStatus(this)">明後日 <span class="count"><?= $dateCounts['dayafter'] ?></span></button>
  </div>
</div>

<table class="orders">
  <thead>
    <tr>
      <th>注文番号</th>
      <th>配達時間</th>
      <th>顧客名</th>
      <th>注文詳細</th>
      <th>合計金額</th>
      <th>ステータス</th>
      <th>操作</th>
      <th>編集</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($orders as $order):
      $isIncomplete = $order["status"] !== "Completed" && $order["status"] !== "Canceled";
      $nokori = $order["nokori_label"] ?? '';
    ?>
      <tr class="visible" data-status="<?= htmlspecialchars($order["status"]) ?>" data-date="<?= htmlspecialchars($order["delivery_time"] ?? $order["date"]) ?>">
        <td>#<?= $order["id"] ?></td>
        <td class="td-delivery">
          <span class="expected-time"><?= htmlspecialchars($order["expected_delivery"] ?? ($order["delivery_time"] ?? $order["date"])) ?></span>
          <?php if ($nokori !== ''): ?>
            <br><span class="nokori <?= $isIncomplete ? 'nokori-red' : '' ?>"><?= htmlspecialchars($nokori) ?></span>
          <?php endif; ?>
        </td>
        <td>
          <?= $order["name"] ?><br>
          <small><?= $order["phone"] ?></small><br>
          <small><?= $order["address"] ?></small>
        </td>
        <td><?= $order["item"] ?></td>
        <td>¥<?= number_format($order["total_amount"]) ?></td>
        <td>
          <span class="status <?= strtolower(str_replace(' ', '-', $order["status"])) ?>">
            <?= $order["status"] ?>
          </span>
        </td>
        <td>
          <?php if ($order["status"] !== "Completed" && $order["status"] !== "Canceled"): ?>
            <button class="btn red cancel-btn" data-id="<?= $order["id"] ?>" data-status="<?= $order["status"] ?>" title="キャンセル"><span class="material-symbols-outlined">close</span></button>

            <?php if ($order["status"] === "New"): ?>
              <button class="btn blue status-btn" data-id="<?= $order["id"] ?>" data-next="In Progress"><span class="material-symbols-outlined">local_pizza</span> 調理開始</button>
            <?php elseif ($order["status"] === "In Progress"): ?>
              <button class="btn yellow status-btn" data-id="<?= $order["id"] ?>" data-next="Completed">完了にする</button>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($order["status"] !== "Completed" && $order["status"] !== "Canceled"): ?>
            <button class="btn edit-btn" data-id="<?= $order["id"] ?>"><span class="material-symbols-outlined">edit</span> 編集</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<script src="ordersfunction.js"></script>
</body>
</html>
