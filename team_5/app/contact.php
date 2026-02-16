<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>お問い合わせ</title>
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
                        <a class="nav-link btn btn-contact rounded-pill px-4 me-2" href="location.php">店舗情報</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-filled-custom rounded-pill px-4" href="time.php">今すぐ注文</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->


    <form action="contact_send.php" method="post">
        <div class="container-fluid mt-1">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success text-center" role="alert">送信が完了しました。</div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
                <div class="alert alert-warning text-center" role="alert">この電話番号は既に登録されています。</div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center" role="alert">送信中にエラーが発生しました。もう一度お試しください。</div>
            <?php endif; ?>

            <div class="contact_form ">
                <h2 class="text-center mb-0">お問い合わせ</h2>
                <div class="mb-3">
                    <label for="name" class="form-label fw-bold" id="name">名前</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="名前を入力してください" required>
                </div>
                <div class="mb-3">
                    <label for="phone" class="form-label fw-bold" id="phone">電話番号</label>
                    <input class="form-control" id="phone" name="phone" placeholder="電話番号を入力してください" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-bold" id="email">メールアドレス</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="メールアドレスを入力してください" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">お問い合わせ方法</label>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="inquiry_method" id="inquiry_method_email" value="email" checked>
                            <label class="form-check-label" for="inquiry_method_email">メール</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="inquiry_method" id="inquiry_method_phone" value="phone">
                            <label class="form-check-label" for="inquiry_method_phone">電話</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label fw-bold" id="message">内容</label>
                    <textarea class="form-control" id="message" name="message" rows="1" data-grazie-editor-id="bc98e6ae-0ff3-4cb6-bdb6-d9295361f48d" spellcheck="false"></textarea><grazie-editor-wrapper data-grazie-editor-id="bc98e6ae-0ff3-4cb6-bdb6-d9295361f48d" class="local origin" style="position: absolute; contain: layout;"></grazie-editor-wrapper><grazie-editor-wrapper data-grazie-editor-id="bc98e6ae-0ff3-4cb6-bdb6-d9295361f48d" class="local visual" style="position: absolute; contain: layout;"></grazie-editor-wrapper>
                </div>

            </div>
        </div>
        <div class="text-center mb-0">
            <button type="submit" class="btn btn-outline-success btn-lg fw-bold">送信</button>
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