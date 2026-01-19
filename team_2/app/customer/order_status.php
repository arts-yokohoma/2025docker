<?php
include '../database/db_conn.php';

// ၁။ အချိန်ဇုန် ညှိခြင်း
date_default_timezone_set('Asia/Tokyo'); 

if (!isset($_GET['id'])) {
    die("No Order ID Provided");
}

$id = $_GET['id'];
$sql = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found!");
}

// --- ၂။ ဈေးနှုန်း တွက်ချက်ခြင်း (Price Calculation) ---
$unit_price = 0;
// Size အလိုက် ဈေးနှုန်းသတ်မှတ်ခြင်း
if ($order['pizza_type'] == 'S') {
    $unit_price = 1000;
} elseif ($order['pizza_type'] == 'M') {
    $unit_price = 2000;
} elseif ($order['pizza_type'] == 'L') {
    $unit_price = 3000;
}

// တစ်ခုဈေး x အရေအတွက်
$total_price = $unit_price * $order['quantity'];


// --- ၃။ Status အလိုက် စာသားပြောင်းမည့် Logic ---
$status_text = "";
$status_color = "";
$show_timer = true; 

switch ($order['status']) {
    case 'Pending':
        $status_text = "အော်ဒါ လက်ခံရရှိထားပါသည် (Waiting)";
        $status_color = "#f39c12"; // Orange
        break;
    case 'Cooking':
        $status_text = "👨‍🍳 စားဖိုမှူး ချက်ပြုတ်နေပါသည် (Cooking)";
        $status_color = "#d35400"; // Dark Orange
        break;
    case 'Delivering':
        $status_text = "🛵 လူကြီးမင်းထံ လာပို့နေပါပြီ (On the way)";
        $status_color = "#2980b9"; // Blue
        break;
    case 'Completed':
        $status_text = "✅ ပို့ဆောင်မှု ပြီးစီးပါပြီ (Completed)";
        $status_color = "#27ae60"; // Green
        $show_timer = false; // ပြီးရင် Timer ဖျောက်မယ်
        break;
    default:
        $status_text = "Processing...";
        $status_color = "grey";
}

// --- ၄။ အချိန်တွက်ချက်ခြင်း ---
$order_time = strtotime($order['order_date']); 
$target_time = $order_time + (30 * 60); // 30 Minutes
$current_time = time(); 
$remaining_seconds = $target_time - $current_time;

if ($remaining_seconds < 0) $remaining_seconds = 0;
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status</title>
    <meta http-equiv="refresh" content="5">
    <style>
        body { font-family: sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px; }
        .card { background: white; max-width: 400px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        /* Status Box Design */
        .status-box { 
            background-color: <?php echo $status_color; ?>; 
            color: white; 
            padding: 15px; 
            border-radius: 8px; 
            font-size: 1.1em; 
            font-weight: bold;
            margin-bottom: 20px;
        }

        .timer-box { font-size: 2.5em; font-weight: bold; color: #333; margin: 10px 0; }
        .details { text-align: left; margin-top: 20px; line-height: 1.8; border-top: 1px solid #ddd; padding-top: 10px; }
        
        /* ဈေးနှုန်းပြမည့်ဒီဇိုင်း */
        .price-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px dashed #ccc;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #555; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

    <div class="card">
        <h2>အော်ဒါ အခြေအနေ (Order Status)</h2>

        <div class="status-box">
            <?php echo $status_text; ?>
        </div>

        <?php if ($show_timer): ?>
            <p>ခန့်မှန်း ကြာချိန်:</p>
            <div class="timer-box">
                ⏱ <span id="timer">...</span>
            </div>
        <?php else: ?>
            <div style="font-size: 1.2em; color: green; margin-bottom: 20px;">
                🙏 ကျေးဇူးတင်ပါသည်။ Again!
            </div>
        <?php endif; ?>

        <div class="details">
            <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
            <p><strong>အမည်:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            <p><strong>လိပ်စာ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
            <p><strong>ဖုန်း:</strong> <?php echo htmlspecialchars($order['phonenumber']); ?></p>
            <p><strong>ပီဇာ:</strong> Size <?php echo htmlspecialchars($order['pizza_type']); ?></p>
            <p><strong>အရေအတွက်:</strong> <?php echo $order['quantity']; ?> ခု</p>

            <div class="price-row">
                <span>Total:</span>
                <span style="color: green;">¥<?php echo number_format($total_price); ?></span>
            </div>
        </div>

        <a href="../customer/index.php" class="btn">ပင်မစာမျက်နှာသို့</a>
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
            let mStr = minutes < 10 ? "0" + minutes : minutes;
            let sStr = seconds < 10 ? "0" + seconds : seconds;
            timerElement.innerHTML = mStr + ":" + sStr;
            timeLeft--;
        }
        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
    <?php endif; ?>

</body>
</html>