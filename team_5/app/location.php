<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>店舗情報</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <!-- navbar -->
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center" href="index.php">
        <img src="img/nav_bar_logo.png" height="60" class="me-2" alt="Team 5 logo">
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link btn btn-contact rounded-pill px-4 me-2" href="contact.php">お問い合わせ</a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn btn-filled-custom rounded-pill px-4" href="time.php">今すぐ注文</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- navbar -->

  <!-- ================= LOCATION SECTION ================= -->
  <section class="location-section d-flex align-items-center">
    <div class="container-fluid position-relative">
      <div class="row align-items-center justify-content-center">

        <!-- INFO CARD -->
        <div class="col-md-7">
          <div class="location-card">
            <p class="fw-bold fs-4">
              住所: 〒220-0072<br>
              神奈川県横浜市西区浅間町 2-105-8
            </p>

            <p class="fw-bold fs-4">電話番号：
              <br>045-324-0011
            </p>

            <div class="icon-row mt-4">
              <a href="tel:045-324-0011" style="text-decoration: none;">
                <div class="icon-circle">
                  <img width="50" height="50" src="https://img.icons8.com/ios-filled/50/phone-disconnected.png" alt="phone-disconnected" />
                </div>
              </a>
              <a href="https://www.google.com/maps/dir//Arts+College+Yokohama,+2+Chome-105-8+Sengencho,+Nishi+Ward,+Yokohama,+Kanagawa+220-0072/@35.463539,139.6071361,17z/data=!4m17!1m7!3m6!1s0x60185c09e09a76a5:0xd9f33045278944be!2sArts+College+Yokohama!8m2!3d35.463539!4d139.609711!16s%2Fg%2F121dc7_8!4m8!1m0!1m5!1m1!1s0x60185c09e09a76a5:0xd9f33045278944be!2m2!1d139.6097146!2d35.4635424!3e3?entry=ttu&g_ep=EgoyMDI2MDExMy4wIKXMDSoASAFQAw%3D%3D"
                target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
                <div class="icon-circle"><img width="50" height="50" src="https://img.icons8.com/ios-filled/50/marker.png" alt="marker" /></div>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- PIZZA IMAGE -->
      <div class="col-md-5 position-relative">
        <img src="img/slice.png"
          alt="Pizza Slice"
          class="pizza-slice">
      </div>

    </div>
    </div>
  </section>
  <!-- ================= LOCATION SECTION END ================= -->





  <script src="js/bootstrap.bundle.min.js"></script>

  <!-- Site footer -->
  <footer class="site-footer mt-5">
    <div class="container py-4">
      <div class="row align-items-center">
        <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
          <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="img/nav_bar_logo.png" height="40" class="me-2" alt="Team 5 logo">
          </a>
          <small class="d-block">&copy; <span id="year"></span> CYBER EDGE. All rights reserved.</small>
        </div>
        <div class="col-md-6 text-center text-md-end">
          <ul class="list-inline mb-0 footer-links">
            <li class="list-inline-item"><a href="/index.php">ホーム</a></li>
            <li class="list-inline-item"><a href="/admin_login.php">Login</a></li>
            <li class="list-inline-item"><a href="contact.php">お問い合わせ</a></li>
          </ul>
        </div>
      </div>
    </div>
  </footer>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
  </script>
</body>

</html>