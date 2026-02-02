<?php
require_once __DIR__ . '/auth.php';
requireRoles(['admin', 'manager', 'driver', 'kitchen']); // Require specific roles
require_once __DIR__ . '/../config/db.php';

// Create shifts table if not exists
$mysqli->query("
CREATE TABLE IF NOT EXISTS shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_id INT NOT NULL,
  day_of_week TINYINT NOT NULL COMMENT '0=月, 1=火, 2=水, 3=木, 4=金, 5=土, 6=日',
  shift_time VARCHAR(20) NOT NULL COMMENT '9:00-15:00 or 15:00-23:00',
  role VARCHAR(20) NOT NULL COMMENT 'キッチン or ドライバー',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_shift (staff_id, day_of_week, shift_time, role),
  FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Handle shift submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shift'])) {
    $staff_id = (int)$_POST['staff_id'];
    $day = (int)$_POST['day'];
    $time = trim($_POST['time']);
    $role = trim($_POST['role']);
    
    if ($staff_id > 0) {
        $stmt = $mysqli->prepare("INSERT IGNORE INTO shifts (staff_id, day_of_week, shift_time, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $staff_id, $day, $time, $role);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = 'シフトを登録しました';
        } elseif ($stmt->affected_rows === 0) {
            $message = 'このシフトは既に登録されています';
        }
        $stmt->close();
    }
}

// Handle shift deletion
if (isset($_GET['delete_shift'])) {
    $shift_id = (int)$_GET['delete_shift'];
    $mysqli->query("DELETE FROM shifts WHERE id = $shift_id");
    header('Location: shift.php');
    exit;
}

