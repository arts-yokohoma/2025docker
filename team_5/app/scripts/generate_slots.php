<?php
// CLI script to generate 30-minute time slots for shifts
// Usage: php generate_slots.php --start-date=2026-01-19 --days=7 --default-capacity=1

require_once __DIR__ . '/../db_config.php';

$opts = getopt('', ['start-date::', 'days::', 'default-capacity::']);
$days = isset($opts['days']) ? max(1, (int)$opts['days']) : 7;
$startDate = isset($opts['start-date']) ? new DateTime($opts['start-date']) : new DateTime();
$defaultCapacity = isset($opts['default-capacity']) ? (int)$opts['default-capacity'] : 1;

$insertSql = <<<SQL
INSERT INTO time_slots (shift_date, slot_start, slot_end, capacity, available)
VALUES (:shift_date, :slot_start, :slot_end, :capacity, :available)
ON CONFLICT (shift_date, slot_start, slot_end) DO NOTHING;
SQL;

$insertStmt = $pdo->prepare($insertSql);

$report = [];
for ($i = 0; $i < $days; $i++) {
    $d = clone $startDate;
    if ($i > 0) {
        $d->modify("+$i day");
    }
    $dateStr = $d->format('Y-m-d');

    // Fetch shift row
    $stmt = $pdo->prepare('SELECT * FROM shifts WHERE shift_date = :d LIMIT 1');
    $stmt->execute([':d' => $dateStr]);
    $shift = $stmt->fetch();

    if (!$shift) {
        // no shift defined for this date; optionally skip or use default
        $report[$dateStr] = ['generated' => 0, 'reason' => 'no shift'];
        continue;
    }

    $generated = 0;
    // iterate 30-minute slots 10:00..21:30
    $ts = new DateTime($dateStr . ' 10:00');
    $end = new DateTime($dateStr . ' 21:30');
    while ($ts <= $end) {
        $slotStart = $ts->format('H:i:s');
        $slotEndDT = clone $ts;
        $slotEndDT->modify('+30 minutes');
        $slotEnd = $slotEndDT->format('H:i:s');

        // determine capacity based on period
        $timeOnly = $ts->format('H:i:s');
        if ($timeOnly < '16:00:00') {
            $capacity = (int)(($shift['morning_kitchen'] ?? 0) + ($shift['morning_driver'] ?? 0));
        } else {
            $capacity = (int)(($shift['evening_kitchen'] ?? 0) + ($shift['evening_driver'] ?? 0));
        }
        if ($capacity <= 0) {
            $capacity = $defaultCapacity;
            $available = false;
        } else {
            $available = true;
        }

        $insertStmt->execute([
            ':shift_date' => $dateStr,
            ':slot_start' => $slotStart,
            ':slot_end' => $slotEnd,
            ':capacity' => $capacity,
            ':available' => $available,
        ]);
        $generated++;

        $ts->modify('+30 minutes');
    }

    $report[$dateStr] = ['generated' => $generated, 'capacity_sample' => $capacity];
}

// print report
foreach ($report as $d => $info) {
    echo "$d: generated={$info['generated']}";
    if (isset($info['reason'])) echo " reason={$info['reason']}";
    if (isset($info['capacity_sample'])) echo " sample_capacity={$info['capacity_sample']}";
    echo PHP_EOL;
}

exit(0);
