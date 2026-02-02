<?php
// ၁။ အချိန်ဇုန် ညှိခြင်း
date_default_timezone_set('Asia/Tokyo'); 
include '../database/db_conn.php';

// --- POST Request Handling (Confirm Received) ---
// Customer က "လက်ခံရရှိပါပြီ" နှိပ်လိုက်ရင် အလုပ်လုပ်မည့်အပိုင်း
if (isset($_POST['confirm_receive'])) {
    $order_id = intval($_POST['order_id']);
    // Status Completed ပြောင်းမယ်၊ Return Time မှတ်မယ်
    $conn->query("UPDATE orders SET status = 'Completed', return_time = NOW() WHERE id = $order_id");
    // Page ကို Refresh လုပ်မယ်
    header("Location: ?id=" . $order_id); 
    exit();
}

// --- GET Order Data ---
if (!isset($_GET['id'])) {
    die("❌ Error: No Order ID Provided");
}

$id = intval($_GET['id']); 
$sql = "SELECT * FROM orders WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("❌ Error: အော်ဒါနံပါတ် မှားယွင်းနေပါသည် (သို့) ရှာမတွေ့ပါ။");
}

// --- ၂။ Column နာမည် အမှားအယွင်းမရှိအောင် စစ်ထုတ်ခြင်း ---
$c_name = $order['customer_name'] ?? $order['name'] ?? '-';
$c_phone = $order['phonenumber'] ?? $order['phone'] ?? '-';
$c_size  = $order['pizza_type'] ?? $order['size'] ?? 'S';
$c_qty   = $order['quantity'] ?? 1;

// Address ကို ပေါင်းစပ်ခြင်း (Database ပေါ်မူတည်၍)
$c_address = $order['full_address'] ?? $order['address'] ?? ($order['address_city'] . ' ' . $order['address_detail']) ?? '-';

// --- ၃။ ဈေးနှုန်း တွက်ချက်ခြင်း ---
$unit_price = 0;
if ($c_size == 'S') $unit_price = 1000;
elseif ($c_size == 'M') $unit_price = 2000;
elseif ($c_size == 'L') $unit_price = 3000;

$total_price = $unit_price * $c_qty;

// --- ၄။ Status အလိုက် စာသားပြောင်းမည့် Logic ---
$status_text = "";
$status_color = "";
$show_timer = true; 

switch ($order['status']) {
    case 'Pending':
        $status_text = "⏳ အော်ဒါ လက်ခံရရှိထားပါသည် (Waiting)";
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
        $show_timer = false; 
        break;
    case 'Rejected':
        $status_text = "❌ အော်ဒါ ပယ်ဖျက်ခံလိုက်ရပါသည်";
        $status_color = "#c0392b"; // Red
        $show_timer = false;
        break;
    default:
        $status_text = "Processing...";
        $status_color = "grey";
}

// --- ၅။ Timer Calculation ---
$remaining_seconds = 0;
// start_time ရှိရင် start_time ကယူ၊ မရှိရင် order_date ကယူ
$base_time = !empty($order['start_time']) ? strtotime($order['start_time']) : strtotime($order['order_date']);

if ($show_timer) {
    $target_time = $base_time + (30 * 60); // မိနစ် ၃၀ ပေါင်းထည့်
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
    <title>Order Status #<?php echo $order['id']; ?></title>
    
    <?php if($order['status'] !== 'Rejected' && $order['status'] !== 'Completed'): ?>
        <meta http-equiv="refresh" content="10">
    <?php endif; ?>

    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px; }
        .card { background: white; max-width: 400px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .status-box { 
            background-color: <?php echo $status_color; ?>; 
            color: white; 
            padding: 15px; 
            border-radius: 8px; 
            font-weight: bold; 
            margin-bottom: 20px; 
            font-size: 1.1em;
        }
        
        .timer-box { font-size: 2.5em; font-weight: bold; color: #333; margin: 10px 0; }
        .details { text-align: left; margin-top: 20px; line-height: 1.8; border-top: 1px solid #ddd; padding-top: 10px; }
        
        .price-row { 
            display: flex; justify-content: space-between; 
            font-size: 1.2em; font-weight: bold; color: #2c3e50; 
            border-top: 2px dashed #ccc; padding-top: 10px; margin-top: 10px; 
        }
        
        .btn { display: inline-block; margin-top: 20px; padding: 12px 25px; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; width: 100%; }
        .btn-home { background: #555; width: auto; }

        /* Reject Box Specific */
        .reject-box {
            background-color: #f8d7da; color: #721c24; 
            padding: 15px; border: 1px solid #f5c6cb; 
            border-radius: 5px; margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="card">
        <h3>Order ID: #<?php echo $order['id']; ?></h3>

        <?php if ($order['status'] == 'Rejected'): ?>
            <div class="reject-box">
                <h2>❌ အော်ဒါ ပယ်ဖျက်ခံလိုက်ရပါသည်</h2>
                <hr style="border-top: 1px solid #f5c6cb;">
                <p><strong>အကြောင်းပြချက်:</strong></p>
                <p style="font-size: 18px; font-weight: bold;">
                    "<?php echo htmlspecialchars($order['reject_reason'] ?? 'ဆိုင်မှ ပယ်ဖျက်လိုက်ပါသည်'); ?>"
                </p>
            </div>
            <a href="../customer/index.php" class="btn btn-home">နောက်တစ်ကြိမ် ပြန်မှာရန်</a>

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
                    🙏 ကျေးဇူးတင်ပါသည်။<br>အစားကောင်းကောင်း သုံးဆောင်ပါ! 🍕
                </div>
            <?php endif; ?>

            <?php if ($order['status'] == 'Delivering'): ?>
                <form method="post">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="confirm_receive" class="btn" style="background: #27ae60;">
                        ✅ လက်ခံရရှိပါပြီ (Received)
                    </button>
                </form>
            <?php endif; ?>

            <div class="details">
                <p><strong>👤 အမည်:</strong> <?php echo htmlspecialchars($c_name); ?></p>
                <p><strong>🏠 လိပ်စာ:</strong> <?php echo htmlspecialchars($c_address); ?></p>
                <p><strong>🍕 ပီဇာ:</strong> Size <?php echo htmlspecialchars($c_size); ?> (x<?php echo $c_qty; ?>)</p>

                <div class="price-row">
                    <span>စုစုပေါင်း:</span>
                    <span style="color: green;">¥<?php echo number_format($total_price); ?></span>
                </div>
            </div>

            <?php if ($order['status'] !== 'Delivering'): ?>
                <a href="../customer/index.php" class="btn btn-home" style="margin-top: 15px;">ပင်မစာမျက်နှာသို့</a>
            <?php endif; ?>

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