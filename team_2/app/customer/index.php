<?php
// customer/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db_conn.php';
// Functions are needed to check the area and distance
require_once '../database/functions.php';

/* ===============================
   Traffic Status Helper
================================ */
function getTrafficStatus() {
    $file = __DIR__ . '/../admin/traffic_status.txt';
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '0';
}

$msg = '';
$msg_type = '';
$postal_code = '';
$suggestions = [];
$show_traffic_warning = false;

/* ===============================
   POST Handling
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------- (1) Delivery Area Check ---------- */
    if (isset($_POST['postal_code'])) {

        // Sanitize Input
        $postal_code = preg_replace('/[^0-9]/', '', $_POST['postal_code']);
        
        // 1. Check if the address exists and is within 5km
        // This function is in database/functions.php
        $check = checkDeliveryArea($postal_code);

        if ($check['status'] === 'error') {
            $msg = '❌ ' . $check['msg'];
            $msg_type = 'error';

        } elseif ($check['status'] === 'out_of_area') {
            $msg = '🚫 ' . $check['msg'];
            $msg_type = 'warning';
            // Show partner shops if available
            if (isset($check['suggestions'])) {
                $suggestions = $check['suggestions'];
            }

        } else {
            // ✅ SUCCESS: Address Found
            $found_address = $check['address'];
            
            // ✅ CRITICAL: Get Distance from the check result
            // We must pass this to order_form.php to calculate "Busy" status
            $distance_km = isset($check['km']) ? $check['km'] : 0; 
            
            $traffic_status = getTrafficStatus();

            /* ---------- Traffic Warning Check ---------- */
            // If traffic is ON and user hasn't agreed yet -> Show Warning
            if ($traffic_status === '1' && empty($_POST['agree_late'])) {
                $show_traffic_warning = true; 
            } else {
                // ✅ REDIRECT TO ORDER FORM
                // We pass 'code', 'address', and 'dist' in the URL
                $encoded_address = urlencode($found_address);
                header("Location: order_form.php?code=$postal_code&address=$encoded_address&dist=$distance_km");
                exit();
            }
        }
    }

    /* ---------- (2) Order Status Check ---------- */
    if (isset($_POST['checkphonenumber'])) {
        $phone = trim($_POST['checkphonenumber']);
        if ($phone !== '') {
            $stmt = $conn->prepare("SELECT id FROM orders WHERE phonenumber = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($order = $result->fetch_assoc()) {
                header('Location: check_order.php?id=' . $order['id']);
                exit;
            } else {
                $msg = '❌ Order not found. Please check your phone number.';
                $msg_type = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Pizza Delivery</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Specific Styles for Index */
        .warn-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 400px; margin: 50px auto; border-top: 5px solid #ffc107; }
    </style>
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f6f9;">

<?php if ($show_traffic_warning): ?>
    <div class="warn-box">
        <div style="font-size: 50px;">⚠️</div>
        <h2 style="color: #856404; margin-top: 0;">ယာဉ်ကြောပိတ်ဆို့နေပါသည်</h2>
        <p>အော်ဒါများပြားနေသဖြင့် ပို့ဆောင်ချိန် <b>၄၅ မိနစ် - ၁ နာရီခန့်</b> ကြာနိုင်ပါသည်။</p>
        
        <form method="post">
            <input type="hidden" name="postal_code" value="<?= htmlspecialchars($postal_code) ?>">
            <input type="hidden" name="agree_late" value="1">
            <button type="submit" class="btn" style="background: #ffc107; color: #333; font-weight: bold;">ရပါတယ်၊ စောင့်ပါမည်</button>
        </form>
        
        <a href="index.php" style="display:block; margin-top:15px; text-decoration:none; color:#666;">မမှာတော့ပါ (နောက်မှမှာမယ်)</a>
    </div>
<?php else: ?>

    <div class="card" style="max-width: 450px; width: 100%;">
        <h2 style="color:#333; text-align:center;">🍕 Fast Pizza</h2>

        <?php if ($msg): ?>
            <div class="<?= $msg_type ?>" style="padding:10px; border-radius:5px; margin-bottom:15px; font-weight:bold; 
                background: <?= ($msg_type=='error')?'#ffebee':'#fff3cd'; ?>; 
                color: <?= ($msg_type=='error')?'#c62828':'#856404'; ?>;">
                <?= htmlspecialchars($msg) ?>

                <?php if (!empty($suggestions)): ?>
                    <div class="suggestion-box" style="margin-top:10px; font-weight:normal;">
                        <p style="color: #666; font-size: 13px; margin-bottom:5px;">▼ မိတ်ဆွေနှင့် နီးစပ်သော အခြားဆိုင်ခွဲများ ▼</p>
                        <?php foreach ($suggestions as $shop): ?>
                            <div class="shop-card">
                                <h4>🏪 <?= htmlspecialchars($shop['name']) ?></h4>
                                <small style="color: #666;">📍 အကွာအဝေး: <b><?= $shop['dist'] ?> km</b></small><br>
                                <a href="<?= htmlspecialchars($shop['url']) ?>" target="_blank">ဆိုင်သို့သွားရန် &rarr;</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <h3>ပို့ဆောင်နိုင်သည့် ဧရိယာ စစ်ဆေးပါ</h3>
        <form method="post">
            <input type="text" name="postal_code" placeholder="Example: 1690073" required value="<?= htmlspecialchars($postal_code) ?>">
            <button type="submit" class="btn-save">Check Delivery</button>
        </form>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

        <h3 style="font-size: 14px; color: #666;">အော်ဒါ အခြေအနေ စစ်ဆေးရန်</h3>
        <form method="post">
            <input type="tel" name="checkphonenumber" placeholder="Phone Number">
            <button type="submit" class="btn" style="background: #6c757d; width:100%;">Search Order</button>
        </form>
    </div>

<?php endif; ?>

</body>
</html>