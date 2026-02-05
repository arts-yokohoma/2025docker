<?php
// customer/check_order.php
ob_start(); 
session_start();

// ၁။ အချိန်ဇုန် ညှိခြင်း
date_default_timezone_set('Asia/Tokyo');
include '../database/db_conn.php';
// Functions ဖိုင် မဖြစ်မနေ လိုအပ်ပါသည် (အကွာအဝေးတွက်ရန်)
require_once '../database/functions.php';

$order = null;

// ၂။ Customer Confirm Logic (Rider Return Time Calculation)
if (isset($_POST['confirm_receive'])) {
    $order_id = intval($_POST['order_id']);

    // (A) အရင်ဆုံး ဒီ Order ရဲ့ Location နဲ့ Rider ID ကို ဆွဲထုတ်မယ်
    $qry = $conn->query("SELECT assigned_slot_id, latitude, longitude FROM orders WHERE id = $order_id");
    $row = $qry->fetch_assoc();
    $slot_id = $row['assigned_slot_id'] ?? 0;
    
    // (B) ပြန်ချိန် တွက်ချက်ခြင်း (Smart Logic)
    $return_minutes = 15; // Default (Lat/Lng မရှိရင် ၁၅ မိနစ်ထားမယ်)

    if (!empty($row['latitude']) && !empty($row['longitude'])) {
        // ဆိုင်တည်နေရာ (functions.php ထဲက SHOP_LAT Constants)
        $dist = calculateDistance(SHOP_LAT, SHOP_LNG, $row['latitude'], $row['longitude']);
        
        // ၁ ကီလိုမီတာ = ၃ မိနစ် + Buffer ၅ မိနစ်
        $return_minutes = ceil($dist * 3) + 5;
    }
    
    // Rider ပြန်ရောက်မည့်အချိန်
    $back_time = date('Y-m-d H:i:s', strtotime("+$return_minutes minutes"));

    // (C) Order Status Update (Completed)
    $stmt = $conn->prepare("UPDATE orders SET status = 'Completed', return_time = NOW() WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // (D) Rider Slot Update (Next Available Time သတ်မှတ်ခြင်း)
    if ($slot_id > 0) {
        // delivery_slots table ရှိမရှိ အရင်စစ်တာ ကောင်းပါတယ်၊ သို့သော် admin.php မှာ ဆောက်ထားပြီးဖြစ်လို့ တန်း Update ပါမယ်
        $sql_slot = "UPDATE delivery_slots SET next_available_time = '$back_time' WHERE slot_id = $slot_id";
        $conn->query($sql_slot);
    }

    // Refresh Page
    header("Location: ?id=" . $order_id); 
    exit();
}

// ၃။ Data ဆွဲထုတ်ခြင်း (လုံခြုံရေး မြှင့်ထားသည်)
if (isset($_POST['checkphonenumber'])) {
    $phone = $_POST['checkphonenumber'];
    
    $stmt = $conn->prepare("SELECT * FROM orders WHERE phonenumber = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        echo "<script>alert('❌ ဒီဖုန်းနံပါတ်နှင့် အော်ဒါမရှိပါ'); window.location.href='index.php';</script>";
        exit();
    }
    header("Location: ?id=" . $order['id']);
    exit();

} elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
} else {
    header("Location: index.php");
    exit();
}

if (!$order) {
    die("❌ Error: Order Not Found");
}

// ၄။ Variable များ ညှိနှိုင်းခြင်း
$c_name = htmlspecialchars($order['customer_name'] ?? $order['name'] ?? '-');
$c_phone = htmlspecialchars($order['phonenumber'] ?? $order['phone'] ?? '-');
$c_address = htmlspecialchars($order['address'] ?? ($order['address_city'] . ' ' . $order['address_detail']) ?? '-');
$c_size = $order['pizza_type'] ?? 'M';
$c_qty = intval($order['quantity'] ?? 1);

// ၅။ ဈေးနှုန်း တွက်ချက်ခြင်း
$unit_price = ($c_size == 'S') ? 1000 : (($c_size == 'M') ? 2000 : 3000);
$total_price = $unit_price * $c_qty;

// ၆။ Status Logic
$status_text = "";
$status_color = "";
$show_timer = false;

