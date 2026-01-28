<?php
// order.php - Updated to match your CSS design
require_once 'db/db.php';

// Check staff availability before showing order page
$capacity = checkOrderCapacity();

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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Order Pizza - Pizza Match</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/test.css">
  <link rel="stylesheet" href="css/order-page.css">
</head>

<body>
<div class="order-header">
  <div class="order-logo">
    🍕 Pizza Match
  </div>
  <div class="order-tagline">Online Order • Free Delivery in 30 mins</div>
</div>

<?php if (!$capacity['can_accept_orders']): ?>
  <div style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
    <div class="error-banner">
      ❌ <?php echo htmlspecialchars($capacity['message']); ?>
    </div>
    <div style="text-align: center;">
      <p style="color: #666; margin-bottom: 20px; font-size: 16px;">
        We are unable to accept new orders at the moment. Please check back soon!
      </p>
      <a href="index.php" class="order-button" style="display: inline-block;">← Back to Home</a>
    </div>
  </div>
<?php else: ?>

<div class="order-page">
  <!-- LEFT : PIZZA LIST -->
  <div class="pizza-list">
    <!-- Small Pizza -->
    <div class="pizza-item">
      <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Small Pizza">
      <div class="pizza-info">
        <h3>Small Pizza (20cm)</h3>
        <p>Perfect for 1-2 people</p>
        <div class="price-display">¥<?php echo number_format($pizza['small_price']); ?></div>
      </div>
      <div class="qty">
        <button type="button" onclick="changeQty('small', -1)">−</button>
        <span id="smallQty">1</span>
        <button type="button" onclick="changeQty('small', 1)">+</button>
      </div>
    </div>

    <!-- Medium Pizza -->
    <div class="pizza-item">
      <img src="<?php echo htmlspecialchars($image_url); ?>" alt="Medium Pizza">
      <div class="pizza-info">
        <h3>Medium Pizza (30cm)</h3>
        <p>Great for 2-3 people</p>
        <div class="price-display">¥<?php echo number_format($pizza['medium_price']); ?></div>
      </div>
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
        <p>Feeds 3-4 people</p>
        <div class="price-display">¥<?php echo number_format($pizza['large_price']); ?></div>
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
      <h2>Order Details</h2>
      <p class="date"><?php echo strtoupper(date('F Y')); ?></p>

      <div class="total-amount">
        ¥ <span id="total"><?php echo number_format($initial_total); ?></span>
      </div>

      <div class="sizes">
        <p>Small <span id="sCount">1</span></p>
        <p>Medium <span id="mCount">2</span></p>
        <p>Large <span id="lCount">1</span></p>
        <p>Total Items <span id="itemCount">4</span></p>
      </div>

      <div class="customer-form">
        <h3>Customer Information</h3>
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="tel" name="phone" placeholder="Phone Number" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="address" placeholder="Delivery Address" required>
        <textarea name="instructions" placeholder="Special Instructions (Allergies, etc.)" rows="3"></textarea>
      </div>

      <!-- Hidden inputs to pass order data -->
      <input type="hidden" name="small_qty" id="hiddenSmall" value="1">
      <input type="hidden" name="medium_qty" id="hiddenMedium" value="2">
      <input type="hidden" name="large_qty" id="hiddenLarge" value="1">
      <input type="hidden" name="small_price" value="<?php echo $pizza['small_price']; ?>">
      <input type="hidden" name="medium_price" value="<?php echo $pizza['medium_price']; ?>">
      <input type="hidden" name="large_price" value="<?php echo $pizza['large_price']; ?>">
      
      <button type="submit" class="confirm-btn">
        <span>Confirm Order & Generate Receipt →</span>
      </button>
      
      <a href="index.php" class="back-btn">
        ← Back to Home
      </a>
    </div>
  </form>
</div>
<?php endif; ?>

<script src="js/order-page.js"></script>
<script>
  // Initialize prices from PHP
  window.addEventListener('DOMContentLoaded', function() {
    initializePrices(<?php echo $pizza['small_price']; ?>, <?php echo $pizza['medium_price']; ?>, <?php echo $pizza['large_price']; ?>);
  });
</script>
</body>
</html>