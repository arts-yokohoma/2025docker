<?php
session_start();
require_once 'db/db.php';

// Initialize variables
$order = [];
$order_found = false;

// Try to get order from database first
if (isset($_GET['order_id']) && isset($pdo)) {
    $order_id = intval($_GET['order_id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $db_order = $stmt->fetch();
        
        if ($db_order) {
            $order_found = true;
            $order = [
                'db_id' => $db_order['id'],
                'order_id' => $db_order['order_number'] ?? 'PH-' . str_pad($db_order['id'], 6, '0', STR_PAD_LEFT),
                'order_date' => date('Y/m/d', strtotime($db_order['order_date'])),
                'order_time' => date('H:i', strtotime($db_order['order_date'])),
                'customer_name' => htmlspecialchars($db_order['customer_name']),
                'customer_phone' => htmlspecialchars($db_order['customer_phone']),
                'customer_email' => htmlspecialchars($db_order['customer_email'] ?? ''),
                'customer_address' => htmlspecialchars($db_order['customer_address']),
                'small_qty' => $db_order['small_quantity'],
                'medium_qty' => $db_order['medium_quantity'],
                'large_qty' => $db_order['large_quantity'],
                'small_price' => $db_order['small_price'],
                'medium_price' => $db_order['medium_price'],
                'large_price' => $db_order['large_price'],
                'total_amount' => $db_order['total_amount'],
                'instructions' => htmlspecialchars($db_order['special_instructions'] ?? ''),
                'status' => $db_order['status']
            ];
        }
    } catch (Exception $e) {
        // Continue to check session
    }
}

// If not from database, check session
if (!$order_found && isset($_SESSION['order_data'])) {
    $order_found = true;
    $order = $_SESSION['order_data'];
    
    // Ensure all required fields exist
    $order = array_merge([
        'order_id' => 'PH-' . date('YmdHis') . rand(1000, 9999),
        'order_date' => date('Y/m/d'),
        'order_time' => date('H:i'),
        'customer_name' => '',
        'customer_phone' => '',
        'customer_email' => '',
        'customer_address' => '',
        'small_qty' => 0,
        'medium_qty' => 0,
        'large_qty' => 0,
        'small_price' => 0,
        'medium_price' => 0,
        'large_price' => 0,
        'total_amount' => 0,
        'instructions' => '',
        'status' => 'confirmed'
    ], $order);
}

// If no order found, redirect to order page
if (!$order_found) {
    header("Location: order.php");
    exit;
}

// Calculate item totals
$small_total = $order['small_qty'] * $order['small_price'];
$medium_total = $order['medium_qty'] * $order['medium_price'];
$large_total = $order['large_qty'] * $order['large_price'];

// Create receipt URL for QR code
$receipt_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
             . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// If we have database ID, create direct link
if (isset($order['db_id'])) {
    $receipt_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                 . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) 
                 . "/receipt.php?order_id=" . $order['db_id'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>領収書 - Pizza Match</title>
  <link rel="stylesheet" href="css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
  <style>
    /* QR code specific styles - smaller and positioned */
    .qr-code-container {
      text-align: center;
      margin: 20px 0;
    }
    
    .qr-code-wrapper {
      display: inline-block;
      padding: 15px;
      background: white;
      border-radius: 10px;
      border: 2px solid #d19758;
    }
    
    #qr-code {
      width: 150px;
      height: 150px;
    }
    
    .qr-text {
      font-size: 12px;
      color: #666;
      margin-top: 10px;
      max-width: 150px;
    }
    
    /* Receipt layout adjustments */
    .receipt-content {
      display: flex;
      gap: 40px;
      align-items: flex-start;
    }
    
    .receipt-details {
      flex: 1;
    }
    
    .receipt-sidebar {
      width: 250px;
      flex-shrink: 0;
    }
    
    @media (max-width: 768px) {
      .receipt-content {
        flex-direction: column;
      }
      
      .receipt-sidebar {
        width: 100%;
      }
    }
  </style>
</head>
<body>
<div class="receipt-container">
  <div class="receipt-header">
    <h1>🍕 ご注文ありがとうございます！</h1>
    <p>ご注文が確定しました。領収書をお受け取りください。</p>
  </div>
  
  <div class="receipt-content">
    <div class="receipt-details">
      <!-- Order info, customer info, and order items from your existing CSS -->
      <div class="order-info">
        <h2>注文情報</h2>
        <div class="info-row">
          <span class="info-label">注文番号:</span>
          <span class="info-value"><?php echo htmlspecialchars($order['order_id']); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">注文日時:</span>
          <span class="info-value"><?php echo htmlspecialchars($order['order_date'] . ' ' . $order['order_time']); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">配達予定:</span>
          <span class="info-value">30-45分以内</span>
        </div>
      </div>
      
      <div class="customer-info">
        <h2>お客様情報</h2>
        <div class="info-row">
          <span class="info-label">お名前:</span>
          <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
        </div>
        <div class="info-row">
          <span class="info-label">電話番号:</span>
          <span class="info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
        </div>
        <?php if (!empty($order['customer_email'])): ?>
        <div class="info-row">
          <span class="info-label">メールアドレス:</span>
          <span class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
          <span class="info-label">配達先住所:</span>
          <span class="info-value"><?php echo htmlspecialchars($order['customer_address']); ?></span>
        </div>
      </div>
      
      <div class="order-items">
        <h2>注文内容</h2>
        <table class="order-table">
          <thead>
            <tr>
              <th>商品</th>
              <th>数量</th>
              <th>単価</th>
              <th>小計</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($order['small_qty'] > 0): ?>
            <tr>
              <td>Small Pizza (20cm)</td>
              <td><?php echo $order['small_qty']; ?></td>
              <td>¥<?php echo number_format($order['small_price']); ?></td>
              <td>¥<?php echo number_format($small_total); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($order['medium_qty'] > 0): ?>
            <tr>
              <td>Medium Pizza (30cm)</td>
              <td><?php echo $order['medium_qty']; ?></td>
              <td>¥<?php echo number_format($order['medium_price']); ?></td>
              <td>¥<?php echo number_format($medium_total); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($order['large_qty'] > 0): ?>
            <tr>
              <td>Large Pizza (40cm)</td>
              <td><?php echo $order['large_qty']; ?></td>
              <td>¥<?php echo number_format($order['large_price']); ?></td>
              <td>¥<?php echo number_format($large_total); ?></td>
            </tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3" style="text-align: right; font-weight: bold;">合計金額:</td>
              <td style="font-weight: bold; color: #d19758; font-size: 20px;">
                ¥<?php echo number_format($order['total_amount']); ?>
              </td>
            </tr>
          </tfoot>
        </table>
        
        <?php if (!empty($order['instructions'])): ?>
        <div class="special-instructions">
          <h4>特別なご要望:</h4>
          <p><?php echo nl2br(htmlspecialchars($order['instructions'])); ?></p>
        </div>
        <?php endif; ?>
      </div>
      
      <div class="action-buttons">
        <button class="print-btn" onclick="window.print()">
          <i class="fas fa-print"></i> 領収書を印刷
        </button>
        <a href="order.php" class="new-order-btn">
          <i class="fas fa-pizza-slice"></i> 新規注文
        </a>
        <a href="index.php" class="home-btn">
          <i class="fas fa-home"></i> ホームに戻る
        </a>
      </div>
    </div>
    
    <div class="receipt-sidebar">
      <!-- QR Code Section -->
      <div class="qr-code-container">
        <div class="qr-code-wrapper">
          <div id="qr-code"></div>
        </div>
        <div class="qr-text">
          <p><strong>このQRコードをスキャン</strong></p>
          <p>領収書をPCで表示</p>
        </div>
      </div>
      
      <!-- Order Status -->
      <div class="status-box">
        <h3>注文ステータス</h3>
        <div class="status confirmed">
          <i class="fas fa-check-circle"></i> 確認済み
        </div>
        <p class="status-note">配達準備中です</p>
      </div>
      
      <!-- Contact Info -->
      <div class="contact-box">
        <h3>お問い合わせ</h3>
        <p><i class="fas fa-phone"></i> 03-1234-5678</p>
        <p><i class="fas fa-clock"></i> 10:00-23:00</p>
      </div>
    </div>
  </div>
</div>

<script>
// Generate QR Code with receipt URL
const receiptUrl = "<?php echo htmlspecialchars($receipt_url); ?>";

QRCode.toCanvas(
    document.getElementById('qr-code'),
    receiptUrl,
    {
        width: 150,
        height: 150,
        margin: 1,
        color: {
            dark: '#d19758',
            light: '#ffffff'
        }
    },
    function (error) {
        if (error) {
            console.error('QR Code error:', error);
            // Show text fallback
            document.getElementById('qr-code').innerHTML = 
                '<div style="text-align: center; padding: 20px;">' +
                '<p style="font-size: 12px; word-break: break-all;">' + 
                receiptUrl.substring(0, 30) + '...' +
                '</p>' +
                '</div>';
        }
    }
);

// Print optimization
window.addEventListener('beforeprint', function() {
    document.querySelector('.receipt-sidebar').style.display = 'none';
    document.querySelector('.action-buttons').style.display = 'none';
});

window.addEventListener('afterprint', function() {
    document.querySelector('.receipt-sidebar').style.display = 'block';
    document.querySelector('.action-buttons').style.display = 'flex';
});
</script>
</body>
</html>