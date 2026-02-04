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

// Display name: name + surname, fallback to username (users may have name/surname columns)
$mysqli->set_charset('utf8mb4');

// Load users with role kitchen or delivery (staff that can be assigned to shifts)
$staffList = [];
$staffQuery = "
  SELECT u.id, u.username,
         COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.name,''), ' ', COALESCE(u.surname,''))), ''), u.username) AS display_name,
         r.name AS role_name
  FROM users u
  JOIN roles r ON u.role_id = r.id
  WHERE r.name IN ('kitchen', 'delivery')
  ORDER BY r.name, u.username
";
$res = $mysqli->query($staffQuery);
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $staffList[] = $row;
  }
  $res->free();
}

// Load existing shifts: show user display name in each timeslot (by user_id)
$shifts = [];
$res = $mysqli->query("
  SELECT s.id, s.day_of_week, s.time_slot, s.role, s.user_id,
         COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.name,''), ' ', COALESCE(u.surname,''))), ''), u.username) AS display_name
  FROM shifts s
  JOIN users u ON u.id = s.user_id
  ORDER BY s.day_of_week, s.time_slot, s.role
");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $key = (int)$row['day_of_week'] . '|' . $row['time_slot'] . '|' . $row['role'];
    $shifts[$key] = ['user_id' => (int)$row['user_id'], 'display_name' => $row['display_name']];
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
    $stmt = $mysqli->prepare("INSERT INTO shifts (user_id, day_of_week, time_slot, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), created_at = CURRENT_TIMESTAMP");
    if ($stmt) {
      $stmt->bind_param("iiss", $user_id, $day_of_week, $time_slot, $role);
      $stmt->execute();
      $stmt->close();
    }
  }
  header('Location: shift.php?saved=1');
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
$flashSaved = isset($_GET['saved']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>シフト | Pizza Mach</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/shift.css">
</head>
<body>

<header class="shift-header">
  <div class="shift-header-inner">
    <img src="../assets/image/logo.png" alt="Pizza Mach" class="shift-logo">
    <h1 class="shift-title">シフト管理</h1>
    <a href="admin.php" class="shift-back">＜ 戻る</a>
    <form method="post" class="shift-logout-form">
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="logout-btn">ログアウト</button>
    </form>
  </div>
</header>

<div class="shift-wrap">
  <?php if ($flashSaved): ?>
  <div class="shift-flash shift-flash-ok">シフトを保存しました。</div>
  <?php endif; ?>

  <!-- Staff list -->
  <section class="shift-card shift-card-staff">
    <h2 class="shift-card-title">担当者一覧</h2>
    <?php if (empty($staffList)): ?>
    <p class="shift-empty-hint">キッチンまたは配達ロールのユーザーがいません。<a href="user.php">ユーザー管理</a>で<strong>キッチン</strong>・<strong>配達</strong>の役割でユーザーを追加してください。</p>
    <?php else: ?>
    <ul class="shift-staff-list">
      <?php foreach ($staffList as $u): ?>
      <li><span class="staff-name"><?= htmlspecialchars($u['display_name'], ENT_QUOTES, 'UTF-8') ?></span> <span class="staff-role"><?= $u['role_name'] === 'kitchen' ? 'キッチン' : '配達' ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <!-- Assign form -->
  <?php if ($canEdit && !empty($staffList)): ?>
  <section class="shift-card shift-card-form">
    <h2 class="shift-card-title">シフトを割り当て</h2>
    <form method="POST" class="shift-form">
      <input type="hidden" name="action" value="save_shift">
      <div class="shift-form-row">
        <div class="shift-field">
          <label for="user_id">担当者</label>
          <select name="user_id" id="user_id" required>
            <option value="">-- 選択 --</option>
            <?php foreach ($staffList as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['display_name'], ENT_QUOTES, 'UTF-8') ?> (<?= $u['role_name'] === 'kitchen' ? 'キッチン' : '配達' ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="shift-field">
          <label for="role">役割</label>
          <select name="role" id="role">
            <option value="キッチン">キッチン</option>
            <option value="ドライバー">ドライバー</option>
          </select>
        </div>
        <div class="shift-field">
          <label for="time_slot">時間帯</label>
          <select name="time_slot" id="time_slot">
            <option value="9:00-15:00">9:00-15:00</option>
            <option value="15:00-23:00">15:00-23:00</option>
          </select>
        </div>
        <div class="shift-field">
          <label for="day_of_week">曜日</label>
          <select name="day_of_week" id="day_of_week">
            <option value="0">月</option>
            <option value="1">火</option>
            <option value="2">水</option>
            <option value="3">木</option>
            <option value="4">金</option>
            <option value="5">土</option>
            <option value="6">日</option>
          </select>
        </div>
        <div class="shift-field shift-field-submit">
          <label>&nbsp;</label>
          <button type="submit" class="btn-save-shift">保存</button>
        </div>
      </div>
    </form>
  </section>
  <?php elseif ($canEdit): ?>
  <p class="readonly-msg">担当者がいないためシフトを割り当てられません。</p>
  <?php else: ?>
  <p class="readonly-msg">シフトの編集は管理者・マネージャーのみ可能です。</p>
  <?php endif; ?>

  <!-- Schedule table -->
  <section class="shift-card shift-card-table">
    <h2 class="shift-card-title">週間スケジュール</h2>
    <div class="shift-table-wrap">
  <table class="shift-table">
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
  </section>
</div>

<script>
(function() {
  const shifts = <?= $shiftsJson ?>;

  function slotKey(day, time, role) {
    return day + '|' + time + '|' + role;
  }

  function escapeHtml(s) {
    var div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
  }

  document.querySelectorAll('.cell').forEach(function(cell) {
    const day = cell.getAttribute('data-day');
    const time = cell.getAttribute('data-time');
    const role = cell.getAttribute('data-role');
    const key = slotKey(day, time, role);
    const data = shifts[key];
    const displayName = (data && (data.display_name || data.username)) ? (data.display_name || data.username) : '';

    if (data && displayName) {
      cell.classList.remove('empty-cell');
      cell.classList.add('filled-cell');
      <?php if ($canEdit): ?>
      cell.innerHTML = '<span class="assignee">' + escapeHtml(displayName) + '</span> <form method="POST" class="cell-remove-form" onsubmit="return confirm(\'このシフトを外しますか？\');"><input type="hidden" name="action" value="remove_shift"><input type="hidden" name="day_of_week" value="' + escapeHtml(day) + '"><input type="hidden" name="time_slot" value="' + escapeHtml(time) + '"><input type="hidden" name="role" value="' + escapeHtml(role) + '"><button type="submit" class="cell-remove" title="削除">×</button></form>';
      <?php else: ?>
      cell.textContent = displayName;
      <?php endif; ?>
    } else {
      cell.innerHTML = '<span class="empty">—</span>';
      cell.classList.add('empty-cell');
      cell.classList.remove('filled-cell');
    }
  });
})();
</script>

</body>
</html>
