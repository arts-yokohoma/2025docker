<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';
// Shifts: admin, manager (RW), kitchen, driver (read-only)
requireRoles(['admin', 'manager', 'kitchen', 'driver']);

$userRole = $_SESSION['admin_role'] ?? 'user';
$canEdit = in_array($userRole, ['admin', 'manager']);

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Ensure shifts table exists (create if not); support multiple users per slot (up to 4)
$mysqli->query("
CREATE TABLE IF NOT EXISTS shifts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  day_of_week TINYINT NOT NULL,
  time_slot VARCHAR(20) NOT NULL,
  role VARCHAR(30) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_shift_slot_user (day_of_week, time_slot, role, user_id),
  INDEX idx_shifts_user (user_id),
  INDEX idx_shifts_slot (day_of_week, time_slot, role),
  CONSTRAINT fk_shifts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
// Migrate existing DB: drop old unique so multiple users per slot are allowed (run once)
$res = $mysqli->query("SHOW INDEX FROM shifts WHERE Key_name = 'uq_shift_slot'");
if ($res && $res->num_rows > 0) {
  $res->free();
  $mysqli->query("ALTER TABLE shifts DROP INDEX uq_shift_slot");
  $mysqli->query("ALTER TABLE shifts ADD UNIQUE KEY uq_shift_slot_user (day_of_week, time_slot, role, user_id)");
  $mysqli->query("ALTER TABLE shifts ADD INDEX idx_shifts_slot (day_of_week, time_slot, role)");
} elseif ($res) {
  $res->free();
}

// Display name: name + surname, fallback to username (users may have name/surname columns)
$mysqli->set_charset('utf8mb4');

// Load shift times from store_hours table
$earlyShift = '9:00-15:00'; // default fallback
$lateShift = '15:00-23:00'; // default fallback
$storeHoursRes = $mysqli->query("SELECT early_shift_start, early_shift_end, late_shift_start, late_shift_end FROM store_hours WHERE id = 1 LIMIT 1");
if ($storeHoursRes && $row = $storeHoursRes->fetch_assoc()) {
  if ($row['early_shift_start'] && $row['early_shift_end']) {
    $earlyShift = substr($row['early_shift_start'], 0, 5) . '-' . substr($row['early_shift_end'], 0, 5);
  }
  if ($row['late_shift_start'] && $row['late_shift_end']) {
    $lateShift = substr($row['late_shift_start'], 0, 5) . '-' . substr($row['late_shift_end'], 0, 5);
  }
  $storeHoursRes->free();
}

// Load users with role kitchen or driver (staff that can be assigned to shifts; drivers can work kitchen slots too)
$staffList = [];
$staffQuery = "
  SELECT u.id, u.username,
         COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.name,''), ' ', COALESCE(u.surname,''))), ''), u.username) AS display_name,
         r.name AS role_name
  FROM users u
  JOIN roles r ON u.role_id = r.id
  WHERE r.name IN ('kitchen', 'driver')
  ORDER BY r.name, u.username
";
$res = $mysqli->query($staffQuery);
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $staffList[] = $row;
  }
  $res->free();
}

// Load existing shifts: up to 4 users per (day, time_slot, role) as array of { user_id, display_name }
$shifts = [];
$res = $mysqli->query("
  SELECT s.day_of_week, s.time_slot, s.role, s.user_id,
         COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.name,''), ' ', COALESCE(u.surname,''))), ''), u.username) AS display_name
  FROM shifts s
  JOIN users u ON u.id = s.user_id
  ORDER BY s.day_of_week, s.time_slot, s.role, s.id
");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $key = (int)$row['day_of_week'] . '|' . $row['time_slot'] . '|' . $row['role'];
    if (!isset($shifts[$key])) $shifts[$key] = [];
    $shifts[$key][] = ['user_id' => (int)$row['user_id'], 'display_name' => $row['display_name']];
  }
  $res->free();
}

