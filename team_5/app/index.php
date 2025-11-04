<?php
echo "<h1>Team 5 - PHP + PostgreSQL</h1>";
echo "<p>Hello from Team 5!</p>";

try {
    $pdo = new PDO("pgsql:host=team_5_db;dbname=team_5_db", "team_5", "team5pass");
    echo "<p>PostgreSQL Connection successful!</p>";
} catch (PDOException $e) {
    echo "<p>PostgreSQL Connection failed: " . $e->getMessage() . "</p>";
}
?>
