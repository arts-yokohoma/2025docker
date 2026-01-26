<?php
// Lightweight connection tester for debugging. Run from project folder:
// php try_connect.php

// If you see "could not find driver", your PHP runtime is missing the PostgreSQL PDO driver.
// - If you're using Docker Compose, run this inside the `team_5_app` container.
// - If you're running PHP on Windows locally, enable/install `pdo_pgsql` (and `pgsql`) in php.ini.

if (!class_exists('PDO')) {
    echo "ERROR: PDO is not available in this PHP runtime.\n";
    exit(1);
}

$drivers = PDO::getAvailableDrivers();
if (!in_array('pgsql', $drivers, true)) {
    echo "ERROR: Missing PDO driver 'pgsql'. Available drivers: " . implode(', ', $drivers) . "\n";
    echo "\n";
    echo "If using Docker Compose, try:\n";
    echo "  docker compose exec team_5_app php -m | grep -i pgsql\n";
    echo "  docker compose exec team_5_app php /var/www/html/try_connect.php\n";
    echo "\n";
    echo "If running PHP on Windows locally, enable these in php.ini (then restart PHP/Apache):\n";
    echo "  extension=pdo_pgsql\n";
    echo "  extension=pgsql\n";
    exit(1);
}

$DB_HOST = getenv('DB_HOST') ?: 'team_5_db';
$DB_PORT = getenv('DB_PORT') ?: '5432';
$DB_NAME = getenv('DB_NAME') ?: 'team_5_db';
$DB_USER = getenv('DB_USER') ?: 'team_5';
$DB_PASS = getenv('DB_PASS') ?: 'team5pass';

$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME}";
try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "OK: connected to {$DB_NAME} at {$DB_HOST}:{$DB_PORT}\n";
    exit(0);
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
