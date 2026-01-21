<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>注文</title>
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
                        <a class="nav-link btn btn-contact rounded-pill px-4 m-2" href="location.php">店舗情報</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-filled-custom rounded-pill px-4 m-2" href="time.php">今すぐ注文</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->
    <!-- body -->
    <form action="menu_process.php" method="post">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success text-center" role="alert">価格を更新しました。</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger text-center" role="alert">価格更新中にエラーが発生しました。</div>
        <?php endif; ?>
        <div class="container_def mx-auto w-50 p-4 m-5">

            <h3 class="text-center mb-4 fs-1 fw-bold">価格管理</h3>

            <div class="row mb-4">
                <!-- S size -->
                <div class="col-md-4">
                    <label for="sizeS" class="form-label fs-3 text-center d-block">Sサイズ</label>
                    <input type="number" class="form-control fs-4" id="sizeS" name="sizeS" step="0.01" min="0" placeholder="価格を入力">
                </div>

                <!-- M size -->
                <div class="col-md-4">
                    <label for="sizeM" class="form-label fs-3 text-center d-block">Mサイズ</label>
                    <input type="number" class="form-control fs-4" id="sizeM" name="sizeM" step="0.01" min="0" placeholder="価格を入力">
                </div>

                <!-- L size -->
                <div class="col-md-4">
                    <label for="sizeL" class="form-label fs-3 text-center d-block">Lサイズ</label>
                    <input type="number" class="form-control fs-4" id="sizeL" name="sizeL" step="0.01" min="0" placeholder="価格を入力">
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                    更新
                </button>
            </div>
        </div>
        <!-- Bottom buttons -->
        <div class="container">
            <div class="row justify-content-center gap-3">
                <div class="col-2 text-center my-4 fs-1 fw-bold">
                    <a href="admin_panel.php" class="btn btn-filled-custom btn-lg fw-bold rounded-2 text-light">ホーム</a>
                </div>
                <div class=" col-2 text-center my-4 fs-1 fw-bold">
                    <a href="logout.php" class="btn btn-danger btn-lg fw-bold">ログアウト</a>
                </div>
            </div>
        </div>
    </form>

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
                        <li class="list-inline-item"><a href="#">お問い合わせ</a></li>
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