<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理メニュー</title>
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
                    <li class="nav-item d-flex align-items-center me-2">
                        <span class="nav-link fw-bold">ログイン中の管理者：
                            <?php echo htmlspecialchars((string)($_SESSION['admin_username'] ?? '')); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-contact rounded-pill px-4 m-2" href="contact.php">お問い合わせ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-filled-custom rounded-pill px-4 m-2" href="index.php">ホーム</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->

    <!-- body -->
    <div class="container text-center mt-5">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success fs-4 fw-bold" role="alert">
                <?php echo htmlspecialchars((string)$_SESSION['flash_success']); ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>
        <div class="row">
            <div class="col container_def_button m-4 fs-1">
                <a href="shift.php" class="text-decoration-none text-dark d-block">シフト<br>管理</a>
            </div>
            <div class="col container_def_button m-4 fs-1">
                <a href="orders.php" class="text-decoration-none text-dark d-block">注文<br>管理</a>
            </div>
            <div class="col container_def_button m-4 fs-1">
                <a href="menu.php" class="text-decoration-none text-dark d-block">メニュー<br>管理</a>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12 col-md-4 container_def_button m-4 fs-1">
                <a href="admin_customer_contacts.php" class="text-decoration-none text-dark d-block">顧客<br>連絡先</a>
            </div>
            <div class="col-12 col-md-4 container_def_button m-4 fs-1">
                <a href="admin_contact_inquiries.php" class="text-decoration-none text-dark d-block">お問い合わせ<br>一覧</a>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="admin_create_user.php" class="btn btn-filled-custom btn-lg rounded-2 fw-bold">管理者ユーザー作成</a>
        </div>
    </div>


    <script src="js/bootstrap.bundle.min.js"></script>
    <div class="text-center my-4 fs-1 fw-bold">
        <a href="logout.php" class="btn btn-danger btn-lg fw-bold">ログアウト</a>
    </div>

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