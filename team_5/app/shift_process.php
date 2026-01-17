<?php
// shift_process.php - handle shift form submission and save to PostgreSQL
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shift.php');
    exit;
}

$shift_date = trim($_POST['shift_date'] ?? '');
$kitchen_count = trim($_POST['kitchen_count'] ?? '');
$driver_count = trim($_POST['driver_count'] ?? '');

$errors = [];
if ($shift_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $shift_date)) {
    $errors[] = 'date';
}

$k = filter_var($kitchen_count, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$d = filter_var($driver_count, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($k === false) {
    $errors[] = 'kitchen_count';
}
if ($d === false) {
    $errors[] = 'driver_count';
}

if (!empty($errors)) {
    header('Location: shift.php?error=validation');
    exit;
}

try {
    // Upsert: insert or update when shift_date already exists
    $sql = "INSERT INTO shifts (shift_date, kitchen_count, driver_count, created_at, updated_at)
            VALUES (:shift_date, :kitchen, :driver, NOW(), NOW())
            ON CONFLICT (shift_date) DO UPDATE
              SET kitchen_count = EXCLUDED.kitchen_count,
                  driver_count = EXCLUDED.driver_count,
                  updated_at = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':shift_date' => $shift_date,
        ':kitchen' => $k,
        ':driver' => $d,
    ]);

    header('Location: shift.php?success=1');
    exit;
} catch (PDOException $e) {
    // Log $e->getMessage() to your error log in production
    header('Location: shift.php?error=1');
    exit;
}
