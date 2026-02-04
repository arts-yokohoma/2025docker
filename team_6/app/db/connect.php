<?php
$host = getenv("DB_HOST") ?: "pizza-postgres";
$port = getenv("DB_PORT") ?: "5432";
$dbname = getenv("DB_NAME") ?: "team_6_db";
$user = getenv("DB_USER") ?: "team_6";
$pass = getenv("DB_PASS") ?: "team6pass";

try {
    $db = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}
