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