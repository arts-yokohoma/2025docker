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
  <title>PizzaHouse</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
  <div class="logo"><img src="image/8.png"pizzaMach" class="logo-img"></div>
  <nav>
    <a href="#">会社案内</a>
    <a href="#">お問い合わせ</a>
  </nav>
</header>

<section class="hero">
  <div class="vertical-text">
    <span>ピザマッチ</span>
    <span>ピザマッチ</span>
    <span>ピザマッチ</span>
  </div>

  <div class="header-pizza">
    <img src="image/pi3.png" alt="Pizza">
  </div>
</section>

<section class="menu">
  <div class="menu-card">
    <img src="2025DOCKER/team_4/pi.jpg">
    <p>Classic tomato,<br>mozzarella, basil</p>
  </div>
  <div class="menu-card">
    <img src="https://cdn-icons-png.flaticon.com/512/1404/1404945.png">
    <p>Peppers, olives,<br>fresh herbs</p>
  </div>
  <div class="menu-card">
    <img src="https://cdn-icons-png.flaticon.com/512/1404/1404945.png">
    <p>Sausage, bacon,<br>premium toppings</p>
  </div>
</section>

<div class="order-btn">
  <button>注文</button>
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