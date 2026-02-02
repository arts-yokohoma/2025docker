<?php
require_once __DIR__ . '/db_config.php';

$tz = new DateTimeZone('Asia/Tokyo');
$today = (new DateTime('now', $tz))->format('Y-m-d');

$shift_date = (string)($_GET['shift_date'] ?? $today);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $shift_date)) {
    $shift_date = $today;
}

$errorMessage = '';
$shiftRow = null;
$shiftHasPeriodColumns = false;
$slots = [];

try {
    // Try new schema first (period columns). Fall back to legacy if columns do not exist.
    try {
        $shiftStmt = $pdo->prepare(
            "SELECT shift_date, kitchen_count, driver_count, created_at, updated_at,\n" .
                "       morning_kitchen, morning_driver, evening_kitchen, evening_driver\n" .
                "  FROM shifts\n" .
                " WHERE shift_date = :shift_date"
        );
        $shiftStmt->execute([':shift_date' => $shift_date]);
        $shiftRow = $shiftStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $shiftHasPeriodColumns = true;
    } catch (PDOException $e) {
        $shiftStmt = $pdo->prepare(
            "SELECT shift_date, kitchen_count, driver_count, created_at, updated_at\n" .
                "  FROM shifts\n" .
                " WHERE shift_date = :shift_date"
        );
        $shiftStmt->execute([':shift_date' => $shift_date]);
        $shiftRow = $shiftStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $shiftHasPeriodColumns = false;
    }

    // Aggregate used orders per time_slot for the selected day (JST).
    $usedStmt = $pdo->prepare(
        "SELECT time_slot, COUNT(*) AS used\n" .
            "  FROM orders\n" .
            " WHERE (created_at AT TIME ZONE 'Asia/Tokyo')::date = :shift_date\n" .
            " GROUP BY time_slot"
    );
    $usedStmt->execute([':shift_date' => $shift_date]);
    $usedMap = [];
    foreach ($usedStmt->fetchAll() as $u) {
        $usedMap[(string)$u['time_slot']] = (int)$u['used'];
    }

    // Load time slots for the selected day.
    $slotStmt = $pdo->prepare(
        "SELECT slot_start, slot_end, capacity, available\n" .
            "  FROM time_slots\n" .
            " WHERE shift_date = :shift_date\n" .
            " ORDER BY slot_start"
    );
    $slotStmt->execute([':shift_date' => $shift_date]);

    foreach ($slotStmt->fetchAll() as $row) {
        $label = substr((string)$row['slot_start'], 0, 5) . '-' . substr((string)$row['slot_end'], 0, 5);
        $capacity = (int)($row['capacity'] ?? 0);
        $used = $usedMap[$label] ?? 0;
        $remaining = $capacity - $used;
        $isActive = filter_var($row['available'], FILTER_VALIDATE_BOOLEAN) && $remaining > 0;

        $slots[] = [
            'slot_start' => (string)$row['slot_start'],
            'slot_end' => (string)$row['slot_end'],
            'label' => $label,
            'capacity' => $capacity,
            'used' => $used,
            'remaining' => $remaining,
            'active' => $isActive,
        ];
    }
} catch (Exception $e) {
    $errorMessage = 'シフト状況の取得に失敗しました。（DB未準備の可能性）';
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$totals = [
    'capacity' => 0,
    'used' => 0,
    'remaining' => 0,
    'activeSlots' => 0,
    'totalSlots' => 0,
];

foreach ($slots as $s) {
    $totals['totalSlots']++;
    $totals['capacity'] += (int)$s['capacity'];
    $totals['used'] += (int)$s['used'];
    $totals['remaining'] += max(0, (int)$s['remaining']);
    if ((bool)$s['active']) {
        $totals['activeSlots']++;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>シフト状況</title>
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
                        <a class="nav-link btn btn-filled-custom rounded-pill px-4 m-2" href="shift.php">シフト管理</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-contact rounded-pill px-4 m-2" href="admin_panel.php">管理メニュー</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->

    <div class="container mt-5">
        <div class="container_def mx-auto" style="max-width: 960px;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3">
                <div>
                    <h2 class="fw-bolder mb-1">現在のシフト状況</h2>
                    <div class="text-muted">対象日: <?php echo h($shift_date); ?></div>
                </div>

                <form class="d-flex gap-2" method="get" action="shift_status.php">
                    <input type="date" class="form-control" name="shift_date" value="<?php echo h($shift_date); ?>" required>
                    <button type="submit" class="btn btn-success fw-bold">表示</button>
                </form>
            </div>

            <hr>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert"><?php echo h($errorMessage); ?></div>
            <?php else: ?>
                <?php if ($shiftRow === null): ?>
                    <div class="alert alert-warning" role="alert">
                        この日のシフトはまだ登録されていません。（<?php echo h($shift_date); ?>）
                    </div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-light rounded-3 h-100 fs-4">
                                <div class="fw-bold mb-1">登録情報</div>
                                <div>Kitchen: <?php echo h((string)($shiftRow['kitchen_count'] ?? '0')); ?> 人</div>
                                <div>Driver: <?php echo h((string)($shiftRow['driver_count'] ?? '0')); ?> 人</div>
                                <div class="text-muted small mt-2">
                                    更新: <?php echo h((string)($shiftRow['updated_at'] ?? '')); ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4 fs-4">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="fw-bold mb-1">時間帯サマリー</div>
                                <div>スロット数: <?php echo h((string)$totals['totalSlots']); ?></div>
                                <div>有効スロット: <?php echo h((string)$totals['activeSlots']); ?></div>
                                <div>総キャパ: <?php echo h((string)$totals['capacity']); ?></div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4 fs-4">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <div class="fw-bold mb-1">予約状況</div>
                                <div>予約数: <?php echo h((string)$totals['used']); ?></div>
                                <div>残り: <?php echo h((string)$totals['remaining']); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($shiftHasPeriodColumns): ?>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="fw-bold mb-1">日勤 (10:00〜16:00)</div>
                                    <div>Kitchen: <?php echo h((string)($shiftRow['morning_kitchen'] ?? '0')); ?> 人</div>
                                    <div>Driver: <?php echo h((string)($shiftRow['morning_driver'] ?? '0')); ?> 人</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="p-3 bg-light rounded-3 h-100">
                                    <div class="fw-bold mb-1">夕勤 (16:00〜22:00)</div>
                                    <div>Kitchen: <?php echo h((string)($shiftRow['evening_kitchen'] ?? '0')); ?> 人</div>
                                    <div>Driver: <?php echo h((string)($shiftRow['evening_driver'] ?? '0')); ?> 人</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="mt-3">
                    <h4 class="fw-bold">タイムスロット一覧</h4>
                    <?php if (empty($slots)): ?>
                        <div class="alert alert-warning" role="alert">
                            この日のタイムスロットがまだ作成されていません。
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>時間帯</th>
                                        <th class="text-end">キャパ</th>
                                        <th class="text-end">予約</th>
                                        <th class="text-end">残り</th>
                                        <th class="text-center">状態</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slots as $s): ?>
                                        <?php
                                        $cap = (int)$s['capacity'];
                                        $used = (int)$s['used'];
                                        $rem = (int)$s['remaining'];
                                        $active = (bool)$s['active'];
                                        ?>
                                        <tr>
                                            <td><?php echo h((string)$s['label']); ?></td>
                                            <td class="text-end"><?php echo h((string)$cap); ?></td>
                                            <td class="text-end"><?php echo h((string)$used); ?></td>
                                            <td class="text-end"><?php echo h((string)max(0, $rem)); ?></td>
                                            <td class="text-center">
                                                <?php if ($cap <= 0): ?>
                                                    <span class="badge bg-secondary">無効</span>
                                                <?php elseif ($active): ?>
                                                    <span class="badge bg-success">受付中</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">満席</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>