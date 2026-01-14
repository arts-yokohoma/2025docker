<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../config/db.php'; // provides $mysqli (mysqli)

// --- helpers ---
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function toTimeOrNull(?string $s): ?string {
    if ($s === null) return null;
    $s = trim($s);
    if ($s === '') return null;
    // input[type=time] gives "HH:MM" typically
    if (strlen($s) === 5) return $s . ':00';
    return $s; // already has seconds
}

function toInt($v, int $default = 0): int {
    if ($v === null || $v === '') return $default;
    if (!is_numeric($v)) return $default;
    return (int)$v;
}

function validateStoreHours(array $d): array {
    // returns [bool ok, ?string error]
    $open  = $d['open_time'];
    $close = $d['close_time'];
    $es = $d['early_shift_start'];
    $ee = $d['early_shift_end'];
    $ls = $d['late_shift_start'];
    $le = $d['late_shift_end'];
    $offset = $d['last_order_offset_min'];

    if (!$open || !$close || !$es || !$ee || !$ls || !$le) {
        return [false, '時間が未入力です。'];
    }
    if (!($open < $close)) return [false, '営業時間が正しくありません。'];
    if (!($es < $ee)) return [false, '早番の時間が正しくありません。'];
    if (!($ls < $le)) return [false, '遅番の時間が正しくありません。'];

    if (!($open <= $es && $ee <= $close)) return [false, '早番は営業時間内に設定してください。'];
    if (!($open <= $ls && $le <= $close)) return [false, '遅番は営業時間内に設定してください。'];

    if (!($ee <= $ls)) return [false, '早番の終了は遅番の開始以前にしてください。'];

    if ($offset < 0) return [false, 'ラストオーダー設定が正しくありません。'];

    return [true, null];
}

$flashOk = null;
$flashErr = null;

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_store_hours') {
            $data = [
                'open_time' => toTimeOrNull($_POST['open_time'] ?? null),
                'close_time' => toTimeOrNull($_POST['close_time'] ?? null),
                'last_order_offset_min' => toInt($_POST['last_order_offset_min'] ?? 30, 30),
                'early_shift_start' => toTimeOrNull($_POST['early_shift_start'] ?? null),
                'early_shift_end' => toTimeOrNull($_POST['early_shift_end'] ?? null),
                'late_shift_start' => toTimeOrNull($_POST['late_shift_start'] ?? null),
                'late_shift_end' => toTimeOrNull($_POST['late_shift_end'] ?? null),
            ];

            [$ok, $err] = validateStoreHours($data);
            if (!$ok) {
                $flashErr = $err;
            } else {
                // Keep single row id=1 (upsert)
                $sql = "
                    INSERT INTO store_hours
                      (id, open_time, close_time, last_order_offset_min,
                       early_shift_start, early_shift_end, late_shift_start, late_shift_end,
                       active, update_time)
                    VALUES
                      (1, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE
                      open_time = VALUES(open_time),
                      close_time = VALUES(close_time),
                      last_order_offset_min = VALUES(last_order_offset_min),
                      early_shift_start = VALUES(early_shift_start),
                      early_shift_end = VALUES(early_shift_end),
                      late_shift_start = VALUES(late_shift_start),
                      late_shift_end = VALUES(late_shift_end),
                      active = 1,
                      update_time = CURRENT_TIMESTAMP
                ";

                $stmt = $mysqli->prepare($sql);
                if (!$stmt) throw new RuntimeException('prepare failed');

                // ss i s s s s  => "ssissss"
                $stmt->bind_param(
                    "ssissss",
                    $data['open_time'],
                    $data['close_time'],
                    $data['last_order_offset_min'],
                    $data['early_shift_start'],
                    $data['early_shift_end'],
                    $data['late_shift_start'],
                    $data['late_shift_end']
                );

                if (!$stmt->execute()) throw new RuntimeException('execute failed');
                $stmt->close();

                $flashOk = '保存しました。';
            }
        }

        if ($action === 'save_menu_item') {
            $id   = toInt($_POST['menu_id'] ?? 0, 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $ps   = toInt($_POST['price_s'] ?? 0, 0);
            $pm   = toInt($_POST['price_m'] ?? 0, 0);
            $pl   = toInt($_POST['price_l'] ?? 0, 0);

            if ($name === '') {
                $flashErr = 'ピザ名を入力してください。';
            } else {
                if ($id > 0) {
                    $stmt = $mysqli->prepare("UPDATE menu SET name=?, price_s=?, price_m=?, price_l=?, update_time=CURRENT_TIMESTAMP WHERE id=?");
                    if (!$stmt) throw new RuntimeException('prepare failed');
                    $stmt->bind_param("siiii", $name, $ps, $pm, $pl, $id);
                    if (!$stmt->execute()) throw new RuntimeException('execute failed');
                    $stmt->close();
                } else {
                    $stmt = $mysqli->prepare("INSERT INTO menu (name, price_s, price_m, price_l, active, create_time, update_time)
                                              VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
                    if (!$stmt) throw new RuntimeException('prepare failed');
                    $stmt->bind_param("siii", $name, $ps, $pm, $pl);
                    if (!$stmt->execute()) throw new RuntimeException('execute failed');
                    $stmt->close();
                }
                $flashOk = '保存しました。';
            }
        }

        if ($action === 'delete_menu_item') {
            $id = toInt($_POST['menu_id'] ?? 0, 0);
            if ($id > 0) {
                // logical delete
                $stmt = $mysqli->prepare("UPDATE menu SET active=0, update_time=CURRENT_TIMESTAMP WHERE id=?");
                if (!$stmt) throw new RuntimeException('prepare failed');
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) throw new RuntimeException('execute failed');
                $stmt->close();
            }
            $flashOk = '削除しました。';
        }

    } catch (Throwable $e) {
        $flashErr = 'エラーが発生しました。';
        // debug if needed:
        // $flashErr = $e->getMessage();
    }
}

