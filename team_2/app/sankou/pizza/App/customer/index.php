<?php require_once 'index_logic.php'; ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fast Pizza Delivery</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f6f9; font-family: 'Helvetica Neue', Arial, sans-serif; }
        .card { max-width: 450px; width: 100%; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
        
        input[type="text"], input[type="tel"] { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 16px; }
        .btn-save { width: 100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; background: #e74c3c; color: white; transition: 0.3s; font-weight: bold; }
        .btn-search { background: #6c757d; width:100%; padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; color: white; font-weight: bold; }
        
        .note-box {
            background: #fff3cd; color: #856404;
            padding: 10px; border-radius: 5px;
            font-size: 13px; text-align: left;
            margin-top: 10px; border: 1px solid #ffeeba;
        }
    </style>
</head>
<body>

<?php if ($show_traffic_warning): ?>
    <div class="card" style="border-top: 5px solid #ffc107;">
        <h2 style="color: #856404;">現在混み合っております</h2>
        <p>お届けまでに <b>45分〜60分</b> ほどお時間を頂いております。</p>
        <form method="post">
            <input type="hidden" name="postal_code" value="<?= htmlspecialchars($postal_code) ?>">
            <input type="hidden" name="agree_late" value="1">
            <button type="submit" class="btn-save" style="background: #ffc107; color: #333;">了承して進む</button>
        </form>
    </div>
<?php else: ?>

    <div class="card">
        <h2 style="color:#333; margin-top: 0;">🍕 Fast Pizza</h2>

        <?php if ($msg): ?>
            <div style="background:#ffebee; color:#c62828; padding:10px; border-radius:5px; margin-bottom:15px; text-align:left;">
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <h3>配達エリアを確認</h3>
        <p style="font-size:12px; color:#666; margin-bottom:5px;">郵便番号を入力してください</p>
        
        <form method="post">
            <input type="text" name="postal_code" placeholder="郵便番号 (例: 1690073)" required value="<?= htmlspecialchars($postal_code) ?>" pattern="\d{7}" title="7桁の数字">
            
            <div class="note-box">
                <p style="margin:0; font-weight:bold;">⚠️ 注意 (Note)</p>
                <p style="margin:5px 0 0 0; line-height:1.4;">
                    11個以上のご注文はお電話ください。<br>
                    Orders over 10 items? Please call.<br>
                    📞 <b>03-1234-5678</b>
                </p>
            </div>

            <button type="submit" class="btn-save" style="margin-top:15px;">エリアを確認する</button>
        </form>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

        <h3 style="font-size: 14px; color: #666;">注文状況を確認</h3>
        <form method="post">
            <input type="tel" name="checkphonenumber" placeholder="電話番号を入力">
            <button type="submit" class="btn-search">検索する</button>
        </form>
    </div>

<?php endif; ?>

</body>
</html>