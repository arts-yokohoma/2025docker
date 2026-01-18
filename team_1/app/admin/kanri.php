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

    // Обязательные поля: время работы и last_order_offset_min
    if (!$open || !$close) {
        return [false, '営業時間が未入力です。'];
    }
    if (!($open < $close)) return [false, '営業時間が正しくありません。'];
    if ($offset < 0) return [false, 'ラストオーダー設定が正しくありません。'];

    // Смены опциональны, но если заполнены - проверяем
    $hasEarlyShift = $es && $ee;
    $hasLateShift = $ls && $le;

    if ($hasEarlyShift) {
        if (!($es < $ee)) return [false, '早番の時間が正しくありません。'];
        if (!($open <= $es && $ee <= $close)) return [false, '早番は営業時間内に設定してください。'];
    }

    if ($hasLateShift) {
        if (!($ls < $le)) return [false, '遅番の時間が正しくありません。'];
        if (!($open <= $ls && $le <= $close)) return [false, '遅番は営業時間内に設定してください。'];
    }

    // Если обе смены заполнены, проверяем что они не пересекаются
    if ($hasEarlyShift && $hasLateShift) {
        if (!($ee <= $ls)) return [false, '早番の終了は遅番の開始以前にしてください。'];
    }

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
            ];

            if (!$data['open_time'] || !$data['close_time']) {
                $flashErr = '営業時間が未入力です。';
            } else if (!($data['open_time'] < $data['close_time'])) {
                $flashErr = '営業時間が正しくありません。';
            } else if ($data['last_order_offset_min'] < 0) {
                $flashErr = 'ラストオーダー設定が正しくありません。';
            } else {
                // Получаем текущие значения смен из БД
                $res = $mysqli->query("SELECT early_shift_start, early_shift_end, late_shift_start, late_shift_end 
                                       FROM store_hours WHERE id=1 AND active=1 LIMIT 1");
                $currentShifts = ['early_shift_start' => null, 'early_shift_end' => null, 
                                  'late_shift_start' => null, 'late_shift_end' => null];
                if ($res && $row = $res->fetch_assoc()) {
                    $currentShifts = $row;
                    $res->free();
                }

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
                      active = 1,
                      update_time = CURRENT_TIMESTAMP
                ";

                $stmt = $mysqli->prepare($sql);
                if (!$stmt) throw new RuntimeException('prepare failed');

                $stmt->bind_param(
                    "ssissss",
                    $data['open_time'],
                    $data['close_time'],
                    $data['last_order_offset_min'],
                    $currentShifts['early_shift_start'],
                    $currentShifts['early_shift_end'],
                    $currentShifts['late_shift_start'],
                    $currentShifts['late_shift_end']
                );

                if (!$stmt->execute()) throw new RuntimeException('execute failed');
                $stmt->close();

                $flashOk = '営業時間を保存しました。';
                // Перенаправляем для показа сообщения
                header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=store_hours');
                exit;
            }
        }

        if ($action === 'save_shifts') {
            $data = [
                'early_shift_start' => toTimeOrNull($_POST['early_shift_start'] ?? null),
                'early_shift_end' => toTimeOrNull($_POST['early_shift_end'] ?? null),
                'late_shift_start' => toTimeOrNull($_POST['late_shift_start'] ?? null),
                'late_shift_end' => toTimeOrNull($_POST['late_shift_end'] ?? null),
            ];

            // Получаем текущие значения времени работы из БД
            $res = $mysqli->query("SELECT open_time, close_time, last_order_offset_min 
                                   FROM store_hours WHERE id=1 AND active=1 LIMIT 1");
            $currentHours = ['open_time' => '11:00:00', 'close_time' => '22:00:00', 'last_order_offset_min' => 30];
            if ($res && $row = $res->fetch_assoc()) {
                $currentHours = $row;
                $res->free();
            }

            // Валидация смен
            $hasEarlyShift = $data['early_shift_start'] && $data['early_shift_end'];
            $hasLateShift = $data['late_shift_start'] && $data['late_shift_end'];
            $err = null;

            if ($hasEarlyShift) {
                if (!($data['early_shift_start'] < $data['early_shift_end'])) {
                    $err = '早番の時間が正しくありません。';
                } else if (!($currentHours['open_time'] <= $data['early_shift_start'] && $data['early_shift_end'] <= $currentHours['close_time'])) {
                    $err = '早番は営業時間内に設定してください。';
                }
            }

            if (!$err && $hasLateShift) {
                if (!($data['late_shift_start'] < $data['late_shift_end'])) {
                    $err = '遅番の時間が正しくありません。';
                } else if (!($currentHours['open_time'] <= $data['late_shift_start'] && $data['late_shift_end'] <= $currentHours['close_time'])) {
                    $err = '遅番は営業時間内に設定してください。';
                }
            }

            if (!$err && $hasEarlyShift && $hasLateShift) {
                if (!($data['early_shift_end'] <= $data['late_shift_start'])) {
                    $err = '早番の終了は遅番の開始以前にしてください。';
                }
            }

            if ($err) {
                $flashErr = $err;
            } else {
                $sql = "
                    INSERT INTO store_hours
                      (id, open_time, close_time, last_order_offset_min,
                       early_shift_start, early_shift_end, late_shift_start, late_shift_end,
                       active, update_time)
                    VALUES
                      (1, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE
                      early_shift_start = VALUES(early_shift_start),
                      early_shift_end = VALUES(early_shift_end),
                      late_shift_start = VALUES(late_shift_start),
                      late_shift_end = VALUES(late_shift_end),
                      active = 1,
                      update_time = CURRENT_TIMESTAMP
                ";

                $stmt = $mysqli->prepare($sql);
                if (!$stmt) throw new RuntimeException('prepare failed');

                $stmt->bind_param(
                    "ssissss",
                    $currentHours['open_time'],
                    $currentHours['close_time'],
                    $currentHours['last_order_offset_min'],
                    $data['early_shift_start'],
                    $data['early_shift_end'],
                    $data['late_shift_start'],
                    $data['late_shift_end']
                );

                if (!$stmt->execute()) throw new RuntimeException('execute failed');
                $stmt->close();

                $flashOk = 'シフト時間を保存しました。';
                // Перенаправляем для показа сообщения
                header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=shifts');
                exit;
            }
        }

        if ($action === 'upload_image') {
            $menuId = toInt($_POST['menu_id'] ?? 0, 0);
            if ($menuId > 0 && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/image/menu/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $file = $_FILES['image'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($ext, $allowedExts)) {
                    $flashErr = '無効な画像形式です。';
                } else {
                    // Удаляем старое изображение, если оно не дефолтное
                    $stmt = $mysqli->prepare("SELECT photo_path FROM menu WHERE id=? LIMIT 1");
                    if (!$stmt) throw new RuntimeException('prepare failed');
                    $stmt->bind_param("i", $menuId);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $row = $res->fetch_assoc()) {
                        $oldPath = $row['photo_path'];
                        if ($oldPath && $oldPath !== './assets/image/menu/photopizza.jpg' && file_exists(__DIR__ . '/..' . ltrim($oldPath, '.'))) {
                            @unlink(__DIR__ . '/..' . ltrim($oldPath, '.'));
                        }
                    }
                    $stmt->close();

                    $fileName = 'menu_' . $menuId . '_' . time() . '.' . $ext;
                    $filePath = $uploadDir . $fileName;
                    // Путь относительно корня приложения (для использования из index.php, cart.php и т.д.)
                    $relativePath = './assets/image/menu/' . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        // Обновляем photo_path в БД
                        $stmt = $mysqli->prepare("UPDATE menu SET photo_path=? WHERE id=?");
                        if ($stmt) {
                            $stmt->bind_param("si", $relativePath, $menuId);
                            $stmt->execute();
                            $stmt->close();
                        }
                        $flashOk = '画像をアップロードしました。';
                        // Перенаправляем на ту же страницу, чтобы показать обновленное изображение
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?uploaded=1');
                        exit;
                    } else {
                        $flashErr = '画像のアップロードに失敗しました。';
                    }
                }
            } else {
                $flashErr = '画像を選択してください。';
            }
        }

        if ($action === 'save_menu_item') {
            $id   = toInt($_POST['menu_id'] ?? 0, 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $ps   = toInt($_POST['price_s'] ?? 0, 0);
            $pm   = toInt($_POST['price_m'] ?? 0, 0);
            $pl   = toInt($_POST['price_l'] ?? 0, 0);
            $photoPath = trim((string)($_POST['photo_path'] ?? ''));

            // Валидация
            if ($name === '') {
                $flashErr = '商品名を入力してください。';
            } else if (mb_strlen($name) > 100) {
                $flashErr = '商品名は100文字以内で入力してください。';
            } else if ($ps < 0 || $pm < 0 || $pl < 0) {
                $flashErr = '価格は0以上で入力してください。';
            } else if ($ps === 0 && $pm === 0 && $pl === 0) {
                $flashErr = '少なくとも1つのサイズの価格を入力してください。';
            } else {
                try {
                    if ($id > 0) {
                        // Обновление существующего товара
                        $active = isset($_POST['active']) && $_POST['active'] === '1' ? 1 : 0;
                        if ($photoPath) {
                            $stmt = $mysqli->prepare("UPDATE menu SET name=?, price_s=?, price_m=?, price_l=?, photo_path=?, active=? WHERE id=?");
                            if (!$stmt) throw new RuntimeException('SQL prepare failed: ' . $mysqli->error);
                            $stmt->bind_param("siiisii", $name, $ps, $pm, $pl, $photoPath, $active, $id);
                        } else {
                            $stmt = $mysqli->prepare("UPDATE menu SET name=?, price_s=?, price_m=?, price_l=?, active=? WHERE id=?");
                            if (!$stmt) throw new RuntimeException('SQL prepare failed: ' . $mysqli->error);
                            $stmt->bind_param("siiiii", $name, $ps, $pm, $pl, $active, $id);
                        }
                        if (!$stmt->execute()) {
                            throw new RuntimeException('SQL execute failed: ' . $stmt->error);
                        }
                        $stmt->close();
                    } else {
                        // Создание нового товара
                        // photo_path может быть пустым (NULL)
                        $photoPathForInsert = $photoPath ? $photoPath : null;
                        $stmt = $mysqli->prepare("INSERT INTO menu (name, price_s, price_m, price_l, photo_path, active)
                                                  VALUES (?, ?, ?, ?, ?, 1)");
                        if (!$stmt) throw new RuntimeException('SQL prepare failed: ' . $mysqli->error);
                        $stmt->bind_param("siiis", $name, $ps, $pm, $pl, $photoPathForInsert);
                        if (!$stmt->execute()) {
                            throw new RuntimeException('SQL execute failed: ' . $stmt->error);
                        }
                        $stmt->close();
                    }
                    $flashOk = '商品を保存しました。';
                    // Перенаправляем для показа сообщения
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=menu');
                    exit;
                } catch (Throwable $e) {
                    $flashErr = '保存に失敗しました: ' . htmlspecialchars($e->getMessage());
                }
            }
        }

        if ($action === 'delete_menu_item') {
            $id = toInt($_POST['menu_id'] ?? 0, 0);
            if ($id > 0) {
                // soft delete через deleted
                $stmt = $mysqli->prepare("UPDATE menu SET deleted=1 WHERE id=?");
                if (!$stmt) throw new RuntimeException('prepare failed: ' . $mysqli->error);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) throw new RuntimeException('execute failed: ' . $stmt->error);
                $stmt->close();
            }
            $flashOk = '商品を削除しました。';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=menu');
            exit;
        }

        if ($action === 'update_menu_status') {
            $id = toInt($_POST['menu_id'] ?? 0, 0);
            $active = isset($_POST['active']) ? 1 : 0;
            $deleted = isset($_POST['deleted']) ? 1 : 0;
            
            if ($id > 0) {
                $stmt = $mysqli->prepare("UPDATE menu SET active=?, deleted=? WHERE id=?");
                if (!$stmt) throw new RuntimeException('prepare failed: ' . $mysqli->error);
                $stmt->bind_param("iii", $active, $deleted, $id);
                if (!$stmt->execute()) throw new RuntimeException('execute failed: ' . $stmt->error);
                $stmt->close();
            }
            $flashOk = 'ステータスを更新しました。';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?saved=menu');
            exit;
        }

    } catch (Throwable $e) {
        $flashErr = 'エラーが発生しました: ' . htmlspecialchars($e->getMessage());
    }
}

