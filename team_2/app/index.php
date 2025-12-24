<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="logo.png" type="image/x-icon">
        <title>ピザマック</title>
        </head>
        <body>
            <header>
                <h1>ピザマックへようこそ！</h1>
            </header>
        </body>
</html>
<?php
echo "<h1>Team 2 - PHP + MySQL</h1>";
echo "<p>Hello from Team 2!</p>";

// MySQL connection test
$mysqli = new mysqli("team_1_db", "team_1", "team1pass", "team_1_db");

if ($mysqli->connect_error) {
    echo "<p>MySQL Connection failed: " . $mysqli->connect_error . "</p>";
} else {
    echo "<p>MySQL Connection successful!</p>";
    echo "<p>Server time: " . date('Y-m-d H:i:s') . "</p>";
}
?>
