<?php
// shift_process.php - handle shift form submission and save to PostgreSQL
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: shift.php');
    exit;
}

$shift_date = trim($_POST['shift_date'] ?? '');
$shift_period = trim($_POST['shift_period'] ?? 'morning');
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
    // Upsert: keep legacy kitchen_count/driver_count and also store per-period counts
    if ($shift_period === 'evening') {
        $sql = "INSERT INTO shifts (shift_date, kitchen_count, driver_count, evening_kitchen, evening_driver, created_at, updated_at)
                VALUES (:shift_date, :kitchen, :driver, :kitchen, :driver, NOW(), NOW())
                ON CONFLICT (shift_date) DO UPDATE
                  SET evening_kitchen = EXCLUDED.evening_kitchen,
                      evening_driver = EXCLUDED.evening_driver,
                      kitchen_count = EXCLUDED.kitchen_count,
                      driver_count = EXCLUDED.driver_count,
                      updated_at = NOW()";
    } else {
        $sql = "INSERT INTO shifts (shift_date, kitchen_count, driver_count, morning_kitchen, morning_driver, created_at, updated_at)
                VALUES (:shift_date, :kitchen, :driver, :kitchen, :driver, NOW(), NOW())
                ON CONFLICT (shift_date) DO UPDATE
                  SET morning_kitchen = EXCLUDED.morning_kitchen,
                      morning_driver = EXCLUDED.morning_driver,
                      kitchen_count = EXCLUDED.kitchen_count,
                      driver_count = EXCLUDED.driver_count,
                      updated_at = NOW()";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':shift_date' => $shift_date,
        ':kitchen' => $k,
        ':driver' => $d,
    ]);

    // After storing the shift counts, populate time_slots for this date
    try {
        $populateSql = <<<SQL
INSERT INTO time_slots (shift_date, slot_start, slot_end, capacity, available)
SELECT s.shift_date,
       (gs)::time AS slot_start,
       (gs + interval '30 minutes')::time AS slot_end,
       (CASE WHEN (gs::time) < time '16:00'
             THEN COALESCE(s.morning_kitchen,0) + COALESCE(s.morning_driver,0)
             ELSE COALESCE(s.evening_kitchen,0) + COALESCE(s.evening_driver,0)
        END) AS capacity,
       (CASE WHEN (CASE WHEN (gs::time) < time '16:00'
                        THEN COALESCE(s.morning_kitchen,0) + COALESCE(s.morning_driver,0)
                        ELSE COALESCE(s.evening_kitchen,0) + COALESCE(s.evening_driver,0)
                   END) > 0 THEN TRUE ELSE FALSE END) AS available
FROM shifts s
CROSS JOIN LATERAL generate_series(
  (s.shift_date + time '10:00'),
  (s.shift_date + time '21:30'),
  interval '30 minutes'
) AS gs
WHERE s.shift_date = :shift_date
ON CONFLICT (shift_date, slot_start, slot_end) DO NOTHING;
SQL;

        $pstmt = $pdo->prepare($populateSql);
        $pstmt->execute([':shift_date' => $shift_date]);
    } catch (PDOException $e) {
        // Non-fatal: slots generation failed, but shift was saved. Log if necessary.
    }

    header('Location: shift.php?success=1');
    exit;
} catch (PDOException $e) {
    // Log $e->getMessage() to your error log in production
    header('Location: shift.php?error=1');
    exit;
}
