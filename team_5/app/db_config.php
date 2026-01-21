<?php
// PostgreSQL configuration - defaults are the docker-compose service credentials
// You can override by setting environment variables (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS)
$DB_HOST = getenv('DB_HOST') ?: 'team_5_db';
$DB_PORT = getenv('DB_PORT') ?: '5432';
$DB_NAME = getenv('DB_NAME') ?: 'team_5_db';
$DB_USER = getenv('DB_USER') ?: 'team_5';
$DB_PASS = getenv('DB_PASS') ?: 'team5pass';

// DSN for PDO with pgsql driver
$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database connection failed.';
    exit;
}
