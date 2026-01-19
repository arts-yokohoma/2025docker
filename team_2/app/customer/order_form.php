<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order form</title>
</head>
<body>
    <?php $auto_address = isset($_GET['address']) ? $_GET['address'] : ''; ?>

    <?php
// Admin က သတ်မှတ်လိုက်တဲ့ Traffic အခြေအနေကို လှမ်းဖတ်မယ်
$is_heavy_traffic = false;
if (file_exists('app/admin/traffic_status.txt')) {
    $status = file_get_contents('app/admin/traffic_status.txt');
    if ($status == '1') {
        $is_heavy_traffic = true;
    }
}
?>

<?php if ($is_heavy_traffic): ?>
    <div style="background-color: #ffcccc; color: red; padding: 10px; border: 1px solid red; margin-bottom: 10px;">
        ⚠️ <strong>သတိပြုရန်:</strong> လက်ရှိ ယာဉ်ကြောပိတ်ဆို့နေသဖြင့် ပို့ဆောင်ချိန် ပုံမှန်ထက် ကြာမြင့်နိုင်ပါသည်။ (ခန့်မှန်းချိန်: ၄၅-၆၀ မိနစ်)
    </div>
<?php else: ?>
    <div style="background-color: #ccffcc; color: green; padding: 10px; border: 1px solid green; margin-bottom: 10px;">
        ✅ <strong>ပုံမှန်အခြေအနေ:</strong> မိနစ် ၃၀ အတွင်း အရောက်ပို့ဆောင်ပါမည်။
    </div>
<?php endif; ?>
    <h1>Select pizza</h1>
        <h2>🍕 メニュー選択</h2>
        <form action="submit_order.php" method="post">
        <select id="size" name="size">
            <option value="S">マルゲリータ S (¥1,000)</option>
            <option value="M">マルゲリータ M (¥2,000)</option>
            <option value="L">マルゲリータ L (¥3,000)</option>
        </select>
        <label for="quality">nannko</label>
        <input type="number" name="quantity" id="quantity">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required><br><br>
        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($auto_address); ?>" required><br><br>
        <label for="phone">Phone Number:</label>
        <input type="tel" id="phone" name="phone" required><br><br>
        <input type="submit" value="Submit Order">
    </form>
    
</body>
</html>