// --- Load data for display ---
// Дефолтные значения используются только если в БД нет записи
$store = [
    'open_time' => '11:00',
    'close_time' => '22:00',
    'last_order_offset_min' => 30,
    'early_shift_start' => null,
    'early_shift_end' => null,
    'late_shift_start' => null,
    'late_shift_end' => null,
];

// store_hours row - загружаем из БД
$res = $mysqli->query("SELECT open_time, close_time, last_order_offset_min,
                              early_shift_start, early_shift_end, late_shift_start, late_shift_end
                       FROM store_hours WHERE id=1 AND active=1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    // Если запись найдена, используем значения из БД (включая NULL)
    foreach ($row as $k => $v) {
        if ($k === 'last_order_offset_min') {
            $store[$k] = $v !== null ? (int)$v : 30; // Дефолт только если NULL
            continue;
        }
        // Для времени: если NULL, оставляем null (пустое поле), иначе форматируем
        if ($v !== null) {
            $store[$k] = substr((string)$v, 0, 5); // HH:MM for input[type=time]
        } else {
            $store[$k] = null; // Явно устанавливаем null для пустых полей
        }
    }
    $res->free();
}
// Если записи нет в БД, остаются дефолтные значения выше

// menu list - показываем только не удаленные (deleted=0)
$menuItems = [];
$res = $mysqli->query("SELECT id, name, price_s, price_m, price_l, photo_path, active, deleted FROM menu WHERE deleted=0 ORDER BY id ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $menuItems[] = $r;
    }
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
    <h2 class="logo">管理パネル</h2>

    <main class="content">
        <h1>設定</h1>

        <?php 
        // Показываем сообщения об успехе из GET параметров (после редиректа)
        if (isset($_GET['saved'])) {
            $savedType = $_GET['saved'];
            if ($savedType === 'store_hours') {
                echo '<div class="flash ok">営業時間を保存しました。</div>';
            } elseif ($savedType === 'shifts') {
                echo '<div class="flash ok">シフト時間を保存しました。</div>';
            } elseif ($savedType === 'menu') {
                echo '<div class="flash ok">商品を保存しました。</div>';
                echo '<script>setTimeout(function(){ alert("商品を保存しました。"); }, 100);</script>';
            }
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="flash ok">商品を削除しました。</div>';
            echo '<script>setTimeout(function(){ alert("商品を削除しました。"); }, 100);</script>';
        }
        if (isset($_GET['uploaded'])) {
            echo '<div class="flash ok">画像をアップロードしました。</div>';
            echo '<script>setTimeout(function(){ alert("画像をアップロードしました。"); }, 100);</script>';
        }
        // Показываем ошибки, если есть
        if ($flashErr): ?>
            <div class="flash err"><?= h($flashErr) ?></div>
        <?php endif; ?>

        <!-- 営業時間 -->
        <section class="card">
            <h2>営業時間・ラストオーダー設定</h2>

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

                <div class="form-footer">
                    <button class="btn-save" type="submit">保存</button>
                </div>
            </form>
        </section>

        <!-- シフト時間 -->
        <section class="card">
            <h2>シフト時間設定</h2>

            <form method="post">
                <input type="hidden" name="action" value="save_shifts">

                <h3>🌞 早番（任意）</h3>
                <div class="row">
                    <div>
                        <label>から</label>
                        <input type="time" name="early_shift_start" value="<?= h($store['early_shift_start'] ?? '') ?>">
                    </div>
                    <div>
                        <label>まで</label>
                        <input type="time" name="early_shift_end" value="<?= h($store['early_shift_end'] ?? '') ?>">
                    </div>
                </div>

                <h3>🌙 遅番（任意）</h3>
                <div class="row">
                    <div>
                        <label>から</label>
                        <input type="time" name="late_shift_start" value="<?= h($store['late_shift_start'] ?? '') ?>">
                    </div>
                    <div>
                        <label>まで</label>
                        <input type="time" name="late_shift_end" value="<?= h($store['late_shift_end'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-footer">
                    <button class="btn-save" type="submit">保存</button>
                </div>
            </form>
        </section>

        <!-- 商品設定 -->
        <section class="card">
            <h2>商品設定</h2>

            <table>
                <thead>
                    <tr>
                        <th>商品</th>
                        <th>Sサイズ</th>
                        <th>Mサイズ</th>
                        <th>Lサイズ</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="menu-tbody">
                    <?php foreach ($menuItems as $item): $mid = (int)$item['id']; 
                        // Добавляем timestamp для обхода кэша при обновлении изображения
                        $photoPath = (string)($item['photo_path'] ?? '');
                        $photoPathWithCache = $photoPath ? ($photoPath . (strpos($photoPath, '?') !== false ? '&' : '?') . 't=' . time()) : '';
                    ?>
                    <tr data-menu-id="<?= $mid ?>">
                        <td style="min-width: 250px;">
                            <input type="text" name="name" value="<?= h((string)$item['name']) ?>" form="f<?= $mid ?>" readonly style="font-size: 18px; width: 100%; margin-bottom: 12px;">
                            <input type="hidden" name="photo_path" value="<?= h($photoPath) ?>" form="f<?= $mid ?>" id="photo_path_<?= $mid ?>">
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="active" id="active_<?= $mid ?>" form="f<?= $mid ?>" 
                                           <?= ((int)($item['active'] ?? 1)) ? 'checked' : '' ?> 
                                           disabled style="cursor: not-allowed;">
                                    <label for="active_<?= $mid ?>" style="cursor: default;">表示</label>
                                </div>
                            </div>
                        </td>
                        <td><input class="price" type="number" name="price_s" value="<?= (int)$item['price_s'] ?>" form="f<?= $mid ?>" readonly min="0" step="1"></td>
                        <td><input class="price" type="number" name="price_m" value="<?= (int)$item['price_m'] ?>" form="f<?= $mid ?>" readonly min="0" step="1"></td>
                        <td><input class="price" type="number" name="price_l" value="<?= (int)$item['price_l'] ?>" form="f<?= $mid ?>" readonly min="0" step="1"></td>
                        <td class="actions">
                            <button class="btn-edit" type="button" onclick="editItem(<?= $mid ?>)">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                </svg>
                                <span class="btn-text">編集</span>
                            </button>
                            <form method="post" id="f<?= $mid ?>" style="display:none;">
                                <input type="hidden" name="action" value="save_menu_item">
                                <input type="hidden" name="menu_id" value="<?= $mid ?>">
                            </form>
                            <form method="post" id="status_<?= $mid ?>" style="display:none;">
                                <input type="hidden" name="action" value="update_menu_status">
                                <input type="hidden" name="menu_id" value="<?= $mid ?>">
                            </form>
                            <button class="btn-upload" type="button" onclick="openUploadModal(<?= $mid ?>, '<?= h($photoPath) ?>', <?= time() ?>)">
                                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                                    <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm40-80h480L570-480 450-320l-90-120-120 160Zm-40 80v-560 560Z"/>
                                </svg>
                                <span class="btn-text">画像</span>
                            </button>
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

            <a href="add_menu_item.php" class="btn-add" style="text-decoration: none; display: inline-block;">＋ 追加</a>
        </section>

    </main>
