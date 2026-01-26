<?php

// Database configuration for team_4_db
$host = "localhost";
$port = "5432";
$dbname = "team_4_db";
$user = "team_4"; // 
$password = "team4pass"; 

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optional: Test connection
    // $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    // If connection fails, try with postgres user
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", "postgres", "postgres");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch(PDOException $e2) {
        die("Database connection failed: " . $e2->getMessage());
    }
}
?>