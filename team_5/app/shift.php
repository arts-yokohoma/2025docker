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
                        <a class="nav-link btn btn-contact rounded-pill px-4 m-2" href="admin_panel.php">管理メニュー</a>
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
    <div class="container text-center mt-5 mx-auto">
        <form id="shiftForm" action="shift_process.php" method="post">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success text-center" role="alert">シフトを保存しました。タイムスロットを生成しました。</div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger text-center" role="alert">シフト保存中にエラーが発生しました。</div>
            <?php endif; ?>
            <div class=" container_def justify-content-center mx-auto w-50">
                <h2 class="fw-bolder">今日のシフトを入力する</h2>

                <div class="mb-4 w-50 mx-auto text-center">
                    <label for="shift_date" class="form-label fs-4 fw-bold d-block">日付</label>
                    <input type="date" id="shift_date" name="shift_date" class="form-control form-control-lg mx-auto" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="row text-center mb-4 mx-auto justify-content-center">
                    <div class="col-12 mb-3">
                        <label class="form-label fs-4 fw-bold d-block">勤務区分</label>
                        <div class="btn-group shift-period" role="group" aria-label="shift period">
                            <input type="radio" class="btn-check" name="shift_period" id="period_morning" value="morning" autocomplete="off" checked>
                            <label class="btn btn-outline-primary" for="period_morning">日勤（10:00〜16:00）</label>

                            <input type="radio" class="btn-check" name="shift_period" id="period_evening" value="evening" autocomplete="off">
                            <label class="btn btn-outline-primary" for="period_evening">夕勤（16:00〜22:00）</label>
                        </div>
                    </div>
                    <!-- Kitchen -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fs-3 fw-bold">キッチン</label>
                        <select class="form-select form-select-lg" name="kitchen_count">
                            <option selected disabled>選択してください</option>
                            <option value="1">1人</option>
                            <option value="2">2人</option>
                            <option value="3">3人</option>
                            <option value="4">4人</option>
                            <option value="5">5人</option>
                        </select>
                    </div>

                    <!-- Driver -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label fs-3 fw-bold">ドライバー</label>
                        <select class="form-select form-select-lg" name="driver_count">
                            <option selected disabled>選択してください</option>
                            <option value="1">1人</option>
                            <option value="2">2人</option>
                            <option value="3">3人</option>
                            <option value="4">4人</option>
                            <option value="5">5人</option>
                        </select>
                    </div>
                </div>

                <div class="text-center">
                    <a id="viewShiftStatus"
                        class="btn btn-filled-custom btn-lg shadow-sm fw-bold ms-2 rounded-2 me-4"
                        href="shift_status.php">
                        シフト状況
                    </a>
                    <button class="btn btn-success btn-lg shadow-sm fw-bold" type="submit">設定</button>
                </div>
            </div>

            <!-- Bottom buttons -->
            <div class="container">
                <div class="row justify-content-center gap-3 mt-3">
                    <div class="col-2 text-center my-4 fs-1 fw-bold">
                        <a href="admin_panel.php" class="btn btn-filled-custom btn-lg fw-bold rounded-2 text-light">ホーム</a>
                    </div>
                    <div class=" col-2 text-center my-4 fs-1 fw-bold">
                        <a href="logout.php" class="btn btn-danger btn-lg fw-bold">ログアウト</a>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        // Update the status link to include the selected date
        (function() {
            var dateInput = document.getElementById('shift_date');
            var statusLink = document.getElementById('viewShiftStatus');
            if (!dateInput || !statusLink) return;

            function updateHref() {
                var d = dateInput.value || '';
                var base = 'shift_status.php';
                statusLink.href = d ? (base + '?shift_date=' + encodeURIComponent(d)) : base;
            }

            dateInput.addEventListener('change', updateHref);
            updateHref();
        })();

        // Confirmation before submitting the shift form (generates time slots)
        (function() {
            var form = document.getElementById('shiftForm');
            if (!form) return;
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var ok = confirm('シフトを保存してタイムスロットを生成します。よろしいですか？');
                if (!ok) {
                    return false;
                }

                // show transient info alert before submit
                var info = document.createElement('div');
                info.className = 'alert alert-info text-center';
                info.role = 'alert';
                info.textContent = '送信中… シフトを保存しています。少々お待ちください。';
                form.parentNode.insertBefore(info, form);

                // prevent double submit
                var btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                }

                // submit after short delay so user sees the message
                setTimeout(function() {
                    form.submit();
                }, 400);
                return true;
            });
        })();
    </script>

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