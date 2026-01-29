<?php
// index.php - Updated with database integration
require_once 'db/db.php';

// Get pizza prices from database
$pizza = ['small_price' => 800, 'medium_price' => 1200, 'large_price' => 1500];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM pizzas LIMIT 1");
        $db_pizza = $stmt->fetch();
        if ($db_pizza) {
            $pizza = array_merge($pizza, $db_pizza);
        }
    } catch (Exception $e) {
        // Use default prices if database fails
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pizza Match</title>

  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="css/test.css">
  <style>
   
  </style>
</head>
<body>

<header class="main-header">
  <div class="header-content">
    <a href="#" class="logo-link">
       <img src="image/8.png" alt="Pizza Match Logo" class="logo-img">
    </a>

    <nav id="nav-menu">
      <a href="#about" onclick="toggleMenu()">会社案内</a>
      <a href="#contact" onclick="toggleMenu()">お問い合わせ</a>
      <a href="order.php" class="order-button" style="margin-left: 15px; padding: 8px 20px; font-size: 14px;">オンライン注文</a>
    </nav>
  </div>

  <div class="header-pizza">
    <img src="image/pi3.png" alt="Pizza">
  </div>
</header>

<main>

  <!-- Price Section Added -->
  <section class="price-section">
    <h2 style="color: #333; font-size: 28px; margin-bottom: 20px; margin-top:30px;">お手頃価格で本格ピザ</h2>
    <p style="color: #666; margin-bottom: 30px;">新鮮な食材で作る当店自慢のピザを30分以内にお届けします</p>
    
    <div class="price-grid">
      <div class="price-card">
        <h4>Small (20cm)</h4>
        <p>1-2人前</p>
        <div class="price-tag">¥<?php echo number_format($pizza['small_price']); ?></div>
        <a href="order.php" class="order-button" style="margin-top: 15px; width: 100%; text-align: center;">注文する</a>
      </div>
      
      <div class="price-card">
        <h4>Medium (30cm)</h4>
        <p>2-3人前</p>
        <div class="price-tag">¥<?php echo number_format($pizza['medium_price']); ?></div>
        <a href="order.php" class="order-button" style="margin-top: 15px; width: 100%; text-align: center;">注文する</a>
      </div>
      
      <div class="price-card">
        <h4>Large (40cm)</h4>
        <p>3-4人前</p>
        <div class="price-tag">¥<?php echo number_format($pizza['large_price']); ?></div>
        <a href="order.php" class="order-button" style="margin-top: 15px; width: 100%; text-align: center;">注文する</a>
      </div>
    </div>
  </section>

  <div class="order-btn">
    <a href="order.php" class="order-button" style="text-decoration: none; display: inline-block;">オンラインで注文する</a>
  </div>

  <section class="about" id="about">
    <h2>当店について</h2>
    <div class="about-box">
      <div class="about-inner">
        <img src="https://cdn-icons-png.flaticon.com/512/2920/2920244.png" alt="Chef">
        <p style="margin-top: 15px;">
          一枚一枚、心を込めて焼き上げています。<br>
          最高の素材と技術で、本物の味をお届けします。
        </p>
        <a href="order.php" class="order-button" style="margin-top: 20px;">今すぐ注文する</a>
      </div>
    </div>
  </section>

  <section class="contact" id="contact">
    <h2>お問い合わせ</h2>
    <div class="contact-box">
      <p>ご予約・ご質問はこちらから</p>
      <p class="email">Email: info@pizzamatch.jp</p>
      <p class="phone">電話: 03-1234-5678</p>
      <a href="order.php" class="order-button" style="margin-top: 20px;">オンライン注文はこちら</a>
    </div>
  </section>

</main>

<footer class="footer">
  <div class="footer-inner">
    <p>© 2026 Pizza Match. All Rights Reserved.</p>
    <nav class="footer-nav">
      <a href="#about">会社案内</a>
      <a href="#contact">お問い合わせ</a>
      <a href="order.php">オンライン注文</a>
    </nav>
  </div>
</footer>

<script>
  function toggleMenu() {
    const nav = document.getElementById('nav-menu');
    nav.classList.toggle('active');
  }

  function scrollToContact() {
    document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
  }
  
  // Add smooth scrolling for all anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const targetId = this.getAttribute('href');
      if(targetId === '#') return;
      
      const targetElement = document.querySelector(targetId);
      if(targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80,
          behavior: 'smooth'
        });
      }
    });
  });
</script>

</body>
</html>