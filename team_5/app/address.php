<?php
session_start();

// Basic guard: if order is missing, send user back to menu selection
if (!isset($_SESSION['order'])) {
    header('Location: order_select.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $zipcode = preg_replace('/[^0-9]/', '', (string)($_POST['zipcode'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $building = trim((string)($_POST['building'] ?? ''));
    $room = trim((string)($_POST['room'] ?? ''));

    $_SESSION['customer'] = [
        'name' => $name,
        'phone' => $phone,
        'zipcode' => $zipcode,
        'address' => $address,
        'building' => $building,
        'room' => $room,
    ];

    header('Location: confirm.php');
    exit;
}

$customer = $_SESSION['customer'] ?? [];
?>
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
    <form class="my-4" method="post" action="">
        <div class="container container_def">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">

                    <h3 class="text-center mb-4 fw-bold">住所入力</h3>

                    <!-- 名前 -->
                    <div class="row align-items-center mb-3">
                        <div class="col-3">
                            <label class="form-label mb-0 fs-3">名前</label>
                        </div>
                        <div class="col-9">
                            <input type="text" name="name" class="form-control fs-3" placeholder="坂口教子" value="<?php echo htmlspecialchars((string)($customer['name'] ?? '')); ?>" autocomplete="name" required>
                        </div>
                    </div>

                    <!-- 電話番号 -->
                    <div class="row align-items-center mb-3">
                        <div class="col-3">
                            <label class="form-label mb-0 fs-3">電話番号</label>
                        </div>
                        <div class="col-9">
                            <input type="tel" name="phone" class="form-control fs-3" placeholder="080 1234 6782" value="<?php echo htmlspecialchars((string)($customer['phone'] ?? '')); ?>" autocomplete="tel" required>
                        </div>
                    </div>

                    <!-- 郵便番号 -->
                    <div class="row align-items-center mb-3">
                        <div class="col-3">
                            <label class="form-label mb-0 fs-3">郵便番号</label>
                        </div>
                        <div class="col-5">
                            <input type="text" id="zipcode" name="zipcode" class="form-control fs-3" placeholder="2200002" value="<?php echo htmlspecialchars((string)($customer['zipcode'] ?? '')); ?>" inputmode="numeric" autocomplete="postal-code" required>
                        </div>
                        <div class="col-4">
                            <button type="button" id="zipSearchBtn" class="btn btn-danger w-100 fs-3">
                                探す
                            </button>
                        </div>
                    </div>

                    <!-- 住所（自動入力） -->
                    <div class="mb-3">
                        <input type="text" id="address" name="address" class="form-control fs-3" placeholder="神奈川県横浜市西区〇〇町" value="<?php echo htmlspecialchars((string)($customer['address'] ?? '')); ?>" autocomplete="street-address" required>
                    </div>

                    <div id="zipError" class="text-danger fw-bold" style="display:none;"></div>

                    <!-- 建物名・部屋番号 -->
                    <div class="row mb-4">
                        <div class="col-6">
                            <input type="text" name="building" class="form-control fs-3" placeholder="11-3 ライオンズマンション" value="<?php echo htmlspecialchars((string)($customer['building'] ?? '')); ?>" autocomplete="address-line2">
                        </div>
                        <div class="col-6">
                            <div class="row align-items-center">
                                <div class="col-6">
                                    <label class="form-label mb-0 fs-3">部屋番号</label>
                                </div>
                                <div class="col-6">
                                    <input type="text" name="room" class="form-control fs-3" placeholder="102" value="<?php echo htmlspecialchars((string)($customer['room'] ?? '')); ?>" autocomplete="address-line3">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Submit (outside the container box) -->
        <div class="text-center mt-3">
            <button type="submit" class="btn btn-success px-5 py-2 fs-3 fw-bold">
                予約 →
            </button>
        </div>
    </form>





    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const zipInput = document.getElementById('zipcode');
            const addressInput = document.getElementById('address');
            const btn = document.getElementById('zipSearchBtn');
            const errorEl = document.getElementById('zipError');

            function setError(message) {
                if (!errorEl) return;
                if (!message) {
                    errorEl.textContent = '';
                    errorEl.style.display = 'none';
                    return;
                }
                errorEl.textContent = message;
                errorEl.style.display = 'block';
            }

            async function searchZip() {
                setError('');
                const zipcode = (zipInput?.value || '').replace(/[^0-9]/g, '');
                if (zipcode.length !== 7) {
                    setError('郵便番号は7桁で入力してください');
                    return;
                }

                btn.disabled = true;
                btn.textContent = '検索中…';
                try {
                    const res = await fetch('zip_search.php?zipcode=' + encodeURIComponent(zipcode), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || data.ok !== true) {
                        setError((data && data.error) ? data.error : '検索に失敗しました');
                        return;
                    }
                    if (addressInput) addressInput.value = data.address || '';
                } catch (e) {
                    setError('検索に失敗しました');
                } finally {
                    btn.disabled = false;
                    btn.textContent = '探す';
                }
            }

            btn?.addEventListener('click', searchZip);
            zipInput?.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchZip();
                }
            });
        });
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