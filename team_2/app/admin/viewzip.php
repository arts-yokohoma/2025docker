<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viwe AREA</title>
</head>
<body>
    <h1>You can view avaliable area</h1>
</body>
</html><?php
include '../database/db_conn.php';
echo "Database connection successful from addzip.php";
$sql = "SELECT * FROM locations";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<h1>Zipcode List</h1>";
    echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>Postal Code</th>
                <th>Address</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["id"] . "</td>
                <td>" . $row["zip_code"] . "</td>
                <td>" . $row["city"] . "</td>
              </tr>";
    }
    echo "</table>";
    echo "<br><a href='addzip.php'>Add New Zipcode</a>";
} else {
    echo "0 results";
}
$conn->close();
?>