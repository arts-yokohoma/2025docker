<?php
// customer/order_form.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../database/db_conn.php';

// (A) Postal Code မပါလာရင် index ကို ပြန်မောင်းထုတ်မယ်
if (!isset($_GET['code'])) {
    header("Location: index.php");
    exit();
}

$postal_code = htmlspecialchars($_GET['code']);
$found_address = isset($_GET['address']) ? urldecode($_GET['address']) : '';

// (B) System Status Calculation (Admin Config & Active Orders)
$k_staff = 3; $d_staff = 2; 
if (file_exists('../admin/staff_config.txt')) {
    $staff_data = file_get_contents('../admin/staff_config.txt');
    if(strpos($staff_data, ',') !== false) {
        list($k_staff, $d_staff) = explode(',', $staff_data);
    }
}

// Active Orders (Pending, Cooking, Delivering)
$sql_active = "SELECT COUNT(*) as active_count FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering')";
$res_active = $conn->query($sql_active);
$row_active = $res_active->fetch_assoc();
$current_active_orders = $row_active['active_count'] ?? 0;

// --- DYNAMIC TIME CALCULATION (Fix Bug Here) ---
// တွက်နည်း: (စားဖိုမှူး + ပို့ဆောင်သူ) x 2 = တပြိုင်နက် လုပ်နိုင်သော အရေအတွက်
$max_capacity = ((int)$k_staff + (int)$d_staff) * 2;
if ($max_capacity < 1) $max_capacity = 5; // Default safety

$base_time = 30; // ပုံမှန်ကြာချိန်
$estimated_time = $base_time;
$is_system_busy = false;

// Capacity ကျော်နေရင် အချိန်တိုးမယ်
if ($current_active_orders >= $max_capacity) {
    $is_system_busy = true;
    // Capacity တစ်ဆ ကျော်တိုင်း မိနစ် ၃၀ ပေါင်းမယ်
    $overload_ratio = floor($current_active_orders / $max_capacity);
    $estimated_time = $base_time + ($overload_ratio * 30);
}

// Traffic Logic
$is_heavy_traffic = false;
if (file_exists('../admin/traffic_status.txt')) {
    $status = file_get_contents('../admin/traffic_status.txt');
    if (trim($status) == '1') {
        $is_heavy_traffic = true;
        $estimated_time += 15; // Traffic ဖြစ်နေရင် ၁၅ မိနစ် ထပ်ပေါင်းမယ်
    }
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
        
        /* Busy Overlay CSS */
        #busy-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.96); z-index: 9999;
            display: flex; justify-content: center; align-items: center; text-align: center;
        }
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
                <p>လက်ရှိအော်ဒါများပြားနေသဖြင့် ပို့ဆောင်ချိန် <b><?php echo $estimated_time; ?> မိနစ်ခန့်</b> ကြာနိုင်ပါသည်။</p>
                <p><b>စောင့်ဆိုင်းပြီး မှာယူလိုပါသလား?</b></p>
                <a href="index.php" class="btn-leave">မမှာတော့ပါ</a>
                <button onclick="document.getElementById('busy-overlay').style.display='none'" class="btn-wait">ရပါတယ်၊ စောင့်မယ်</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container">
        <?php if ($is_heavy_traffic): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <i class="fas fa-traffic-light"></i> ယာဉ်ကြောပိတ်ဆို့နေပါသည် (ကြာချိန်: <?php echo $estimated_time; ?> မိနစ်)
            </div>
        <?php elseif ($is_system_busy): ?>
            <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ffeeba;">
                <i class="fas fa-clock"></i> အော်ဒါများနေသဖြင့် <b><?php echo $estimated_time; ?> မိနစ်</b> ခန့် ကြာပါမည်။
            </div>
        <?php else: ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> မိနစ် ၃၀ အတွင်း အရောက်ပို့ဆောင်ပါမည်။
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
            
            var name = document.getElementById('name').value;
            var city = document.getElementById('address_city').value;
            var detail = document.getElementById('address_detail').value;
            var size = document.getElementById('size').value;
            var qty = document.getElementById('quantity').value;
            
            // PHP မှ တွက်ထားသောအချိန်ကို JS သို့ ယူခြင်း
            var estimatedTime = "<?php echo $estimated_time; ?>";

            // Price Calculation
            var price = (size === 'S') ? 1000 : (size === 'M' ? 2000 : 3000);
            var total = price * qty;

            Swal.fire({
                title: 'Confirm Order?',
                html: `
                    <div style="text-align: left;">
                        <b>Address:</b> ${city} ${detail} <br>
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