<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
// Shifts: admin, manager (RW), kitchen, delivery (read-only)
requireRoles(['admin', 'manager', 'kitchen', 'delivery']);

$userRole = $_SESSION['admin_role'] ?? 'user';
$canEdit = in_array($userRole, ['admin', 'manager']);

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Ensure shifts table exists (create if not)
$mysqli->query("
CREATE TABLE IF NOT EXISTS shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  day_of_week TINYINT NOT NULL,
  time_slot VARCHAR(20) NOT NULL,
  role VARCHAR(30) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shift_slot (day_of_week, time_slot, role),
  INDEX idx_shifts_user (user_id),
  CONSTRAINT fk_shifts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Load users with role kitchen or delivery (staff that can be assigned to shifts)
$staffList = [];
$res = $mysqli->query("
  SELECT u.id, u.username
  FROM users u
  JOIN roles r ON u.role_id = r.id
  WHERE r.name IN ('kitchen', 'delivery') AND u.active = 1
  ORDER BY u.username
");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $staffList[] = $row;
  }
  $res->free();
}

// Load existing shifts
$shifts = [];
$res = $mysqli->query("
  SELECT s.day_of_week, s.time_slot, s.role, s.user_id, u.username
  FROM shifts s
  JOIN users u ON u.id = s.user_id
  ORDER BY s.day_of_week, s.time_slot, s.role
");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $key = (int)$row['day_of_week'] . '|' . $row['time_slot'] . '|' . $row['role'];
    $shifts[$key] = ['user_id' => (int)$row['user_id'], 'username' => $row['username']];
  }
  $res->free();
}

// Handle save: assign a person from the list to a shift slot
if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_shift') {
  $user_id = (int)($_POST['user_id'] ?? 0);
  $day_of_week = (int)($_POST['day_of_week'] ?? -1);
  $time_slot = trim($_POST['time_slot'] ?? '');
  $role = trim($_POST['role'] ?? '');

  $validSlots = ['9:00-15:00', '15:00-23:00'];
  $validRoles = ['キッチン', 'ドライバー'];

  if ($user_id > 0 && $day_of_week >= 0 && $day_of_week <= 6 && in_array($time_slot, $validSlots) && in_array($role, $validRoles)) {
    $stmt = $mysqli->prepare("INSERT INTO shifts (user_id, day_of_week, time_slot, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)");
    if ($stmt) {
      $stmt->bind_param("iiss", $user_id, $day_of_week, $time_slot, $role);
      $stmt->execute();
      $stmt->close();
    }
  }
  header('Location: shift.php');
  exit;
}

// Handle remove: clear a shift slot
if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_shift') {
  $day_of_week = (int)($_POST['day_of_week'] ?? -1);
  $time_slot = trim($_POST['time_slot'] ?? '');
  $role = trim($_POST['role'] ?? '');

  $validSlots = ['9:00-15:00', '15:00-23:00'];
  $validRoles = ['キッチン', 'ドライバー'];

  if ($day_of_week >= 0 && $day_of_week <= 6 && in_array($time_slot, $validSlots) && in_array($role, $validRoles)) {
    $stmt = $mysqli->prepare("DELETE FROM shifts WHERE day_of_week = ? AND time_slot = ? AND role = ?");
    if ($stmt) {
      $stmt->bind_param("iss", $day_of_week, $time_slot, $role);
      $stmt->execute();
      $stmt->close();
    }
  }
  header('Location: shift.php');
  exit;
}

$shiftsJson = json_encode($shifts);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>シフトページ</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/shift.css">
</head>

