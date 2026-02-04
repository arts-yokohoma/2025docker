<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db/connect.php';

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

// =============================
// ADD / EDIT
// =============================
if ($action === 'add' || $action === 'edit') {

    $item = null;

    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $name = $_POST['name'];
        $price_s = $_POST['price_s'];
        $price_m = $_POST['price_m'];
        $price_l = $_POST['price_l'];
        $is_sold_out = isset($_POST['is_sold_out']) ? 1 : 0;
        $image = $_POST['current_image'] ?? '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "assets/images/";
            $image = time() . '_' . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
        }

        if ($action === 'edit') {
            $stmt = $db->prepare("
                UPDATE menu_items 
                SET name = ?, price_s = ?, price_m = ?, price_l = ?, image = ?, is_sold_out = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $price_s, $price_m, $price_l, $image, $is_sold_out, $id
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO menu_items (name, price_s, price_m, price_l, image, is_sold_out)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $price_s, $price_m, $price_l, $image, $is_sold_out
            ]);
        }

        header("Location: menu_admin.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?php echo $action === 'edit' ? "ピザ編集" : "新しいピザを追加"; ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
  <h1><?php echo $action === 'edit' ? "✏ ピザ編集" : "➕ 新しいピザを追加"; ?></h1>
</header>

<main>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="current_image" value="<?php echo $item['image'] ?? ''; ?>">

  <label>ピザ名:
    <input type="text" name="name" value="<?php echo $item['name'] ?? ''; ?>" required>
  </label>

  <label>小サイズ:
    <input type="number" name="price_s" value="<?php echo $item['price_s'] ?? ''; ?>" required>
  </label>

  <label>中サイズ:
    <input type="number" name="price_m" value="<?php echo $item['price_m'] ?? ''; ?>" required>
  </label>

  <label>大サイズ:
    <input type="number" name="price_l" value="<?php echo $item['price_l'] ?? ''; ?>" required>
  </label>

  <label>画像:
    <input type="file" name="image">
    <?php if (!empty($item['image'])): ?>
      <img src="assets/images/<?php echo $item['image']; ?>" width="80">
    <?php endif; ?>
  </label>

  <label>
    <input type="checkbox" name="is_sold_out" <?php if (!empty($item['is_sold_out'])) echo 'checked'; ?>>
    売り切れ
  </label>

  <button type="submit"><?php echo $action === 'edit' ? "更新" : "追加"; ?></button>
</form>
</main>

</body>
</html>
<?php
}

// =============================
// =============================
// DELETE
// =============================
if ($action === 'delete' && $id) {

    // 1. Get the image name to remove the file
    $stmt = $db->prepare("SELECT image FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item && !empty($item['image'])) {
        $imagePath = "assets/images/" . $item['image'];
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }
    }

    try {
        $db->beginTransaction();

        // 2. Delete from all "Child" tables first
        // Delete from sales
        $stmtSales = $db->prepare("DELETE FROM sales WHERE menu_item_id = ?");
        $stmtSales->execute([$id]);

        // Delete from order_items (The new error you just got)
        $stmtOrderItems = $db->prepare("DELETE FROM order_items WHERE menu_item_id = ?");
        $stmtOrderItems->execute([$id]);

        // 3. Finally, delete the "Parent" menu item
        $stmtMenu = $db->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmtMenu->execute([$id]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        die("Error deleting item: " . $e->getMessage());
    }

    header("Location: menu_admin.php");
    exit;
}

// =============================
// SOLD OUT TOGGLE
// =============================
if ($action === 'soldout' && $id) {

    $stmt = $db->prepare("
        UPDATE menu_items 
        SET is_sold_out = 1 - is_sold_out 
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    header("Location: menu_admin.php");
    exit;
}
?>
