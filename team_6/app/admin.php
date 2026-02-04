<?php
// 1. セッションとデータベース接続
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db/connect.php';


// --- ここにイベントデータの準備を追加 ---
$events = [
    ['date' => '01-21', 'type' => 'event', 'title' => '新メニュー試食会'], 
    ['date' => '01-25', 'type' => 'birthday', 'name' => '佐藤店長'],
    ['date' => '02-01', 'type' => 'event', 'title' => '棚卸し実施日']
];

$today_md = date('m-d');
$is_birthday_today = false; // 紙吹雪用フラグ

foreach ($events as $e) {
    if ($e['date'] === $today_md && $e['type'] === 'birthday') {
        $is_birthday_today = true;
    }
}

// 2. 管理者ログイン処理
if (isset($_POST['admin_login'])) {
    $uid = trim($_POST['uid']);
    $upass = trim($_POST['upass']);
    $stmt = $db->prepare("SELECT * FROM staff WHERE user_id = ? LIMIT 1");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($upass, $user['password'])) {
        $_SESSION['admin_user_id'] = $user['user_id'];
        $_SESSION['admin_name'] = $user['name'];
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "IDまたはパスワードが正しくありません。";
    }
}

// 3. ログアウト処理
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$today = date('Y-m-d');
$is_logged_in = isset($_SESSION['admin_user_id']);

// 4. データ取得 (PostgreSQL互換クエリ)
$stmt = $db->prepare("SELECT m.name, SUM(s.quantity) AS total_orders, SUM(s.total_amount) AS total_amount FROM sales s JOIN menu_items m ON s.menu_item_id = m.id WHERE s.order_date::date = ? GROUP BY m.name");
$stmt->execute([$today]);
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sort sales data by total_amount descending to determine ranking
usort($sales, function($a, $b) {
    return $b['total_amount'] <=> $a['total_amount'];
});

// --- NEW GRAPH LOGIC (10:00 to 22:00) ---
$start_h = 10;
$end_h = 22;
$hourly_sales = array_fill($start_h, ($end_h - $start_h + 1), 0.0);
$hourly_orders = array_fill($start_h, ($end_h - $start_h + 1), 0);
$js_labels = [];

for ($i = $start_h; $i <= $end_h; $i++) {
    $js_labels[] = $i . "時";
}

// 1. Totals for the table (Keep this as is)
$grandTotalOrders = 0;
$grandTotalAmount = 0;
foreach($sales as $row) {
    $grandTotalOrders += (int)$row['total_orders'];
    $grandTotalAmount += (float)$row['total_amount'];
}

