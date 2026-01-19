<?php
$servername = "team_2_mysql"; // container_name ကို host အဖြစ်သုံးရပါမယ်
$username = "team_2";       // MYSQL
$password = "team2pass";    // MYSQL_PASSWORD
$dbname = "team_2_db";  // これを　必ず使う　MYSQL_DATABASE 
//上のをそのまま必ず使うこと！
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>