<?php
include 'db_conn.php';

// Create locations table
$sql1 = "CREATE TABLE IF NOT EXISTS locations(
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
zip_code VARCHAR(10) NOT NULL,
state text,
city text
)";
if ($conn->query($sql1) === TRUE) {
    echo "Table zipcodes created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create orders table (FIXED TIMESTAMP)
$sql2 = "CREATE TABLE IF NOT EXISTS orders (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
customer_name VARCHAR(50) NOT NULL,
phonenumber VARCHAR(15) NOT NULL,
address VARCHAR(100) NOT NULL,
pizza_type VARCHAR(50) NOT NULL,
quantity INT(3) NOT NULL,
postal_code VARCHAR(20) NULL,
status VARCHAR(50) DEFAULT 'Pending',
order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
start_time DATETIME NULL,
departure_time DATETIME NULL,
return_time DATETIME NULL,
reject_reason TEXT NULL
)";

if ($conn->query($sql2) === TRUE) {
    echo "Table orders created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// ... existing code ...

// ဆိုင်ခွဲများ သိမ်းမည့် Table
$sql_partners = "CREATE TABLE IF NOT EXISTS partner_shops (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    website_url VARCHAR(255) NOT NULL
)";

if ($conn->query($sql_partners) === TRUE) {
    echo "Partner Shops table created successfully<br>";
} else {
    echo "Error: " . $conn->error . "<br>";
}

$conn->close();
?>