</div>

<!-- Модалка для загрузки изображения -->
<div id="upload-modal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="modal-close" onclick="closeUploadModal()">&times;</span>
        <h2>画像をアップロード</h2>
        <div class="image-preview-container">
            <div class="current-image">
                <h3>現在の画像</h3>
                <div id="current-image-wrapper">
                    <img id="current-image-preview" src="" alt="現在の画像" class="preview-image" style="display:none;">
                    <p id="no-current-image" style="color: var(--muted); font-size: 13px; margin: 8px 0; padding: 20px; text-align: center; background: #f5f5f5; border-radius: 8px;">画像がありません</p>
                </div>
            </div>
            <div class="new-image">
                <h3>新しい画像（プレビュー）</h3>
                <img id="new-image-preview" src="" alt="新しい画像" class="preview-image" style="display:none;">
                <p id="no-preview" style="color: var(--muted); font-size: 13px; margin: 8px 0;">画像を選択してください</p>
            </div>
        </div>
        <form method="post" enctype="multipart/form-data" id="upload-form" onsubmit="handleImageUpload(event)">
            <input type="hidden" name="action" value="upload_image">
            <input type="hidden" name="menu_id" id="upload-menu-id">
            <label style="display: block; margin: 16px 0;">
                <input type="file" name="image" accept="image/*" required id="image-input" style="width: 100%; padding: 8px; margin: 8px 0;">
            </label>
            <div class="form-footer">
                <button type="button" onclick="closeUploadModal()">キャンセル</button>
                <button type="submit" class="btn-save">アップロード</button>
            </div>
        </form>
    </div>
