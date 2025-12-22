<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta naconme="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pizza Page</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #FFF8E7; /* မီးသွေးသလောက်ဖြူအနည်းငယ် */
      font-family: Arial, sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    /* --- PIZZA LABEL --- */
    .pizza-label {
      writing-mode: vertical-rl;
      text-orientation: mixed;
      font-weight: bold;
      font-style: italic; /* စောင်းစောင်းရေး */
      font-size: 20px;
      position: absolute;
      left: 40px;
      top: 120px;
      color: #333;
      letter-spacing: 2px;
    }

    /* --- MAIN BANNER --- */
    .banner {
      background-color: #E78A32; /* ချိုသာတဲ့ အနီလိမ္မော် */
      width: 300px; /* ပိုကြီးအောင်ပြောင်းထား */
      height: 350px;
      margin: 100px auto 50px;
      position: relative;
      clip-path: polygon(0 0, 100% 0, 100% 85%, 50% 100%, 0 85%);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .banner img {
      width: 260px; /* ပိုကြီးအောင်ပြောင်းထား */
      border-radius: 10px;
      position: relative;
      top: 0;
    }

    /* --- DETAILS BOXES --- */
    .details-container {
      display: flex;
      justify-content: center;
      gap: 40px;
      margin-bottom: 80px;
      flex-wrap: wrap;
    }

    .details-box {
      background-color: #ECA457;
      width: 150px;
      height: 120px;
      border-radius: 20px;
      position: relative;
      padding-top: 40px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }

    .details-box:hover {
      transform: scale(1.05);
    }

    .details-box img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      position: absolute;
      top: -30px;
      left: 50%;
      transform: translateX(-50%);
      border: 3px solid white;
    }

    .details-box p {
      margin-top: 10px;
      color: #fff;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <div class="pizza-label">pizza<br>pizza<br>pizza</div>

  <div class="banner">
    <img src="pi.jpg" alt="Pizza">
  </div>

  <div class="details-container">
    <div class="details-box">
      <img src="pzmr.webp" alt="Pizza">
      <p>details</p>
    </div>
    <div class="details-box">
      <img src="pzmr.webp" alt="Pizza">
      <p>details</p>
    </div>
    <div class="details-box">
      <img src="pzmr.webp" alt="Pizza">
      <p>details</p>
    </div>
  </div>

</body>
</html>