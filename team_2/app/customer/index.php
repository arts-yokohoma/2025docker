<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../database/db_conn.php';
require_once '../database/functions.php';

/* ===============================
   Traffic Status
================================ */
function getTrafficStatus()
{
    $file = __DIR__ . '/../admin/traffic_status.txt';
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '0';
}

/* ===============================
   Init
================================ */
$show_order_form = false;
$msg = '';
$msg_type = '';
$postal_code = '';
$found_address = '';

/* ===============================
   POST Handling
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---------- (1) Delivery Area Check ---------- */
    if (isset($_POST['postal_code'])) {

        // sanitize postal code
        $postal_code = preg_replace('/[^0-9]/', '', $_POST['postal_code']);
        $check = checkDeliveryArea($postal_code);

        if ($check['status'] === 'error') {

            $msg = '❌ ' . $check['msg'];
            $msg_type = 'error';

        } elseif ($check['status'] === 'out_of_area') {

            $msg = '🚫 ' . $check['msg'];
            $msg_type = 'error';
            $show_order_form = false;

        } else {

            // SUCCESS
            $found_address = $check['address'];
            $traffic_status = getTrafficStatus();

            /* ---------- Traffic confirm ---------- */
            if ($traffic_status === '1' && empty($_POST['agree_late'])) {
                ?>
                <!DOCTYPE html>
                <html lang="my">
                <head>
                    <meta charset="UTF-8">
                    <title>Traffic Warning</title>
                </head>
                <body style="font-family:sans-serif;background:#f8f9fa;">
                    <div style="max-width:500px;margin:80px auto;padding:40px;text-align:center;
                                background:#fff3cd;border:2px solid #ffc107;border-radius:15px;">
                        <h2>⚠️ ယာဉ်ကြောပိတ်ဆို့နေပါသည်</h2>
                        <p>ပို့ဆောင်ချိန် <b>၄၅ မိနစ်ထက် ပိုကြာနိုင်ပါသည်</b></p>
                        <form method="post">
                            <input type="hidden" name="postal_code" value="<?= htmlspecialchars($postal_code) ?>">
                            <input type="hidden" name="agree_late" value="1">
                            <button type="submit">ရပါတယ် ဆက်မှာမယ်</button>
                            <a href="index.php">မမှာတော့ပါ</a>
                        </form>
                    </div>
                </body>
                </html>
                <?php
                exit;
            }

            $msg = '✅ ပို့ဆောင်နိုင်သော ဧရိယာအတွင်း ရှိပါသည်။';
            $msg_type = 'success';
            $show_order_form = true;
        }
    }

    /* ---------- (2) Order Status Check ---------- */
    if (isset($_POST['checkphonenumber'])) {

        $phone = trim($_POST['checkphonenumber']);

        if ($phone !== '') {
            $stmt = $conn->prepare(
                "SELECT id FROM orders WHERE phonenumber = ? ORDER BY id DESC LIMIT 1"
            );
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
    <title>Pizza Delivery</title>
</head>
<body>

<h1>🍕 Fast Pizza</h1>

<?php if ($msg): ?>
    <div class="<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<?php if ($show_order_form): ?>
    <?php
    // 🔴 order_form ကို မပြင်ဘဲ address ပို့
    $_GET['address'] = $found_address;
    ?>
    <?php include 'order_form.php'; ?>
<?php else: ?>

    <h3>ပို့ဆောင်နိုင်သည့် ဧရိယာ စစ်ဆေးပါ</h3>
    <form method="post">
        <input type="text" name="postal_code" placeholder="1690073" required>
        <button type="submit">Check Delivery</button>
    </form>

    <hr>

    <h3>အော်ဒါ အခြေအနေ စစ်ဆေးရန်</h3>
    <form method="post">
        <input type="text" name="checkphonenumber" placeholder="ဖုန်းနံပါတ်">
        <button type="submit">Search Order</button>
    </form>

<?php endif; ?>

</body>
</html>
