<?php
$dbPath = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create users table (runs only once)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password TEXT
        )
    ");

    // Create default admin user (only if not exists)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->execute() && $stmt->fetchColumn() == 0) {
        $hash = password_hash('admin', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)")
            ->execute(['admin', $hash]);
    }

} catch (PDOException $e) {
    die("Database error");
}
