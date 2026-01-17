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
                    <button class="btn btn-contact rounded-pill px-4">お問い合わせ</button>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->

    form
    <form action="contact_send.php" method="post">
        <div class="container-fluid mt-1">
            <div class="contact_form ">
                <h2 class="text-center mb-0">お問い合わせ</h2>
                <div class="mb-3">
                    <label for="exampleFormControlInput1" class="form-label" id="name">名前</label>
                    <input type="text" class="form-control" id="exampleFormControlInput1" name="name" placeholder="名前を入力してください" required>
                </div>
                <div class="mb-3">
                    <label for="exampleFormControlInput1" class="form-label" id="phone">電話番号</label>
                    <input class="form-control" id="exampleFormControlInput1" name="phone" placeholder="電話番号を入力してください" required>
                </div>
                <div class="mb-3">
                    <label for="exampleFormControlInput1" class="form-label" id="mail">メールアドレス</label>
                    <input type="email" class="form-control" id="exampleFormControlInput1" name="email" placeholder="メールアドレスを入力してください" required>
                </div>
                <div class="mb-3">
                    <label for="exampleFormControlTextarea1" class="form-label">内容</label>
                    <textarea class="form-control" id="exampleFormControlTextarea1" name="message" rows="3" data-grazie-editor-id="bc98e6ae-0ff3-4cb6-bdb6-d9295361f48d" spellcheck="false"></textarea><grazie-editor-wrapper data-grazie-editor-id="bc98e6ae-0ff3-4cb6-bdb6-d9295361f48d" class="local origin" style="position: absolute; contain: layout;"></grazie-editor-wrapper><grazie-editor-wrapper data-grazie-editor-id="bc98e6ae-0ff3-4cb6-bdb6-d9295361f48d" class="local visual" style="position: absolute; contain: layout;"></grazie-editor-wrapper>
                </div>

            </div>
        </div>
        <div class="text-center mb-0">
            <button type="submit" class="btn btn-outline-success btn-lg">送信</button>
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
                        <li class="list-inline-item"><a href="/time.php">Login</a></li>
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