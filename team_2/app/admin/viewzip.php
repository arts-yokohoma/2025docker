<?php
include '../database/db_conn.php';
echo "Database connection successful from addzip.php";
$sql = "SELECT * FROM zipcodes";
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
                <td>" . $row["postal_code"] . "</td>
                <td>" . $row["address"] . "</td>
              </tr>";
    }
    echo "</table>";
    echo "<br><a href='addzip.php'>Add New Zipcode</a>";
} else {
    echo "0 results";
}
$conn->close();
?>