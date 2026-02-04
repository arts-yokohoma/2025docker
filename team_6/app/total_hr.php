<?php
include 'db/connect.php';

$records = [];
$totalMonthHours = 0;
$userName = '';
$userId = $_POST['uid'] ?? '';

// 1. Month Navigation Logic (Always exists in background)
$selectedMonth = $_POST['month_filter'] ?? date('Y-m');
$prevMonth = date('Y-m', strtotime($selectedMonth . " -1 month"));
$nextMonth = date('Y-m', strtotime($selectedMonth . " +1 month"));

// 2. Data Fetching (Triggered by ID entry OR month change)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($userId)) {
    $userId = trim($userId);

    // Get Staff Name
    $stmt = $db->prepare("SELECT name FROM staff WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userName = $stmt->fetchColumn() ?: '不明';

    // Only get data if name was found
    if ($userName !== '不明') {
        $stmt = $db->prepare("
            SELECT 
                login_time, 
                logout_time, 
                rest_time, 
                ROUND(total_hours::numeric, 2) as total_hours 
            FROM work_logs 
            WHERE user_id = ? 
            AND TO_CHAR(login_time, 'YYYY-MM') = ?
            ORDER BY login_time DESC
        ");
        $stmt->execute([$userId, $selectedMonth]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($records as $r) {
            $totalMonthHours += floatval($r['total_hours']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>総労働時間 - PIZZA PLANET</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins','Hiragino Sans',sans-serif;}
  body{display:flex;height:100vh;background:#f0f2f5;}

  .sidebar{
      width:240px; background: linear-gradient(180deg, #ff4b2b, #ff416c);
      color:white; padding:25px; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
  }
  .sidebar h2{margin-bottom:30px; font-size: 20px; text-align:center; letter-spacing: 1px;}
  .sidebar ul{list-style:none;}
  .sidebar ul li{margin:15px 0;}
  .sidebar ul li a{
      color:white; text-decoration:none; display:block; padding:12px; 
      border-radius:8px; transition:0.3s; background: rgba(255,255,255,0.1);
  }
  .sidebar ul li a:hover{background:rgba(255,255,255,0.2); transform: translateX(5px);}
  
  .main{flex:1; padding:40px; overflow-y:auto;}
  h1{color:#333; margin-bottom:30px; font-size: 28px; border-left: 5px solid #ff4b2b; padding-left: 15px;}

  #userInputBox{
      background: white; padding: 25px; border-radius: 12px; 
      box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;
      display: flex; align-items: center; gap: 15px;
  }
  input[type="text"]{ padding:12px; width:200px; border:1px solid #ddd; border-radius:8px; outline: none; }
  
  .nav-btn {
      background: #f0f2f5; border: 1px solid #ddd; padding: 10px 15px;
      border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.2s;
  }
  .nav-btn:hover { background: #e0e0e0; }

  button[type="submit"] {
      background: #333; color: white; border: none; padding: 12px 25px; border-radius: 8px;
      font-weight: bold; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 0 #000;
      position: relative; top: 0;
  }
  button[type="submit"]:hover { top: -2px; box-shadow: 0 6px 0 #000; }
  button[type="submit"]:active { top: 2px; box-shadow: 0 2px 0 #000; }

  .info-summary {
      background: #fff; border-left: 5px solid #ff4b2b; padding: 20px;
      border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      display: flex; justify-content: space-between; align-items: center;
  }
  .info-summary b { color: #ff4b2b; font-size: 1.2em; }

  table{width:100%; border-collapse:collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);}
  th,td{padding:15px; text-align:center; border-bottom: 1px solid #eee;}
  th{background:#ff4b2b; color:white; font-weight: 500;}
  tr:hover { background: #fffaf9; }

  .home-link { margin-top: 30px; display: inline-block; color: #ff4b2b; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
  <div class="sidebar">
    <h2>管理パネル</h2>
    <ul>
      <li><a href="menu_admin.php">🍔 メニュー変更</a></li>
      <li><a href="staff_management.php">👥 スタッフ管理</a></li>
      <li><a href="shift.php">📅 シフト管理</a></li>
      <li><a href="total_hr.php" style="background:rgba(255,255,255,0.3);">⏱ 総労働時間</a></li>
    </ul>
    <div style="text-align:center; margin-top:40px;">
      <a href="admin.php" style="background:#fff; color:#ff4b2b; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold; box-shadow: 0 4px 0 #ddd;">⬅ 戻る</a>
    </div>
  </div>

  <div class="main">
    <h1>⏱ 総労働時間照会</h1>
    
    <form method="POST" id="userInputBox">
      <label style="font-weight:bold; color:#555;">スタッフID:</label>
      <input type="text" name="uid" placeholder="例: staff01" value="<?php echo htmlspecialchars($userId); ?>" required>
      
      <input type="hidden" name="month_filter" id="month_filter" value="<?php echo $selectedMonth; ?>">
      
      <button type="submit">データ抽出</button>

      <?php if ($userId): ?>
      <div style="display:flex; align-items:center; gap:10px; margin-left:auto; border-left:1px solid #ddd; padding-left:20px;">
        <button type="button" class="nav-btn" onclick="changeMonth('<?php echo $prevMonth; ?>')">&lt;</button>
        <b style="font-size:1rem; color:#333; min-width:90px; text-align:center;">
            <?php echo date('Y/m', strtotime($selectedMonth)); ?>
        </b>
        <button type="button" class="nav-btn" onclick="changeMonth('<?php echo $nextMonth; ?>')">&gt;</button>
      </div>
      <?php endif; ?>
    </form>

    <?php if ($userId && $userName !== '不明'): ?>
      <div class="info-summary">
        <div>👤 名前: <b><?php echo htmlspecialchars($userName); ?></b></div>
        <div>🆔 ID: <b><?php echo htmlspecialchars($userId); ?></b></div>
        <div>🗓 <b><?php echo htmlspecialchars($selectedMonth); ?></b> の合計: <b><?php echo number_format($totalMonthHours, 2); ?> 時間</b></div>
      </div>

      <?php if (!empty($records)): ?>
      <table>
        <thead>
          <tr>
            <th>ログイン時刻</th>
            <th>ログアウト時刻</th>
            <th>休憩 (分)</th>
            <th>実働時間 (hr)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars(date('Y/m/d g:i A', strtotime($r['login_time']))); ?></td>
              <td><?php echo htmlspecialchars($r['logout_time'] ? date('Y/m/d g:i A', strtotime($r['logout_time'])) : '---'); ?></td>
              <td><?php echo htmlspecialchars($r['rest_time']); ?> 分</td>
              <td style="font-weight:bold; color:#ff4b2b;"><?php echo htmlspecialchars($r['total_hours']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div style="background:#fff2f0; color:#ff4b2b; padding:20px; border-radius:8px; border:1px solid #ffccc7;">
          ⚠ <b><?php echo htmlspecialchars($selectedMonth); ?></b> の勤務記録が見つかりませんでした。
        </div>
      <?php endif; ?>

    <?php elseif ($userId): ?>
      <div style="background:#fff2f0; color:#ff4b2b; padding:20px; border-radius:8px; border:1px solid #ffccc7;">
        ⚠ ユーザーID <b>"<?php echo htmlspecialchars($userId); ?>"</b> は存在しません。
      </div>
    <?php endif; ?>

    <a href="admin.php" class="home-link">← ダッシュボードへ戻る</a>
  </div>

  <script>
    function changeMonth(newMonth) {
        document.getElementById('month_filter').value = newMonth;
        document.getElementById('userInputBox').submit();
    }
  </script>
</body>
</html>