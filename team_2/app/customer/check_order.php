<?php
ob_start(); // Header error ဖြေရှင်းရန် output buffer စမည်
session_start();
date_default_timezone_set('Asia/Tokyo');
include '../database/db_conn.php';

$order = null;

// ၁။ ဖုန်းနံပါတ်ဖြင့် ရှာခြင်း (သို့) ID ဖြင့် ရှာခြင်း
if (isset($_POST['checkphonenumber'])) {
    $chkorder = $_POST['checkphonenumber'];
    $stmt = $conn->prepare("SELECT * FROM orders WHERE phonenumber = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $chkorder);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
} elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
}

// Order မရှိလျှင်
if (!$order) {
    echo "<script>alert('Order not found!'); window.location='../customer/index.php';</script>";
    exit();
}

// ၂။ Customer Confirm Logic (လက်ခံရရှိပါပြီ) - Error မတက်အောင် ပြင်ထားသော အပိုင်း
if (isset($_POST['confirm_receive'])) {
    $order_id = intval($_POST['order_id']);
    
    // မှတ်ချက်: Database မှာ received_time column မရှိသေးရင် Error တက်နိုင်လို့
    // Status တစ်ခုတည်းကိုပဲ အရင်ပြောင်းပါမယ်။
    $stmt = $conn->prepare("UPDATE orders SET status = 'Completed' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        // Success
        header("Location: check_order.php?id=" . $order_id); 
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}

// ၃။ ဈေးနှုန်း တွက်ချက်ခြင်း (Price Calculation Logic)
// Database ထဲမှာ total_price မရှိခဲ့ရင် ဒီ PHP ကုဒ်က အလိုအလျောက် တွက်ပေးပါလိမ့်မယ်
$unit_price = 0;
$type = strtoupper($order['pizza_type']); // အကြီးစာလုံးပြောင်းမယ် (S, M, L)

if ($type == 'S') {
    $unit_price = 1000;
} elseif ($type == 'M') {
    $unit_price = 2000;
} else {
    $unit_price = 3000; // L or others
}

// အကယ်၍ Database မှာ total_price ရှိပြီးသားဆိုရင် အဲ့ဒါကိုယူမယ်၊ မရှိရင် တွက်မယ်
if (!empty($order['total_price']) && $order['total_price'] > 0) {
    $calculated_total = $order['total_price'];
} else {
    $calculated_total = $unit_price * $order['quantity'];
}

// ၄။ Status & Timer Logic
$status_text = "";
$status_color = "";
$show_timer = false;
$remaining_seconds = 0;
$current_time = time();

if ($order['status'] == 'Rejected') {
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
    
    if (!empty($order['departure_time'])) {
        $dept = strtotime($order['departure_time']);
        $target = $dept + (15 * 60); // ၁၅ မိနစ်
        $remaining_seconds = $target - $current_time;
    } else {
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
    <?php if ($order['status'] != 'Completed' && $order['status'] != 'Rejected'): ?>
        <meta http-equiv="refresh" content="10">
    <?php endif; ?>
    
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px; }
        .card { background: white; max-width: 400px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .status-box { background-color: <?php echo $status_color; ?>; color: white; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; }
        .timer-box { font-size: 2.5em; font-weight: bold; color: #333; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 25px; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .price-text { font-size: 1.4em; color: #27ae60; font-weight: bold; }
        .info-row { text-align: left; padding: 5px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; color: #555; }
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

            <div style="margin-top: 20px; background: #fafafa; padding: 15px; border-radius: 8px;">
                <div class="info-row">
                    <span class="info-label">Order ID:</span> #<?php echo $order['id']; ?>
                </div>
                <div class="info-row">
                    <span class="info-label">အမည်:</span> <?php echo htmlspecialchars($order['customer_name']); ?>
                </div>
                <div class="info-row">
                    <span class="info-label">ပီဇာ:</span> <?php echo htmlspecialchars($order['pizza_type']); ?> (Size)
                </div>
                <div class="info-row">
                    <span class="info-label">အရေအတွက်:</span> <?php echo $order['quantity']; ?> ခု
                </div>
                
                <div style="margin-top: 15px; border-top: 2px dashed #ccc; padding-top: 10px; text-align: center;">
                    <div style="font-size: 0.9em; color: #777;">ကျသင့်ငွေ စုစုပေါင်း</div>
                    <div class="price-text"><?php echo number_format($calculated_total); ?> Ks</div>
                </div>
            </div>

            <?php if ($order['status'] == 'Delivering'): ?>
                <form method="post" style="margin-top: 20px;">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="confirm_receive" class="btn" style="background: #27ae60; width: 100%;">
                        ✅ အော်ဒါလက်ခံရရှိပါပြီ
                    </button>
                </form>
            <?php endif; ?>
            
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
<?php ob_end_flush(); ?>