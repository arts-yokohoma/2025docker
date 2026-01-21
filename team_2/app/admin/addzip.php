<?php
include_once '../database/db_conn.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $zipcode = $_POST['zipcode'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    
    $stmt = $conn->prepare("INSERT INTO locations (zip_code, city, address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $zipcode, $city, $state);
    
    if ($stmt->execute()) {
        header("location: viewzip.php");
        echo "<h2>Zipcode added successfully!</h2>";
        exit();
        } else {
            echo "<h2>Error adding zipcode: " . htmlspecialchars($stmt->error) . "</h2>";
            }
            
            $stmt->close();
            $conn->close();
            }
            ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add zipcodes</title>
</head>
<body>
    <h1>Add Zipcodes</h1>
    <form action="" method="post">
        <label for="zipcode">Zipcode:</label>
        <input type="text" id="zipcode" name="zipcode" required>
        <br><br>
        <label for="city">City:</label>
        <input type="text" id="city" name="city" required>
        <br><br>
        <label for="state">State:</label>
        <input type="text" id="state" name="state" required>
        <br><br>
        <input type="submit" value="Add Zipcode">
    </form>
</body>
</html>