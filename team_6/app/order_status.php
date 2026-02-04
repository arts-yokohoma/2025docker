<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db/connect.php';

// --- HANDLE DELETION ---
if (isset($_POST['delete_history'])) {
    $order_ids = $_POST['selected_orders'] ?? [];
    $phone_redirect = $_POST['phone'] ?? '';
    if (!empty($order_ids)) {
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $stmt = $db->prepare("UPDATE orders SET status = 'Cancelled' WHERE id IN ($placeholders)");
        $stmt->execute($order_ids);
        header("Location: order_status.php?msg=deleted&phone=" . urlencode($phone_redirect));
        exit;
    }
}

date_default_timezone_set('Asia/Tokyo');
$currentHour = (int)date('H');
$isOpen = ($currentHour >= 10 && $currentHour < 22);

$shop_lat = 35.4760;
$shop_lng = 139.6200;

$orders = [];
$phone = '';
if(isset($_POST['check_order']) || isset($_GET['phone'])){
    $phone = $_POST['phone'] ?? $_GET['phone'];
    $stmt = $db->prepare("SELECT * FROM customers WHERE phone = ?");
    $stmt->execute([$phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$customer){
        $error_message = "この電話番号では注文が見つかりませんでした。";
    } else {
        $customer_id = $customer['id'];
        $stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY order_time DESC");
        $stmt->execute([$customer_id]);
        $all_raw_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $orders = []; 
        $active_count = 0;
        $history_count = 0;

        foreach($all_raw_orders as $order) {
            if($order['status'] === 'Cancelled' || $order['status'] === 'Hidden') continue;
            $orders[] = $order;
            if($order['status'] === 'Delivered') {
                $history_count++;
            } else {
                $active_count++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文状況確認 | ピザマッハ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
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

        .page-wrapper { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .status-card { background: var(--card-bg); padding: 30px; border-radius: 25px; box-shadow: var(--shadow); margin-bottom: 40px; }
        
        .search-box { text-align: center; position: sticky; top: 90px; z-index: 900; background: white; padding: 20px; border: 2px solid var(--accent-red); }
        .input-row { display: flex; justify-content: center; gap: 10px; margin-top: 15px; }
        .input-style { padding: 12px; border-radius: 12px; border: 2px solid #eee; width: 60%; }
        .btn-main { background: var(--accent-red); color: white; border: none; padding: 12px 25px; border-radius: 12px; font-weight: bold; cursor: pointer; }

        .section-title { margin: 50px 0 20px; border-bottom: 2px solid var(--accent-red); display: flex; align-items: center; gap: 10px; padding-bottom: 10px;}
        .order-items-list { background: #f9f9f9; padding: 15px; border-radius: 15px; list-style: none; }
        .order-items-list li { padding: 8px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        
        /* Progress Bar */
        .progress-wrapper { position: relative; display: flex; justify-content: space-between; margin: 40px 0; }
        .progress-bg-line { position: absolute; top: 25px; left: 0; right: 0; height: 6px; background: var(--progress-gray); z-index: 1; }
        .progress-fill-line { position: absolute; top: 0; left: 0; height: 100%; background: var(--accent-red); transition: width 0.8s; }
        .step { position: relative; z-index: 2; text-align: center; width: 60px; }
        .step-circle { width: 50px; height: 50px; border-radius: 50%; background: white; border: 4px solid var(--progress-gray); margin: 0 auto; display: flex; align-items: center; justify-content: center; }
        .step.active .step-circle { border-color: var(--accent-red); color: var(--accent-red); }
        .step-label { font-size: 0.75rem; font-weight: bold; margin-top: 8px; }

        .history-card { border-left: 8px solid #bdc3c7; }
        .leaflet-marker-icon { transition: transform 4s linear !important; }
         @media (max-width: 1000px) { .header-wrapper { flex-direction: column; gap: 15px; text-align: center; } .cart-box { position: static; } }
    </style>
</head>
<body>
<audio id="deliverySound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

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
    <div class="status-card search-box">
        <form method="post" action="order_status.php">
            <label style="font-weight: bold;">📦 注文状況を確認する</label>
            <div class="input-row">
                <input type="text" name="phone" id="user-phone-input" class="input-style" placeholder="電話番号を入力" value="<?php echo htmlspecialchars($phone); ?>" required>
                <button type="submit" name="check_order" class="btn-main">確認する</button>
            </div>
        </form>
    </div>

    <?php if(!empty($orders)): 
        $stmt_items = $db->prepare("SELECT oi.*, m.name FROM order_items oi JOIN menu_items m ON oi.menu_item_id = m.id WHERE oi.order_id = ?");
    ?>
        
        <div id="order-live-area">
            <h2 class="section-title"><i class="fas fa-utensils"></i> 現在進行中の注文</h2>
            <?php 
            $active_count = 0;
            foreach($orders as $order): 
                if($order['status'] === 'Delivered') continue;
                $active_count++;
                
                $status_index = 0;
                switch($order['status']){
                    case 'Pending': $status_index = 1; break;
                    case 'Cooking': $status_index = 2; break;
                    case 'Ready': case 'Out for Delivery': $status_index = 3; break;
                }
                $progress_width = ($status_index - 1) * 33.33;
            ?>
                <div class="status-card" data-order-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>">
                    <div style="display: flex; justify-content: space-between;">
                        <h3 style="color:var(--accent-red);">注文番号 #<?php echo $order['id']; ?></h3>
                        <span><?php echo date('g:i A', strtotime($order['order_time'])); ?></span>
                    </div>

                    <ul class="order-items-list">
                        <?php
                        $stmt_items->execute([$order['id']]);
                        foreach($stmt_items->fetchAll() as $item): ?>
                            <li><span><?php echo htmlspecialchars($item['name']); ?></span><span>× <?php echo $item['quantity']; ?></span></li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="progress-wrapper">
                        <div class="progress-bg-line"><div class="progress-fill-line" style="width: <?php echo $progress_width; ?>%;"></div></div>
                        <div class="step <?php echo $status_index >= 1 ? 'active' : ''; ?>"><div class="step-circle"><i class="fas fa-clipboard-check"></i></div><div class="step-label">受付</div></div>
                        <div class="step <?php echo $status_index >= 2 ? 'active' : ''; ?>"><div class="step-circle"><i class="fas fa-fire-alt"></i></div><div class="step-label">調理中</div></div>
                        <div class="step <?php echo $status_index >= 3 ? 'active' : ''; ?>"><div class="step-circle"><i class="fas fa-motorcycle"></i></div><div class="step-label">配達中</div></div>
                        <div class="step"><div class="step-circle"><i class="fas fa-home"></i></div><div class="step-label">完了</div></div>
                    </div>

                    <div id="map-<?php echo $order['id']; ?>" class="order-map" style="width: 100%; height: 350px; border-radius: 20px;"></div>
                </div>
            <?php endforeach; if($active_count == 0) echo "<div class='empty-state'>進行中の注文はありません。</div>"; ?>
        </div>
        <h2 class="section-title"><i class="fas fa-history"></i> 過去の注文履歴</h2>
        <?php if($history_count > 0): ?>
            <form method="post">
                <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                <?php foreach($orders as $order): if($order['status'] !== 'Delivered') continue; ?>
                    <div class="status-card history-card">
                        <input type="checkbox" name="selected_orders[]" value="<?php echo $order['id']; ?>" class="order-checkbox">
                        注文番号 #<?php echo $order['id']; ?> - 配達完了
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="delete_history" class="btn-main">履歴を削除</button>
            </form>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

<script>
// --- Map and Tracking Logic ---
let activeMaps = {}; // Keep track of initialized maps

function initOrderTracking(orderId, shopLat, shopLng, custLat, custLng, status) {
    var mapId = 'map-' + orderId;
    if(!document.getElementById(mapId) || activeMaps[mapId]) return;

    var map = L.map(mapId).setView([shopLat, shopLng], 14);
    activeMaps[mapId] = map;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    var routingControl = L.Routing.control({
        waypoints: [L.latLng(shopLat, shopLng), L.latLng(custLat, custLng)],
        lineOptions: { styles: [{ color: '#e63946', weight: 6 }] },
        createMarker: function(i, wp) {
            if (i === 0) return L.marker(wp.latLng, {icon: L.icon({iconUrl: 'https://cdn-icons-png.flaticon.com/512/608/608674.png', iconSize: [30, 30]}) });
            return L.marker(wp.latLng);
        },
        show: false
    }).addTo(map);

    if(status === 'Out for Delivery') {
        var driverMarker = L.marker([shopLat, shopLng], {
            icon: L.icon({ iconUrl: 'https://cdn-icons-png.flaticon.com/512/3063/3063822.png', iconSize: [50, 50] })
        }).addTo(map);

        routingControl.on('routesfound', function(e) {
            var coords = e.routes[0].coordinates;
            var i = 0;
            function animate() {
                if (i < coords.length) {
                    driverMarker.setLatLng([coords[i].lat, coords[i].lng]);
                    i++; setTimeout(animate, 1500);
                } else {
                    document.getElementById('deliverySound').play();
                }
            }
            setTimeout(animate, 2000);
        });
    }
}

function startAllMaps() {
    <?php if(!empty($orders)): foreach($orders as $order): if($order['status'] === 'Delivered') continue; ?>
        initOrderTracking("<?php echo $order['id']; ?>", <?php echo $shop_lat; ?>, <?php echo $shop_lng; ?>, <?php echo $order['lat'] ?? 35.4775; ?>, <?php echo $order['lng'] ?? 139.6230; ?>, "<?php echo $order['status']; ?>");
    <?php endforeach; endif; ?>
}

// --- AJAX LIVE UPDATE LOGIC ---
function refreshLiveArea() {
    const phone = "<?php echo urlencode($phone); ?>";
    if(!phone) return;

    // Don't refresh if user is interacting with checkboxes
    if(document.querySelectorAll('.order-checkbox:checked').length > 0) return;

    fetch(`order_status.php?phone=${phone}`)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.getElementById('order-live-area').innerHTML;
            const liveArea = document.getElementById('order-live-area');

            // Only update if HTML changed (status updated)
            if(liveArea.innerHTML.trim() !== newContent.trim()) {
                liveArea.innerHTML = newContent;
                activeMaps = {}; // Clear map references to allow re-init
                startAllMaps();
            }
        });
}

window.onload = startAllMaps;
setInterval(refreshLiveArea, 5000); // Live update every 5 seconds
</script>
</body>
</html>