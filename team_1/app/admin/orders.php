<?php include __DIR__ . "/mock_orders.php"; 

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
  <link rel="stylesheet" href="css/orders.css?v=<?= filemtime(__DIR__ . '/css/orders.css') ?>">
  <style>
    .status.completed { background: #e7f3ff !important; color: #0a58d4 !important; border: 1px solid #bfd9ff !important; }
    .tab-btn[data-status="Completed"].active { color: #28a745 !important; }
    .tab-btn[data-status="Completed"].active::after { background: #28a745 !important; }
  </style>
  <script>
    function filterByStatus(btn) {
      const status = btn.getAttribute('data-status');
      console.log('Button clicked, filtering by:', status);
      
      // Get all rows and buttons
      const rows = document.querySelectorAll('tbody tr');
      const buttons = document.querySelectorAll('.tab-btn');
      
      console.log('Total rows:', rows.length);
      
      // Remove active class from all buttons
      buttons.forEach(b => {
        b.classList.remove('active');
      });
      
      // Add active class to clicked button
      btn.classList.add('active');
      console.log('Active button set');
      
      // Filter and show/hide rows
      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        console.log('Row status:', rowStatus, 'Filter:', status);
        
        if (status === 'all') {
          // Show all rows
          row.style.display = 'table-row';
          console.log('Showing row (all)');
        } else if (rowStatus === status) {
          // Show matching rows
          row.style.display = 'table-row';
          console.log('Showing row (match)');
        } else {
          // Hide non-matching rows
          row.style.display = 'none';
          console.log('Hiding row');
        }
      });
      
      console.log('Filter complete');
    }
  </script>
</head>
<body>

<h2 class="page-title">注文ページ</h2>
<div class="filter-container">
  <div class="filter-tabs">
    <button class="tab-btn active" data-status="all" onclick="filterByStatus(this)">すべて <span class="count"><?= count($orders) ?></span></button>
    <button class="tab-btn" data-status="New" onclick="filterByStatus(this)">🔵 New <span class="count"><?= $statusCounts['New'] ?></span></button>
    <button class="tab-btn" data-status="In Progress" onclick="filterByStatus(this)"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#0000F5"><path d="M177-560q14-36 4.5-64T149-680q-33-40-43.5-75.5T102-840h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78Zm160 0q14-36 5-64t-32-56q-33-40-44-75.5t-4-84.5h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78Zm160 0q14-36 5-64t-32-56q-33-40-44-75.5t-4-84.5h78q-8 38-2.5 62t28.5 52q38 46 48.5 81.5t.5 84.5h-78ZM200-160q-50 0-85-35t-35-85v-200h561q5-34 27-59.5t54-36.5l185-62 25 76-185 62q-12 4-19.5 14.5T720-462v182q0 50-35 85t-85 35H200Zm0-80h400q17 0 28.5-11.5T640-280v-120H160v120q0 17 11.5 28.5T200-240Zm200-80Z"/></svg>In Progress <span class="count"><?= $statusCounts['In Progress'] ?></span></button>
    <button class="tab-btn" data-status="Completed" onclick="filterByStatus(this)">✅ Completed <span class="count"><?= $statusCounts['Completed'] ?></span></button>
    <button class="tab-btn" data-status="Canceled" onclick="filterByStatus(this)">❌ Canceled <span class="count"><?= $statusCounts['Canceled'] ?></span></button>
  </div>
</div>

<table class="orders">
  <thead>
    <tr>
      <th>注文番号</th>
      <th>日時</th>
      <th>顧客名</th>
      <th>注文詳細</th>
      <th>合計金額</th>
      <th>ステータス</th>
      <th class="text-center">操作</th>
      <th class="text-center">編集</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($orders as $order): ?>
      <tr class="visible" data-status="<?= htmlspecialchars($order["status"]) ?>">
        <td>#<?= $order["id"] ?></td>
        <td><?= $order["date"] ?></td>
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
        <td class="text-center">
          <?php if ($order["status"] !== "Completed" && $order["status"] !== "Canceled"): ?>
            <button class="btn red cancel-btn" data-id="<?= $order["id"] ?>" data-status="<?= $order["status"] ?>" title="キャンセル">❌</button>

            <?php if ($order["status"] === "New"): ?>
              <button class="btn blue status-btn" data-id="<?= $order["id"] ?>" data-next="In Progress">調理開始</button>
            <?php elseif ($order["status"] === "In Progress"): ?>
              <button class="btn yellow status-btn" data-id="<?= $order["id"] ?>" data-next="Completed">完了にする</button>
            <?php endif; ?>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if ($order["status"] !== "Completed" && $order["status"] !== "Canceled"): ?>
            <button class="btn edit-btn" data-id="<?= $order["id"] ?>">編集</button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<script src="ordersfunction.js"></script>
</body>
</html>