</div>

<script>
let editingItemId = null;

function editItem(menuId) {
    const tr = document.querySelector(`tr[data-menu-id="${menuId}"]`);
    if (!tr) return;

    if (editingItemId === menuId) {
        // Сохраняем изменения через форму
        const form = document.getElementById(`f${menuId}`);
        if (!form) {
            console.error('Form not found for menu ID:', menuId);
            return;
        }
        
        // Очищаем форму от старых динамически добавленных полей
        const existingFields = form.querySelectorAll('input[type="hidden"][name="name"], input[type="hidden"][name="price_s"], input[type="hidden"][name="price_m"], input[type="hidden"][name="price_l"], input[type="hidden"][name="photo_path"]');
        existingFields.forEach(field => {
            if (field.id !== `photo_path_${menuId}`) {
                field.remove();
            }
        });
        
        const nameInput = tr.querySelector('input[name="name"]');
        const priceSInput = tr.querySelector('input[name="price_s"]');
        const priceMInput = tr.querySelector('input[name="price_m"]');
        const priceLInput = tr.querySelector('input[name="price_l"]');
        const photoPathInput = document.getElementById(`photo_path_${menuId}`);
        
        if (!nameInput || !priceSInput || !priceMInput || !priceLInput) {
            console.error('Input fields not found');
            alert('入力フィールドが見つかりません');
            return;
        }
        
        // Валидация на клиенте
        const name = nameInput.value.trim();
        if (!name) {
            alert('商品名を入力してください');
            return;
        }
        
        // Создаем скрытые поля в форме
        const nameField = document.createElement('input');
        nameField.type = 'hidden';
        nameField.name = 'name';
        nameField.value = name;
        form.appendChild(nameField);
        
        const priceSField = document.createElement('input');
        priceSField.type = 'hidden';
        priceSField.name = 'price_s';
        priceSField.value = parseInt(priceSInput.value) || 0;
        form.appendChild(priceSField);
        
        const priceMField = document.createElement('input');
        priceMField.type = 'hidden';
        priceMField.name = 'price_m';
        priceMField.value = parseInt(priceMInput.value) || 0;
        form.appendChild(priceMField);
        
        const priceLField = document.createElement('input');
        priceLField.type = 'hidden';
        priceLField.name = 'price_l';
        priceLField.value = parseInt(priceLInput.value) || 0;
        form.appendChild(priceLField);
        
        if (photoPathInput && photoPathInput.value) {
            const photoPathField = document.createElement('input');
            photoPathField.type = 'hidden';
            photoPathField.name = 'photo_path';
            photoPathField.value = photoPathInput.value.trim();
            form.appendChild(photoPathField);
        }
        
        // Сохраняем чекбокс active
        const activeCheckbox = tr.querySelector('input[name="active"]');
        if (activeCheckbox) {
            const activeField = document.createElement('input');
            activeField.type = 'hidden';
            activeField.name = 'active';
            activeField.value = activeCheckbox.checked ? '1' : '0';
            form.appendChild(activeField);
        }
        
        form.submit();
    } else {
        // Включаем режим редактирования
        if (editingItemId) {
            cancelEdit(editingItemId);
        }
        editingItemId = menuId;
        
        tr.querySelectorAll('input[readonly]').forEach(input => {
            input.removeAttribute('readonly');
            input.style.background = '#fff';
        });
        // Включаем чекбокс active для редактирования
        const activeCheckbox = tr.querySelector('input[name="active"]');
        if (activeCheckbox) {
            activeCheckbox.removeAttribute('disabled');
            activeCheckbox.style.cursor = 'pointer';
        }
        const editBtn = tr.querySelector('.btn-edit');
        const btnText = editBtn ? editBtn.querySelector('.btn-text') : null;
        if (btnText) {
            btnText.textContent = '保存';
        } else if (editBtn) {
            editBtn.textContent = '保存';
        }
    }
}

