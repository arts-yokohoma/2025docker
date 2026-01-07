<?php
echo "<h1>Team 1 - PHP + MySQL</h1>";
echo "<p>Hello from Team 1!</p>";

// MySQL connection test
$mysqli = new mysqli("team_1_db", "team_1", "team1pass", "team_1_db");

if ($mysqli->connect_error) {
    echo "<p>MySQL Connection failed: " . $mysqli->connect_error . "</p>";
} else {
    echo "<p>MySQL Connection successful!</p>";
    echo "<p>Server time: " . date('Y-m-d H:i:s') . "</p>";
}
?>
