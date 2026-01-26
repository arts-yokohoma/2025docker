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

    // After storing the shift counts, sync time_slots for this date.
    // 勤務区分: morning = 10:00–16:00, evening = 16:00–22:00
    try {
        $countsStmt = $pdo->prepare(
            "SELECT COALESCE(morning_driver,0) AS morning_driver, COALESCE(evening_driver,0) AS evening_driver\n" .
                "FROM shifts WHERE shift_date = :shift_date"
        );
        $countsStmt->execute([':shift_date' => $shift_date]);
        $counts = $countsStmt->fetch(PDO::FETCH_ASSOC) ?: ['morning_driver' => 0, 'evening_driver' => 0];

        $syncSql = <<<SQL
INSERT INTO time_slots (shift_date, slot_start, slot_end, capacity, available)
SELECT :shift_date::date AS shift_date,
       (gs)::time AS slot_start,
       (gs + interval '30 minutes')::time AS slot_end,
       :capacity::int AS capacity,
       (CASE
            WHEN :capacity::int <= 0 THEN FALSE
            ELSE (
                :capacity::int > (
                    SELECT COUNT(*)
                    FROM orders o
                    WHERE (o.created_at AT TIME ZONE 'Asia/Tokyo')::date = :shift_date::date
                      AND o.time_slot = (to_char((gs)::time, 'HH24:MI') || '-' || to_char((gs + interval '30 minutes')::time, 'HH24:MI'))
                )
            )
        END) AS available
FROM generate_series(
  (:shift_date::date + (:range_start)::time),
  (:shift_date::date + (:range_end)::time),
  interval '30 minutes'
) AS gs
ON CONFLICT (shift_date, slot_start, slot_end) DO UPDATE
  SET capacity = EXCLUDED.capacity,
      available = EXCLUDED.available;
SQL;

        $deleteSql = "DELETE FROM time_slots\n" .
            "WHERE shift_date = :shift_date\n" .
            "  AND slot_start >= (:range_start)::time\n" .
            "  AND slot_start <= (:range_end)::time";

        $syncStmt = $pdo->prepare($syncSql);
        $deleteStmt = $pdo->prepare($deleteSql);

        $morningDrivers = (int)($counts['morning_driver'] ?? 0);
        $eveningDrivers = (int)($counts['evening_driver'] ?? 0);

        if ($morningDrivers > 0) {
            $syncStmt->execute([
                ':shift_date' => $shift_date,
                ':range_start' => '10:00',
                ':range_end' => '15:30',
                ':capacity' => $morningDrivers,
            ]);
        } else {
            $deleteStmt->execute([
                ':shift_date' => $shift_date,
                ':range_start' => '10:00',
                ':range_end' => '15:30',
            ]);
        }

        if ($eveningDrivers > 0) {
            $syncStmt->execute([
                ':shift_date' => $shift_date,
                ':range_start' => '16:00',
                ':range_end' => '21:30',
                ':capacity' => $eveningDrivers,
            ]);
        } else {
            $deleteStmt->execute([
                ':shift_date' => $shift_date,
                ':range_start' => '16:00',
                ':range_end' => '21:30',
            ]);
        }

        // Cleanup any legacy slots outside 10:00–22:00.
        $pdo->prepare(
            "DELETE FROM time_slots WHERE shift_date = :shift_date AND (slot_start < time '10:00' OR slot_start > time '21:30')"
        )->execute([':shift_date' => $shift_date]);
    } catch (PDOException $e) {
        error_log('time_slots sync failed: ' . $e->getMessage());
        header('Location: shift.php?error=slots');
        exit;
    }

    header('Location: shift.php?success=1');
    exit;
} catch (PDOException $e) {
    // Log $e->getMessage() to your error log in production
    header('Location: shift.php?error=1');
    exit;
}
