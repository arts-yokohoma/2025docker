<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// --- Shop Status Logic (Matches Index) ---
date_default_timezone_set('Asia/Tokyo');
$currentHour = (int)date('H');
$isOpen = ($currentHour >= 10 && $currentHour < 22);

// Shop info
$shop_postal = "220-0072";
$shop_address = "神奈川県横浜市西区浅間町2-105-8";
$shop_lat = 35.4760;
$shop_lng = 139.6200;

// Handle form submission for route
$maps_link = "";
if (isset($_POST['customer_postal']) && !empty($_POST['customer_postal'])) {
    $customer_postal = htmlspecialchars($_POST['customer_postal'], ENT_QUOTES, 'UTF-8');
    $maps_link = "https://www.google.com/maps/dir/?api=1&origin={$shop_postal}&destination={$customer_postal}";
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>お問い合わせ | ピザマッハ</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<style>
     :root {
        --bg-color: #fffafa; /* Very light Sakura white */
        --card-bg: #ffffff;
        --accent-red: #e63946; 
        --sakura-pink: #fff0f0; 
        --status-blue: #3498db; 
        --status-red: #d63031;
        --text-dark: #2d3436;
        --shadow-soft: 0 8px 30px rgba(0,0,0,0.05);
    }

    body { 
        margin: 0; 
        font-family: 'Helvetica Neue', 'Arial', 'Hiragino Kaku Gothic ProN', sans-serif;
        background-color: var(--bg-color); 
        color: var(--text-dark); 
    }

    /* --- MODERN JAPANESE THEME HEADER (Synced with Index) --- */
   .header-wrapper {
        background: var(--sakura-pink); 
        padding: 15px 4%;
        display: flex; justify-content: space-between; align-items: center;
        position: sticky; top: 0; z-index: 1000;
        border-bottom: 3px solid var(--accent-red);
        box-shadow: 0 4px 15px rgba(230, 57, 70, 0.1);
    }
    
    .logo-section { display: flex; align-items: center; gap: 15px; }
    .logo-left { width: 50px; height: 50px; border-radius: 50%; background: var(--accent-red); padding: 5px; }
    .brand-name h1 { margin: 0; font-size: 1.6rem; color: var(--accent-red); font-weight: 900; }
    .brand-name p { margin: 0; font-size: 0.7rem; color: #636e72; font-weight: bold; }
     /* --- Status Buttons (Color Logic Restored) --- */
    .status-container { display: flex; gap: 10px; }
    .status-btn {
        padding: 8px 18px; border-radius: 50px; font-size: 0.85rem;
        font-weight: 800; display: flex; align-items: center; gap: 8px; color: #2d3436;
        background: #fff;
        border: 2px solid #fab1a0;
        box-shadow: 0 3px 0px #fab1a0;
    }
    
    /* Icon color logic based on $isOpen */
    .icon-clock-status { 
        font-size: 1.1rem;
        color: <?php echo $isOpen ? 'var(--status-blue)' : 'var(--status-red)'; ?>;
        text-shadow: 0 0 5px <?php echo $isOpen ? 'rgba(52,152,219,0.3)' : 'rgba(214,48,49,0.3)'; ?>;
    }

    .status-cash { background: var(--accent-red); color: white; border: none; box-shadow: 0 3px 0px #c0392b; }

    /* --- Navigation --- */
    .nav-links { display: flex; gap: 5px; }
    .nav-item { 
        color: var(--text-dark); text-decoration: none; padding: 10px 15px; border-radius: 12px;
        font-size: 0.95rem; font-weight: 900; transition: 0.3s; display: flex; align-items: center; gap: 6px;
    }
    .nav-item:hover { background: #ffebeb; color: var(--accent-red); }


    /* --- Page Layout --- */
    .page-wrapper { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
    .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
    .contact-card { background: var(--card-bg); padding: 40px; border-radius: 25px; box-shadow: var(--shadow); border: 1px solid #fdf0f0; }

    h2 { font-size: 1.6rem; color: var(--accent-red); border-bottom: 2px solid #eee; padding-bottom: 15px; margin-top: 0; display: flex; align-items: center; gap: 10px; }

    .info-item { margin-bottom: 25px; display: flex; align-items: flex-start; gap: 15px; }
    .info-item i { font-size: 1.4rem; color: var(--accent-red); margin-top: 5px; }
    .info-text strong { display: block; font-size: 0.9rem; color: #888; }
    .info-text p, .info-text a { font-size: 1.15rem; margin: 0; font-weight: bold; color: var(--text-dark); text-decoration: none; }
    .info-text a:hover { color: var(--accent-red); }

    #shop-map { width: 100%; height: 450px; border-radius: 20px; box-shadow: var(--shadow); border: 5px solid white; }

    .input-group { display: flex; justify-content: center; gap: 10px; margin-top: 20px; }
    input[type="text"] { padding: 15px 20px; border-radius: 12px; border: 2px solid #eee; width: 250px; font-size: 1.1rem; font-weight: bold; }
    .btn-search { background: var(--accent-red); color: white; border: none; padding: 15px 30px; border-radius: 12px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .btn-search:hover { background: #c0392b; transform: scale(1.05); }

    .route-link { margin-top: 20px; display: inline-block; background: #3498db; color: white; padding: 15px 40px; border-radius: 50px; text-decoration: none; font-weight: bold; transition: 0.3s; }
    .route-link:hover { background: #2980b9; box-shadow: 0 5px 15px rgba(52,152,219,0.3); }

     @media (max-width: 1000px) { .header-wrapper { flex-direction: column; gap: 15px; text-align: center; } .cart-box { position: static; } }
</style>
</head>
<body>
<header class="header-wrapper">
  <div class="logo-section">
    <img src="assets/images/logo.png" alt="Logo" class="logo-left">
    <div class="brand-name">
      <h1>ピザマッハ</h1>
      <p>PIZZA MASH - 職人仕込みの味</p>
    </div>
  </div>

  <div class="status-container">
    <div class="status-btn">
        <i class="fas fa-clock icon-clock-status"></i>営業時間 10:00 - 22:00
    </div>
    <div class="status-btn status-cash">
        <i class="fas fa-yen-sign" style="color:#ffeaa7"></i>   現金のみ
    </div>
     <div class="status-btn delivery">
        <i class="fas fa-bolt" style="color:#27ae60"></i> 
    30分以内にお届け
</div>
  </div>
 
  <nav class="nav-links">
    <a href="index.php" class="nav-item"><i class="fas fa-home"></i> ホーム</a>
    <a href="index.php" class="nav-item"><i class="fas fa-utensils"></i> メニュー</a>
    <a href="order_status.php" class="nav-item"><i class="fas fa-shipping-fast"></i> 注文状況</a>
    <a href="contact.php" class="nav-item"><i class="fas fa-phone-alt"></i> お問い合わせ</a>
  </nav>
</header>

<div class="page-wrapper">

    <div class="contact-grid">
        <div class="contact-card">
            <h2><i class="fas fa-store"></i> 店舗情報</h2>
            
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div class="info-text">
                    <strong>住所</strong>
                    <p>〒<?= $shop_postal ?><br><?= $shop_address ?></p>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-phone-alt"></i>
                <div class="info-text">
                    <strong>電話番号</strong>
                    <a href="tel:0451234567">045-123-4567</a>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-envelope"></i>
                <div class="info-text">
                    <strong>メール</strong>
                    <a href="mailto:info@pizzamach.jp">info@pizzamach.jp</a>
                </div>
            </div>

            <div class="info-item">
                <i class="fas fa-clock"></i>
                <div class="info-text">
                    <strong>営業時間</strong>
                    <p>10:00 ～ 22:00 (年中無休)</p>
                </div>
            </div>
        </div>

        <div class="contact-card" style="background: #2d3436; color: white; border: none;">
            <h2 style="color: white; border-color: rgba(255,255,255,0.1);"><i class="fas fa-route"></i> ルート確認</h2>
            <p style="font-size: 0.95rem; opacity: 0.8;">お届け先、またはご自宅の郵便番号を入力してください。店舗からの最適なルートをご案内します。</p>
            
            <form method="post" class="input-group">
                <input type="text" name="customer_postal" placeholder="例: 2200072" value="<?= isset($customer_postal) ? $customer_postal : '' ?>" required>
                <button type="submit" class="btn-search">検索する</button>
            </form>

            <?php if (!empty($maps_link)): ?>
                <div style="text-align: center;">
                    <a href="<?= $maps_link ?>" target="_blank" class="route-link">
                        <i class="fas fa-external-link-alt"></i> Googleマップを開く
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <section style="margin-top: 40px;">
        <h2 style="justify-content: center;"><i class="fas fa-map-marked-alt"></i> 店舗地図</h2>
        <div id="shop-map"></div>
    </section>

    <footer style="text-align:center; margin-top:60px; padding-bottom: 40px; color: #888;">
        <p>© 2026 ピザマッハ | PIZZA MASH All Rights Reserved.</p>
    </footer>

</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    var map = L.map('shop-map', { scrollWheelZoom: false }).setView([<?= $shop_lat ?>, <?= $shop_lng ?>], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    L.marker([<?= $shop_lat ?>, <?= $shop_lng ?>]).addTo(map)
        .bindPopup("<b style='font-size:1.1rem; color:#e63946;'>ピザマッハ</b><br>横浜店")
        .openPopup();
</script>

</body>
</html>