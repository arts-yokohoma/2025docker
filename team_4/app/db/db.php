<?php
try {
    $host = "team_4_db"; 
    $db   = "team_4_db";
    $user = "team_4";
    $pass = "team4pass";

    $pdo = new PDO(
        "pgsql:host=$host;dbname=$db",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

} catch (PDOException $e) {
    die("Database connection error");
}
