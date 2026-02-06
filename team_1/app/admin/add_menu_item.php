<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/.././config/db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function toInt($v, int $default = 0): int {
    if ($v === null || $v === '') return $default;
    if (!is_numeric($v)) return $default;
    return (int)$v;
}

$flashErr = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $ps   = toInt($_POST['price_s'] ?? 0, 0);
    $pm   = toInt($_POST['price_m'] ?? 0, 0);
    $pl   = toInt($_POST['price_l'] ?? 0, 0);
    
    // Валидация
    if ($name === '') {
        $flashErr = '商品名を入力してください。';
    } else if ($ps === 0 && $pm === 0 && $pl === 0) {
        $flashErr = '少なくとも1つのサイズの価格を入力してください。';
    } else if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $flashErr = '画像をアップロードしてください。';
    } else {
        // Загрузка изображения
        $uploadDir = __DIR__ . '/../assets/image/menu/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowedExts)) {
            $flashErr = '無効な画像形式です。';
        } else {
            $active = isset($_POST['active']) ? 1 : 0;
            // Сначала создаем запись в БД
            $stmt = $mysqli->prepare("INSERT INTO menu (name, price_s, price_m, price_l, photo_path, active, deleted) VALUES (?, ?, ?, ?, '', ?, 0)");
            if (!$stmt) {
                $flashErr = 'エラーが発生しました。';
            } else {
                $stmt->bind_param("siiii", $name, $ps, $pm, $pl, $active);
                if (!$stmt->execute()) {
                    $flashErr = '保存に失敗しました。';
                } else {
                    $newMenuId = $stmt->insert_id;
                    $stmt->close();

                    // Сохраняем изображение с ID
                    $fileName = 'menu_' . $newMenuId . '_' . time() . '.' . $ext;
                    $filePath = $uploadDir . $fileName;
                    $relativePath = './assets/image/menu/' . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $filePath)) {
                        // Обновляем photo_path
                        $stmt = $mysqli->prepare("UPDATE menu SET photo_path=? WHERE id=?");
                        $stmt->bind_param("si", $relativePath, $newMenuId);
                        $stmt->execute();
                        $stmt->close();

                        header('Location: kanri.php?saved=menu');
                        exit;
                    } else {
                        // Удаляем запись если не удалось загрузить файл
                        $mysqli->query("DELETE FROM menu WHERE id=$newMenuId");
                        $flashErr = '画像のアップロードに失敗しました。';
                    }
                }
            }
        }
    }
}

$savedName = $_POST['name'] ?? '';
$savedPriceS = $_POST['price_s'] ?? '';
$savedPriceM = $_POST['price_m'] ?? '';
$savedPriceL = $_POST['price_l'] ?? '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>管理パネル | 新規商品追加</title>
<link rel="stylesheet" href="css/kanri.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<div class="layout">
    <div class="logo-section kanri-header">
        <img src="../assets/image/logo.png" alt="Pizza Mach" class="kanri-logo">
        <h2 class="logo" style="margin: 0;">Pizza Mach - 新規商品追加</h2>
        <a href="kanri.php" class="kanri-back">戻る</a>
        <a href="logout.php" class="logout-btn" style="text-decoration: none;">ログアウト</a>
    </div>

    <main class="content">
        <div class="page-header">
            <div>
                <h1 class="page-title">新規商品追加</h1>
                <p class="page-subtitle">Add New Menu Item</p>
            </div>
        </div>

        <?php if ($flashErr): ?>
            <div class="flash err">✕ <?= h($flashErr) ?></div>
        <?php endif; ?>

        <section class="card">
            <div class="card-header">
                <h2><span class="card-icon" style="background: var(--success-alpha); color: var(--success);">➕</span> 商品情報入力</h2>
            </div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">商品名 <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="name" id="name" value="<?= h($savedName) ?>" required maxlength="100" placeholder="例: マルゲリータ">
                </div>

                <div class="row">
                    <div class="form-group">
                        <label for="price_s">Sサイズ価格 (円)</label>
                        <input type="number" name="price_s" id="price_s" value="<?= h($savedPriceS) ?>" min="0" step="1" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="price_m">Mサイズ価格 (円)</label>
                        <input type="number" name="price_m" id="price_m" value="<?= h($savedPriceM) ?>" min="0" step="1" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="price_l">Lサイズ価格 (円)</label>
                        <input type="number" name="price_l" id="price_l" value="<?= h($savedPriceL) ?>" min="0" step="1" placeholder="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="photo">商品画像 <span style="color: var(--danger);">*</span></label>
                    <input type="file" name="photo" id="photo" accept="image/*" required>
                </div>

                <div class="form-group">
                    <div class="checkbox-item">
                        <input type="checkbox" name="active" id="active_new" checked>
                        <label for="active_new">公開する（チェックを外すと非公開になります）</label>
                    </div>
                </div>

                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 16px 0;">※ 価格は少なくとも1つのサイズを入力してください。</p>

                <div class="form-footer">
                    <a href="kanri.php" class="btn btn-cancel">キャンセル</a>
                    <button type="submit" class="btn btn-primary">商品を追加</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>
