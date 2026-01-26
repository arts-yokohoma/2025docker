<?php
try {
    $pdo = new PDO(
        "pgsql:host=localhost;port=5432;dbname=team_4_db",
        "team_4",
        "team4pass",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
