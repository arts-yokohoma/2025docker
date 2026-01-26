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
$qr_text .= "========================\n";
$qr_text .= "Order ID: {$order['order_id']}\n";
$qr_text .= "Date: {$order['order_date']} {$order['order_time']}\n";
$qr_text .= "Status: Confirmed\n";
$qr_text .= "\n";
$qr_text .= "CUSTOMER:\n";
$qr_text .= "Name: {$order['customer_name']}\n";
$qr_text .= "Phone: {$order['customer_phone']}\n";
$qr_text .= "Address: {$order['customer_address']}\n";
$qr_text .= "\n";
$qr_text .= "ORDER DETAILS:\n";

if ($order['small_qty'] > 0) {
    $qr_text .= "Small Pizza (20cm) x{$order['small_qty']} - ¥{$order['small_price']} each\n";
}
if ($order['medium_qty'] > 0) {
    $qr_text .= "Medium Pizza (30cm) x{$order['medium_qty']} - ¥{$order['medium_price']} each\n";
}
if ($order['large_qty'] > 0) {
    $qr_text .= "Large Pizza (40cm) x{$order['large_qty']} - ¥{$order['large_price']} each\n";
}

$qr_text .= "\n";
$qr_text .= "TOTAL AMOUNT:\n";
$qr_text .= "¥" . number_format($order['total_amount']) . "\n";
$qr_text .= "\n";
$qr_text .= "DELIVERY TIME:\n";
$qr_text .= "30-45 minutes\n";
$qr_text .= "\n";
$qr_text .= "CONTACT:\n";
$qr_text .= "📞 03-1234-5678\n";
$qr_text .= "⏰ 10:00-23:00\n";
$qr_text .= "\n";
$qr_text .= "Thank you for your order!";

// Generate QR code image using PHP GD
function generateQRCodeImage($text) {
    // Create image dimensions
    $width = 200;
    $height = 200;
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $white = imagecolorallocate($image, 255, 255, 255);
    $brown = imagecolorallocate($image, 209, 151, 88); // Pizza brown
    $dark_brown = imagecolorallocate($image, 180, 130, 70);
    $yellow = imagecolorallocate($image, 255, 204, 0); // Cheese yellow
    $black = imagecolorallocate($image, 0, 0, 0);
    
    // Fill background
    imagefill($image, 0, 0, $white);
    
    // Draw border
    imagerectangle($image, 5, 5, $width-5, $height-5, $brown);
    
    // Draw pizza icon in center
    $center_x = $width / 2;
    $center_y = $height / 2 - 10;
    
    // Pizza base (circle)
    imagefilledellipse($image, $center_x, $center_y, 80, 80, $brown);
    imageellipse($image, $center_x, $center_y, 80, 80, $dark_brown);
    
    // Cheese dots (toppings)
    for ($i = 0; $i < 8; $i++) {
        $angle = (2 * M_PI / 8) * $i;
        $x = $center_x + cos($angle) * 25;
        $y = $center_y + sin($angle) * 25;
        imagefilledellipse($image, $x, $y, 10, 10, $yellow);
    }
    
    // Text at bottom
    imagestring($image, 4, $center_x - 45, $center_y + 50, "PIZZA MATCH", $black);
    imagestring($image, 3, $center_x - 40, $center_y + 70, "Order Receipt", $black);
    
    // Add order ID
    $order_id_short = substr($order['order_id'], 0, 12);
    imagestring($image, 2, $center_x - 30, $center_y + 90, $order_id_short, $dark_brown);
    
    // Capture image as base64
    ob_start();
    imagepng($image);
    $image_data = ob_get_clean();
    imagedestroy($image);
    
    return 'data:image/png;base64,' . base64_encode($image_data);
}

// Generate the QR code image
$qr_code_image = generateQRCodeImage($qr_text);

