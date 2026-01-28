<?php
// ၁။ အချိန်ဇုန် ညှိခြင်း
date_default_timezone_set('Asia/Tokyo');

include '../database/db_conn.php';
// ⚠️ Logic ဖိုင်ကို လှမ်းချိတ်ထားပါတယ် (database folder ထဲမှာ functions.php ရှိရပါမယ်)
include '../database/functions.php';

$order = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkphonenumber'])) {
    $chkorder = mysqli_real_escape_string($conn, $_POST['checkphonenumber']);
    
    // နောက်ဆုံး မှာထားတဲ့ Order ကို ယူမယ်
    $query = "SELECT * FROM orders WHERE phonenumber = '$chkorder' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($query);
    $order = $result->fetch_assoc();
    
    if (!$order) {
        echo "<h2 style='text-align:center; color:red;'>Order not found! (အော်ဒါမရှိပါ)</h2>";
        echo "<center><a href='index.php'>Back</a></center>";
        exit();
    }
} elseif (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM orders WHERE id = '$id'";
    $result = $conn->query($query);
    $order = $result->fetch_assoc();
} else {
    header("Location: index.php");
    exit();
}

// ⚠️ Location စစ်ဆေးခြင်း
// (ဒီနေရာမှာ တွက်ထားပေမယ့် အောက်က HTML မှာ မပြရင် အလကားဖြစ်နေပါလိမ့်မယ်)
$delivery_check = checkDeliveryArea($order['postal_code']);

// ၂။ Customer က "လက်ခံရရှိပါပြီ" ဟု နှိပ်လိုက်လျှင်
if (isset($_POST['confirm_receive'])) {
    $order_id = $_POST['order_id'];
    $conn->query("UPDATE orders SET status = 'Completed' WHERE id = $order_id");
    header("Location: check_order.php?id=" . $order_id); 
    exit();
}

// ၃။ ဈေးနှုန်း တွက်ချက်ခြင်း
$unit_price = 0;
if ($order['pizza_type'] == 'S') $unit_price = 1000;
elseif ($order['pizza_type'] == 'M') $unit_price = 2000;
elseif ($order['pizza_type'] == 'L') $unit_price = 3000;

$total_price = $unit_price * $order['quantity'];

// ၄။ Status Logic
$status_text = "";
$status_color = "";
$show_timer = true; 

switch ($order['status']) {
    case 'Pending':
        $status_text = "အော်ဒါ လက်ခံရရှိထားပါသည် (Waiting)";
        $status_color = "#f39c12";
        break;
    case 'Cooking':
        $status_text = "👨‍🍳 စားဖိုမှူး ချက်ပြုတ်နေပါသည် (Cooking)";
        $status_color = "#d35400";
        break;
    case 'Delivering':
        $status_text = "🛵 လူကြီးမင်းထံ လာပို့နေပါပြီ (On the way)";
        $status_color = "#2980b9";
        break;
    case 'Completed':
        $status_text = "✅ ပို့ဆောင်မှု ပြီးစီးပါပြီ (Completed)";
        $status_color = "#27ae60";
        $show_timer = false; 
        break;
    default:
        $status_text = "Processing...";
        $status_color = "grey";
}

// ၅။ အချိန်တွက်ချက်ခြင်း
$remaining_seconds = 0;
if ($order['status'] != 'Pending' && !empty($order['start_time'])) {
    $start_time = strtotime($order['start_time']); 
    $target_time = $start_time + (30 * 60); // ၃၀ မိနစ်
    $current_time = time(); 
    $remaining_seconds = $target_time - $current_time;
    if ($remaining_seconds < 0) $remaining_seconds = 0;
}
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
        .details { text-align: left; margin-top: 20px; line-height: 1.8; border-top: 1px solid #ddd; padding-top: 10px; }
        .price-row { display: flex; justify-content: space-between; font-size: 1.2em; font-weight: bold; color: #2c3e50; border-top: 2px dashed #ccc; padding-top: 10px; margin-top: 10px; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 25px; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; }
        
        /* ⚠️ Location Alert အတွက် CSS အသစ်များ */
        .alert-box { padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9em; text-align: left; border: 1px solid #ddd; }
        .ok { background: #e8f8f5; color: #27ae60; border-color: #27ae60; }
        .warning { background: #fdedec; color: #c0392b; border-color: #c0392b; }
    </style>
</head>
<body>

    <div class="card">
        <h2>အော်ဒါ အခြေအနေ</h2>

        <div class="status-box">
            <?php echo $status_text; ?>
        </div>
        
        <?php if ($delivery_check['status'] == 'ok'): ?>
            <div class="alert-box ok"><?php echo $delivery_check['msg']; ?></div>
        <?php elseif ($delivery_check['status'] == 'warning'): ?>
            <div class="alert-box warning"><?php echo $delivery_check['msg']; ?></div>
        <?php endif; ?>

        <?php if ($order['status'] == 'Pending'): ?>
            <p>ဆိုင်မှ အော်ဒါကို အတည်ပြုရန် စောင့်ဆိုင်းနေပါသည်...</p>
        <?php elseif ($show_timer): ?>
            <p>ခန့်မှန်း ကြာချိန်:</p>
            <div class="timer-box">
                ⏱ <span id="timer">...</span>
            </div>
        <?php else: ?>
            <div style="font-size: 1.2em; color: green; margin-bottom: 20px;">
                🙏 ကျေးဇူးတင်ပါသည်။ အစားကောင်းကောင်း သုံးဆောင်ပါ!
            </div>
        <?php endif; ?>

        <?php if ($order['status'] == 'Delivering'): ?>
            <form method="post" style="margin-top: 10px;">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <button type="submit" name="confirm_receive" class="btn" style="background: #27ae60;">
                    ✅ အော်ဒါလက်ခံရရှိပါပြီ
                </button>
            </form>
        <?php endif; ?>

        <div class="details">
            <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
            <p><strong>အမည်:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>လိပ်စာ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            <p><strong>Postal Code:</strong> <?php echo htmlspecialchars($order['postal_code']); ?></p>
            <p><strong>ပီဇာ:</strong> Size <?php echo htmlspecialchars($order['pizza_type']); ?> (<?php echo $order['quantity']; ?> ခု)</p>

            <div class="price-row">
                <span>စုစုပေါင်း:</span>
                <span style="color: green;">¥<?php echo number_format($total_price); ?></span>
            </div>
        </div>

        <a href="index.php" class="btn" style="background: #555;">ပင်မစာမျက်နှာသို့</a>
    </div>

    <?php if ($show_timer && $order['status'] != 'Pending'): ?>
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