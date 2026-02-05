<?php
// customer/index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db_conn.php';
require_once '../database/functions.php';

/* ===============================
   Traffic Status Helper
================================ */
function getTrafficStatus()
{
    $file = __DIR__ . '/../admin/traffic_status.txt';
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '0';
}

$msg = '';
$msg_type = '';
$postal_code = '';
$suggestions = []; // ဆိုင်ခွဲများ ထည့်ရန် Array

/* ===============================
   POST Handling
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------- (1) Delivery Area Check ---------- */
    if (isset($_POST['postal_code'])) {

        // sanitize postal code
        $postal_code = preg_replace('/[^0-9]/', '', $_POST['postal_code']);
        
        // Call the function from functions.php
        $check = checkDeliveryArea($postal_code);

        if ($check['status'] === 'error') {

            $msg = '❌ ' . $check['msg'];
            $msg_type = 'error';

        } elseif ($check['status'] === 'out_of_area') {

            $msg = '🚫 ' . $check['msg'];
            $msg_type = 'warning'; 
            // Suggestion ပါလာရင် ယူမယ်
            if (isset($check['suggestions'])) {
                $suggestions = $check['suggestions'];
            }

        } else {

            // ✅ SUCCESS AREA
            $found_address = $check['address'];
            // (FIX) Capture Distance for Kitchen Logic
            $distance_km = isset($check['km']) ? $check['km'] : 0; 
            
            $traffic_status = getTrafficStatus();

            /* ---------- Traffic Warning Check ---------- */
            // Traffic ပိတ်နေပြီး (1)၊ Customer က သဘောတူထားခြင်းမရှိသေးရင် (empty agree_late)
            if ($traffic_status === '1' && empty($_POST['agree_late'])) {
                ?>
                <!DOCTYPE html>
                <html lang="my">
                <head>
                    <meta charset="UTF-8">
                    <title>Traffic Warning</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        body { font-family: 'Segoe UI', sans-serif; background: #fff3cd; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                        .warn-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 400px; border-top: 5px solid #ffc107; }
                        h2 { color: #856404; margin-top: 0; }
                        button { background: #ffc107; border: none; padding: 10px 20px; color: #333; font-weight: bold; border-radius: 5px; cursor: pointer; font-size: 16px; }
                        button:hover { background: #e0a800; }
                        a { color: #666; text-decoration: none; display: inline-block; margin-top: 15px; font-size: 14px; }
                    </style>
                </head>
                <body>
                    <div class="warn-box">
                        <div style="font-size: 50px;">⚠️</div>
                        <h2>ယာဉ်ကြောပိတ်ဆို့နေပါသည်</h2>
                        <p>အော်ဒါများပြားနေသဖြင့် ပို့ဆောင်ချိန် <b>၄၅ မိနစ် - ၁ နာရီခန့်</b> ကြာနိုင်ပါသည်။</p>
                        
                        <form method="post">
                            <input type="hidden" name="postal_code" value="<?= htmlspecialchars($postal_code) ?>">
                            <input type="hidden" name="agree_late" value="1">
                            <button type="submit">ရပါတယ်၊ စောင့်ပါမည်</button>
                        </form>
                        
                        <a href="index.php">မမှာတော့ပါ (နောက်မှမှာမယ်)</a>
                    </div>
                </body>
                </html>
                <?php
                exit; // Warning ပြပြီးရင် ကုဒ်ကို ဒီမှာ ရပ်မယ်
            }

            // ✅ Redirect to order_form.php with Distance
            $encoded_address = urlencode($found_address);
            // (FIX) Added &dist=$distance_km
            header("Location: order_form.php?code=$postal_code&address=$encoded_address&dist=$distance_km");
            exit();
        }
    }

    /* ---------- (2) Order Status Check ---------- */
    if (isset($_POST['checkphonenumber'])) {
        $phone = trim($_POST['checkphonenumber']);

        if ($phone !== '') {
            // Check for existing order
            $stmt = $conn->prepare("SELECT id FROM orders WHERE phonenumber = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param('s', $phone);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($order = $result->fetch_assoc()) {
                header('Location: check_order.php?id=' . $order['id']);
                exit;
            } else {
                $msg = '❌ အော်ဒါရှာမတွေ့ပါ။ ဖုန်းနံပါတ် ပြန်စစ်ပါ။';
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
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 450px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-bottom: 10px; background: #007bff; color: white; transition: 0.3s; }
        button:hover { background: #0056b3; }
        
        /* Alerts */
        .error { color: #dc3545; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #f5c6cb; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #ffeeba; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 15px; }

        /* Suggestions Box */
        .suggestion-box { text-align: left; margin-top: 15px; }
        .shop-card { 
            background: #fff; 
            border-left: 5px solid #28a745; 
            padding: 10px; 
            margin-bottom: 10px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 4px;
            font-size: 14px;
        }
        .shop-card h4 { margin: 0 0 5px; color: #333; }
        .shop-card a { 
            display: inline-block; 
            text-decoration: none; 
            background: #28a745; 
            color: white; 
            padding: 5px 10px; 
            font-size: 12px; 
            border-radius: 3px; 
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="card">
    <h2 style="color:#333;">🍕 Fast Pizza</h2>

    <?php if ($msg): ?>
        <div class="<?= $msg_type ?>">
            <?= htmlspecialchars($msg) ?>

            <?php if (!empty($suggestions)): ?>
                <div class="suggestion-box">
                    <p style="color: #666; font-size: 13px; margin-bottom:10px;">▼ မိတ်ဆွေနှင့် နီးစပ်သော အခြားဆိုင်ခွဲများ ▼</p>
                    
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
        <button type="submit">Check Delivery</button>
    </form>

    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">

    <h3 style="font-size: 14px; color: #666;">အော်ဒါ အခြေအနေ စစ်ဆေးရန်</h3>
    <form method="post">
        <input type="tel" name="checkphonenumber" placeholder="Phone Number">
        <button type="submit" style="background: #6c757d;">Search Order</button>
    </form>
</div>

</body>
</html>