<?php

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

include '../database/db_conn.php';

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT * FROM orders";
$result = mysqli_query($conn, $sql);

$orderData = array();
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $orderData[] = $row;
    }
} else {
    die("Error: " . mysqli_error($conn));
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order View</title>
</head>
<body>
    <h1>Orders</h1>
    <table border='1'>
        <tr><th>ID</th><th>Customer Name</th><th>Phone</th><th>Address</th><th>Pizza Type</th><th>Quantity</th><th>Order Date</th></tr>
        <?php
        foreach($orderData as $row) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['customer_name'] . "</td>";
            echo "<td>" . $row['phonenumber'] . "</td>";
            echo "<td>" . $row['address'] . "</td>";
            echo "<td>" . $row['pizza_type'] . "</td>";
            echo "<td>" . $row['quantity'] . "</td>";
            echo "<td>" . $row['order_date'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>