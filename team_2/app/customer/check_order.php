<?php
date_default_timezone_set('Asia/Tokyo');
include '../database/db_conn.php';

$order = null;

// ၁။ ဖုန်းနံပါတ်ဖြင့် ရှာခြင်း (သို့) ID ဖြင့် ရှာခြင်း
if (isset($_POST['checkphonenumber'])) {
    $chkorder = mysqli_real_escape_string($conn, $_POST['checkphonenumber']);
    $query = "SELECT * FROM orders WHERE phonenumber = '$chkorder' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    $order = $result->fetch_assoc();
} elseif (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM orders WHERE id = '$id'";
    $result = $conn->query($query);
    $order = $result->fetch_assoc();
}

// Order မရှိလျှင်
if (!$order) {
    echo "<script>alert('Order not found!'); window.location='../customer/index.php';</script>";
    exit();
}

// ၂။ Customer Confirm Logic (လက်ခံရရှိပါပြီ)
if (isset($_POST['confirm_receive'])) {
    $order_id = $_POST['order_id'];
    // Status Completed ပြောင်းမယ်၊ Received Time မှတ်မယ်
    $conn->query("UPDATE orders SET status = 'Completed', received_time = NOW() WHERE id = $order_id");
    header("Location: check_order.php?id=" . $order_id); 
    exit();
}

// ၃။ Status & Timer Logic
$status_text = "";
$status_color = "";
$show_timer = false;
$remaining_seconds = 0;
$current_time = time();

if ($order['status'] == 'Rejected') {
    // Rejected ဖြစ်နေလျှင်
    $status_text = "❌ ဤအော်ဒါကို ဆိုင်မှ ပယ်ဖျက်လိုက်ပါသည်";
    $status_color = "#c0392b";
} elseif ($order['status'] == 'Pending') {
    $status_text = "အော်ဒါ လက်ခံရရှိထားပါသည် (Waiting)";
    $status_color = "#f39c12";
} elseif ($order['status'] == 'Cooking') {
    $status_text = "👨‍🍳 စားဖိုမှူး ချက်ပြုတ်နေပါသည် (Cooking)";
    $status_color = "#d35400";
    $show_timer = true;
    if (!empty($order['start_time'])) {
        $start = strtotime($order['start_time']);
        $target = $start + (30 * 60); // မိနစ် ၃၀
        $remaining_seconds = $target - $current_time;
    }
} elseif ($order['status'] == 'Delivering') {
    $status_text = "🛵 လူကြီးမင်းထံ လာပို့နေပါပြီ (On the way)";
    $status_color = "#2980b9";
    $show_timer = true;
    
    // Timer ကို Departure Time ကနေ ၁၅ မိနစ် တွက်မယ်
    if (!empty($order['departure_time'])) {
        $dept = strtotime($order['departure_time']);
        $target = $dept + (15 * 60); // ၁၅ မိနစ်
        $remaining_seconds = $target - $current_time;
    } else {
        // Departure time မရှိရင် (Error ကာကွယ်ရန်) Start time နဲ့ပဲ ဆက်ပြ
        $start = strtotime($order['start_time']);
        $remaining_seconds = ($start + (30*60)) - $current_time;
    }
} elseif ($order['status'] == 'Completed') {
    $status_text = "✅ ပို့ဆောင်မှု ပြီးစီးပါပြီ (Completed)";
    $status_color = "#27ae60";
}

if ($remaining_seconds < 0) $remaining_seconds = 0;
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status</title>
    <meta http-equiv="refresh" content="10">
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px; }
        .card { background: white; max-width: 400px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .status-box { background-color: <?php echo $status_color; ?>; color: white; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; }
        .timer-box { font-size: 2.5em; font-weight: bold; color: #333; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 25px; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="card">
        <h2>အော်ဒါ အခြေအနေ</h2>

        <?php if ($order['status'] == 'Rejected'): ?>
            <div class="status-box">
                <?php echo $status_text; ?>
            </div>
            <p>တောင်းပန်ပါသည်။ ပစ္စည်းကုန်နေခြင်း (သို့) ဆိုင်ပိတ်ချိန် နီးကပ်နေခြင်း ဖြစ်နိုင်ပါသည်။</p>
            <a href="../customer/index.php" class="btn" style="background: darkred;">ပင်မစာမျက်နှာသို့</a>

        <?php else: ?>
            <div class="status-box">
                <?php echo $status_text; ?>
            </div>

            <?php if ($show_timer): ?>
                <p>ခန့်မှန်း ကြာချိန်:</p>
                <div class="timer-box">
                    ⏱ <span id="timer">...</span>
                </div>
            <?php elseif ($order['status'] == 'Completed'): ?>
                <div style="font-size: 1.2em; color: green; margin-bottom: 20px;">
                    🙏 ကျေးဇူးတင်ပါသည်။ အစားကောင်းကောင်း သုံးဆောင်ပါ!
                </div>
            <?php endif; ?>

            <?php if ($order['status'] == 'Delivering'): ?>
                <form method="post">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="confirm_receive" class="btn" style="background: #27ae60; width: 100%;">
                        ✅ အော်ဒါလက်ခံရရှိပါပြီ
                    </button>
                </form>
            <?php endif; ?>

            <div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 10px; text-align: left;">
                <p><strong>ID:</strong> #<?php echo $order['id']; ?></p>
                <p><strong>အမည်:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p><strong>ပီဇာ:</strong> <?php echo htmlspecialchars($order['pizza_type']); ?> (x<?php echo $order['quantity']; ?>)</p>
            </div>
            
            <a href="../customer/index.php" class="btn" style="background: #555; margin-top: 20px;">Back to Home</a>

        <?php endif; ?>
    </div>

    <?php if ($show_timer): ?>
    <script>
        let timeLeft = <?php echo $remaining_seconds; ?>;
        const timerElement = document.getElementById('timer');

        function updateTimer() {
            if (timeLeft <= 0) {
                timerElement.innerHTML = "အချိန်ပြည့်ပါပြီ";
                timerElement.style.color = "red";
                return;
            }
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            timerElement.innerHTML = (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
            timeLeft--;
        }
        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
    <?php endif; ?>

</body>
</html>