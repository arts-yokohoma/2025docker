<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ピザのマッハ</title>
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
                    <a href="/contact.php"><button class="btn btn-contact rounded-pill px-4">お問い合わせ</button></a>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->



    <!-- hero -->
    <header class="hero-section d-flex align-items-center">
        <div class="container-fluid p-0 position-relative h-100">
            <div class="row g-0 h-100 align-items-center">

                <div class="col-md-5 h-100">
                    <div class="pizza-container">
                        <img src="img/hero.png" alt="Fresh Pizza" class="img-fluid pizza-img">
                    </div>
                </div>

                <div class="col-md-6 ps-md-3 text-center text-md-start ">
                    <h1 class="display-3 fw-bold main-headline mb-4">
                        焼きたての美味しい<br>
                        ピザを<br>
                        お客様の手元に。
                    </h1>

                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3">
                        <a href="/time.php" class="btn btn-filled-custom px-5 py-3 fs-5">今すぐ注文</a>
                        <a href="/location.php" class="btn btn-outline-custom px-5 py-3 fs-5">店舗情報</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- hero -->

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
                        <li class="list-inline-item"><a href="/location.php">店舗情報</a></li>
                        <li class="list-inline-item"><a href="contact.php">お問い合わせ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

</body>

</html>