<?php
// order.php - Updated with database integration
require_once 'db/db.php';

// Get pizza prices from database
$pizza = ['small_price' => 800, 'medium_price' => 1200, 'large_price' => 1500];
$image_url = 'https://images.unsplash.com/photo-1601924638867-3ec62c7e5c79';

if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM pizzas LIMIT 1");
        $db_pizza = $stmt->fetch();
        if ($db_pizza) {
            $pizza['small_price'] = $db_pizza['small_price'];
            $pizza['medium_price'] = $db_pizza['medium_price'];
            $pizza['large_price'] = $db_pizza['large_price'];
            if (!empty($db_pizza['image_url'])) {
                $image_url = $db_pizza['image_url'];
            }
        }
    } catch (Exception $e) {
        // Use default prices if database fails
    }
}

// Calculate initial total (matching your hardcoded 1286)
$initial_total = ($pizza['small_price'] * 1) + ($pizza['medium_price'] * 2) + ($pizza['large_price'] * 1);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Order Pizza - Pizza House</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Add back button styles */
    .back-to-home {
      display: inline-block;
      margin-top: 15px;
      color: #b1352f;
      text-decoration: none;
      font-weight: 600;
    }
    .back-to-home i {
      margin-right: 8px;
    }
  </style>
</head>

<body>
<div class="header">
  <div class="logo">
    <a href="index.php" style="color: white; text-decoration: none;">
      🍕 Pizza House
    </a>
  </div>
  <div class="tagline">オンライン注文 • 30分以内配達</div>
</div>

<div class="order-page">
  <!-- LEFT : PIZZA LIST -->
  <div class="pizza-list">
    <!-- Small Pizza -->
    <div class="pizza-item">
      <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Small Pizza">
      <div class="pizza-info">
        <h3>Small Pizza (20cm)</h3>
        <p>¥<?php echo number_format($pizza['small_price']); ?></p>
      </div>
      <div class="qty">
        <button type="button" onclick="changeQty('small', -1)">−</button>
        <span id="smallQty">1</span>
        <button type="button" onclick="changeQty('small', 1)">+</button>
      </div>
    </div>

    <!-- Medium Pizza -->
    <div class="pizza-item">
      <img src="https://images.unsplash.com/photo-1594007654729-407eedc4be65" alt="Pepperoni Pizza">
      <div class="qty">
        <button type="button" onclick="changeQty('medium', -1)">−</button>
        <span id="mediumQty">2</span>
        <button type="button" onclick="changeQty('medium', 1)">+</button>
      </div>
    </div>

    <!-- Large Pizza -->
    <div class="pizza-item">
      <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Large Pizza">
      <div class="pizza-info">
        <h3>Large Pizza (40cm)</h3>
        <p>¥<?php echo number_format($pizza['large_price']); ?></p>
      </div>
      <div class="qty">
        <button type="button" onclick="changeQty('large', -1)">−</button>
        <span id="largeQty">1</span>
        <button type="button" onclick="changeQty('large', 1)">+</button>
      </div>
    </div>
  </div>

  <!-- RIGHT : ORDER DETAILS -->
  <form action="process_order.php" method="POST" id="orderForm">
    <div class="order-summary">
      <h2>注文詳細</h2>
      <p class="date"><?php echo strtoupper(date('F Y')); ?></p>

      <h1>¥ <span id="total"><?php echo number_format($initial_total); ?></span></h1>

      <div class="sizes">
        <p>Small <span>-----</span> <span id="sCount">1</span> × ¥<span id="smallPrice"><?php echo $pizza['small_price']; ?></span></p>
        <p>Medium <span>-----</span> <span id="mCount">2</span> × ¥<span id="mediumPrice"><?php echo $pizza['medium_price']; ?></span></p>
        <p>Large <span>-----</span> <span id="lCount">1</span> × ¥<span id="largePrice"><?php echo $pizza['large_price']; ?></span></p>
        <p style="font-weight: bold; margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 10px;">
          合計数量 <span>-----</span> <span id="itemCount">4</span>
        </p>
      </div>

      <div class="customer-form">
        <h3 style="color: white; margin-bottom: 15px; font-size: 18px;">お客様情報</h3>
        <input type="text" name="name" placeholder="お名前" required>
        <input type="tel" name="phone" placeholder="電話番号" required>
        <input type="email" name="email" placeholder="メールアドレス" required>
        <input type="text" name="address" placeholder="配達先住所" required>
        <textarea name="instructions" placeholder="特別なご要望（アレルギーなど）" rows="2" style="width: 100%; padding: 12px; border-radius: 8px; border: 2px solid #f5b38b; font-family: 'Poppins', sans-serif;"></textarea>
      </div>

      <!-- Hidden inputs to pass order data -->
      <input type="hidden" name="small_qty" id="hiddenSmall" value="1">
      <input type="hidden" name="medium_qty" id="hiddenMedium" value="2">
      <input type="hidden" name="large_qty" id="hiddenLarge" value="1">
      <input type="hidden" name="small_price" value="<?php echo $pizza['small_price']; ?>">
      <input type="hidden" name="medium_price" value="<?php echo $pizza['medium_price']; ?>">
      <input type="hidden" name="large_price" value="<?php echo $pizza['large_price']; ?>">
      
      <button type="submit" class="confirm-btn">
        <i class="fas fa-check-circle"></i> 注文を確定する →
      </button>
      
      <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i> ホームに戻る
      </a>
    </div>
  </form>
</div>

<script>
// JavaScript for quantity adjustment and price calculation
let quantities = {
    small: 1,
    medium: 2,
    large: 1
};

const prices = {
    small: <?php echo $pizza['small_price']; ?>,
    medium: <?php echo $pizza['medium_price']; ?>,
    large: <?php echo $pizza['large_price']; ?>
};

function changeQty(size, change) {
    if (quantities[size] + change >= 0) {
        quantities[size] += change;
        document.getElementById(size + 'Qty').textContent = quantities[size];
        document.getElementById(size[0] + 'Count').textContent = quantities[size];
        document.getElementById('hidden' + size.charAt(0).toUpperCase() + size.slice(1)).value = quantities[size];
        updateTotal();
    }
}

function updateTotal() {
    let total = 0;
    let itemCount = 0;
    
    for (let size in quantities) {
        total += quantities[size] * prices[size];
        itemCount += quantities[size];
    }
    
    document.getElementById('total').textContent = total.toLocaleString();
    document.getElementById('hiddenTotal').value = total;
    document.getElementById('itemCount').textContent = itemCount;
    
    // Update counts display
    document.getElementById('sCount').textContent = quantities.small;
    document.getElementById('mCount').textContent = quantities.medium;
    document.getElementById('lCount').textContent = quantities.large;
}

// Form validation
document.getElementById('orderForm').addEventListener('submit', function(e) {
    let itemCount = quantities.small + quantities.medium + quantities.large;
    
    if (itemCount === 0) {
        e.preventDefault();
        alert('数量を選択してください。');
        return;
    }
    
    // Show loading
    const submitBtn = document.querySelector('.confirm-btn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 処理中...';
    submitBtn.disabled = true;
});
</script>
</body>
</html>