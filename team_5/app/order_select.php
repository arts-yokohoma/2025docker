<?php
session_start();
// load DB prices for menu (falls back to defaults on error)
require_once __DIR__ . '/db_config.php';

$time_slot = '';
if (isset($_GET['time_slot'])) {
    $time_slot = (string)$_GET['time_slot'];
    $_SESSION['time_slot'] = $time_slot;
} elseif (isset($_SESSION['time_slot'])) {
    $time_slot = (string)$_SESSION['time_slot'];
}

$price_s = 800;
$price_m = 1200;
$price_l = 1500;
try {
    $stmt = $pdo->prepare('SELECT size_s, size_m, size_l FROM menu_prices WHERE id = 1');
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) {
        // convert to integer yen for display
        $price_s = (int)round($row['size_s']);
        $price_m = (int)round($row['size_m']);
        $price_l = (int)round($row['size_l']);
    }
} catch (Exception $e) {
    // keep defaults on error
}

// Handle form submission: save selected quantities and total to session, then forward
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qty_s = isset($_POST['qty_s']) ? (int)$_POST['qty_s'] : 0;
    $qty_m = isset($_POST['qty_m']) ? (int)$_POST['qty_m'] : 0;
    $qty_l = isset($_POST['qty_l']) ? (int)$_POST['qty_l'] : 0;

    // compute total using prices loaded above
    $total = $price_s * $qty_s + $price_m * $qty_m + $price_l * $qty_l;

    // store order in session
    $_SESSION['order'] = [
        'qty_s' => $qty_s,
        'qty_m' => $qty_m,
        'qty_l' => $qty_l,
        'total' => $total,
    ];

    // preserve time_slot if present in POST
    if (isset($_POST['time_slot'])) {
        $_SESSION['time_slot'] = (string)$_POST['time_slot'];
    }

    // redirect to address entry page
    header('Location: address.php');
    exit;
}
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


    <?php if (!empty($time_slot)): ?>
        <div class="container mt-4">
            <div class="alert alert-info text-center mb-0 fs-4" role="alert">
                選択した時間帯：<span class="fw-bold"><?php echo htmlspecialchars($time_slot); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php if (!empty($time_slot)): ?>
            <input type="hidden" name="time_slot" value="<?php echo htmlspecialchars($time_slot); ?>">
        <?php endif; ?>
        <div class="container container_def h-75 pb-0 mb-0">
            <!-- Images row -->
            <div class="row text-center mb-3 align-items-center">
                <div class="col-md-4 mb-2 d-flex align-items-center justify-content-center">
                    <img src="img/hero.png" class="img-fluid mb-3 img-s" style="width:60%" alt="S size pizza">
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-center justify-content-center">
                    <img src="img/hero.png" class="img-fluid mb-3 w-75" alt="M size pizza">
                </div>
                <div class="col-md-4 mb-2 d-flex align-items-center justify-content-center">
                    <img src="img/hero.png" class="img-fluid mb-3 w-100" alt="L size pizza">
                </div>
            </div>

            <!-- Details & inputs row -->
            <div class="row text-center align-items-stretch">
                <!-- S Size -->
                <div class="col-md-4 mb-4 product" data-price="<?= $price_s ?>">
                    <h5 class="fw-bold fs-3">ピザマーガリーター<br>Sサイズ</h5>
                    <p class="fs-4">20cm（1〜2人）</p>
                    <p class="price fw-bold fs-3"><?= $price_s ?>¥</p>

                    <div class="input-group justify-content-center w-50 mx-auto">
                        <button type="button" class="btn btn-outline-secondary btn-decr fw-bolder fs-1">−</button>
                        <input type="number" name="qty_s" min="0" class="form-control text-center qty fs-3" value="0" aria-label="S quantity">
                        <button type="button" class="btn btn-outline-secondary btn-incr fw-bolder fs-1">＋</button>
                    </div>
                </div>
                <!-- M Size -->
                <div class="col-md-4 mb-4 product" data-price="<?= $price_m ?>">
                    <h5 class="fw-bold fs-3">ピザマーガリーター<br>Mサイズ</h5>
                    <p class="fs-4">27cm（2〜3人）</p>
                    <p class="price fw-bold fs-3"><?= $price_m ?>¥</p>

                    <div class="input-group justify-content-center w-50 mx-auto">
                        <button type="button" class="btn btn-outline-secondary btn-decr fw-bolder fs-1">−</button>
                        <input type="number" name="qty_m" min="0" class="form-control text-center qty fs-3" value="0" aria-label="M quantity">
                        <button type="button" class="btn btn-outline-secondary btn-incr fw-bolder fs-1">＋</button>
                    </div>
                </div>

                <!-- L Size -->
                <div class="col-md-4 mb-4 product" data-price="<?= $price_l ?>">
                    <h5 class="fw-bold fs-3">ピザマーガリーター<br>Lサイズ</h5>
                    <p class="fs-4">32cm（3〜4人）</p>
                    <p class="price fw-bold fs-3"><?= $price_l ?>¥</p>

                    <div class="input-group justify-content-center w-50 mx-auto">
                        <button type="button" class="btn btn-outline-secondary btn-decr fw-bolder fs-1">−</button>
                        <input type="number" name="qty_l" min="0" class="form-control text-center qty fs-3" value="0" aria-label="L quantity">
                        <button type="button" class="btn btn-outline-secondary btn-incr fw-bolder fs-1">＋</button>
                    </div>
                </div>

            </div>

        </div>

        <!-- Total & Next -->
        <div class="row align-items-center m-5">
            <div class="col-12 d-flex justify-content-center gap-5">
                <button type="button" id="totalButton" class="btn btn-filled-custom btn-lg fs-2">
                    合計： <span id="totalAmount">0¥</span>
                </button>
                <button type="submit" class="btn btn-success fs-3">
                    住所入力へ →
                </button>
            </div>
        </div>
        </div>

    </form>


    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const products = document.querySelectorAll('.product');
            const totalEl = document.getElementById('totalAmount');

            function parseQty(el) {
                const v = parseInt(el.value, 10);
                return Number.isNaN(v) ? 0 : v;
            }

            function updateTotal() {
                let total = 0;
                products.forEach(p => {
                    const price = parseInt(p.dataset.price, 10) || 0;
                    const qtyInput = p.querySelector('.qty');
                    const qty = parseQty(qtyInput);
                    total += price * qty;
                });
                totalEl.textContent = total + '¥';
            }

            // Attach handlers
            products.forEach(p => {
                const incr = p.querySelector('.btn-incr');
                const decr = p.querySelector('.btn-decr');
                const qtyInput = p.querySelector('.qty');

                incr && incr.addEventListener('click', () => {
                    qtyInput.value = parseQty(qtyInput) + 1;
                    updateTotal();
                });
                decr && decr.addEventListener('click', () => {
                    const newVal = Math.max(0, parseQty(qtyInput) - 1);
                    qtyInput.value = newVal;
                    updateTotal();
                });
            });

            // initialize total
            updateTotal();
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