function cancelEdit(menuId) {
    const tr = document.querySelector(`tr[data-menu-id="${menuId}"]`);
    if (!tr) return;
    
        tr.querySelectorAll('input').forEach(input => {
            if (input.type !== 'checkbox') {
                input.setAttribute('readonly', 'readonly');
                input.style.background = '#f5f5f5';
            } else {
                // Для чекбокса используем disabled вместо readonly
                input.setAttribute('disabled', 'disabled');
                input.style.cursor = 'not-allowed';
            }
        });
        const editBtn = tr.querySelector('.btn-edit');
        const btnText = editBtn ? editBtn.querySelector('.btn-text') : null;
        if (btnText) {
            btnText.textContent = '編集';
        } else if (editBtn) {
            editBtn.textContent = '編集';
        }
        editingItemId = null;
}

function openUploadModal(menuId, currentImagePath, timestamp) {
    const modal = document.getElementById('upload-modal');
    const menuIdInput = document.getElementById('upload-menu-id');
    const currentPreview = document.getElementById('current-image-preview');
    const noCurrentImage = document.getElementById('no-current-image');
    const newPreview = document.getElementById('new-image-preview');
    const noPreview = document.getElementById('no-preview');
    const imageInput = document.getElementById('image-input');
    
    if (!modal || !menuIdInput) {
        console.error('Modal or menu ID input not found!');
        return;
    }
    
    menuIdInput.value = menuId;
    
    // Показываем текущее изображение только если оно есть
    // Исправляем путь: в БД путь относительно корня приложения (./assets/),
    // но в админке нужно использовать ../assets/
    if (currentImagePath && currentImagePath.trim() !== '') {
        let imagePath = currentImagePath;
        // Если путь начинается с './assets/', заменяем на '../assets/' для админки
        if (imagePath.startsWith('./assets/')) {
            imagePath = '../' + imagePath.substring(2);
        }
        const separator = imagePath.includes('?') ? '&' : '?';
        currentPreview.src = imagePath + separator + 't=' + timestamp;
        currentPreview.style.display = 'block';
        noCurrentImage.style.display = 'none';
    } else {
        currentPreview.style.display = 'none';
        noCurrentImage.style.display = 'block';
    }
    
    newPreview.style.display = 'none';
    noPreview.style.display = 'block';
    imageInput.value = '';
    
    modal.style.display = 'block';
    
    // Превью нового изображения - используем один обработчик
    imageInput.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                newPreview.src = e.target.result;
                newPreview.style.display = 'block';
                noPreview.style.display = 'none';
            };
            reader.readAsDataURL(file);
        } else {
            newPreview.style.display = 'none';
            noPreview.style.display = 'block';
        }
    };
}

function closeUploadModal() {
    const modal = document.getElementById('upload-modal');
    const form = document.getElementById('upload-form');
    const newPreview = document.getElementById('new-image-preview');
    const noPreview = document.getElementById('no-preview');
    
    modal.style.display = 'none';
    form.reset();
    newPreview.style.display = 'none';
    noPreview.style.display = 'block';
}

// Закрытие модалки при клике вне её
window.onclick = function(event) {
    const modal = document.getElementById('upload-modal');
    if (event.target === modal) {
        closeUploadModal();
    }
}

function handleImageUpload(event) {
    return true;
}
</script>

</body>
</html>