switch ($order['status']) {
    case 'Pending':
        $status_text = "⏳ အော်ဒါ လက်ခံရရှိထားပါသည် (Waiting)";
        $status_color = "#f39c12"; // Orange
        $show_timer = false;
        break;
    case 'Cooking':
        $status_text = "👨‍🍳 စားဖိုမှူး ချက်ပြုတ်နေပါသည် (Cooking)";
        $status_color = "#d35400"; // Dark Orange
        $show_timer = true;
        break;
    case 'Delivering':
        $status_text = "🛵 လူကြီးမင်းထံ လာပို့နေပါပြီ (On the way)";
        $status_color = "#2980b9"; // Blue
        $show_timer = true;
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

// ၇။ Timer Calculation
$remaining_seconds = 0;
if ($show_timer) {
    // start_time ရှိရင် start_time ကိုသုံး၊ မရှိရင် order_date ကိုသုံး
    $time_string = !empty($order['start_time']) ? $order['start_time'] : $order['order_date'];
    $target_time = strtotime($time_string) + (30 * 60); // 30 Minutes
    $remaining_seconds = max(0, $target_time - time());
}
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status #<?php echo $order['id']; ?></title>
    
    <?php if($order['status'] != 'Completed' && $order['status'] != 'Rejected'): ?>
        <meta http-equiv="refresh" content="15">
    <?php endif; ?>

    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f4f4; text-align: center; padding: 20px; }
        .card { background: white; max-width: 400px; margin: 0 auto; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .status-box { background-color: <?php echo $status_color; ?>; color: white; padding: 15px; border-radius: 8px; font-weight: bold; margin-bottom: 20px; font-size: 1.1em; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .timer-box { font-size: 2.5em; font-weight: bold; color: #333; margin: 10px 0; }
        .details { text-align: left; margin-top: 20px; line-height: 1.8; border-top: 1px solid #ddd; padding-top: 15px; }
        .price-row { display: flex; justify-content: space-between; font-size: 1.3em; font-weight: bold; color: #2c3e50; border-top: 2px dashed #ccc; padding-top: 10px; margin-top: 10px; }
        .btn { display: inline-block; padding: 12px 25px; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; font-size: 16px; width: 100%; margin-top: 10px; }
        .btn-home { background: #555; width: auto; margin-top: 15px; }
        .reject-box { background-color: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="card">
        <h3 style="color: #555;">Order ID: #<?php echo $order['id']; ?></h3>

        <?php if ($order['status'] == 'Rejected'): ?>
            <div class="reject-box">
                <h2>❌ အော်ဒါ ပယ်ဖျက်ခံလိုက်ရပါသည်</h2>
                <hr style="border-top: 1px solid #f5c6cb;">
                <p><strong>အကြောင်းပြချက်:</strong></p>
                <p style="font-size: 18px; font-weight: bold;">
                    "<?php echo htmlspecialchars($order['reject_reason'] ?? 'ဆိုင်မှ ပယ်ဖျက်လိုက်ပါသည်'); ?>"
                </p>
            </div>
            <a href="index.php" class="btn btn-home">နောက်တစ်ကြိမ် ပြန်မှာရန်</a>

        <?php else: ?>
            <div class="status-box">
                <?php echo $status_text; ?>
            </div>

            <?php if ($show_timer): ?>
                <p style="margin-bottom:5px; color:#666;">ခန့်မှန်း ကြာချိန်:</p>
                <div class="timer-box">
                    ⏱ <span id="timer">...</span>
                </div>
            <?php elseif ($order['status'] == 'Pending'): ?>
                <p>ဆိုင်မှ အတည်ပြုချက် စောင့်ဆိုင်းနေပါသည်...</p>
            <?php elseif ($order['status'] == 'Completed'): ?>
                <div style="font-size: 1.2em; color: green; margin-bottom: 20px;">
                    🙏 ကျေးဇူးတင်ပါသည်။<br>အစားကောင်းကောင်း သုံးဆောင်ပါ! 🍕
                </div>
            <?php endif; ?>

            <?php if ($order['status'] == 'Delivering'): ?>
                <form method="post" style="margin: 20px 0;">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <button type="submit" name="confirm_receive" class="btn" style="background: #27ae60;">
                        ✅ လက်ခံရရှိပါပြီ (Received)
                    </button>
                    <p style="font-size: 12px; color: red; margin-top: 5px;">
                        * ပစ္စည်းရရှိပါက နှိပ်ပေးရန် မေတ္တာရပ်ခံပါသည်။
                    </p>
                </form>
            <?php endif; ?>

            <div class="details">
                <h4 style="margin-top:0;">အော်ဒါ အချက်အလက်များ</h4>
                <p><strong>👤 အမည်:</strong> <?php echo $c_name; ?></p>
                <p><strong>📞 ဖုန်း:</strong> <?php echo $c_phone; ?></p>
                <p><strong>🏠 လိပ်စာ:</strong> <?php echo $c_address; ?></p>
                <p><strong>🍕 ပီဇာ:</strong> Size <?php echo $c_size; ?> (x<?php echo $c_qty; ?>)</p>

                <div class="price-row">
                    <span>စုစုပေါင်း:</span>
                    <span style="color: green;">¥<?php echo number_format($total_price); ?></span>
                </div>
            </div>

            <a href="index.php" class="btn btn-home">ပင်မစာမျက်နှာသို့</a>
            <?php if ($order['status'] !== 'Delivering'): ?>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <?php if ($show_timer): ?>
    <script>
        let timeLeft = <?php echo $remaining_seconds; ?>;
        const timerElement = document.getElementById('timer');

        function updateTimer() {
            if (timeLeft <= 0) {
                timerElement.innerHTML = "00:00";
                timerElement.style.color = "red";
                return;
            }
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            // 0:5 အစား 0:05 ဖြစ်အောင် String Pad လုပ်ခြင်း
            let mStr = minutes.toString().padStart(2, '0');
            let sStr = seconds.toString().padStart(2, '0');
            
            timerElement.innerHTML = mStr + ":" + sStr;
            timeLeft--;
        }
        
        updateTimer(); 
        setInterval(updateTimer, 1000);
    </script>
    <?php endif; ?>

</body>
</html>
<?php ob_end_flush(); ?>