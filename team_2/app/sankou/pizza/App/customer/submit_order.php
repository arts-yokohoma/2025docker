<?php
// customer/submit_order.php
require_once 'submit_order_logic.php';

// CASE 1: SUCCESS → Redirect
if ($success_order_id > 0) {
    header("Location: check_order.php?id=" . $success_order_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Order...</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .modal { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); text-align: center; max-width: 400px; width: 90%; }
        .btn { padding: 12px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 5px; width: 100%; }
        .btn-wait { background: #ffc107; color: #333; font-weight: bold; }
        .btn-cancel { background: #dc3545; color: white; text-decoration: none; display: inline-block; box-sizing: border-box; }
        .alert-icon { font-size: 50px; color: #dc3545; margin-bottom: 15px; }
    </style>
</head>
<body>

    <?php if ($is_overloaded): ?>
        <div class="modal">
            <div class="alert-icon">👨‍🍳⚠️</div>
            <h2 style="margin-top:0;">Kitchen is Busy!</h2>
            <p style="color: #666; line-height: 1.5;">
                လက်ရှိ အော်ဒါများပြားနေပါသည်။<br>
                မှာယူပါက <b>မိနစ် <?= $overload_wait_time ?> ခန့်</b> စောင့်ဆိုင်းရပါမည်။
            </p>

            <form method="POST">
                <input type="hidden" name="name" value="<?= htmlspecialchars($_POST['name']) ?>">
                <input type="hidden" name="phone" value="<?= htmlspecialchars($_POST['phone']) ?>">
                <input type="hidden" name="address_city" value="<?= htmlspecialchars($_POST['address_city']) ?>">
                <input type="hidden" name="address_detail" value="<?= htmlspecialchars($_POST['address_detail']) ?>">
                <input type="hidden" name="postal_code" value="<?= htmlspecialchars($_POST['postal_code']) ?>">
                <input type="hidden" name="size" value="<?= htmlspecialchars($_POST['size']) ?>">
                <input type="hidden" name="quantity" value="<?= htmlspecialchars($_POST['quantity']) ?>">
                
                <input type="hidden" name="latitude" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
                
                <input type="hidden" name="confirm_wait" value="1">

                <button type="submit" class="btn btn-wait">
                    ⏳ ရပါတယ်၊ စောင့်ပါမည် (Wait)
                </button>
            </form>

            <a href="index.php" class="btn btn-cancel">
                ❌ မမှာတော့ပါ (Decline)
            </a>
        </div>

    <?php elseif (!empty($error_message)): ?>
        <div class="modal">
            <h2 style="color:#dc3545;">Error!</h2>
            <p><?= $error_message ?></p>
            <a href="index.php" class="btn btn-cancel">Go Back</a>
        </div>
        
    <?php else: ?>
        <script>window.location.href = 'index.php';</script>
    <?php endif; ?>

</body>
</html>