<body>
<div class="box">
  <div class="header-section" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
    <img src="../assets/image/logo.png" alt="Pizza Mach" style="height: 40px; width: auto;">
    <h1 style="margin: 0;">Pizza Mach</h1>
    <h1 style="margin: 0;">シフトページ</h1>
    <form method="post" style="display: inline; margin-left: auto;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="logout-btn">ログアウト</button>
    </form>
  </div>

  <!-- ===== FORM: assign person from list to shift ===== -->
  <?php if ($canEdit): ?>
  <div class="form">
    <form method="POST" style="display: contents;">
      <input type="hidden" name="action" value="save_shift">
      <select name="user_id" id="user_id" required>
        <option value="">-- 担当者を選択 --</option>
        <?php foreach ($staffList as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <select name="role" id="role">
        <option value="キッチン">キッチン</option>
        <option value="ドライバー">ドライバー</option>
      </select>
      <select name="time_slot" id="time_slot">
        <option value="9:00-15:00">9:00-15:00</option>
        <option value="15:00-23:00">15:00-23:00</option>
      </select>
      <select name="day_of_week" id="day_of_week">
        <option value="0">月</option>
        <option value="1">火</option>
        <option value="2">水</option>
        <option value="3">木</option>
        <option value="4">金</option>
        <option value="5">土</option>
        <option value="6">日</option>
      </select>
      <button type="submit">シフトを保存</button>
    </form>
  </div>
  <?php else: ?>
  <p class="readonly-msg">シフトの編集は管理者・マネージャーのみ可能です。</p>
  <?php endif; ?>

  <!-- ===== TABLE ===== -->
  <h2>週間のスケジュール</h2>
  <table>
    <tr>
      <th>時間</th><th>役割</th>
      <th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th><th>日</th>
    </tr>
    <tr>
      <td class="time" rowspan="2">9:00-15:00</td>
      <th>キッチン</th>
      <td class="cell" data-day="0" data-time="9:00-15:00" data-role="キッチン"></td>
      <td class="cell" data-day="1" data-time="9:00-15:00" data-role="キッチン"></td>
      <td class="cell" data-day="2" data-time="9:00-15:00" data-role="キッチン"></td>
      <td class="cell" data-day="3" data-time="9:00-15:00" data-role="キッチン"></td>
      <td class="cell" data-day="4" data-time="9:00-15:00" data-role="キッチン"></td>
      <td class="cell" data-day="5" data-time="9:00-15:00" data-role="キッチン"></td>
      <td class="cell" data-day="6" data-time="9:00-15:00" data-role="キッチン"></td>
    </tr>
    <tr>
      <th>ドライバー</th>
      <td class="cell" data-day="0" data-time="9:00-15:00" data-role="ドライバー"></td>
      <td class="cell" data-day="1" data-time="9:00-15:00" data-role="ドライバー"></td>
      <td class="cell" data-day="2" data-time="9:00-15:00" data-role="ドライバー"></td>
      <td class="cell" data-day="3" data-time="9:00-15:00" data-role="ドライバー"></td>
      <td class="cell" data-day="4" data-time="9:00-15:00" data-role="ドライバー"></td>
      <td class="cell" data-day="5" data-time="9:00-15:00" data-role="ドライバー"></td>
      <td class="cell" data-day="6" data-time="9:00-15:00" data-role="ドライバー"></td>
    </tr>
    <tr>
      <td class="time" rowspan="2">15:00-23:00</td>
      <th>キッチン</th>
      <td class="cell" data-day="0" data-time="15:00-23:00" data-role="キッチン"></td>
      <td class="cell" data-day="1" data-time="15:00-23:00" data-role="キッチン"></td>
      <td class="cell" data-day="2" data-time="15:00-23:00" data-role="キッチン"></td>
      <td class="cell" data-day="3" data-time="15:00-23:00" data-role="キッチン"></td>
      <td class="cell" data-day="4" data-time="15:00-23:00" data-role="キッチン"></td>
      <td class="cell" data-day="5" data-time="15:00-23:00" data-role="キッチン"></td>
      <td class="cell" data-day="6" data-time="15:00-23:00" data-role="キッチン"></td>
    </tr>
    <tr>
      <th>ドライバー</th>
      <td class="cell" data-day="0" data-time="15:00-23:00" data-role="ドライバー"></td>
      <td class="cell" data-day="1" data-time="15:00-23:00" data-role="ドライバー"></td>
      <td class="cell" data-day="2" data-time="15:00-23:00" data-role="ドライバー"></td>
      <td class="cell" data-day="3" data-time="15:00-23:00" data-role="ドライバー"></td>
      <td class="cell" data-day="4" data-time="15:00-23:00" data-role="ドライバー"></td>
      <td class="cell" data-day="5" data-time="15:00-23:00" data-role="ドライバー"></td>
      <td class="cell" data-day="6" data-time="15:00-23:00" data-role="ドライバー"></td>
    </tr>
  </table>

</div>

<script>
(function() {
  const shifts = <?= $shiftsJson ?>;

  function slotKey(day, time, role) {
    return day + '|' + time + '|' + role;
  }

  document.querySelectorAll('.cell').forEach(function(cell) {
    const day = cell.getAttribute('data-day');
    const time = cell.getAttribute('data-time');
    const role = cell.getAttribute('data-role');
    const key = slotKey(day, time, role);
    const data = shifts[key];

    if (data) {
      cell.textContent = data.username;
      cell.classList.remove('empty-cell');
      <?php if ($canEdit): ?>
      cell.innerHTML = '<span class="assignee">' + (data.username || '') + '</span> <form method="POST" style="display:inline;" onsubmit="return confirm(\'このシフトを外しますか？\');"><input type="hidden" name="action" value="remove_shift"><input type="hidden" name="day_of_week" value="' + day + '"><input type="hidden" name="time_slot" value="' + time + '"><input type="hidden" name="role" value="' + role + '"><button type="submit" class="cell-remove">×</button></form>';
      <?php endif; ?>
    } else {
      cell.innerHTML = '<span class="empty">空</span>';
      cell.classList.add('empty-cell');
    }
  });
})();
</script>
<style>
.cell-remove { background: #ef5350; color: #fff; border: none; border-radius: 4px; cursor: pointer; padding: 2px 8px; font-size: 14px; }
.cell-remove:hover { background: #e53935; }
.assignee { margin-right: 6px; }
.readonly-msg { color: #666; margin-bottom: 16px; }
</style>

</body>
</html>