// Create receipt URL
$receipt_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
             . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

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
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* QR code specific styles */
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
      margin: 0 auto;
    }
    
    .qr-text-info {
      margin-top: 15px;
      padding: 15px;
      background: #f8f9fa;
      border-radius: 8px;
      text-align: left;
    }
    
    .qr-text-info h4 {
      color: #d19758;
      margin-top: 0;
      margin-bottom: 10px;
    }
    
    .qr-info-item {
      margin: 8px 0;
      font-size: 14px;
      display: flex;
    }
    
    .qr-info-label {
      font-weight: bold;
      min-width: 80px;
      color: #666;
    }
    
    .qr-info-value {
      flex: 1;
      color: #333;
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
      width: 300px;
      flex-shrink: 0;
    }
    
    @media (max-width: 992px) {
      .receipt-content {
        flex-direction: column;
      }
      
      .receipt-sidebar {
        width: 100%;
      }
    }
    
    /* QR scan note */
    .qr-scan-note {
      background: #e6f7ff;
      padding: 10px;
      border-radius: 5px;
      margin-top: 10px;
      font-size: 12px;
      color: #0066cc;
      text-align: center;
    }
    
    .qr-scan-note i {
      margin-right: 5px;
    }
    
    /* Print optimizations */
    @media print {
      .receipt-sidebar {
        display: block !important;
        page-break-inside: avoid;
      }
      
      .qr-code-wrapper {
        border: 1px solid #ccc;
        box-shadow: none;
      }
      
      .action-buttons {
        display: none !important;
      }
    }
    
    /* Status box styling */
    .status-box {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
      border-left: 4px solid #28a745;
    }
    
    .status {
      padding: 8px 12px;
      background: #28a745;
      color: white;
      border-radius: 5px;
      display: inline-block;
      font-weight: bold;
    }
    
    .status-note {
      margin-top: 10px;
      font-size: 14px;
      color: #666;
    }
    
    .contact-box {
      background: #e6f7ff;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
    }
    
    .contact-box h3 {
      margin-top: 0;
      color: #007bff;
    }
    
    /* Action buttons */
    .action-buttons {
      display: flex;
      gap: 10px;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    
    .print-btn, .new-order-btn, .home-btn {
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
    
    .print-btn {
      background: #28a745;
      color: white;
    }
    
    .print-btn:hover {
      background: #218838;
    }
    
    .new-order-btn {
      background: #ffc107;
      color: #212529;
    }
    
    .new-order-btn:hover {
      background: #e0a800;
    }
    
    .home-btn {
      background: #6c757d;
      color: white;
    }
    
    .home-btn:hover {
      background: #5a6268;
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
      
      <!-- Customer info -->
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
      
      <!-- Order items -->
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
      
      <!-- Action buttons -->
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
        <h3><i class="fas fa-qrcode"></i> デジタル領収書</h3>
        <div class="qr-code-wrapper">
          <!-- QR code image generated by PHP -->
          <img src="<?php echo $qr_code_image; ?>" alt="QR Code" class="qr-code-image">
        </div>
        
        <div class="qr-scan-note">
          <i class="fas fa-mobile-alt"></i> この画像を保存して共有できます
        </div>
        
        <!-- QR Code Information Preview -->
        <div class="qr-text-info">
          <h4>領収書情報:</h4>
          <div class="qr-info-item">
            <span class="qr-info-label">店舗:</span>
            <span class="qr-info-value">Pizza Match</span>
          </div>
          <div class="qr-info-item">
            <span class="qr-info-label">注文番号:</span>
            <span class="qr-info-value"><?php echo htmlspecialchars($order['order_id']); ?></span>
          </div>
          <div class="qr-info-item">
            <span class="qr-info-label">お名前:</span>
            <span class="qr-info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
          </div>
          <div class="qr-info-item">
            <span class="qr-info-label">合計金額:</span>
            <span class="qr-info-value">¥<?php echo number_format($order['total_amount']); ?></span>
          </div>
          <div class="qr-info-item">
            <span class="qr-info-label">ステータス:</span>
            <span class="qr-info-value">確認済み</span>
          </div>
        </div>
      </div>
      
      <!-- Order Status -->
      <div class="status-box">
        <h3>注文ステータス</h3>
        <div class="status">
          <i class="fas fa-check-circle"></i> 確認済み
        </div>
        <p class="status-note">配達準備中です</p>
      </div>
      
      <!-- Contact Info -->
      <div class="contact-box">
        <h3>お問い合わせ</h3>
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
    const buttons = document.querySelector('.action-buttons');
    if (buttons) buttons.style.display = 'none';
});

window.addEventListener('afterprint', function() {
    const buttons = document.querySelector('.action-buttons');
    if (buttons) buttons.style.display = 'flex';
});

// Show QR code text on click (for debugging)
document.querySelector('.qr-code-image').addEventListener('click', function() {
    alert('このQRコードには次の情報が含まれています:\n\n' + 
          '<?php echo str_replace(["\n", "'"], ["\\n", "\\'"], $qr_text); ?>');
});
</script>
</body>
</html>