// Max assignees per shift block (day + time_slot + role)
define('SHIFT_MAX_PER_SLOT', 4);

// Handle save: add a person to a shift slot (max 4 per slot; drivers can be assigned to kitchen)
if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_shift') {
  $user_id = (int)($_POST['user_id'] ?? 0);
  $day_of_week = (int)($_POST['day_of_week'] ?? -1);
  $time_slot = trim($_POST['time_slot'] ?? '');
  $role = trim($_POST['role'] ?? '');

  $validSlots = ['9:00-15:00', '15:00-23:00'];
  $validRoles = ['キッチン', 'ドライバー'];

  if ($user_id > 0 && $day_of_week >= 0 && $day_of_week <= 6 && in_array($time_slot, $validSlots) && in_array($role, $validRoles)) {
    $countStmt = $mysqli->prepare("SELECT COUNT(*) AS n FROM shifts WHERE day_of_week = ? AND time_slot = ? AND role = ?");
    $countStmt->bind_param("iss", $day_of_week, $time_slot, $role);
    $countStmt->execute();
    $count = (int)($countStmt->get_result()->fetch_assoc()['n'] ?? 0);
    $countStmt->close();
    if ($count < SHIFT_MAX_PER_SLOT) {
      $stmt = $mysqli->prepare("INSERT IGNORE INTO shifts (user_id, day_of_week, time_slot, role) VALUES (?, ?, ?, ?)");
      if ($stmt) {
        $stmt->bind_param("iiss", $user_id, $day_of_week, $time_slot, $role);
        $stmt->execute();
        $stmt->close();
      }
    }
  }
  header('Location: shift.php?saved=1');
  exit;
}

