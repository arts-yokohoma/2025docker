<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/db_config.php';

$errorMessage = '';
$inquiries = [];

$qRaw = trim((string)($_GET['q'] ?? ''));
$fromRaw = trim((string)($_GET['from'] ?? ''));
$toRaw = trim((string)($_GET['to'] ?? ''));
$pageRaw = (string)($_GET['page'] ?? '1');

$perPage = 50;
$page = (int)$pageRaw;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $perPage;

$isValidDate = static function (string $value): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
};

$normalizeDigits = static function (string $value): string {
    return preg_replace('/\D+/', '', $value) ?? '';
};

$buildWhere = static function () use ($qRaw, $fromRaw, $toRaw, $isValidDate, $normalizeDigits): array {
    $where = [];
    $params = [];

    if ($qRaw !== '') {
        $digits = $normalizeDigits($qRaw);
        $where[] = '(name ILIKE :q OR email ILIKE :q OR message ILIKE :q OR phone LIKE :phone_like)';
        $params[':q'] = '%' . $qRaw . '%';
        $params[':phone_like'] = '%' . ($digits !== '' ? $digits : $qRaw) . '%';
    }

    if ($fromRaw !== '' && $isValidDate($fromRaw)) {
        $where[] = "(created_at AT TIME ZONE 'Asia/Tokyo')::date >= :from_date";
        $params[':from_date'] = $fromRaw;
    }

    if ($toRaw !== '' && $isValidDate($toRaw)) {
        $where[] = "(created_at AT TIME ZONE 'Asia/Tokyo')::date <= :to_date";
        $params[':to_date'] = $toRaw;
    }

    return [$where, $params];
};

try {
    [$where, $params] = $buildWhere();

    $countSql = 'SELECT COUNT(*) AS cnt FROM customer';
    if ($where) {
        $countSql .= ' WHERE ' . implode(' AND ', $where);
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)($countStmt->fetch()['cnt'] ?? 0);

    $sql = "SELECT phone,
                   name,
                   email,
                   message,
                   to_char(created_at AT TIME ZONE 'Asia/Tokyo', 'YYYY-MM-DD HH24:MI') AS created_jst
            FROM customer";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY created_at DESC';

    $sql .= ' LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $inquiries = $stmt->fetchAll();

    $totalPages = (int)max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
} catch (PDOException $e) {
    $errorMessage = 'お問い合わせデータを取得できませんでした。（DB未準備の可能性）';
    $total = 0;
    $totalPages = 1;
}

$buildQuery = static function (array $overrides = []) use ($qRaw, $fromRaw, $toRaw, $page): string {
    $query = array_merge(
        [
            'q' => $qRaw,
            'from' => $fromRaw,
            'to' => $toRaw,
            'page' => (string)$page,
        ],
        $overrides
    );

    foreach ($query as $k => $v) {
        if ($v === '' || $v === null) {
            unset($query[$k]);
        }
    }

    return http_build_query($query);
};

$h = static function (string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>お問い合わせ一覧</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .inquiries-table td,
        .inquiries-table th {
            padding: 0.35rem 0.5rem;
        }

        .message-cell {
            max-width: 520px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        details>summary {
            cursor: pointer;
        }
    </style>
</head>

<body>
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

    <div class="container my-4">
        <div class="container_def">
            <h3 class="text-center fw-bold mb-4">お問い合わせ一覧</h3>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <form class="d-flex gap-2 align-items-center flex-wrap" method="get" action="admin_contact_inquiries.php">
                    <label for="q" class="fw-bold mb-0">検索</label>
                    <input type="text" id="q" name="q" value="<?php echo $h($qRaw); ?>" class="form-control" style="max-width: 280px;" placeholder="名前 / 電話 / メール / 内容">

                    <label for="from" class="fw-bold mb-0">from</label>
                    <input type="date" id="from" name="from" value="<?php echo $h($fromRaw); ?>" class="form-control w-25">

                    <label for="to" class="fw-bold mb-0">to</label>
                    <input type="date" id="to" name="to" value="<?php echo $h($toRaw); ?>" class="form-control w-25">

                    <button type="submit" class="btn btn-filled-custom px-4">検索</button>
                    <a class="btn btn-outline-secondary px-4" href="admin_contact_inquiries.php">クリア</a>
                </form>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $h($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-light border" role="alert">
                全件数: <span class="fw-bold"><?php echo (int)$total; ?></span>
                / 表示中: <span class="fw-bold"><?php echo (int)count($inquiries); ?></span>
                （<?php echo (int)$perPage; ?>件/ページ）
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered inquiries-table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>日時</th>
                            <th>名前</th>
                            <th>電話番号</th>
                            <th>メール</th>
                            <th>内容</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$inquiries): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">データがありません</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inquiries as $row): ?>
                                <?php
                                $message = (string)($row['message'] ?? '');
                                $summary = $message;
                                if (mb_strlen($summary, 'UTF-8') > 120) {
                                    $summary = mb_substr($summary, 0, 120, 'UTF-8') . '...';
                                }
                                ?>
                                <tr>
                                    <td><?php echo $h((string)($row['created_jst'] ?? '')); ?></td>
                                    <td class="fw-bold"><?php echo $h((string)($row['name'] ?? '')); ?></td>
                                    <td><?php echo $h((string)($row['phone'] ?? '')); ?></td>
                                    <td><?php echo $h((string)($row['email'] ?? '')); ?></td>
                                    <td class="message-cell">
                                        <details>
                                            <summary><?php echo $h($summary); ?></summary>
                                            <div class="mt-2 border rounded p-2 bg-white"><?php echo $h($message); ?></div>
                                        </details>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($totalPages) && $totalPages > 1): ?>
                <?php
                $prevPage = max(1, $page - 1);
                $nextPage = min($totalPages, $page + 1);
                ?>
                <nav aria-label="pagination" class="d-flex justify-content-center mt-3">
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="admin_contact_inquiries.php?<?php echo $h($buildQuery(['page' => (string)$prevPage])); ?>">前へ</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link"><?php echo (int)$page; ?> / <?php echo (int)$totalPages; ?></span>
                        </li>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="admin_contact_inquiries.php?<?php echo $h($buildQuery(['page' => (string)$nextPage])); ?>">次へ</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="admin_panel.php" class="btn btn-filled-custom btn-lg fw-bold rounded-2 text-light">ホーム</a>
                <a href="logout.php" class="btn btn-danger btn-lg fw-bold">ログアウト</a>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>

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