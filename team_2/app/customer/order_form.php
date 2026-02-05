<?php
// customer/order_form.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db_conn.php';
// Functions မရှိရင် Error တက်မှာစိုးလို့ စစ်ထည့်ပါတယ်
if (file_exists('../database/functions.php')) {
    require_once '../database/functions.php';
}

// (A) Check Inputs
if (!isset($_GET['code'])) {
    header("Location: index.php");
    exit();
}

$postal_code = htmlspecialchars($_GET['code']);
$found_address = isset($_GET['address']) ? htmlspecialchars(urldecode($_GET['address'])) : '';
$distance_km = isset($_GET['dist']) ? floatval($_GET['dist']) : 0; 

// (B) Staff Config Load
$k_staff = 3; 
$d_staff = 2;
$config_file = '../admin/staff_config.txt';

if (file_exists($config_file)) {
    $staff_data = file_get_contents($config_file);
    if(strpos($staff_data, ',') !== false) {
        list($k_staff, $d_staff) = explode(',', $staff_data);
    }
}

// (C) System Capacity Calculation
$rider_limit = $d_staff * 1; 

// Active Load
$sql_load = "SELECT COUNT(*) as total FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering')";
$res_load = $conn->query($sql_load);
$current_load = $res_load->fetch_assoc()['total'] ?? 0;

// (D) Logic Check
$base_time = 30; 
$estimated_time = $base_time;
$is_system_busy = false; // Default: Not Busy
$near_distance_threshold = 2.0; 

// 1. Smart Logic (Rider Return Time Check)
if (function_exists('canAcceptNewOrder') && !canAcceptNewOrder($distance_km)) {
    $is_system_busy = true; // <--- Busy ဖြစ်ကြောင်း သတ်မှတ်
    $estimated_time = 60; 
}

// 2. Hard Capacity Limit (Backup Logic)
if (!$is_system_busy && $current_load >= $rider_limit) {
    if ($distance_km > $near_distance_threshold) {
        $is_system_busy = true; // <--- Busy ဖြစ်ကြောင်း သတ်မှတ်
        $estimated_time = 60; 
    } else {
        $estimated_time = 45; // နီးရင် Busy မပြဘူး၊ အချိန်ပဲတိုးမယ်
    }
}

// (E) Traffic Logic
$traffic_file = '../admin/traffic_status.txt';
$is_heavy_traffic = false;
if (file_exists($traffic_file) && trim(file_get_contents($traffic_file)) == '1') {
    $is_heavy_traffic = true;
    $estimated_time += 15;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Pizza</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Choice Box Style */
        .choice-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            text-align: center;
            border-top: 5px solid #dc3545;
        }
        .time-badge {
            background: #fff3cd; color: #856404;
            padding: 10px 20px; border-radius: 50px;
            font-weight: bold; font-size: 1.2em;
            margin: 20px 0; border: 1px solid #ffeeba; display: inline-block;
        }
    </style>
</head>
<body>

    <div id="choice-box" class="choice-container" style="display: <?php echo $is_system_busy ? 'block' : 'none'; ?>;">
        <div style="font-size: 60px; margin-bottom: 10px;">⏳</div>
        <h2 style="color: #dc3545; margin: 0;">ဆိုင်အလုပ်များနေပါသည်</h2>
        <p style="color: #666; margin-top: 10px;">လက်ရှိ အော်ဒါများပြားနေသဖြင့် ပို့ဆောင်ချိန် ကြာမြင့်နိုင်ပါသည်။</p>
        
        <div class="time-badge">
            <i class="fas fa-clock"></i> ကြာချိန်: <?= $estimated_time ?> မိနစ်
        </div>

        <p style="margin-bottom: 25px; font-weight: bold;">စောင့်ဆိုင်းပြီး မှာယူလိုပါသလား?</p>
        
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="index.php" class="btn" style="background:#6c757d; color:white; width: auto;">မမှာတော့ပါ</a>
            
            <button onclick="revealForm()" class="btn" style="background:#28a745; color:white; width: auto;">
                လက်ခံသည် (မှာမယ်)
            </button>
        </div>
    </div>


    <div id="main-form" class="container" style="display: <?php echo $is_system_busy ? 'none' : 'block'; ?>;">
        
        <?php if ($is_heavy_traffic): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <i class="fas fa-traffic-light"></i> ယာဉ်ကြောပိတ်ဆို့နေပါသည် (ကြာချိန်: <?php echo $estimated_time; ?> မိနစ်)
            </div>
        <?php elseif ($is_system_busy): ?>
             <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <i class="fas fa-clock"></i> အော်ဒါများနေသဖြင့် <b><?= $estimated_time ?> မိနစ်</b> ခန့် ကြာပါမည်။
            </div>
        <?php else: ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> မိနစ် <?= $estimated_time ?> အတွင်း အရောက်ပို့ဆောင်ပါမည်။
            </div>
        <?php endif; ?>

        <h2>🍕 Order Details</h2>
        
        <form id="orderForm" action="submit_order.php" method="post" onsubmit="return finalCheck(event)">
            <input type="hidden" name="postal_code" value="<?= $postal_code ?>">
            <label>အမည်</label> <input type="text" name="name" id="name" required>
            <label>ဖုန်းနံပါတ်</label> <input type="tel" name="phone" id="phone" required>
            <label>လိပ်စာ (City)</label> <input type="text" name="address_city" id="address_city" value="<?= $found_address ?>" readonly style="background: #eee;">
            <label>အိမ်နံပါတ်/အခန်းနံပါတ်</label> <input type="text" name="address_detail" id="address_detail" placeholder="Room 101, Building A" required>
            <label>ပီဇာ အမျိုးအစား</label>
            <select name="size" id="size">
                <option value="S">Small (¥1,000)</option>
                <option value="M" selected>Medium (¥2,000)</option>
                <option value="L">Large (¥3,000)</option>
            </select>
            <label>အရေအတွက်</label> <input type="number" name="quantity" id="quantity" value="1" min="1" max="10">
            <button type="submit" class="btn-order">Order Now</button>
        </form>
        <a href="index.php" style="display:block; margin-top:15px; text-align:center; color:#666; text-decoration:none;">Change Location</a>
    </div>

    <script src="../assets/main.js"></script>
    <script>
        // ✅ ဒီ Function က Choice Box ကိုဖျောက်ပြီး Form ကိုဖော်ပေးတာပါ
        function revealForm() {
            document.getElementById('choice-box').style.display = 'none';
            document.getElementById('main-form').style.display = 'block';
        }

        function finalCheck(event) {
            event.preventDefault(); 
            var size = document.getElementById('size').value;
            var qty = document.getElementById('quantity').value;
            var estimatedTime = "<?= $estimated_time; ?>";
            var price = (size === 'S') ? 1000 : (size === 'M' ? 2000 : 3000);
            var total = price * qty;

            Swal.fire({
                title: 'Confirm Order?',
                html: `<div style="text-align: left;"><b>Pizza:</b> ${size} x ${qty} <br><b>Est. Time:</b> <span style="color:red; font-weight:bold;">${estimatedTime} mins</span> <br><hr><b>Total:</b> <span style="color:green; font-weight:bold;">¥${total}</span></div>`,
                icon: 'question', showCancelButton: true, confirmButtonText: 'Yes, Place Order', confirmButtonColor: '#28a745', cancelButtonColor: '#d33'
            }).then((result) => { if (result.isConfirmed) document.getElementById('orderForm').submit(); });
        }
    </script>
</body>
</html>