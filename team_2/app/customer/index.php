<?php
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
            $msg_type = 'error';

        } else {

            // SUCCESS AREA
            $found_address = $check['address'];
            $traffic_status = getTrafficStatus();

            /* ---------- Traffic Warning Check ---------- */
            if ($traffic_status === '1' && empty($_POST['agree_late'])) {
                // Show Warning Interstitial
                ?>
                <!DOCTYPE html>
                <html lang="my">
                <head>
                    <meta charset="UTF-8">
                    <title>Traffic Warning</title>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                </head>
                <body style="font-family:sans-serif;background:#f8f9fa;">
                    <div style="max-width:500px;margin:80px auto;padding:40px;text-align:center;
                                background:#fff3cd;border:2px solid #ffc107;border-radius:15px;">
                        <h2>⚠️ ယာဉ်ကြောပိတ်ဆို့နေပါသည်</h2>
                        <p>ပို့ဆောင်ချိန် <b>၄၅ မိနစ်ထက် ပိုကြာနိုင်ပါသည်</b></p>
                        <form method="post">
                            <input type="hidden" name="postal_code" value="<?= htmlspecialchars($postal_code) ?>">
                            <input type="hidden" name="agree_late" value="1">
                            <button type="submit" style="padding:10px 20px; cursor:pointer;">ရပါတယ် ဆက်မှာမယ်</button>
                            <br><br>
                            <a href="index.php">မမှာတော့ပါ</a>
                        </form>
                    </div>
                </body>
                </html>
                <?php
                exit; // Stop script here to show warning
            }

            // ✅ CORRECTED: Redirect to order_form.php
            // We pass the code and address via URL parameters (GET)
            $encoded_address = urlencode($found_address);
            header("Location: order_form.php?code=$postal_code&address=$encoded_address");
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
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-bottom: 10px; background: #007bff; color: white; }
        .error { color: #dc3545; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h2 style="color:#333;">🍕 Fast Pizza</h2>

    <?php if ($msg): ?>
        <div class="<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
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