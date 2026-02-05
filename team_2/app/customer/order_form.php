<?php
// customer/order_form.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../database/db_conn.php';

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

// (C) System Capacity Calculation (Logic ပြင်ဆင်ထားသည်)
// ယခင်: Kitchen Load ကိုပဲ စစ်ခဲ့သည်။
// ယခု: တဆိုင်လုံးရှိ အော်ဒါအရေအတွက် (Total Load) ကို စစ်ပါမည်။

// ၁။ ဆိုင်တစ်ခုလုံး လက်ခံနိုင်သော အမြင့်ဆုံးပမာဏ (Max Capacity)
// Kitchen (၁ ယောက် ၄ ခု) + Rider (၁ ယောက် ၂ ခု) ဟု တွက်ဆလိုက်မည်
$system_limit = ($k_staff * 4) + ($d_staff * 2); 

// ၂။ လက်ရှိ ဆိုင်ထဲတွင် ရှိနေသမျှ အော်ဒါများ (Pending + Cooking + Delivering)
// Delivering ကိုပါ ထည့်တွက်မှ Rider မအားရင် System Busy ဖြစ်မည်
$sql_load = "SELECT COUNT(*) as total FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering')";
$res_load = $conn->query($sql_load);
$current_load = $res_load->fetch_assoc()['total'] ?? 0;

// (D) Logic Check: Load များနေရင် Distance စစ်မည်
$base_time = 30; 
$estimated_time = $base_time;
$is_system_busy = false;
$near_distance_threshold = 2.0; // 2 km

// အကယ်၍ လက်ရှိအော်ဒါပေါင်းက Limit ထက်ကျော်နေရင် (သို့) ညီနေရင်
if ($current_load >= $system_limit) {
    // Capacity ပြည့်နေပြီ (Busy)
    if ($distance_km <= $near_distance_threshold) {
        // နီးရင် လက်ခံမယ်၊ အချိန်တိုးမယ်
        $estimated_time = 50; 
    } else {
        // ဝေးရင် လုံးဝလက်မခံတော့ပါ (Busy Overlay ပြမည်)
        $is_system_busy = true;
        $estimated_time = 60; 
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        
        /* Busy Overlay */
        #busy-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.96); z-index: 9999; display: flex; justify-content: center; align-items: center; text-align: center; }
        .warning-box { border: 2px solid #dc3545; padding: 30px; background: white; border-radius: 10px; max-width: 90%; }
        
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-order { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 15px; }
        .btn-wait { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-leave { background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin: 5px; }
    </style>
</head>
<body>

    <?php if ($is_system_busy): ?>
        <div id="busy-overlay">
            <div class="warning-box">
                <h2 style="color: #dc3545;">⚠️ ဆိုင်အလုပ်များနေပါသည်</h2>
                <p>လက်ရှိ အော်ဒါများပြည့်နေပါသည် (Current Orders: <?= $current_load ?>)</p>
                <p>လူကြီးမင်း၏ နေရာသည် ဆိုင်နှင့် အနည်းငယ်ဝေးကွာသောကြောင့် လက်ရှိအချိန်တွင် ပို့ဆောင်ရန် ခက်ခဲနေပါသည်။</p>
                
                <a href="index.php" class="btn-leave">မမှာတော့ပါ</a>
                <button onclick="document.getElementById('busy-overlay').style.display='none'" class="btn-wait">စောင့်မှာမယ်</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container">
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
                <i class="fas fa-check-circle"></i> မိနစ် <?= $estimated_time ?> အတွင်း အရောက်ပို့ဆောင်ပါမည်။ (အကွာအဝေး: <?= $distance_km ?> km)
            </div>
        <?php endif; ?>

        <h2>🍕 Order Details</h2>
        
        <form id="orderForm" action="submit_order.php" method="post" onsubmit="return finalCheck(event)">
            <input type="hidden" name="postal_code" value="<?= $postal_code ?>">

            <label>အမည်</label>
            <input type="text" name="name" id="name" required>

            <label>ဖုန်းနံပါတ်</label>
            <input type="tel" name="phone" id="phone" required>

            <label>လိပ်စာ (City)</label>
            <input type="text" name="address_city" id="address_city" value="<?= $found_address ?>" readonly style="background: #eee;">

            <label>အိမ်နံပါတ်/အခန်းနံပါတ်</label>
            <input type="text" name="address_detail" id="address_detail" placeholder="Room 101, Building A" required>

            <label>ပီဇာ အမျိုးအစား</label>
            <select name="size" id="size">
                <option value="S">Small (¥1,000)</option>
                <option value="M" selected>Medium (¥2,000)</option>
                <option value="L">Large (¥3,000)</option>
            </select>

            <label>အရေအတွက်</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" max="10">

            <button type="submit" class="btn-order">Order Now</button>
        </form>

        <a href="index.php" style="display:block; margin-top:15px; text-align:center; color:#666; text-decoration:none;">Change Location</a>
    </div>

    <script>
        function finalCheck(event) {
            event.preventDefault(); 
            var size = document.getElementById('size').value;
            var qty = document.getElementById('quantity').value;
            var estimatedTime = "<?= $estimated_time; ?>";
            var price = (size === 'S') ? 1000 : (size === 'M' ? 2000 : 3000);
            var total = price * qty;

            Swal.fire({
                title: 'Confirm Order?',
                html: `
                    <div style="text-align: left;">
                        <b>Pizza:</b> ${size} x ${qty} <br>
                        <b>Est. Time:</b> <span style="color:red; font-weight:bold;">${estimatedTime} mins</span> <br>
                        <hr>
                        <b>Total:</b> <span style="color:green; font-weight:bold;">¥${total}</span>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Place Order',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('orderForm').submit();
                }
            });
        }
    </script>
</body>
</html>