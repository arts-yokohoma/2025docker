<?php
// これを　必ず使う　MYSQL_DATABASE
include 'db_conn.php';

//上のをそのまま必ず使うこと！

// Create connection
//create table zipcodes
$sql1 = "CREATE TABLE IF NOT EXISTS locations(
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
zip_code VARCHAR(10) NOT NULL,
state text,
city text
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
// create_tb.php ထဲက table ဆောက်တဲ့ ကုဒ်တွေရဲ့ အောက်မှာ ဒါလေး ထည့်ပါ
$sql3 = "ALTER TABLE orders ADD COLUMN IF NOT EXISTS start_time DATETIME NULL";
if ($conn->query($sql3) === TRUE) {
    echo "Column start_time added successfully";
} else {
    echo "Error updating table: " . $conn->error;
}

//to create



$conn->close();
?>