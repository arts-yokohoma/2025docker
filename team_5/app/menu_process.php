<?php
// menu_process.php - validate menu prices and upsert into PostgreSQL
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: menu.php');
    exit;
}

$sizeS = trim($_POST['sizeS'] ?? '');
$sizeM = trim($_POST['sizeM'] ?? '');
$sizeL = trim($_POST['sizeL'] ?? '');

$errors = [];
foreach (['sizeS' => $sizeS, 'sizeM' => $sizeM, 'sizeL' => $sizeL] as $k => $v) {
    if ($v === '' || !is_numeric($v) || floatval($v) < 0) {
        $errors[] = $k;
    }
}

if (!empty($errors)) {
    header('Location: menu.php?error=validation');
    exit;
}

$s = number_format((float)$sizeS, 2, '.', '');
$m = number_format((float)$sizeM, 2, '.', '');
$l = number_format((float)$sizeL, 2, '.', '');

try {
    $sql = "INSERT INTO menu_prices (id, size_s, size_m, size_l, updated_at)
            VALUES (1, :s, :m, :l, NOW())
            ON CONFLICT (id) DO UPDATE SET
                size_s = EXCLUDED.size_s,
                size_m = EXCLUDED.size_m,
                size_l = EXCLUDED.size_l,
                updated_at = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':s' => $s, ':m' => $m, ':l' => $l]);

    header('Location: menu.php?success=1');
    exit;
} catch (PDOException $e) {
    // log error in production
    header('Location: menu.php?error=1');
    exit;
}
