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
  <link rel="stylesheet" href="css/orders.css">
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

<h2>注文ページ</h2>

<div class="filter-container">
  <div class="filter-tabs">
    <button class="tab-btn active" data-status="all" onclick="filterByStatus(this)">すべて <span class="count"><?= count($orders) ?></span></button>
    <button class="tab-btn" data-status="New" onclick="filterByStatus(this)">🔵 New <span class="count"><?= $statusCounts['New'] ?></span></button>
    <button class="tab-btn" data-status="In Progress" onclick="filterByStatus(this)">🔄 In Progress <span class="count"><?= $statusCounts['In Progress'] ?></span></button>
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
<script src="ordersfunction.js"></script>
</body>
</html>
