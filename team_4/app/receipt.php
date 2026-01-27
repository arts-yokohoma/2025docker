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

// Create clean text representation for QR code
$qr_text = "🍕 PIZZA MATCH RECEIPT 🍕\n";
$qr_text .= "Order ID: {$order['order_id']}\n";
$qr_text .= "Date: {$order['order_date']} {$order['order_time']}\n";
$qr_text .= "Customer: {$order['customer_name']}\n";
$qr_text .= "Total: ¥" . number_format($order['total_amount']) . "\n";
$qr_text .= "Status: Confirmed\n";

// Generate QR code URL using Google Charts API (free, reliable)
$qr_data = urlencode($qr_text);
$qr_size = "180x180";
$qr_color = "d19758"; // Pizza brown color

// Generate QR code image URL using Google Charts API
$qr_code_url = "https://chart.googleapis.com/chart?cht=qr&chs={$qr_size}&chl={$qr_data}&chco={$qr_color}";

// Alternative API if Google fails (QRCode Monkey)
$qr_code_url_alt = "https://api.qrserver.com/v1/create-qr-code/?size={$qr_size}&data={$qr_data}&color={$qr_color}";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>領収書 - Pizza Match</title>
  <link rel="stylesheet" href="css/style.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      background: #f5f5f5;
    }
    
    .receipt-container {
      max-width: 1000px;
      margin: 0 auto;
      background: white;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
      overflow: hidden;
    }
    
    .receipt-header {
      background: linear-gradient(135deg, #d19758, #ffcc00);
      color: white;
      padding: 30px;
      text-align: center;
    }
    
    .receipt-header h1 {
      margin: 0;
      font-size: 28px;
    }
    
    .receipt-content {
      display: flex;
      min-height: 500px;
    }
    
    @media (max-width: 768px) {
      .receipt-content {
        flex-direction: column;
      }
    }
    
    .receipt-details {
      flex: 1;
      padding: 30px;
    }
    
    .receipt-sidebar {
      width: 300px;
      background: #f8f9fa;
      padding: 30px;
      border-left: 3px solid #d19758;
    }
    
    .section {
      margin-bottom: 30px;
    }
    
    .section h2 {
      color: #d19758;
      border-bottom: 2px solid #eee;
      padding-bottom: 10px;
      margin-top: 0;
    }
    
    .info-row {
      display: flex;
      margin: 10px 0;
      padding: 8px 0;
      border-bottom: 1px dotted #eee;
    }
    
    .info-label {
      font-weight: bold;
      width: 150px;
      color: #666;
    }
    
    .info-value {
      flex: 1;
      color: #333;
    }
    
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
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    
    .qr-code-image {
      width: 180px;
      height: 180px;
      display: block;
      border-radius: 5px;
    }
    
    .qr-info-box {
      margin-top: 15px;
      padding: 15px;
      background: white;
      border-radius: 8px;
      text-align: left;
    }
    
    .status-box {
      background: #e6f7ff;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
      border-left: 4px solid #28a745;
    }
    
    .status {
      display: inline-block;
      background: #28a745;
      color: white;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: bold;
    }
    
    .contact-box {
      background: #fff3cd;
      padding: 20px;
      border-radius: 8px;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    
    button, .btn {
      padding: 12px 20px;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s;
    }
    
    button:hover, .btn:hover {
      opacity: 0.9;
      transform: translateY(-2px);
    }
    
    .print-btn {
      background: #28a745;
      color: white;
    }
    
    .new-order-btn {
      background: #ffc107;
      color: #212529;
    }
    
    .home-btn {
      background: #6c757d;
      color: white;
    }
    
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }
    
    th {
      background: #d19758;
      color: white;
      padding: 12px;
      text-align: left;
    }
    
    td {
      padding: 10px;
      border-bottom: 1px solid #eee;
    }
    
    tfoot td {
      font-weight: bold;
      background: #f8f9fa;
    }
    
    .special-instructions {
      background: #fff3cd;
      padding: 15px;
      border-radius: 5px;
      margin-top: 20px;
    }
    
    .qr-fallback {
      display: none;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      margin-top: 10px;
      text-align: center;
    }
    
    .qr-fallback .code {
      font-family: monospace;
      background: white;
      padding: 10px;
      border-radius: 5px;
      margin: 10px 0;
      font-size: 14px;
      word-break: break-all;
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
      <!-- Order info -->
      <div class="section">
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
      
      <!-- Customer info -->
      <div class="section">
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
      
      <!-- Order items -->
      <div class="section">
        <h2>注文内容</h2>
        <table>
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
              <td colspan="3" style="text-align: right;">合計金額:</td>
              <td style="color: #d19758; font-size: 20px;">
                ¥<?php echo number_format($order['total_amount']); ?>
              </td>
            </tr>
          </tfoot>
        </table>
        
        <?php if (!empty($order['instructions'])): ?>
        <div class="special-instructions">
          <h4 style="margin-top: 0; color: #856404;">特別なご要望:</h4>
          <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($order['instructions'])); ?></p>
        </div>
        <?php endif; ?>
      </div>
      
      <!-- Action buttons -->
      <div class="action-buttons">
        <button class="print-btn" onclick="window.print()">
          <i class="fas fa-print"></i> 領収書を印刷
        </button>
        <a href="order.php" class="btn new-order-btn">
          <i class="fas fa-pizza-slice"></i> 新規注文
        </a>
        <a href="index.php" class="btn home-btn">
          <i class="fas fa-home"></i> ホームに戻る
        </a>
      </div>
    </div>
    
    <div class="receipt-sidebar">
      <!-- QR Code Section -->
      <div class="qr-code-container">
        <h3 style="text-align: center; color: #d19758; margin-top: 0;">
          <i class="fas fa-qrcode"></i> デジタル領収書
        </h3>
        
        <div class="qr-code-wrapper">
          <!-- QR code image from API -->
          <img src="<?php echo $qr_code_url; ?>" 
               alt="QR Code" 
               class="qr-code-image"
               id="qrCodeImage"
               onerror="this.onerror=null; this.src='<?php echo $qr_code_url_alt; ?>';">
        </div>
        
        <div class="qr-info-box">
          <h4 style="margin-top: 0; color: #666;">QRコード情報:</h4>
          <div style="margin: 8px 0; font-size: 14px;">
            <span style="font-weight: bold; color: #666;">注文番号:</span>
            <span><?php echo htmlspecialchars($order['order_id']); ?></span>
          </div>
          <div style="margin: 8px 0; font-size: 14px;">
            <span style="font-weight: bold; color: #666;">お名前:</span>
            <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
          </div>
          <div style="margin: 8px 0; font-size: 14px;">
            <span style="font-weight: bold; color: #666;">合計金額:</span>
            <span style="color: #d19758; font-weight: bold;">¥<?php echo number_format($order['total_amount']); ?></span>
          </div>
          <div style="margin: 8px 0; font-size: 14px;">
            <span style="font-weight: bold; color: #666;">ステータス:</span>
            <span>確認済み</span>
          </div>
        </div>
        
        <!-- Fallback section (hidden by default) -->
        <div class="qr-fallback" id="qrFallback">
          <p style="color: #666; font-size: 14px;">
            <i class="fas fa-exclamation-triangle"></i> QRコードが表示できない場合は、以下のコードを使用してください:
          </p>
          <div class="code">
            <?php echo htmlspecialchars($order['order_id']); ?>
          </div>
          <button onclick="copyOrderCode()" style="background: #d19758; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 14px;">
            <i class="fas fa-copy"></i> コードをコピー
          </button>
        </div>
      </div>
      
      <!-- Order Status -->
      <div class="status-box">
        <h3 style="margin-top: 0;">注文ステータス</h3>
        <div class="status">
          <i class="fas fa-check-circle"></i> 確認済み
        </div>
        <p style="margin-top: 10px; font-size: 14px; color: #666;">
          配達準備中です。お支払いは商品到着時にお願いします。
        </p>
      </div>
      
      <!-- Contact Info -->
      <div class="contact-box">
        <h3 style="margin-top: 0;">お問い合わせ</h3>
        <p><i class="fas fa-phone"></i> 03-1234-5678</p>
        <p><i class="fas fa-clock"></i> 10:00-23:00</p>
        <p><i class="fas fa-map-marker-alt"></i> 東京都渋谷区...</p>
      </div>
    </div>
  </div>
</div>

<script>
// Print optimization
window.addEventListener('beforeprint', function() {
    document.querySelector('.action-buttons').style.display = 'none';
});

window.addEventListener('afterprint', function() {
    document.querySelector('.action-buttons').style.display = 'flex';
});

// Check if QR code loads successfully
window.addEventListener('load', function() {
    const qrImage = document.getElementById('qrCodeImage');
    
    // Check if image loaded successfully
    setTimeout(function() {
        if (!qrImage.complete || qrImage.naturalHeight === 0) {
            // QR code failed to load, show fallback
            document.getElementById('qrFallback').style.display = 'block';
        }
    }, 1000);
});

// Copy order code to clipboard
function copyOrderCode() {
    const code = "<?php echo htmlspecialchars($order['order_id']); ?>";
    
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(function() {
            alert('注文コードをコピーしました: ' + code);
        }).catch(function() {
            fallbackCopy(code);
        });
    } else {
        fallbackCopy(code);
    }
}

function fallbackCopy(code) {
    const textArea = document.createElement('textarea');
    textArea.value = code;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            alert('注文コードをコピーしました: ' + code);
        } else {
            alert('コピーに失敗しました。手動でコピーしてください: ' + code);
        }
    } catch (err) {
        alert('コピーに失敗しました。手動でコピーしてください: ' + code);
    }
    
    document.body.removeChild(textArea);
}

// Right-click to save QR code
document.getElementById('qrCodeImage').addEventListener('contextmenu', function(e) {
    alert('QRコードを右クリックして「名前を付けて画像を保存」を選択してください。');
});
</script>
</body>
</html>