<?php
session_start();

$errorMessage = '';
$slots = [];

require_once __DIR__ . '/db_config.php';

$tz = new DateTimeZone('Asia/Tokyo');
$today = (new DateTime('now', $tz))->format('Y-m-d');

try {
    // Aggregate used orders per time_slot for today (JST).
    $usedStmt = $pdo->prepare(
        "SELECT time_slot, COUNT(*) AS used
         FROM orders
         WHERE (created_at AT TIME ZONE 'Asia/Tokyo')::date = :shift_date
         GROUP BY time_slot"
    );
    $usedStmt->execute([':shift_date' => $today]);
    $usedMap = [];
    foreach ($usedStmt->fetchAll() as $u) {
        $usedMap[(string)$u['time_slot']] = (int)$u['used'];
    }

    // Load today's time slots from DB.
    $slotStmt = $pdo->prepare(
        "SELECT slot_start, slot_end, capacity, available
         FROM time_slots
         WHERE shift_date = :shift_date
         ORDER BY slot_start"
    );
    $slotStmt->execute([':shift_date' => $today]);

    foreach ($slotStmt->fetchAll() as $row) {
        $label = substr((string)$row['slot_start'], 0, 5) . '-' . substr((string)$row['slot_end'], 0, 5);
        $capacity = (int)($row['capacity'] ?? 0);
        if ($capacity <= 0) {
            continue;
        }
        $used = $usedMap[$label] ?? 0;
        $remaining = $capacity - $used;
        $isActive = filter_var($row['available'], FILTER_VALIDATE_BOOLEAN) && $remaining > 0;

        $slots[] = [
            'label' => $label,
            'capacity' => $capacity,
            'used' => $used,
            'remaining' => $remaining,
            'active' => $isActive,
        ];
    }
} catch (Exception $e) {
    $errorMessage = '時間帯の取得に失敗しました。（DB未準備の可能性）';
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
                    <button class="btn btn-contact rounded-pill px-4">お問い合わせ</button>
                </ul>
            </div>
        </div>
    </nav>
    <!-- navbar -->


    <form id="timeSlotForm" action="order_select.php" method="get">
        <input type="hidden" name="time_slot" id="selectedTimeSlot" value="">

        <div class="container mt-3">
            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php elseif (empty($slots)): ?>
                <div class="alert alert-warning" role="alert">
                    本日の時間帯がまだ作成されていません。管理者がシフトを登録してください。（<?php echo htmlspecialchars($today); ?>）
                </div>
            <?php else: ?>
                <div id="time-slots" class="row g-4 text-center">
                    <?php foreach ($slots as $slot): ?>
                        <?php
                        $label = (string)$slot['label'];
                        $active = (bool)$slot['active'];
                        $remaining = (int)$slot['remaining'];
                        ?>
                        <div class="col-6 col-md-3">
                            <button
                                type="button"
                                class="btn w-100 py-4 time-slot-btn <?php echo $active ? '' : 'disabled'; ?>"
                                <?php echo $active ? '' : 'disabled'; ?>
                                data-slot="<?php echo htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                aria-pressed="false">
                                <?php if ($active): ?>
                                    <span class="fs-3 fw-bold"><?php echo htmlspecialchars($label); ?></span><br>
                                    <span class="small">残り <?php echo htmlspecialchars((string)$remaining); ?></span>
                                <?php else: ?>
                                    <span class="fs-3 fw-bold"><?php echo htmlspecialchars($label); ?></span><br>
                                    <span class="text-danger">満席</span>
                                <?php endif; ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-center my-5">
            <button id="submitBtn" type="submit" class="btn btn-success fs-3 px-5" disabled>
                メニューへ進む
            </button>
        </div>
    </form>



    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        const container = document.getElementById("time-slots");
        const selectedTimeSlot = document.getElementById("selectedTimeSlot");
        const submitBtn = document.getElementById("submitBtn");
        const form = document.getElementById("timeSlotForm");

        if (container) {
            container.querySelectorAll('.time-slot-btn').forEach(btn => {
                if (btn.disabled) return;
                btn.addEventListener('click', function() {
                    container.querySelectorAll('.time-slot-btn.is-selected').forEach(b => {
                        b.classList.remove('is-selected');
                        b.setAttribute('aria-pressed', 'false');
                    });

                    btn.classList.add('is-selected');
                    btn.setAttribute('aria-pressed', 'true');
                    selectedTimeSlot.value = btn.dataset.slot || '';
                    submitBtn.disabled = !selectedTimeSlot.value;
                });
            });
        }

        form.addEventListener('submit', function(e) {
            if (!selectedTimeSlot.value) {
                e.preventDefault();
                alert('時間を1つ選択してください。');
            }
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
                        <li class="list-inline-item"><a href="admin_login.php">Login</a></li>
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