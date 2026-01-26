<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Pizza</title>
  <link rel="stylesheet" href="css/style.css?v=3.0">
</head>


<body>
<div class="header">
  <div class="logo">🍕 pizzaMach</div>
  <div class="tagline">Order Online • Free Delivery in 30 mins</div>
</div>

<div class="order-page">
  <!-- LEFT : PIZZA LIST -->
  <div class="pizza-list">
    <div class="pizza-item">
      <img src="image/p1.jpg" style="width:120px">
      <div class="qty">
        <button type="button" onclick="changeQty('small',-1)">−</button>
        <span id="smallQty">1</span>
        <button type="button" onclick="changeQty('small',1)">+</button>
      </div>
    </div>

    <div class="pizza-item">
      <img src="image/p2.jpg" style="width:200px">
      <div class="qty">
        <button type="button" onclick="changeQty('medium',-1)">−</button>
        <span id="mediumQty">2</span>
        <button type="button" onclick="changeQty('medium',1)">+</button>
      </div>
    </div>

    <div class="pizza-item">
      <img src="image/p3.jpg" style="width:300px">
      <div class="qty">
        <button type="button" onclick="changeQty('large',-1)">−</button>
        <span id="largeQty">1</span>
        <button type="button" onclick="changeQty('large',1)">+</button>
      </div>
    </div>
  </div>

  <!-- RIGHT : ORDER DETAILS -->
  <form action="receipt.html" method="GET" id="orderForm">
    <div class="order-summary">
      <h2>Order Details</h2>
      <p class="date">NOVEMBER 2019</p>

      <h1>¥ <span id="total">1286</span></h1>

      <div class="sizes">
        <p>Small <span>-----</span> <span id="sCount">1</span></p>
        <p>Medium <span>-----</span> <span id="mCount">2</span></p>
        <p>Large <span>-----</span> <span id="lCount">1</span></p>
        <p>Total Items <span>-----</span> <span id="itemCount">4</span></p>
      </div>

      <div class="customer-form">
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="tel" name="phone" placeholder="Phone Number" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="address" placeholder="Delivery Address" required>
      </div>

      <!-- Hidden inputs to pass order data -->
      <input type="hidden" name="smallQty" id="hiddenSmall" value="1">
      <input type="hidden" name="mediumQty" id="hiddenMedium" value="2">
      <input type="hidden" name="largeQty" id="hiddenLarge" value="1">
      <input type="hidden" name="total" id="hiddenTotal" value="1286">
      
      <button type="submit" class="confirm-btn">
        Confirm Order & Generate Receipt →
      </button>
    </div>
  </form>
</div>
<script src="js/order.js"></script>
</body>
</html>