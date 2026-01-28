<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/order_form.css">
    <title>Order form</title>
</head>
<body>

<?php 
// index.php ကနေ variable အနေနဲ့လာရင် ဒါမှမဟုတ် URL ကလာရင် ဖမ်းမယ်
$auto_address = isset($found_address) ? $found_address : (isset($_GET['address']) ? $_GET['address'] : ''); 

$is_heavy_traffic = false;
if (file_exists('../admin/traffic_status.txt')) {
    $status = file_get_contents('../admin/traffic_status.txt');
    if (trim($status) == '1') {
        $is_heavy_traffic = true;
    }
}
?>

<div class="order-box">
    <?php if ($is_heavy_traffic): ?>
        <div class="traffic-msg" style="background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
            ⚠️ ယာဉ်ကြောပိတ်ဆို့နေပါသည် (၄၅-၆၀ မိနစ်ခန့် ကြာနိုင်သည်)
        </div>
    <?php else: ?>
        <div class="traffic-msg" style="background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
            ✅ မိနစ် ၃၀ အတွင်း အရောက်ပို့ဆောင်ပါမည်။
        </div>
    <?php endif; ?>

    <h1 style="text-align: center; color: #dc3545;">Pizza Order Form</h1>
    <h2 style="text-align: center;">🍕 メニュー選択</h2>

    <form id="orderForm" action="submit_order.php" method="post" onsubmit="return confirmOrder(event)">
        
        <input type="hidden" name="postal_code" value="<?php echo htmlspecialchars($postal_code ?? ''); ?>">

        <label>Size:</label>
        <select name="size" id="size">
            <option value="S">Margherita S (¥1,000)</option>
            <option value="M">Margherita M (¥2,000)</option>
            <option value="L">Margherita L (¥3,000)</option>
        </select>

        <label>Quantity:</label>
        <input type="number" name="quantity" id="quantity" value="1" min="1">

        <label>Name:</label>
        <input type="text" name="name" id="name" required>

        <label>Phone Number:</label>
        <input type="tel" name="phone" id="phone" required>

        <label>City / District:</label>
        <input type="text" name="address_city" id="address_city" value="<?php echo htmlspecialchars($auto_address); ?>" readonly style="background-color: #eee;">

        <label>Building Name / House No. / Street:</label>
        <input type="text" name="address_detail" id="address_detail" placeholder="ဥပမာ- တိုက် (၅)၊ အခန်း (၂၀၄)" required>

        <input type="submit" value="Submit Order" style="width:100%; padding: 10px; background: #dc3545; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px;">
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function confirmOrder(event) {
    event.preventDefault(); // Form submit ကို ခေတ္တတားမယ်

    // ID တွေကိုသုံးပြီး Data ဆွဲထုတ်မယ်
    const name = document.getElementById('name').value;
    const phone = document.getElementById('phone').value;
    const sizeSelect = document.getElementById('size');
    const pizzaName = sizeSelect.options[sizeSelect.selectedIndex].text;
    const qty = document.getElementById('quantity').value;
    const city = document.getElementById('address_city').value;
    const detail = document.getElementById('address_detail').value;

    Swal.fire({
        title: '<span style="color: #dc3545;">အော်ဒါ အနှစ်ချုပ်</span>',
        html: `
            <div style="text-align: left; padding: 10px; border: 1px solid #eee; border-radius: 8px; background: #fafafa; font-size: 14px;">
                <p style="margin: 5px 0;"><strong>👤 ဝယ်သူအမည်:</strong> ${name}</p>
                <p style="margin: 5px 0;"><strong>📞 ဖုန်းနံပါတ်:</strong> ${phone}</p>
                <hr style="border: 0.5px solid #ddd;">
                <p style="margin: 5px 0;"><strong>🍕 ပီဇာ:</strong> ${pizzaName}</p>
                <p style="margin: 5px 0;"><strong>🔢 အရေအတွက်:</strong> ${qty} ခု</p>
                <hr style="border: 0.5px solid #ddd;">
                <p style="margin: 5px 0;"><strong>📍 ပို့ဆောင်မည့်လိပ်စာ:</strong><br>
                ${city}<br>${detail}</p>
            </div>
            <p style="margin-top: 15px; font-weight: bold; color: #555;">အချက်အလက်များ မှန်ကန်ပါသလား?</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ဟုတ်ကဲ့၊ မှာယူမယ်',
        cancelButtonText: 'ပြန်ပြင်မယ်',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // User က Confirm နှိပ်ရင် Form ကို Submit တကယ်လုပ်မယ်
            document.getElementById('orderForm').submit();
        }
    });

    return false;
}
</script>
    
</body>
</html>