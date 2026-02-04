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
    <div class="add-menu-header">
        <img src="../assets/image/logo.png" alt="Pizza Mach" class="add-menu-logo">
        <h2 class="logo" style="margin: 0;">Pizza Mach - 新規商品追加</h2>
        <a href="kanri.php" class="add-menu-back">戻る</a>
        <a href="logout.php" class="add-menu-logout">ログアウト</a>
    </div>

    <main class="content">
        <h1>新規商品追加</h1>

        <?php if ($flashErr): ?>
            <div class="flash err"><?= h($flashErr) ?></div>
        <?php endif; ?>

        <section class="card card-pizza-create">
            <form method="post" enctype="multipart/form-data" class="pizza-create-form">
                <div class="pizza-create-row">
                    <div class="pizza-field pizza-field-name">
                        <label for="name">商品名 <span class="required">*</span></label>
                        <input type="text" name="name" id="name" value="<?= h($savedName) ?>" required maxlength="100" placeholder="例: マルゲリータ">
                    </div>
                    <div class="pizza-field pizza-field-price">
                        <label for="price_s">S (円)</label>
                        <input type="number" name="price_s" id="price_s" value="<?= h($savedPriceS) ?>" min="0" step="1" placeholder="0">
                    </div>
                    <div class="pizza-field pizza-field-price">
                        <label for="price_m">M (円)</label>
                        <input type="number" name="price_m" id="price_m" value="<?= h($savedPriceM) ?>" min="0" step="1" placeholder="0">
                    </div>
                    <div class="pizza-field pizza-field-price">
                        <label for="price_l">L (円)</label>
                        <input type="number" name="price_l" id="price_l" value="<?= h($savedPriceL) ?>" min="0" step="1" placeholder="0">
                    </div>
                    <div class="pizza-field pizza-field-photo">
                        <label for="photo">画像 <span class="required">*</span></label>
                        <input type="file" name="photo" id="photo" accept="image/*" required class="input-file">
                    </div>
                    <div class="pizza-field pizza-field-active">
                        <label class="label-inline">表示</label>
                        <div class="checkbox-wrap">
                            <input type="checkbox" name="active" id="active_new" checked>
                            <label for="active_new">ON</label>
                        </div>
                    </div>
                </div>
                <p class="pizza-create-hint">価格は少なくとも1つ入力してください。</p>
                <div class="form-footer">
                    <a href="kanri.php" class="btn-cancel">キャンセル</a>
                    <button type="submit" class="btn-save">保存</button>
                </div>
            </form>
        </section>
    </main>
</div>

</body>
</html>
