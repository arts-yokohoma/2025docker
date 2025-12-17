<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PizzaHouse</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Poppins', sans-serif;
      background: #fdf8f3;
      color: #333;
    }
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 60px;
    }
    .logo {
      display: flex;
      align-items: center;
    }
    .logo-img {
      height: 95px;
      width: auto;
    }
    nav a {
      margin-left: 30px;
      text-decoration: none;
      color: #333;
      font-weight: 400;
    }
    .hero {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 80px 60px;
      position: relative;
    }
    .vertical-text {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      color: #f08a24;
      font-weight: 500;
      display: flex;
      flex-direction: column;
      gap: 80px; /* SPACE BETWEEN EACH WORD */
    }
    .vertical-text span {
      display: block;
      transform: rotate(-90deg);
      transform-origin: left center;
      white-space: nowrap;
      letter-spacing: 2px;
    }
    .hero-card {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      position: relative;
    }
    .hero-card img {
      width: 280px;
      border-radius: 10px;
    }
    .badge {
      position: absolute;
      top: -20px;
      left: 50%;
      transform: translateX(-50%);
      background: linear-gradient(135deg,#f6a03d,#e07b18);
      width: 200px;
      height: 40px;
    }
    .menu {
      display: flex;
      justify-content: center;
      gap: 40px;
      padding: 60px;
    }
    .menu-card {
      background: #f1e4d6;
      border-radius: 15px;
      padding: 30px 20px;
      width: 220px;
      text-align: center;
    }
    .menu-card img {
      width: 80px;
      margin-bottom: 15px;
    }
    .menu-card p {
      font-size: 14px;
    }
    .order-btn {
      display: flex;
      justify-content: center;
      margin: 20px 0 80px;
    }
    .order-btn button {
      background: linear-gradient(135deg,#ff9a6a,#ff6a2b);
      border: none;
      color: #fff;
      padding: 14px 40px;
      font-size: 15px;
      border-radius: 25px;
      cursor: pointer;
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    .about {
      padding: 60px;
    }
    .about h2 {
      font-size: 36px;
      margin-bottom: 30px;
    }
    .about-box {
      background: #efe3d6;
      border-radius: 20px;
      padding: 60px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .about-inner {
      background: #f4d6c2;
      border-radius: 20px;
      padding: 50px 80px;
    }
    .about-inner img {
      width: 80px;
    }
  </style>
</head>
<body>

<header>
  <div class="logo"><img src="8.png" alt="pizzaMach" class="logo-img"></div>
  <nav>
    <a href="#">Menu</a>
    <a href="#">About</a>
    <a href="#">Contact</a>
  </nav>
</header>

<section class="hero">
  <div class="vertical-text">
    <span>pizzaMach</span>
    <span>pizzaMach</span>
    <span>pizzaMach</span>
  </div>
  <div class="hero-card">
    <div class="badge"></div>
    <img src="https://images.unsplash.com/photo-1601924638867-3ec62c7e5c79" alt="Pizza">
  </div>
</section>

<section class="menu">
  <div class="menu-card">
    <img src="https://cdn-icons-png.flaticon.com/512/1404/1404945.png">
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
  <button>Order Now</button>
</div>

<section class="about" id="about">
  <h2>About Us</h2>
  <div class="about-box">
    <div class="about-inner">
      <img src="https://cdn-icons-png.flaticon.com/512/2920/2920244.png" alt="About">
    </div>
  </div>
</section>

</body>
</html>
