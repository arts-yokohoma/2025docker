<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'db/connect.php';

if (isset($_POST['cart_data'])) { $_SESSION['cart'] = json_decode($_POST['cart_data'], true); }
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo "<p>カートが空です！<a href='index.php'>メニューへ戻る</a></p>";
    exit;
}

// 1. Calculate total pizzas
$cart_pizza_count = 0;
foreach ($cart as $item) {
    $cart_pizza_count += (int)$item['qty'];
}

date_default_timezone_set('Asia/Tokyo');
$open_time = strtotime('10:00'); $close_time = strtotime('22:00'); $slot_interval = 30;
$date_today = date('Y-m-d'); $current_time = time();

// 2. ENHANCED Capacity Logic (FIXED FOR NEW RULE)
function get_slot_status($db, $date, $time, $current_cart_qty, $slot_duration = 30, $min_per_pizza = 5) {
    $db_time = date("H:i:00", strtotime($time)); 

    // Check Staff
    $stmt = $db->prepare("SELECT COUNT(*) FROM staff_shift WHERE shift_date = ?::date AND shift_start <= ?::time AND shift_end > ?::time");
    $stmt->execute([$date, $db_time, $db_time]); 
    $staff_count = (int)$stmt->fetchColumn();

    // ✅ FIXED: Only count 'Confirmed' orders. 
    // Pending orders (people typing OTP) will NOT block the slot for others.
   $stmt = $db->prepare("
    SELECT COALESCE(SUM(oi.quantity), 0) 
    FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.delivery_date = ?::date 
      AND o.delivery_time = ?::time 
      AND o.status IN ('Pending', 'Cooking', 'Ready', 'Out for Delivery', 'Delivered')
");
    $stmt->execute([$date, $db_time]); 
    $booked_qty = (int)$stmt->fetchColumn();

    $max_capacity = $staff_count * ($slot_duration / $min_per_pizza);
    $remaining = $max_capacity - $booked_qty;

    if ($staff_count === 0) return ['class'=>'full', 'text'=>'休止', 'msg'=>'受付停止中', 'rem'=>0];
    if ($remaining <= 0) return ['class'=>'full', 'text'=>'満席', 'msg'=>'予約いっぱいです', 'rem'=>0];
    
    // If user's specific cart is too big for the remaining space
    if ($current_cart_qty > $remaining) {
        return ['class'=>'full', 'text'=>'容量不足', 'msg'=>"あと{$remaining}枚まで可", 'rem'=>$remaining];
    }

    if ($booked_qty >= ($max_capacity * 0.7)) {
        return ['class'=>'busy', 'text'=>'混雑', 'msg'=>'残りわずか', 'rem'=>$remaining];
    }

    return ['class'=>'available', 'text'=>'空き', 'msg'=>'予約可能です', 'rem'=>$remaining];
}

function generateOTP($length = 4){ return str_pad(rand(0, 9999), $length, '0', STR_PAD_LEFT); }

// 3. Final Order Processing (Server-side re-check)
if (isset($_POST['confirm_order'])) {
    $delivery_time = $_POST['delivery_time'];
    
    // Re-verify capacity before showing OTP
    $check = get_slot_status($db, $date_today, $delivery_time, $cart_pizza_count);
    if ($check['class'] === 'full') {
        echo "<script>alert('申し訳ありません。入力中に予約がいっぱいになりました。別の時間を選択してください。'); window.location.href='order_confirm.php';</script>";
        exit;
    }

    $phone = $_POST['phone']; $first_name = $_POST['first_name']; $last_name = $_POST['last_name']; $address = $_POST['address'];
    
    // Check for customer
    $stmt = $db->prepare("SELECT id FROM customers WHERE phone = ?"); 
    $stmt->execute([$phone]); 
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        $stmt = $db->prepare("INSERT INTO customers (phone, first_name, last_name, address) VALUES (?, ?, ?, ?) RETURNING id");
        $stmt->execute([$phone, $first_name, $last_name, $address]); 
        $customer_id = $stmt->fetchColumn();
    } else { 
        $customer_id = $customer['id']; 
    }
    
    $otp = generateOTP();
    // Save to session only (No DB write yet)
    $_SESSION['order_data'] = [
        'customer_id' => $customer_id, 
        'cart' => $cart, 
        'otp' => $otp, 
        'delivery_time' => $delivery_time, 
        'delivery_date' => $date_today
    ];
    
    // Calculate total for display
    $total_price = 0;
    foreach($cart as $item){ $total_price += $item['price'] * $item['qty']; }
    $_SESSION['order_data']['total_price'] = $total_price;

    // Show OTP screen
    echo "<div style='text-align:center; padding:40px; background:#fdfaf0; min-height:100vh; font-family:sans-serif;'>
            <h2 style='color:#b45f04;'>確認コード入力</h2>
            <p>ご本人確認のため、表示されたコードを入力してください。</p>
            <p style='font-size:3rem; color:#d35400; margin:20px 0;'><strong>$otp</strong></p>
            <form method='post' action='order_process.php'>
                <input type='text' name='otp_input' autocomplete='off' placeholder='4桁のコード' required style='font-size:1.5rem; padding:15px; text-align:center; border-radius:8px; border:2px solid #e6b422; width:200px;'><br><br>
                <button type='submit' style='padding:15px 40px; background:#e6b422; color:white; border:none; border-radius:50px; font-weight:bold; font-size:1.2rem; cursor:pointer;'>注文を確定する</button>
            </form>
            <p style='margin-top:20px; color:#666;'>※確定ボタンを押すまで予約は確保されません。</p>
          </div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ご注文確認 - ピザマッハ</title>
<style>
  :root { --gold: #e6b422; --cream: #fff8e7; --text: #333; --green: #2e7d32; --amber: #ffa000; --red: #c62828; }
  body { background-color: #fdfaf0; font-family: "Yu Gothic", sans-serif; color: var(--text); margin: 0; padding: 15px; }
  .page-wrapper { max-width: 750px; margin: 0 auto; }
  .step-card { background: white; border: 2px solid var(--gold); border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
  .step-title { font-size: 1.3rem; color: #b45f04; margin-bottom: 15px; border-bottom: 2px solid var(--cream); padding-bottom: 8px; font-weight: bold; }
  
  .time-slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 8px; }
  .slot { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 90px; border-radius: 8px; padding: 5px; color: #fff; cursor: pointer; transition: 0.2s; border: 3px solid transparent; text-align: center; }
  .slot.available { background: var(--green); } 
  .slot.busy { background: var(--amber); color: #000; } 
  .slot.full { background: var(--red); opacity: 0.6; cursor: not-allowed; }
  .slot input { display: none; }
  .slot:has(input:checked) { border-color: #000; transform: scale(1.03); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }

  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .wide { grid-column: span 2; }
  label { display: block; font-weight: bold; font-size: 0.9rem; margin-bottom: 4px; color: #b45f04; }
  input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box; font-size: 1.1rem; }
  
  #customer-section { opacity: 0.3; pointer-events: none; transition: 0.5s; filter: grayscale(1); }
  #customer-section.active { opacity: 1; pointer-events: auto; filter: grayscale(0); }
  
  .back-btn {
    display: inline-block;
    background: #f8f9fa; /* Lighter background so it doesn't compete with the Gold button */
    color: #b45f04;
    text-decoration: none;
    padding: 12px 30px;
    border: 2px solid #e6b422;
    border-radius: 50px;
    font-weight: bold;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.back-btn:hover {
    background: #e6b422;
    color: white;
    transform: translateY(-2px);
}
  .submit-btn { width: 100%; background: var(--gold); color: white; padding: 12px 30px; border: none; border-radius: 10px; font-size: 1.2rem; font-weight: bold; cursor: pointer; margin-top: 15px; }
</style>
</head>
<body>
<div class="page-wrapper">
  <header><h1 style="text-align:center; color:#b45f04;">ご注文確認</h1></header>

  <form method="post" id="mainOrderForm">
    <div class="step-card">
        <div class="step-title">1. ご注文内容</div>
        <?php $total = 0; foreach ($cart as $item) { 
            $item_total = $item['price'] * $item['qty']; $total += $item_total;
            echo "<div style='display:flex; justify-content:space-between; padding:5px 0;'><span>{$item['name']} × {$item['qty']}</span><strong>¥".number_format($item_total)."</strong></div>";
        } ?>
        <div style="text-align:right; font-size:1.4rem; color:#d35400; font-weight:bold; margin-top:10px;">合計：¥<?php echo number_format($total); ?></div>
    </div>

    <div class="step-card">
      <div class="step-title">2. お届け時間</div>
      <p style="font-size: 0.95rem; color: #b45f04; background: #fff8e7; padding: 10px; border-radius: 8px; border-left: 5px solid var(--gold); margin-bottom:15px;">
        <strong>ご注文合計枚数: <?php echo $cart_pizza_count; ?> 枚</strong><br>
        <span style="font-size:0.8rem;">※枚数制限により選択できない時間帯があります。</span>
      </p>

      <div class="time-slot-grid">
        <?php 
        for ($t = $open_time; $t < $close_time; $t += $slot_interval * 60) {
            if ($t < $current_time) continue;
            $time_label = date('H:i', $t); 
            $slot = get_slot_status($db, $date_today, $time_label, $cart_pizza_count);
            $isDisabled = ($slot['class'] == 'full') ? 'disabled' : '';
            
            echo "
            <label class='slot {$slot['class']}'>
                <input type='radio' name='delivery_time' value='{$time_label}' $isDisabled required>
                <span style='font-weight:bold; font-size:1.15rem;'>{$time_label}</span>
                <span style='font-size:0.85rem; font-weight:bold;'>{$slot['text']}</span>
                <span style='font-size:0.75rem; line-height:1.2; margin-top:2px;'>{$slot['msg']}</span>
                <span style='font-size:0.65rem; border-top:1px solid rgba(255,255,255,0.3); margin-top:5px; padding-top:2px;'>残り {$slot['rem']} 枚</span>
            </label>";
        } 
        ?>
      </div>

      <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">ご希望の時間が空いていない場合は、メニューに戻って枚数を調整してください。</p>
        <a href="index.php" class="back-btn">メニューに戻る</a>
    </div>

    </div>

    <div class="step-card" id="customer-section">
      <div class="step-title">3. お届け先情報</div>
      <div class="form-grid">
        <div class="wide"><label>携帯電話番号</label><input type="text" name="phone" required></div>
        <div><label>姓</label><input type="text" name="last_name" required></div>
        <div><label>名</label><input type="text" name="first_name" required></div>
        
        <div class="wide">
          <label>郵便番号 (7桁・ハイフンなし)</label>
          <input type="text" id="zipcode" name="zipcode" maxlength="7" placeholder="1000001" required>
          <div id="zip-status" style="font-size:0.8rem; color:#d35400; margin-top:4px;"></div>
        </div>

        <div><label>都道府県</label><input type="text" id="pref" name="pref" required></div>
        <div><label>市区町村</label><input type="text" id="city" name="city" required></div>
        <div class="wide"><label>町域・番地</label><input type="text" id="addr" name="addr" required></div>
        
        <textarea id="address" name="address" hidden></textarea>
        <button type="submit" name="confirm_order" class="submit-btn">確認コードを発行する</button>
      </div>
    </div>
  </form>
</div>

<script src="assets/js/zipcode.js"></script>
<script>
document.querySelectorAll('input[name="delivery_time"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (!this.disabled) {
            const section = document.getElementById('customer-section');
            section.classList.add('active');
            section.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

document.getElementById('mainOrderForm').addEventListener('submit', function(e) {
    const pref = document.getElementById('pref').value;
    const city = document.getElementById('city').value;
    const addr = document.getElementById('addr').value;
    if(!pref || !city) {
        alert("郵便番号から住所を確定させてください。");
        e.preventDefault(); return false;
    }
    document.getElementById('address').value = pref + city + addr;
});

// Auto-refresh the page every 60 seconds so users see updated capacity
setTimeout(function() {
    if (!document.querySelector('#customer-section.active')) {
        window.location.reload();
    }
}, 60000);
</script>
</body>
</html>