// --- Load data for display ---
$store = [
    'open_time' => '11:00',
    'close_time' => '22:00',
    'last_order_offset_min' => 30,
    'early_shift_start' => '09:00',
    'early_shift_end' => '13:00',
    'late_shift_start' => '14:00',
    'late_shift_end' => '23:00',
];

// store_hours row
$res = $mysqli->query("SELECT open_time, close_time, last_order_offset_min,
                              early_shift_start, early_shift_end, late_shift_start, late_shift_end
                       FROM store_hours WHERE id=1 AND active=1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    foreach ($row as $k => $v) {
        if ($v === null) continue;
        if ($k === 'last_order_offset_min') { $store[$k] = (int)$v; continue; }
        $store[$k] = substr((string)$v, 0, 5); // HH:MM for input[type=time]
    }
    $res->free();
}

// menu list
$menuItems = [];
$res = $mysqli->query("SELECT id, name, price_s, price_m, price_l FROM menu WHERE active=1 ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $menuItems[] = $r;
    $res->free();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>管理パネル</title>
<link rel="stylesheet" href="css/kanri.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<div class="layout">
    <h2 class="logo">◆ 管理パネル</h2>

    <main class="content">
        <h1>設定</h1>

        <?php if ($flashOk): ?>
            <div class="flash ok"><?= h($flashOk) ?></div>
        <?php endif; ?>
        <?php if ($flashErr): ?>
            <div class="flash err"><?= h($flashErr) ?></div>
        <?php endif; ?>

        <!-- 営業時間 + シフト -->
        <section class="card">
            <h2>営業時間・ラストオーダー・シフト設定</h2>

            <form method="post">
                <input type="hidden" name="action" value="save_store_hours">

                <div class="row">
                    <div>
                        <label>開店時間</label>
                        <input type="time" name="open_time" value="<?= h($store['open_time']) ?>" required>
                    </div>
                    <div>
                        <label>閉店時間</label>
                        <input type="time" name="close_time" value="<?= h($store['close_time']) ?>" required>
                    </div>
                    <div>
                        <label>ラストオーダー（閉店の何分前）</label>
                        <select name="last_order_offset_min">
                            <?php
                            $opts = [0, 15, 30, 45, 60, 90, 120];
                            foreach ($opts as $m):
                                $sel = ((int)$store['last_order_offset_min'] === $m) ? 'selected' : '';
                            ?>
                                <option value="<?= $m ?>" <?= $sel ?>><?= $m ?>分前</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h2 style="margin-top:18px;">シフト時間設定</h2>

                <h3>🌞 早番</h3>
                <div class="row">
                    <div>
                        <label>から</label>
                        <input type="time" name="early_shift_start" value="<?= h($store['early_shift_start']) ?>" required>
                    </div>
                    <div>
                        <label>まで</label>
                        <input type="time" name="early_shift_end" value="<?= h($store['early_shift_end']) ?>" required>
                    </div>
                </div>

                <h3>🌙 遅番</h3>
                <div class="row">
                    <div>
                        <label>から</label>
                        <input type="time" name="late_shift_start" value="<?= h($store['late_shift_start']) ?>" required>
                    </div>
                    <div>
                        <label>まで</label>
                        <input type="time" name="late_shift_end" value="<?= h($store['late_shift_end']) ?>" required>
                    </div>
                </div>

                <div class="form-footer">
                    <button class="btn-save" type="submit">保存</button>
                </div>
            </form>
        </section>

        <!-- ピザ価格 -->
        <section class="card">
            <h2>ピザ価格設定</h2>

            <table>
                <thead>
                    <tr>
                        <th>ピザ</th>
                        <th>Sサイズ</th>
                        <th>Mサイズ</th>
                        <th>Lサイズ</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="menu-tbody">
                    <?php foreach ($menuItems as $item): $mid = (int)$item['id']; ?>
                    <tr>
                        <td><input type="text" name="name" value="<?= h((string)$item['name']) ?>" form="f<?= $mid ?>"></td>
                        <td><input class="price" name="price_s" value="<?= (int)$item['price_s'] ?>" form="f<?= $mid ?>"></td>
                        <td><input class="price" name="price_m" value="<?= (int)$item['price_m'] ?>" form="f<?= $mid ?>"></td>
                        <td><input class="price" name="price_l" value="<?= (int)$item['price_l'] ?>" form="f<?= $mid ?>"></td>
                        <td class="actions">
                            <form method="post" id="f<?= $mid ?>" style="display:inline;">
                                <input type="hidden" name="action" value="save_menu_item">
                                <input type="hidden" name="menu_id" value="<?= $mid ?>">
                                <button class="btn-save-mini" type="submit">保存</button>
                            </form>

                            <form method="post" style="display:inline;" onsubmit="return confirm('削除しますか？');">
                                <input type="hidden" name="action" value="delete_menu_item">
                                <input type="hidden" name="menu_id" value="<?= $mid ?>">
                                <button class="btn-delete" type="submit">削除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button class="btn-add" type="button" id="btn-add">＋ 追加</button>
        </section>

    </main>
</div>

<script>
(function () {
  const tbody = document.getElementById('menu-tbody');
  const btnAdd = document.getElementById('btn-add');

  btnAdd.addEventListener('click', function() {
    const uid = 'new_' + Date.now();
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="text" name="name" value="" form="${uid}"></td>
      <td><input class="price" name="price_s" value="" form="${uid}"></td>
      <td><input class="price" name="price_m" value="" form="${uid}"></td>
      <td><input class="price" name="price_l" value="" form="${uid}"></td>
      <td class="actions">
        <form method="post" id="${uid}" style="display:inline;">
          <input type="hidden" name="action" value="save_menu_item">
          <input type="hidden" name="menu_id" value="0">
          <button class="btn-save-mini" type="submit">保存</button>
        </form>
        <button class="btn-delete" type="button">削除</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  tbody.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-delete') && e.target.type === 'button') {
      e.target.closest('tr').remove();
    }
  });
})();
</script>

</body>
</html>
