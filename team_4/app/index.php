<?php
echo "<h1>Team 4 - PHP + PostgreSQL</h1>";
echo "<p>this is for MYTEAM!</p>";

// PostgreSQL connection test
try {
    $pdo = new PDO("pgsql:host=team_4_db;dbname=team_4_db", "team_4", "team4pass");
    echo "<p>PostgreSQL Connection successful!</p>";
    
    $stmt = $pdo->query("SELECT version();");
    $version = $stmt->fetch();
    echo "<p>Database: " . $version[0] . "</p>";
} catch (PDOException $e) {
    echo "<p>PostgreSQL Connection failed: " . $e->getMessage() . "</p>";
}
?>
