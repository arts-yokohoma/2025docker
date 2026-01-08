<?php
$servername = "team_2_db"; // container_name　を　使う ကို host အဖြစ်သုံးရပါမယ်
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
//create table zipcodes
$sql1 = "CREATE TABLE IF NOT EXISTS zipcodes (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
postal_code VARCHAR(10) NOT NULL,
address VARCHAR(100) NOT NULL
)";
if ($conn->query($sql1) === TRUE) {
    echo "Table zipcodes created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

// to create orders table
$sql2 = "CREATE TABLE IF NOT EXISTS orders (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
customer_name VARCHAR(50) NOT NULL,
phonenumber VARCHAR(15) NOT NULL,
address VARCHAR(100) NOT NULL,
pizza_type VARCHAR(50) NOT NULL,
quantity INT(3) NOT NULL,
order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)";
if ($conn->query($sql2) === TRUE) {
    echo "Table orders created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

//to create



$conn->close();
?>