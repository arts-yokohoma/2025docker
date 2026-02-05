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
  <link rel="stylesheet" href="css/receipt.css">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
              <td>スモールピザ (20cm)</td>
              <td><?php echo $order['small_qty']; ?></td>
              <td>¥<?php echo number_format($order['small_price']); ?></td>
              <td>¥<?php echo number_format($small_total); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($order['medium_qty'] > 0): ?>
            <tr>
              <td>ミディアムピザ (30cm)</td>
              <td><?php echo $order['medium_qty']; ?></td>
              <td>¥<?php echo number_format($order['medium_price']); ?></td>
              <td>¥<?php echo number_format($medium_total); ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if ($order['large_qty'] > 0): ?>
            <tr>
              <td>ラージピザ (40cm)</td>
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

<script src="js/receipt.js"></script>
</body>
</html>