// Get all active staff users
$staffList = [];
$staffResult = $mysqli->query("
    SELECT u.id, u.login, u.first_name, u.last_name, r.role_name
    FROM staff_users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.active = 1 AND r.active = 1
    ORDER BY u.last_name, u.first_name
");
if ($staffResult) {
    while ($row = $staffResult->fetch_assoc()) {
        $staffList[] = $row;
    }
}

// Load shift times from store_hours
$storeShifts = [
    'early_shift_start' => null,
    'early_shift_end' => null,
    'late_shift_start' => null,
    'late_shift_end' => null,
];

$storeResult = $mysqli->query("
    SELECT early_shift_start, early_shift_end, late_shift_start, late_shift_end
    FROM store_hours WHERE id=1 AND active=1 LIMIT 1
");
if ($storeResult && $row = $storeResult->fetch_assoc()) {
    $storeShifts = [
        'early_shift_start' => $row['early_shift_start'] ? substr($row['early_shift_start'], 0, 5) : null,
        'early_shift_end' => $row['early_shift_end'] ? substr($row['early_shift_end'], 0, 5) : null,
        'late_shift_start' => $row['late_shift_start'] ? substr($row['late_shift_start'], 0, 5) : null,
        'late_shift_end' => $row['late_shift_end'] ? substr($row['late_shift_end'], 0, 5) : null,
    ];
}

// Build available shift times from store settings
$availableShiftTimes = [];
if ($storeShifts['early_shift_start'] && $storeShifts['early_shift_end']) {
    $availableShiftTimes[] = $storeShifts['early_shift_start'] . '-' . $storeShifts['early_shift_end'];
}
if ($storeShifts['late_shift_start'] && $storeShifts['late_shift_end']) {
    $availableShiftTimes[] = $storeShifts['late_shift_start'] . '-' . $storeShifts['late_shift_end'];
}

// Get all shifts grouped by time, role, day
$shifts = [];
$shiftResult = $mysqli->query("
    SELECT s.id, s.day_of_week, s.shift_time, s.role, 
           u.first_name, u.last_name, u.login
    FROM shifts s
    JOIN staff_users u ON s.staff_id = u.id
    WHERE u.active = 1
    ORDER BY s.shift_time, s.role, s.day_of_week
");
if ($shiftResult) {
    while ($row = $shiftResult->fetch_assoc()) {
        $key = $row['shift_time'] . '|' . $row['role'] . '|' . $row['day_of_week'];
        $shifts[$key] = [
            'id' => $row['id'],
            'name' => $row['last_name'] . ' ' . $row['first_name'],
            'login' => $row['login']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>シフトページ</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
/* ====== BASIC ====== */
body{
  font-family: Arial, sans-serif;
    background:grey;
  padding: 20px;
}
.box{
  max-width:1100px;
  margin:auto;
  background:#fff;
  border-radius:16px;
  padding:24px;
}
h1{
  margin:0 0 20px;
  border-bottom:2px solid #eee;
  padding-bottom:10px;
}

/* ====== FORM ====== */
.form{
  display:grid;
  grid-template-columns:1.2fr 1fr 1fr 1fr auto;
  gap:10px;
  margin-bottom:24px;
}
input,select,button{
  padding:10px;
  border-radius:8px;
  border:1px solid #ddd;
}
button{
  background:#5b6dff;
  color:#fff;
  border:none;
  cursor:pointer;
}
button:hover{opacity:.9}

/* ====== TABLE ====== */
table{
  width:100%;
  border-collapse:collapse;
  font-size:14px;
}
th,td{
  border-bottom:1px solid #eee;
  padding:10px;
  text-align:center;
}
th{
  background:#f5f7fb;
}
.time{
  font-weight:bold;
  color:#4f46e5;
  white-space:nowrap;
}
.empty{
  color: #aa1106;
  font-style:italic;
}
</style>
</head>

<body>
<div class="box">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">シフトページ</h1>
    <a href="admin.php" style="text-decoration: none; padding: 8px 16px; background: #f3f4f6; border-radius: 8px; color: #555;">← 戻る</a>
  </div>

  <?php if ($message): ?>
    <div style="padding: 12px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 16px;">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($availableShiftTimes)): ?>
    <div style="padding: 16px; background: #fff3cd; color: #856404; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
      <strong>⚠ シフト時間が設定されていません</strong><br>
      <a href="kanri.php" style="color: #0066cc; text-decoration: underline;">店舗設定ページ</a>で早番・遅番の時間を設定してください。
    </div>
  <?php else: ?>
    <!-- ===== FORM ===== -->
    <form method="post">
      <div class="form">
        <select name="staff_id" required>
          <option value="">スタッフを選択</option>
          <?php foreach ($staffList as $staff): ?>
            <option value="<?= $staff['id'] ?>">
              <?= htmlspecialchars($staff['last_name'] . ' ' . $staff['first_name']) ?> (@<?= htmlspecialchars($staff['login']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <select name="role" required>
          <option>キッチン</option>
          <option>ドライバー</option>
        </select>
        <select name="time" required>
          <?php foreach ($availableShiftTimes as $shiftTime): ?>
            <option><?= htmlspecialchars($shiftTime) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="day" required>
          <option value="0">月</option>
          <option value="1">火</option>
          <option value="2">水</option>
          <option value="3">木</option>
          <option value="4">金</option>
          <option value="5">土</option>
          <option value="6">日</option>
        </select>
        <button type="submit" name="add_shift">シフトを登録</button>
      </div>
    </form>
  <?php endif; ?>

  <!-- ===== TABLE ===== -->
  <?php if (!empty($availableShiftTimes)): ?>
    <h2>週間のスケージュール</h2>
    <table>
      <tr>
        <th>時間</th><th>役割</th>
        <th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th><th>日</th>
      </tr>

      <?php
      // Build schedule rows dynamically from available shift times
      $schedules = [];
      foreach ($availableShiftTimes as $shiftTime) {
          $schedules[] = ['time' => $shiftTime, 'role' => 'キッチン'];
          $schedules[] = ['time' => $shiftTime, 'role' => 'ドライバー'];
      }

      $rowspan_done = [];
      foreach ($schedules as $idx => $sched):
          $time = $sched['time'];
          $role = $sched['role'];
          $show_time = !in_array($time, $rowspan_done);
          if ($show_time) $rowspan_done[] = $time;
      ?>
      <tr>
        <?php if ($show_time): ?>
          <td class="time" rowspan="2"><?= htmlspecialchars($time) ?></td>
        <?php endif; ?>
        <th><?= htmlspecialchars($role) ?></th>
        <?php for ($day = 0; $day < 7; $day++):
            $key = $time . '|' . $role . '|' . $day;
            $shift = $shifts[$key] ?? null;
        ?>
          <td class="cell" style="<?= $shift ? 'cursor: pointer;' : '' ?>" <?= $shift ? 'onclick="if(confirm(\'削除しますか？\')) location.href=\'?delete_shift=' . $shift['id'] . '\'"' : '' ?>>
            <?php if ($shift): ?>
              <?= htmlspecialchars($shift['name']) ?>
            <?php else: ?>
              <span class="empty">空</span>
            <?php endif; ?>
          </td>
        <?php endfor; ?>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

</div>


</body>
</html>