// Handle remove: remove one person from a shift slot
if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_shift') {
  $user_id = (int)($_POST['user_id'] ?? 0);
  $day_of_week = (int)($_POST['day_of_week'] ?? -1);
  $time_slot = trim($_POST['time_slot'] ?? '');
  $role = trim($_POST['role'] ?? '');

  $validSlots = [$earlyShift, $lateShift];
  $validRoles = ['キッチン', 'ドライバー'];

  if ($user_id > 0 && $day_of_week >= 0 && $day_of_week <= 6 && in_array($time_slot, $validSlots) && in_array($role, $validRoles)) {
    $stmt = $mysqli->prepare("DELETE FROM shifts WHERE day_of_week = ? AND time_slot = ? AND role = ? AND user_id = ?");
    if ($stmt) {
      $stmt->bind_param("issi", $day_of_week, $time_slot, $role, $user_id);
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
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
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
    <p class="shift-hint">配達スタッフもキッチン枠に割り当て可能です。1枠あたり最大<?= SHIFT_MAX_PER_SLOT ?>名まで登録できます。</p>
    <?php if (empty($staffList)): ?>
    <p class="shift-empty-hint">キッチンまたはドライバーロールのユーザーがいません。<a href="user.php">ユーザー管理</a>で<strong>キッチン</strong>・<strong>ドライバー</strong>の役割でユーザーを追加してください。</p>
    <?php else: ?>
    <ul class="shift-staff-list">
      <?php foreach ($staffList as $u): ?>
      <li><span class="staff-name"><?= htmlspecialchars($u['display_name'], ENT_QUOTES, 'UTF-8') ?></span> <span class="staff-role"><?= $u['role_name'] === 'kitchen' ? 'キッチン' : 'ドライバー' ?></span></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

  <!-- Assign form -->
  <?php if ($canEdit && !empty($staffList)): ?>
  <section class="shift-card shift-card-form">
    <h2 class="shift-card-title">シフトを追加</h2>
    <p class="shift-form-hint">担当者を選び、枠（役割・時間帯・曜日）を指定して「追加」で登録。同じ枠に最大<?= SHIFT_MAX_PER_SLOT ?>名まで追加できます。</p>
    <form method="POST" class="shift-form">
      <input type="hidden" name="action" value="save_shift">
      <div class="shift-form-row">
        <div class="shift-field">
          <label for="user_id">担当者</label>
          <select name="user_id" id="user_id" required>
            <option value="">-- 選択 --</option>
            <?php foreach ($staffList as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['display_name'], ENT_QUOTES, 'UTF-8') ?> (<?= $u['role_name'] === 'kitchen' ? 'キッチン' : 'ドライバー' ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="shift-field">
          <label for="role">枠の役割</label>
          <select name="role" id="role">
            <option value="キッチン">キッチン</option>
            <option value="ドライバー">ドライバー</option>
          </select>
        </div>
        <div class="shift-field">
          <label for="time_slot">時間帯</label>
          <select name="time_slot" id="time_slot">
            <option value="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?></option>
            <option value="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?></option>
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
          <button type="submit" class="btn-save-shift">追加</button>
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
    <p class="shift-table-caption">各枠に最大<?= SHIFT_MAX_PER_SLOT ?>名まで登録できます。×で個別に解除。</p>
    <div class="shift-table-wrap">
  <table class="shift-table">
    <tr>
      <th>時間</th><th>役割</th>
      <th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th><th>日</th>
    </tr>
    <tr>
      <td class="time" rowspan="2"><?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?></td>
      <th>キッチン</th>
      <td class="cell" data-day="0" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="1" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="2" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="3" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="4" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="5" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="6" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
    </tr>
    <tr>
      <th>ドライバー</th>
      <td class="cell" data-day="0" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="1" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="2" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="3" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="4" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="5" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="6" data-time="<?= htmlspecialchars($earlyShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
    </tr>
    <tr>
      <td class="time" rowspan="2"><?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?></td>
      <th>キッチン</th>
      <td class="cell" data-day="0" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="1" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="2" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="3" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="4" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="5" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
      <td class="cell" data-day="6" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="キッチン"></td>
    </tr>
    <tr>
      <th>ドライバー</th>
      <td class="cell" data-day="0" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="1" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="2" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="3" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="4" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="5" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
      <td class="cell" data-day="6" data-time="<?= htmlspecialchars($lateShift, ENT_QUOTES, 'UTF-8') ?>" data-role="ドライバー"></td>
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
    const list = shifts[key];
    const assignees = Array.isArray(list) ? list : (list ? [list] : []);

    if (assignees.length > 0) {
      cell.classList.remove('empty-cell');
      cell.classList.add('filled-cell', 'cell-multi');
      cell.innerHTML = '<div class="cell-assignees">' + assignees.map(function(a) {
        const name = (a.display_name || a.username || '').trim() || '—';
        <?php if ($canEdit): ?>
        return '<div class="assignee-row">' +
          '<span class="assignee-name">' + escapeHtml(name) + '</span>' +
          '<form method="POST" class="cell-remove-form" onsubmit="return confirm(\'この担当者をシフトから外しますか？\');">' +
          '<input type="hidden" name="action" value="remove_shift">' +
          '<input type="hidden" name="user_id" value="' + (a.user_id || '') + '">' +
          '<input type="hidden" name="day_of_week" value="' + escapeHtml(day) + '">' +
          '<input type="hidden" name="time_slot" value="' + escapeHtml(time) + '">' +
          '<input type="hidden" name="role" value="' + escapeHtml(role) + '">' +
          '<button type="submit" class="cell-remove" title="削除"><span class="material-icons">delete</span></button></form></div>';
        <?php else: ?>
        return '<div class="assignee-row"><span class="assignee-name">' + escapeHtml(name) + '</span></div>';
        <?php endif; ?>
      }).join('') + '</div>';
    } else {
      cell.innerHTML = '<span class="empty">—</span>';
      cell.classList.add('empty-cell');
      cell.classList.remove('filled-cell', 'cell-multi');
    }
  });
})();
</script>

</body>
</html>
