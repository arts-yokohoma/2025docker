<?php
// Connect to database
$pdo = new PDO("pgsql:host=team_4_db;dbname=team_4_db", "team_4", "team4pass");

// Create simple names table
$pdo->exec("CREATE TABLE IF NOT EXISTS names (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL
)");

echo "✅ Table 'names' is ready!";
?>
