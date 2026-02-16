<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/db_config.php';

$errorMessage = '';
$successMessage = '';
$inquiries = [];

if (empty($_SESSION['admin_inquiry_csrf'])) {
    $_SESSION['admin_inquiry_csrf'] = bin2hex(random_bytes(16));
}

$allowedStatuses = ['未対応', '対応中', '対応済み'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($csrf === '' || !hash_equals((string)$_SESSION['admin_inquiry_csrf'], $csrf)) {
        $errorMessage = '更新に失敗しました。（セキュリティ検証エラー）';
    } else {
        $phone = trim((string)($_POST['phone'] ?? ''));
        $returnQuery = (string)($_POST['return_query'] ?? '');
        $status = trim((string)($_POST['inquiry_status'] ?? ''));
        $method = trim((string)($_POST['inquiry_method'] ?? ''));

        try {
            if ($action === 'update_status') {
                if (!in_array($status, $allowedStatuses, true)) {
                    throw new RuntimeException('invalid status');
                }
                $u = $pdo->prepare('UPDATE customer SET inquiry_status = :s WHERE phone = :p');
                $u->execute([':s' => $status, ':p' => $phone]);
            } elseif ($action === 'update_method') {
                if ($method !== 'email' && $method !== 'phone') {
                    throw new RuntimeException('invalid method');
                }
                $u = $pdo->prepare('UPDATE customer SET inquiry_method = :m WHERE phone = :p');
                $u->execute([':m' => $method, ':p' => $phone]);
            }

            $params = [];
            parse_str($returnQuery, $params);
            $params['updated'] = '1';
            header('Location: admin_contact_inquiries.php?' . http_build_query($params));
            exit;
        } catch (Exception $e) {
            $errorMessage = '更新に失敗しました。（サーバーエラー）';
        }
    }
}

$qRaw = trim((string)($_GET['q'] ?? ''));
$methodRaw = trim((string)($_GET['method'] ?? ''));
$fromRaw = trim((string)($_GET['from'] ?? ''));
$toRaw = trim((string)($_GET['to'] ?? ''));
$pageRaw = (string)($_GET['page'] ?? '1');

if (!empty($_GET['updated'])) {
    $successMessage = '更新しました。';
}

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

$buildWhere = static function () use ($qRaw, $methodRaw, $fromRaw, $toRaw, $isValidDate, $normalizeDigits): array {
    $where = [];
    $params = [];

    if ($qRaw !== '') {
        $digits = $normalizeDigits($qRaw);
        $where[] = '(name ILIKE :q OR email ILIKE :q OR message ILIKE :q OR phone LIKE :phone_like)';
        $params[':q'] = '%' . $qRaw . '%';
        $params[':phone_like'] = '%' . ($digits !== '' ? $digits : $qRaw) . '%';
    }

    if ($methodRaw === 'email' || $methodRaw === 'phone') {
        $where[] = 'inquiry_method = :method';
        $params[':method'] = $methodRaw;
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
                 inquiry_method,
                 inquiry_status,
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

$buildQuery = static function (array $overrides = []) use ($qRaw, $methodRaw, $fromRaw, $toRaw, $page): string {
    $query = array_merge(
        [
            'q' => $qRaw,
            'method' => $methodRaw,
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

                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold">方法</span>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="method" id="method_email" value="email" <?php echo $methodRaw === 'email' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="method_email">メール</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="radio" name="method" id="method_phone" value="phone" <?php echo $methodRaw === 'phone' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="method_phone">電話</label>
                        </div>
                        <small class="text-muted">（未選択=すべて）</small>
                    </div>

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

            <?php if ($successMessage !== ''): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $h($successMessage); ?>
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
                            <th>方法</th>
                            <th>ステータス</th>
                            <th>名前</th>
                            <th>電話番号</th>
                            <th>メール</th>
                            <th>内容</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$inquiries): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">データがありません</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inquiries as $row): ?>
                                <?php
                                $phone = (string)($row['phone'] ?? '');
                                $rowMethod = (string)($row['inquiry_method'] ?? 'email');
                                $rowStatus = (string)($row['inquiry_status'] ?? '未対応');
                                $rowClass = '';
                                if ($rowStatus === '未対応') {
                                    $rowClass = 'table-danger';
                                } elseif ($rowStatus === '対応中') {
                                    $rowClass = 'table-warning';
                                } elseif ($rowStatus === '対応済み') {
                                    $rowClass = 'table-success';
                                }
                                $message = (string)($row['message'] ?? '');
                                $summary = $message;
                                if (mb_strlen($summary, 'UTF-8') > 120) {
                                    $summary = mb_substr($summary, 0, 120, 'UTF-8') . '...';
                                }
                                ?>
                                <tr class="<?php echo $h($rowClass); ?>">
                                    <td><?php echo $h((string)($row['created_jst'] ?? '')); ?></td>
                                    <td>
                                        <form method="post" class="d-flex gap-2 align-items-center flex-wrap">
                                            <input type="hidden" name="action" value="update_method">
                                            <input type="hidden" name="csrf_token" value="<?php echo $h((string)$_SESSION['admin_inquiry_csrf']); ?>">
                                            <input type="hidden" name="phone" value="<?php echo $h($phone); ?>">
                                            <input type="hidden" name="return_query" value="<?php echo $h((string)($_SERVER['QUERY_STRING'] ?? '')); ?>">

                                            <select name="inquiry_method" class="form-select form-select-sm" style="max-width: 120px;">
                                                <option value="email" <?php echo $rowMethod !== 'phone' ? 'selected' : ''; ?>>メール</option>
                                                <option value="phone" <?php echo $rowMethod === 'phone' ? 'selected' : ''; ?>>電話</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">更新</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" class="d-flex gap-2 align-items-center flex-wrap">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo $h((string)$_SESSION['admin_inquiry_csrf']); ?>">
                                            <input type="hidden" name="phone" value="<?php echo $h($phone); ?>">
                                            <input type="hidden" name="return_query" value="<?php echo $h((string)($_SERVER['QUERY_STRING'] ?? '')); ?>">

                                            <select name="inquiry_status" class="form-select form-select-sm" style="max-width: 140px;">
                                                <option value="未対応" <?php echo $rowStatus === '未対応' ? 'selected' : ''; ?>>未対応</option>
                                                <option value="対応中" <?php echo $rowStatus === '対応中' ? 'selected' : ''; ?>>対応中</option>
                                                <option value="対応済み" <?php echo $rowStatus === '対応済み' ? 'selected' : ''; ?>>対応済み</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">更新</button>
                                        </form>
                                    </td>
                                    <td class="fw-bold"><?php echo $h((string)($row['name'] ?? '')); ?></td>
                                    <td><?php echo $h($phone); ?></td>
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