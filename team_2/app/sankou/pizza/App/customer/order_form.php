<?php
// Logic file handles calculation & interception
require_once 'order_form_logic.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['order_form_title'] ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container { max-width: 500px; margin: 20px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-order { width: 100%; padding: 14px; background: #e74c3c; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 15px; font-weight: bold; }
        .btn-order:hover { background: #c0392b; }
    </style>
</head>
<body>

    <div id="main-form" class="container">
        
        <?php if (isset($is_heavy_traffic) && $is_heavy_traffic): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align:center;">
                <i class="fas fa-traffic-light"></i> <?= $lang['heavy_traffic'] ?> (目安: <?php echo $estimated_time; ?> <?= $lang['mins'] ?>)
            </div>
        <?php elseif ($estimated_time > 30): ?>
             <div style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align:center;">
                <i class="fas fa-clock"></i> 
                <?= $lang['kitchen_busy'] ?>: <b><?= $estimated_time ?> <?= $lang['mins'] ?></b>
            </div>
        <?php else: ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align:center;">
                <i class="fas fa-check-circle"></i> 通常配達: <b><?= $estimated_time ?> <?= $lang['mins'] ?></b>
            </div>
        <?php endif; ?>

        <h2 style="text-align:center; color:#333;"><?= $lang['order_form_title'] ?></h2>
        
        <form id="orderForm" action="submit_order.php" method="post" onsubmit="return confirmOrder(event)">
            <input type="hidden" name="postal_code" value="<?= $postal_code ?>">
            <input type="hidden" name="latitude" value="<?= $lat ?>">
            <input type="hidden" name="longitude" value="<?= $lng ?>">
            
            <?php if(isset($_GET['confirm_wait'])): ?>
                <input type="hidden" name="confirm_wait" value="1">
            <?php endif; ?>

            <label><?= $lang['name'] ?></label>
            <input type="text" name="name" id="name" required placeholder="例: 山田 太郎">

            <label><?= $lang['phone'] ?></label>
            <input type="tel" name="phone" id="phone" required placeholder="例: 09012345678 (ハイフンなし)">

            <label><?= $lang['city'] ?></label>
            <input type="text" name="address_city" id="address_city" value="<?= $found_address ?>" readonly style="background: #eee;">

            <label><?= $lang['detail'] ?></label>
            <input type="text" name="address_detail" id="address_detail" placeholder="例: 1-2-3 ○○マンション 101号室" required>

            <label><?= $lang['size'] ?></label>
            <select name="size" id="size">
                <option value="S">S (¥1,000)</option>
                <option value="M" selected>M (¥2,000)</option>
                <option value="L">L (¥3,000)</option>
            </select>

            <input type="number" name="quantity" id="quantity" value="<?= isset($_GET['qty']) ? intval($_GET['qty']) : 1 ?>" min="1" max="10">

            <button type="submit" class="btn-order"><?= $lang['order_btn'] ?></button>
        </form>

        <a href="index.php" style="display:block; margin-top:15px; text-align:center; color:#666; text-decoration:none; font-size:14px;">戻る</a>
    </div>

    <script>
        function confirmOrder(e) {
            e.preventDefault();
            
            // 1. Get Values
            var name = document.getElementById('name').value.trim();
            var phone = document.getElementById('phone').value.trim();
            var detail = document.getElementById('address_detail').value.trim();
            var size = document.getElementById('size').value;
            var qty = document.getElementById('quantity').value;
            
            // 2. 🔥 PHONE VALIDATION (JAPAN FORMAT)
            // Remove hyphens just in case
            var cleanPhone = phone.replace(/-/g, "");
            
            // Check: Must start with 0, and be 10 or 11 digits
            var phoneRegex = /^0\d{9,10}$/;
            
            if (!phoneRegex.test(cleanPhone)) {
                Swal.fire({
                    title: '電話番号エラー',
                    text: '正しい電話番号を入力してください (例: 09012345678)',
                    icon: 'warning',
                    confirmButtonColor: '#e74c3c'
                });
                return; // Stop form submission
            }

            // 3. Address Detail Check
            if (detail.length < 2) {
                Swal.fire('エラー', '住所の詳細を入力してください', 'warning');
                return;
            }

            // 4. Calculate Price
            var estimatedTime = "<?= $estimated_time; ?>";
            var price = (size === 'S') ? 1000 : (size === 'M' ? 2000 : 3000);
            var total = price * qty;

            // 5. Confirm Dialog
            Swal.fire({
                title: '確認 (Confirm)',
                html: `
                    <div style="text-align: left;">
                        <b>Size:</b> ${size} x ${qty} <br>
                        <b>ETA:</b> <span style="color:red; font-weight:bold;">${estimatedTime} <?= $lang['mins'] ?></span> <br>
                        <hr>
                        <b>Total:</b> <span style="color:green; font-weight:bold;">¥${total.toLocaleString()}</span>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '注文する',
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#d33',
                cancelButtonText: 'キャンセル'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Update phone input to clean version (no hyphens) before submitting
                    document.getElementById('phone').value = cleanPhone;
                    document.getElementById('orderForm').submit();
                }
            });
        }
    </script>
</body>
</html>