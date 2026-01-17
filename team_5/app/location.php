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
					<button class="btn btn-contact rounded-pill px-4 mx-2">ホーム</button>
                    <button class="btn btn-contact rounded-pill px-4 mx-2">お問い合わせ</button>
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
            神奈川県横浜市西区浅間町2-105-8
          </p>

          <p class="fw-bold fs-4">電話番号：
            <br>045-324-0011</p>

          <div class="icon-row mt-4">
            <div class="icon-circle">📞</div>
            <div class="icon-circle">📍
               ,<div class="
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               
               "></div>
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
                        <li class="list-inline-item"><a href="/time.php">Login</a></li>
                        <li class="list-inline-item"><a href="#">お問い合わせ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>document.getElementById('year').textContent = new Date().getFullYear();</script>
</body>
</html>
