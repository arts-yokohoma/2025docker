<?php
echo "<h1>Team 6 - PHP + PostgreSQL</h1>";
echo "<p>Hello from 00000000000000000sooo !</p>";

try {
    $pdo = new PDO("pgsql:host=team_6_db;dbname=team_6_db", "team_6", "team6pass");
    echo "<p>PostgreSQL Connection successful!</p>";
} catch (PDOException $e) {
    echo "<p>PostgreSQL Connection failed: " . $e->getMessage() . "</p>";
}
?>
