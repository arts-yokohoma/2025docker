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

// Calculate initial total
$initial_total = ($pizza['small_price'] * 1) + ($pizza['medium_price'] * 2) + ($pizza['large_price'] * 1);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Order Pizza - Pizza Match</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    /* Order page specific styles that match your design */
    .order-header {
      background: #ce9851;
      color: white;
      padding: 20px 40px;
      border-bottom-left-radius: 50% 30px;
      border-bottom-right-radius: 50% 30px;
      text-align: center;
      margin-bottom: 60px;
    }
    
    .order-logo {
      font-size: 28px;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    .order-tagline {
      font-size: 14px;
      opacity: 0.9;
      margin-top: 5px;
    }
    
    .order-page {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px 60px;
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
    }
    
    .pizza-list {
      flex: 1;
      min-width: 300px;
    }
    
    .pizza-item {
      background: white;
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      gap: 25px;
      transition: transform 0.3s;
    }
    
    .pizza-item:hover {
      transform: translateY(-5px);
    }
    
    .pizza-item img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #f4d6c2;
    }
    
    .pizza-info {
      flex: 1;
    }
    
    .pizza-info h3 {
      color: #d19758;
      margin-bottom: 5px;
      font-size: 18px;
    }
    
    .pizza-info p {
      color: #666;
      font-size: 14px;
      margin-bottom: 10px;
    }
    
    .price-display {
      color: #d19758;
      font-size: 20px;
      font-weight: bold;
    }
    
    .qty {
      display: flex;
      align-items: center;
      gap: 15px;
      background: #f4d6c2;
      padding: 8px 20px;
      border-radius: 30px;
    }
    
    .qty button {
      background: #d19758;
      color: white;
      border: none;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      font-size: 18px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.3s;
    }
    
    .qty button:hover {
      background: #b87e40;
    }
    
    .qty span {
      font-size: 18px;
      font-weight: bold;
      min-width: 25px;
      text-align: center;
    }
    
    .order-summary {
      background: #efe3d6;
      border-radius: 20px;
      padding: 30px;
      min-width: 320px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    
    .order-summary h2 {
      color: #d19758;
      margin-bottom: 10px;
      text-align: center;
    }
    
    .date {
      text-align: center;
      color: #666;
      font-size: 14px;
      letter-spacing: 2px;
      margin-bottom: 20px;
    }
    
    .total-amount {
      text-align: center;
      font-size: 42px;
      color: #d19758;
      font-weight: bold;
      margin: 20px 0;
    }
    
    .sizes {
      background: rgba(255,255,255,0.5);
      padding: 20px;
      border-radius: 15px;
      margin: 25px 0;
    }
    
    .sizes p {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
      padding-bottom: 12px;
      border-bottom: 1px dashed #ddd;
    }
    
    .sizes p:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
      font-weight: bold;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 2px solid #ddd;
    }
    
    .customer-form {
      background: rgba(255,255,255,0.5);
      padding: 20px;
      border-radius: 15px;
      margin-top: 25px;
    }
    
    .customer-form h3 {
      color: #d19758;
      margin-bottom: 15px;
      font-size: 16px;
    }
    
    .customer-form input,
    .customer-form textarea {
      width: 100%;
      padding: 12px 15px;
      margin-bottom: 15px;
      border: 2px solid #f4d6c2;
      border-radius: 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      transition: border-color 0.3s;
    }
    
    .customer-form input:focus,
    .customer-form textarea:focus {
      outline: none;
      border-color: #d19758;
    }
    
    .confirm-btn {
      background: #d19758;
      color: white;
      border: none;
      padding: 15px;
      border-radius: 30px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s;
      width: 100%;
      margin-top: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    .confirm-btn:hover {
      background: #b87e40;
    }
    
    .back-btn {
      display: block;
      text-align: center;
      margin-top: 15px;
      color: #d19758;
      text-decoration: none;
      font-weight: 600;
    }
    
    .back-btn:hover {
      text-decoration: underline;
    }
    
    .error-banner {
      background: #f8d7da;
      border: 2px solid #dc3545;
      color: #721c24;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
      text-align: center;
      font-size: 16px;
      font-weight: 600;
    }
    
    .disabled-form {
      opacity: 0.6;
      pointer-events: none;
    }
    
    @media (max-width: 768px) {
      .order-page {
        flex-direction: column;
      }
      
      .pizza-item {
        flex-direction: column;
        text-align: center;
      }
      
      .pizza-item img {
        width: 150px;
        height: 150px;
      }
      
      .order-summary {
        min-width: auto;
      }
    }
  </style>
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
        alert('Please select quantity.');
        return;
    }
    
    // Show loading
    const submitBtn = document.querySelector('.confirm-btn');
    submitBtn.innerHTML = '<span>Processing Order...</span>';
    submitBtn.disabled = true;
});
</script>
</body>
</html>