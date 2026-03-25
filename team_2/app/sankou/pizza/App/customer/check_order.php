<?php
session_start();
require_once '../database/db_conn.php'; // $lang and Timezone included

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$order_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) { die("Order not found!"); }

// 3. Timer Calculation
$start_time = strtotime($order['order_date']);
$duration_mins = intval($order['estimated_mins']); 
$target_time = $start_time + ($duration_mins * 60);
$remaining_seconds = $target_time - time();

// Status Logic with Japanese Text
$status = $order['status'];
$status_color = '#ccc';
$status_icon = 'fa-clock';
$show_timer = true;

if ($status == 'Pending') {
    $status_text = $lang['status_pending'];
    $status_color = "#f1c40f"; $status_icon = "fa-hourglass-start";
} elseif ($status == 'Cooking') {
    $status_text = $lang['status_cooking'];
    $status_color = "#e67e22"; $status_icon = "fa-fire";
} elseif ($status == 'Delivering') {
    $status_text = $lang['status_delivering'];
    $status_color = "#3498db"; $status_icon = "fa-motorcycle";
} elseif ($status == 'Completed') {
    $status_text = $lang['status_completed'];
    $status_color = "#2ecc71"; $status_icon = "fa-check-circle";
    $show_timer = false;
} elseif ($status == 'Rejected') {
    $status_text = $lang['status_rejected'];
    $status_color = "#e74c3c"; $status_icon = "fa-times-circle";
    $show_timer = false;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status #<?= $order_id ?></title>
    <meta http-equiv="refresh" content="30">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; width: 90%; max-width: 400px; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; }
        .status-icon { font-size: 60px; margin-bottom: 15px; color: <?= $status_color ?>; }
        .status-text { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 5px; }
        .order-info { color: #777; font-size: 14px; margin-bottom: 20px; }
        
        /* TIMER STYLES */
        .timer-box { background: #f8f9fa; border: 2px solid <?= $status_color ?>; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .timer-label { font-size: 12px; color: #666; font-weight: bold; letter-spacing: 1px; }
        .countdown { font-size: 32px; font-weight: bold; color: #333; }
        .eta { font-size: 12px; color: #888; }

        .btn-home { display: inline-block; text-decoration: none; background: #333; color: white; padding: 10px 20px; border-radius: 5px; margin-top: 15px; }
        .details-box { text-align: left; background: #fafafa; padding: 15px; border-radius: 8px; font-size: 13px; }
        .details-box p { margin: 5px 0; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    </style>
</head>
<body>

    <div class="card">
        <div class="status-icon">
            <i class="fas <?= $status_icon ?>"></i>
        </div>

        <div class="status-text"><?= $status_text ?></div>
        <div class="order-info">Order ID: #<?= $order_id ?> | <?= $order['pizza_type'] ?> x <?= $order['quantity'] ?></div>

        <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">

        <?php if ($show_timer): ?>
            <div class="timer-box">
                <div class="timer-label"><?= $lang['eta'] ?></div>
                <div class="countdown" id="timer">--:--</div>
                <div class="eta">
                    (目安: <?= $duration_mins ?> <?= $lang['mins'] ?>)
                </div>
            </div>
        <?php endif; ?>

        <div class="details-box">
            <p><b><?= $lang['name'] ?>:</b> <?= htmlspecialchars($order['customer_name']) ?></p>
            <p><b><?= $lang['phone'] ?>:</b> <?= htmlspecialchars($order['phonenumber']) ?></p>
            <p><b><?= $lang['address'] ?>:</b> <?= htmlspecialchars($order['address']) ?></p>
        </div>

        <a href="index.php" class="btn-home">トップページへ戻る</a>
    </div>

    <script>
        let remainingSeconds = <?= ($remaining_seconds > 0) ? $remaining_seconds : 0 ?>;

        function updateTimer() {
            if (remainingSeconds <= 0) {
                document.getElementById("timer").innerHTML = "<?= $lang['arriving_soon'] ?>";
                document.getElementById("timer").style.color = "#27ae60";
                return;
            }

            let m = Math.floor(remainingSeconds / 60);
            let s = remainingSeconds % 60;
            s = s < 10 ? "0" + s : s;

            document.getElementById("timer").innerHTML = m + ":" + s + " <span style='font-size:14px'><?= $lang['mins'] ?></span>";
            remainingSeconds--;
        }

        <?php if ($show_timer): ?>
            updateTimer();
            setInterval(updateTimer, 1000);
        <?php endif; ?>
    </script>

</body>
</html>