// 2. Fetch Hourly Data - Force cast to timestamp to find the hour
$stmt_graph = $db->prepare("
    SELECT
        EXTRACT(HOUR FROM order_date AT TIME ZONE 'UTC' AT TIME ZONE 'Asia/Tokyo') AS h,
        SUM(total_amount) AS amt,
        SUM(quantity) AS cnt
    FROM sales
    WHERE order_date::date = ?
    GROUP BY h
    ORDER BY h
");
$stmt_graph->execute([$today]);

while($g = $stmt_graph->fetch(PDO::FETCH_ASSOC)) {
    $h_val = (int)$g['h'];
    if (isset($hourly_sales[$h_val])) {
        // Use += here just in case there are multiple entries for the same hour
        $hourly_sales[$h_val] = (float)$g['amt'];
        $hourly_orders[$h_val] = (int)$g['cnt'];
    }
}
$js_sales = array_values($hourly_sales);
$js_orders = array_values($hourly_orders);
// ==========================

$sql = "SELECT sh.id AS shift_id, st.id AS staff_id, st.user_id, st.name, st.post, sh.shift_date, sh.shift_type, sh.shift_start, sh.shift_end, (SELECT sa.action_type FROM staff_attendance sa WHERE sa.staff_id = st.id AND sa.action_time::date = ? ORDER BY sa.action_time DESC LIMIT 1) AS last_action, (SELECT to_char(sa.action_time, 'HH24:MI:SS') FROM staff_attendance sa WHERE sa.staff_id = st.id AND sa.action_time::date = ? ORDER BY sa.action_time DESC LIMIT 1) AS last_action_time FROM staff_shift sh JOIN staff st ON sh.staff_id = st.id WHERE sh.shift_date = ? ORDER BY sh.shift_start";
$stmt = $db->prepare($sql);
$stmt->execute([$today, $today, $today]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ピザマッハ - 管理パネル</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root { --primary: #ff4b2b; --secondary: #ff416c; --dark: #333; --bg: #f0f2f5; }
    * {margin:0;padding:0;box-sizing:border-box;font-family:"Poppins","Hiragino Sans","Meiryo",sans-serif;}
    body {display:grid;grid-template-areas:"header header header""sidebar main rightbar";grid-template-columns:260px 1fr 300px;grid-template-rows:80px auto;height:100vh;background:var(--bg); color: var(--dark);}
    
    /* 固定されたヘッダーCSS */
    header {grid-area: header;background: linear-gradient(135deg, var(--primary), var(--secondary));color: white;display: flex;justify-content: space-between;align-items: center;padding: 0 25px;box-shadow: 0 2px 10px rgba(0,0,0,0.1);z-index: 1000;}
    .header-logo-area {display: flex;align-items: center;gap: 15px;}
    .logo-img {height: 55px; /* ヘッダーの高さに合わせて調整 */width: auto;border-radius: 8px;}
    .header-text h1 {font-size: 28px;font-weight: 900;letter-spacing: 1px;line-height: 1;margin-bottom: 4px;}
    .header-text .subtitle {font-size: 11px;opacity: 0.9;}
    .nav-btn {background: white; color: var(--primary); border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s; box-shadow: 0 4px 0 #d33216; position: relative; top: 0; margin-left: 10px;}
    .nav-btn:hover { top: -2px; box-shadow: 0 6px 0 #d33216; }
    .nav-btn:active { top: 2px; box-shadow: 0 2px 0 #d33216; }
    button[onclick="toggleAttendance()"].nav-btn { background: var(--dark); color: white; box-shadow: 0 4px 0 #000; }

    aside {grid-area:sidebar; background:#fff; border-right:1px solid #ddd; padding:15px; overflow-y:auto;}
    main {grid-area:main; padding:25px; background:var(--bg); overflow-y:auto;}
    .rightbar {grid-area:rightbar; background:#fff; border-left:1px solid #ddd; padding:15px; overflow-y:auto;}

    .card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 25px; border: 1px solid #eee; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .card-h { font-size: 16px; font-weight: bold; color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 8px; border-left: 4px solid var(--primary); padding-left: 10px;}

    .login-section { background: #fff5f2; padding: 15px; border-radius: 10px; margin-bottom: 15px; border: 1px solid #ffe0d4; }
    .login-section input { width: 100%; padding: 10px; margin-bottom: 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
    .login-btn { width: 100%; background: var(--primary); color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: bold; }

   /* Calendar Table Base */
#calendar-table { width: 100%; border-collapse: separate; border-spacing: 2px; text-align: center; font-size: 11px; table-layout: fixed; }

/* Weekday Headers - Added background to make white text visible and centered */
#calendar-table th { background: var(--primary); color: white; padding: 8px 0; text-align: center; font-weight: bold; width: 14.2%; border-radius: 4px; }

/* Date Cells */
#calendar-table td { padding: 10px 0; border-radius: 4px; transition: 0.2s; cursor: default; text-align: center; }

/* Hover Effect */
#calendar-table td:hover:not(:empty) { background: rgba(213, 214, 246, 1); }

/* Today Highlight */
.today { background: var(--primary) !important; color: white !important; font-weight: bold; border-radius: 50% !important; }

/* Sunday and Saturday Colors */
.sun { color: hsla(358, 83%, 51%, 0.95); } 
.sat { color: #0000ff; }
    /* Weather Card */
    .weather-box { text-align: center; padding: 10px; background: linear-gradient(to bottom, #fff, #f0f7ff); border-radius: 10px; }
    .weather-icon { font-size: 40px; margin: 5px 0; display: block; filter: drop-shadow(2px 2px 2px rgba(0,0,0,0.1)); }



/* イベントセクションのスタイル */
.event-item { 
    position: relative; 
    display: flex; 
    align-items: center; 
    gap: 10px; 
    padding: 10px; 
    margin-bottom: 8px; 
    border-radius: 8px; 
    font-size: 13px;
    transition: 0.3s;
}
.event-birthday { background: #fff0f3; border: 1px solid #ffccd5; color: #ff4b6b; }
.event-store { background: #f0f7ff; border: 1px solid #d0e7ff; color: #007bff; }

.delete-btn {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    color: #ff4b2b;
    font-weight: bold;
    cursor: pointer;
    opacity: 0; /* 普段は隠す */
    transition: 0.2s;
}
.event-item:hover .delete-btn { opacity: 1; } /* ホバーで表示 */


    /* Rightbar Components */
    .todo-list { list-style: none; }
    .todo-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 13px; }
    .todo-item input[type="checkbox"] { accent-color: var(--primary); cursor: pointer; }
    .action-link { font-size: 12px; color: var(--primary); text-decoration: none; font-weight: bold; display: block; margin-top: 10px; text-align: right; }
    .action-link:hover { text-decoration: underline; }

    ./* --- Optimized Table & Ranking CSS --- */
.locked-content { cursor: pointer; transition: 0.3s; }

table { 
    width: 100%; 
    border-collapse: separate; 
    border-spacing: 0; /* Use spacing 0 for clean borders */
    margin-bottom: 10px;
}

table th {
    background: var(--primary); 
    color: white; 
    padding: 12px 15px; 
    font-size: 13px; 
    text-align: left;
}

/* Rounded corners for the table header */
table th:first-child { border-top-left-radius: 8px; }
table th:last-child { border-top-right-radius: 8px; }

table td {
    padding: 12px 15px; 
    border-bottom: 1px solid #eee; 
    font-size: 14px;
    vertical-align: middle; /* Aligns icons and text perfectly */
}

/* Ranking Badge Styles */
.rank-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    font-weight: bold;
    font-size: 12px;
}
/* --- Ranking Icon Style --- */
.rank-display {
    font-size: 24px; /* アイコンを大きく */
    display: inline-block;
    width: 35px;    /* 幅を固定して文字の開始位置を揃える */
    text-align: center;
}

.rank-number {
    font-size: 16px;
    font-weight: bold;
    color: #888;
    font-family: 'Arial', sans-serif;
}

/* 1位の行だけ少し目立たせる（お好みで） */
.top-row {
    background-color: #fffdf0 !important;
}

/* 表の余白調整 */
table td {
    padding: 15px 12px; /* 上下の余白を広げて見やすく */
    vertical-align: middle;
}

.now-cell { font-weight: bold; color: var(--primary); }

/* Right-align numbers for better readability */
.num-align { text-align: right; font-family: 'Courier New', monospace; font-weight: bold; }

    #attendanceBox { position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:white; border-radius:15px; padding:30px; box-shadow:0 0 50px rgba(0,0,0,0.5); width:400px; z-index:9999; }
    .hidden { display: none !important; }
  </style>
</head>
<body>

<header>
  <div class="header-logo-area">
    <img src="assets/images/logo.png" alt="Logo" class="logo-img">
    <div class="header-text">
      <h1>ピザマッハ🍕</h1>
      <p class="subtitle">できたての美味しいピザをマッハでお届け！</p>
    </div>
  </div>
  <nav>
    <button onclick="toggleAttendance()" class="nav-btn">勤怠入力</button>
    <a href="staff_management.php" onclick="return checkAction(event)" class="nav-btn">マネージャー</a>
    <a href="kitchen.php" onclick="return checkAction(event)" class="nav-btn">注文管理</a>
    <a href="driver.php" onclick="return checkAction(event)" class="nav-btn">配達状況</a>
  </nav>
</header>

<aside>
  <div class="login-section">
    <?php if(!$is_logged_in): ?>
      <h4 style="color:var(--primary); margin-bottom:10px; font-size: 14px;">🔐 管理者ログイン</h4>
      <form method="POST">
        <input type="text" name="uid" id="uid_focus" placeholder="ユーザーID" required>
        <input type="password" name="upass" placeholder="パスワード" required>
        <button type="submit" name="admin_login" class="login-btn">ログイン</button>
      </form>
      <?php if(isset($login_error)) echo "<p style='color:red; font-size:10px; margin-top:5px;'>$login_error</p>"; ?>
    <?php else: ?>
      <div style="text-align: center; font-size: 13px;">
        <p>管理者: <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong></p>
        <a href="?logout=1" style="color:var(--primary); text-decoration: none; font-weight: bold;">ログアウト</a>
        <p id="timer-display" style="font-size:10px; color:red; margin-top:5px;">自動切断まで: 60秒</p>
      </div>
    <?php endif; ?>
  </div>

  <div class="card" style="text-align: center; padding: 15px;">
    <div id="current-time" style="font-size: 26px; font-weight: bold; color: var(--primary);">00:00:00</div>
    <div style="font-size: 12px; color: #666;"><?= date('Y年m月d日') ?></div>
  </div>

  <div class="card" style="padding: 15px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <button onclick="changeMonth(-1)" style="border:none; background:none; color:var(--primary); cursor:pointer; font-size: 18px;">◀</button>
        <span id="month-year" style="font-weight:bold; color:var(--primary); font-size: 14px;"></span>
        <button onclick="changeMonth(1)" style="border:none; background:none; color:var(--primary); cursor:pointer; font-size: 18px;">▶</button>
    </div>
    <table id="calendar-table"></table>
  </div>

  <div class="card weather-box">
    <div style="font-size: 12px; font-weight: bold; color: #555;">今日のお天気</div>
    <span id="w-icon" class="weather-icon">--</span>
    <div id="w-desc" style="font-size: 14px; font-weight: bold;">読み込み中...</div>
    <div id="w-temp" style="font-size: 11px; color: #888;">--°C</div>
</div>
</aside>

<main>
 <div class="card locked-content" onclick="checkAction(event)">
    <div class="card-h">📊 本日の売上ランキング</div>
    <table>
        <thead>
            <tr>
                <th style="width: 70px; text-align: center;">ランク</th>
                <th>メニュー</th>
                <th style="text-align: right;">注文数</th>
                <th style="text-align: right;">売上金額</th>
            </tr>
        </thead>
        <tbody>
    <?php 
    $rank = 1;
    foreach($sales as $row): 
        // アイコンの判定
        $display = "";
        if ($rank === 1) $display = "👑";
        else if ($rank === 2) $display = "🥈";
        else if ($rank === 3) $display = "🥉";
        else $display = '<span class="rank-number">' . $rank . '</span>';
        
        $rowClass = ($rank === 1) ? 'class="top-row"' : '';
    ?>
    <tr <?= $rowClass ?>>
        <td style="text-align: center;">
            <span class="rank-display"><?= $display ?></span>
        </td>
        <td style="font-weight: bold; font-size: 15px;">
            <?= htmlspecialchars($row['name']) ?>
        </td>
        <td class="num-align">
            <?= number_format($row['total_orders']) ?> <small>件</small>
        </td>
        <td class="num-align" style="font-size: 1.1em;">
            ¥<?= number_format($row['total_amount']) ?>
        </td>
    </tr>
    <?php 
    $rank++;
    endforeach; 
    ?>
</tbody>
        <tfoot>
            <tr style="background: #fff5f2; font-weight: bold;">
                <td colspan="2" style="text-align: right; border-bottom: none; border-bottom-left-radius: 8px;">合計</td>
                <td class="num-align" style="border-bottom: none;"><?= $grandTotalOrders ?> 件</td>
                <td class="num-align" style="color: var(--primary); font-size: 1.2em; border-bottom: none; border-bottom-right-radius: 8px;">
                    ¥<?= number_format($grandTotalAmount) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</div>

  <div class="card locked-content" onclick="checkAction(event)">
    <div class="card-h">📈 売上グラフ（トレンド）</div>
    <div style="height: 280px;"><canvas id="salesHourlyChart"></canvas></div>
  </div>

  <div class="card locked-content" onclick="checkAction(event)">
    <div class="card-h">🕒 出勤・シフト状況</div>
    <table>
      <thead>
        <tr><th>氏名</th><th>役職</th><th>時間</th><th>ステータス</th></tr>
      </thead>
      <tbody>
        <?php foreach($shifts as $shift): ?>
        <tr>
          <td><?= htmlspecialchars($shift['name']) ?></td>
          <td><?= htmlspecialchars($shift['post']) ?></td>
          <td><?= date('H:i', strtotime($shift['shift_start'])) ?>-<?= date('H:i', strtotime($shift['shift_end'])) ?></td>
          <td class="now-cell"><?= !empty($shift['last_action']) ? strtoupper($shift['last_action']) : "準備中" ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>


 <div class="card locked-content" onclick="checkAction(event)">
    <div class="card-h">🎉 重要イベント & 記念日 &  メモ</div>
    
    <div id="event-list-container" class="event-list">
        <div class="event-item event-store">
            <span class="event-icon">📢</span>
            <div>
                <span class="event-date">01-21</span>
                <strong>新メニュー試食会</strong>
            </div>
            <button class="delete-btn" onclick="removeEvent(this)">×</button>
        </div>
    </div>

    <div id="add-event-form" class="hidden" style="margin-top:15px; border-top:1px solid #eee; padding-top:10px;">
        <input type="date" id="new-event-date" style="width:100%; margin-bottom:5px; padding:5px;">
        <input type="text" id="new-event-title" placeholder="イベント名または名前" style="width:100%; margin-bottom:5px; padding:5px;">
        <select id="new-event-type" style="width:100%; margin-bottom:10px; padding:5px;">
            <option value="event">📢 一般イベント</option>
            <option value="birthday">🎂 誕生日</option>
        </select>
        <button onclick="saveEvent()" style="width:100%; background:var(--primary); color:white; border:none; padding:8px; border-radius:5px; cursor:pointer;">保存</button>
    </div>

    <a href="javascript:void(0)" onclick="showEventForm()" id="add-event-link" class="action-link">+ イベントを登録</a>
</div>
</main>

<div class="rightbar">
    <div class="card locked-content" onclick="checkAction(event)">
        <div class="card-h">📝 本日のタスク (ToDo)</div>
        <ul class="todo-list">
            <li class="todo-item"><input type="checkbox"> 清掃点検表の記入</li>
            <li class="todo-item"><input type="checkbox" checked> 釣り銭の準備</li>
            <li class="todo-item"><input type="checkbox"> ピザ生地の仕込み確認</li>
        </ul>
        <a href="#" class="action-link">+ タスクを追加</a>
    </div>

    <div class="card locked-content" onclick="checkAction(event)">
        <div class="card-h">📦 在庫・発注管理</div>
        <div style="font-size: 13px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span>チーズ在庫</span><span style="color:red; font-weight:bold;">残り 2kg</span>
            </div>
            <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                <span>小麦粉在庫</span><span>残り 15kg</span>
            </div>
            <hr style="border:none; border-top:1px solid #eee; margin:10px 0;">
            <a href="#" class="action-link">在庫チェックを行う</a>
            <a href="#" class="action-link" style="color:#2ecc71;">🍕 業者へ発注を出す</a>
        </div>
    </div>

    <div class="card locked-content" onclick="checkAction(event)">
        <div class="card-h">🚛 本日の入荷予定</div>
        <div style="font-size: 12px; background: #f9f9f9; padding: 10px; border-radius: 5px;">
            <div style="margin-bottom:5px;">🕒 14:00 - 野菜配送 (完了)</div>
            <div>🕒 17:30 - ドリンク類補充</div>
        </div>
        <a href="#" class="action-link">入荷完了を報告</a>
    </div>
</div>

<div id="attendanceBox" class="hidden">
    <h2 style="text-align:center; color:var(--primary); margin-bottom:20px;">勤怠入力</h2>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:15px;">
        <button class="action-select-btn" onclick="setAtAction(1)" id="at1" style="padding:12px; border:1px solid #ddd; border-radius:5px; cursor:pointer; background:white;">出勤</button>
        <button class="action-select-btn" onclick="setAtAction(2)" id="at2" style="padding:12px; border:1px solid #ddd; border-radius:5px; cursor:pointer; background:white;">退勤</button>
        <button class="action-select-btn" onclick="setAtAction(3)" id="at3" style="padding:12px; border:1px solid #ddd; border-radius:5px; cursor:pointer; background:white;">休憩入</button>
        <button class="action-select-btn" onclick="setAtAction(4)" id="at4" style="padding:12px; border:1px solid #ddd; border-radius:5px; cursor:pointer; background:white;">休憩戻</button>
    </div>
    <input type="hidden" id="atAction" value="">
    <input id="atUserId" type="text" placeholder="スタッフID" style="width:100%; padding:12px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
    <input id="atPassword" type="password" placeholder="パスワード" style="width:100%; padding:12px; margin-bottom:20px; border:1px solid #ddd; border-radius:5px;">
    <div style="display:flex; gap:10px;">
        <button onclick="submitAttendance()" style="flex:2; background:var(--primary); color:white; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;">打刻確定</button>
        <button onclick="toggleAttendance()" style="flex:1; background:#999; color:white; border:none; padding:12px; border-radius:8px; cursor:pointer;">閉じる</button>
    </div>
</div>

<script>
// --- Chart.js ---
const ctx = document.getElementById('salesHourlyChart').getContext('2d');
// Ensure salesChart is defined in the global scope so setInterval can find it
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($js_labels) ?>,
        datasets: [{
            label: '売上 (¥)',
            data: <?= json_encode($js_sales) ?>,
            borderColor: '#ff4b2b',
            backgroundColor: 'rgba(255, 75, 43, 0.2)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            yAxisID: 'y'
        }, {
            label: '件数',
            data: <?= json_encode($js_orders) ?>,
            borderColor: '#333',
            borderDash: [5, 5],
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { 
                beginAtZero: true, 
                ticks: { callback: (v) => '¥' + v.toLocaleString() } 
            },
            y1: { 
                position: 'right', 
                beginAtZero: true, 
                grid: { drawOnChartArea: false },
                ticks: { stepSize: 1 }
            }
        }
    }
});

// Automatic Refresh (updates the graph every 5 seconds without reloading)
setInterval(async () => {
    try {
        const res = await fetch('api/hourly_sales.php');
        if (res.ok) {
            const data = await res.json();
            // Update data
            salesChart.data.datasets[0].data = data.sales;
            salesChart.data.datasets[1].data = data.orders;
            
            // Re-render with a smooth animation
            salesChart.update('active'); 
        }
    } catch (e) {
        console.error("Graph sync failed:", e);
    }
}, 5000); // 5000ms = 5 seconds



// --- Auth Guard ---
function checkAction(e) {
    if (<?= $is_logged_in ? 'false' : 'true' ?>) {
        e.preventDefault();
        alert("🔒 アクセス制限\nこの機能を使用するにはログインが必要です。");
        document.getElementById('uid_focus').focus();
        return false;
    }
    return true;
}

// --- Clock ---
function updateTime() { document.getElementById("current-time").textContent = new Date().toLocaleTimeString('ja-JP', {hour12:false}); }
setInterval(updateTime, 1000); updateTime();

// --- Calendar Logic (Fixed Sunday start) ---
let curDate = new Date();
function renderCalendar(date) {
    const y = date.getFullYear(), m = date.getMonth();
    document.getElementById("month-year").textContent = `${y}年 ${m + 1}月`;
    const firstDay = new Date(y, m, 1).getDay();
    const lastDate = new Date(y, m + 1, 0).getDate();
    const today = new Date();
    
    let html = "<tr><th class='sun'>日</th><th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th class='sat'>土</th></tr><tr>";
    for(let i=0; i < firstDay; i++) html += "<td></td>";
    for(let d=1; d <= lastDate; d++) {
        const cls = (d === today.getDate() && m === today.getMonth() && y === today.getFullYear()) ? "today" : "";
        const dayOfWeek = (firstDay + d - 1) % 7;
        let dayCls = "";
        if(dayOfWeek === 0) dayCls = "sun"; else if(dayOfWeek === 6) dayCls = "sat";
        
        html += `<td class="${cls} ${dayCls}">${d}</td>`;
        if((d + firstDay) % 7 === 0) html += "</tr><tr>";
    }
    document.getElementById("calendar-table").innerHTML = html + "</tr>";
}
function changeMonth(n) { curDate.setMonth(curDate.getMonth() + n); renderCalendar(curDate); }
renderCalendar(curDate);

async function updateWeather() {
    try {
        const res = await fetch('https://www.jma.go.jp/bosai/forecast/data/forecast/140000.json');
        if (!res.ok) throw new Error('Network response was not ok');
        const data = await res.json();
        
        // 1. 天気の表示 (横浜エリアは通常 index 0)
        const areaData = data[0].timeSeries[0].areas[0];
        const weatherName = areaData.weathers[0];
        document.getElementById('w-desc').textContent = weatherName;
        
        // 2. アイコン判定 (優先順位: 雪 > 雨 > 曇 > 晴)
        let icon = "🌡️";
        if (weatherName.includes("雪")) icon = "❄️";
        else if (weatherName.includes("雨")) icon = "☔";
        else if (weatherName.includes("曇")) icon = "☁️";
        else if (weatherName.includes("晴")) icon = "☀️";
        document.getElementById('w-icon').textContent = icon;

        // 3. 気温の取得 (より安全なパスで探す)
        let tempValue = "";
        try {
            // 気象庁データから現在の気温に近いものを探す
            // data[0].timeSeries[2] もしくは data[1].timeSeries[1] を参照
            const tempSeries = data[0].timeSeries.find(s => s.areas[0].temps);
            if (tempSeries) {
                tempValue = tempSeries.areas[0].temps[0];
            }
        } catch (e) {
            tempValue = "--";
        }

        document.getElementById('w-temp').textContent = tempValue !== "--" ? `横浜: 約 ${tempValue}°C` : "横浜エリア";

    } catch (error) {
        console.error("Weather Error:", error);
        // エラー時はデフォルト値を表示
        document.getElementById('w-icon').textContent = "☁️";
        document.getElementById('w-desc').textContent = "横浜: 曇り時々晴れ";
        document.getElementById('w-temp').textContent = "22°C";
    }
}
updateWeather();


// --- Attendance ---
function toggleAttendance() { document.getElementById('attendanceBox').classList.toggle('hidden'); }
function setAtAction(n) {
    document.getElementById('atAction').value = n;
    document.querySelectorAll('.action-select-btn').forEach(b => b.style.background = 'white');
    document.getElementById('at' + n).style.background = '#fff5f2';
    document.getElementById('at' + n).style.borderColor = 'var(--primary)';
}
async function submitAttendance() {
    const map = {'1':'login', '2':'logout', '3':'rest_start', '4':'rest_finish'};
    const fd = new FormData();
    fd.append('user_id', document.getElementById('atUserId').value);
    fd.append('password', document.getElementById('atPassword').value);
    fd.append('action', map[document.getElementById('atAction').value]);
    const res = await fetch('staff_attendance.php', { method: 'POST', body: fd });
    const data = await res.json();
    alert(data.msg); if(data.status === 1) location.reload();
}
// --- Events JS (FIXED) ---
function showEventForm() {
    document.getElementById('add-event-form').classList.remove('hidden');
    document.getElementById('add-event-link').classList.add('hidden');
}

function removeEvent(btn) {
    if (confirm("このイベントを削除しますか？")) { btn.parentElement.remove(); }
}

function saveEvent() {
    const dateVal = document.getElementById('new-event-date').value;
    const titleVal = document.getElementById('new-event-title').value;
    const typeVal = document.getElementById('new-event-type').value;

    if (!dateVal || !titleVal) return alert("入力してください");

    const mmdd = dateVal.split('-').slice(1).join('-');
    const container = document.getElementById('event-list-container');
    const div = document.createElement('div');
    const isBirthday = typeVal === 'birthday';
    
    div.className = `event-item ${isBirthday ? 'event-birthday' : 'event-store'}`;
    div.innerHTML = `
        <span class="event-icon">${isBirthday ? '🎂' : '📢'}</span>
        <div><span class="event-date" style="font-size:10px; opacity:0.7; display:block;">${mmdd}</span><strong>${titleVal}</strong> ${isBirthday ? 'さん 誕おめ！' : ''}</div>
        <button class="delete-btn" onclick="removeEvent(this)">×</button>
    `;

    container.appendChild(div);
    document.getElementById('add-event-form').classList.add('hidden');
    document.getElementById('add-event-link').classList.remove('hidden');

    // 紙吹雪を飛ばす (お祝い!)
    if(isBirthday) {
        confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
    }
}

// ページ読み込み時に誕生日チェック
window.onload = () => {
    if (<?= $is_birthday_today ? 'true' : 'false' ?>) {
        confetti({ particleCount: 150, spread: 100, origin: { y: 0.6 } });
    }
};

</script>
</body>
</html>