<?php
// PostgreSQL configuration - update these with your credentials
$DB_HOST = '127.0.0.1';
$DB_PORT = '5432';
$DB_NAME = 'your_database';
$DB_USER = 'your_user';
$DB_PASS = 'your_password';

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
