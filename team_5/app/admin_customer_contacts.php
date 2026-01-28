<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/db_config.php';

$errorMessage = '';
$contacts = [];

$qRaw = trim((string)($_GET['q'] ?? ''));
$fromRaw = trim((string)($_GET['from'] ?? ''));
$toRaw = trim((string)($_GET['to'] ?? ''));
$export = (string)($_GET['export'] ?? '');

$isValidDate = static function (string $value): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
};

$normalizeDigits = static function (string $value): string {
    return preg_replace('/\D+/', '', $value) ?? '';
};

try {
    $sql = "SELECT phone,
                   name,
                   zipcode,
                   address,
                   building,
                   room,
                   to_char(first_seen_at AT TIME ZONE 'Asia/Tokyo', 'YYYY-MM-DD HH24:MI') AS first_seen_jst,
                   to_char(last_seen_at AT TIME ZONE 'Asia/Tokyo', 'YYYY-MM-DD HH24:MI') AS last_seen_jst
            FROM customer_contacts";

    $where = [];
    $params = [];

    if ($qRaw !== '') {
        $digits = $normalizeDigits($qRaw);
        $where[] = "(name ILIKE :q OR phone LIKE :phone_like)";
        $params[':q'] = '%' . $qRaw . '%';
        $params[':phone_like'] = '%' . ($digits !== '' ? $digits : $qRaw) . '%';
    }

    if ($fromRaw !== '' && $isValidDate($fromRaw)) {
        $where[] = "(last_seen_at AT TIME ZONE 'Asia/Tokyo')::date >= :from_date";
        $params[':from_date'] = $fromRaw;
    }

    if ($toRaw !== '' && $isValidDate($toRaw)) {
        $where[] = "(last_seen_at AT TIME ZONE 'Asia/Tokyo')::date <= :to_date";
        $params[':to_date'] = $toRaw;
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY last_seen_at DESC LIMIT 1000';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contacts = $stmt->fetchAll();

    if ($export === 'csv') {
        $filename = 'customer_contacts_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        // UTF-8 BOM for Excel compatibility
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fputcsv($out, ['phone', 'name', 'zipcode', 'address', 'building', 'room', 'first_seen_jst', 'last_seen_jst']);
        foreach ($contacts as $row) {
            fputcsv($out, [
                (string)($row['phone'] ?? ''),
                (string)($row['name'] ?? ''),
                (string)($row['zipcode'] ?? ''),
                (string)($row['address'] ?? ''),
                (string)($row['building'] ?? ''),
                (string)($row['room'] ?? ''),
                (string)($row['first_seen_jst'] ?? ''),
                (string)($row['last_seen_jst'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }
} catch (PDOException $e) {
    $errorMessage = '顧客連絡先を取得できませんでした。（DB未準備の可能性）';
}

$buildQuery = static function (array $overrides = []) use ($qRaw, $fromRaw, $toRaw): string {
    $query = array_merge(
        [
            'q' => $qRaw,
            'from' => $fromRaw,
            'to' => $toRaw,
        ],
        $overrides
    );

    // Remove empty values for cleaner URLs
    foreach ($query as $k => $v) {
        if ($v === '' || $v === null) {
            unset($query[$k]);
        }
    }

    return http_build_query($query);
};
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>顧客連絡先</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .contacts-table td,
        .contacts-table th {
            padding: 0.35rem 0.5rem;
        }
    </style>
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

    <div class="container my-4">
        <div class="container_def">
            <h3 class="text-center fw-bold mb-4">顧客連絡先（プロモーション用）</h3>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 align-items-center flex-wrap" method="get" action="admin_customer_contacts.php">
                    <label for="q" class="fw-bold mb-0">検索</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="<?php echo htmlspecialchars($qRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                        class="form-control"
                        style="max-width: 260px;"
                        placeholder="名前 or 電話番号">

                    <label for="from" class="fw-bold mb-0">from</label>
                    <input type="date" id="from" name="from" value="<?php echo htmlspecialchars($fromRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="form-control">

                    <label for="to" class="fw-bold mb-0">to</label>
                    <input type="date" id="to" name="to" value="<?php echo htmlspecialchars($toRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" class="form-control">

                    <button type="submit" class="btn btn-filled-custom px-4">検索</button>
                    <a class="btn btn-outline-secondary px-4" href="admin_customer_contacts.php">クリア</a>
                </form>

                <a class="btn btn-outline-custom px-4" href="admin_customer_contacts.php?<?php echo htmlspecialchars($buildQuery(['export' => 'csv']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">CSV出力</a>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($errorMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-light border" role="alert">
                表示件数: <span class="fw-bold"><?php echo (int)count($contacts); ?></span>（最大1000件）
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered contacts-table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>電話番号</th>
                            <th>名前</th>
                            <th>住所</th>
                            <th>初回</th>
                            <th>最終</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$contacts): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">データがありません</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $c): ?>
                                <?php
                                $addr = trim((string)($c['address'] ?? ''));
                                $building = trim((string)($c['building'] ?? ''));
                                $room = trim((string)($c['room'] ?? ''));
                                if ($building !== '') {
                                    $addr .= ($addr !== '' ? ' ' : '') . $building;
                                }
                                if ($room !== '') {
                                    $addr .= ($addr !== '' ? ' ' : '') . $room;
                                }
                                ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars((string)($c['phone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($addr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($c['first_seen_jst'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string)($c['last_seen_jst'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="admin_panel.php" class="btn btn-filled-custom btn-lg fw-bold rounded-2 text-light">ホーム</a>
                <a href="logout.php" class="btn btn-danger btn-lg fw-bold">ログアウト</a>
            </div>
        </div>
    </div>

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