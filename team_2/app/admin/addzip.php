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
        <label for="area">Area:</label>
        <input type="text" id="area" name="area" required>
        <br><br>
        <input type="submit" value="Add Zipcode">
    </form>
</body>
</html>
<?php
include '../database/db_conn.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $zipcode = $_POST['zipcode'];
    $area = $_POST['area'];

    $stmt = $conn->prepare("INSERT INTO zipcodes (zipcode, area) VALUES (?, ?)");
    $stmt->bind_param("ss", $zipcode, $area);

    if ($stmt->execute()) {
        echo "New zipcode added successfully";
        echo "<br><a href='addzip.php'>Add another zipcode</a>";
        echo "<br><a href='viewzip.php'>View all zipcodes</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
