<?php
//show tables from database
include 'db_conn.php';
$sql = "SELECT * FROM locations";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h1>Locations List</h1>";
    echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Region</th>
            </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $row["id"] . "</td>
                <td>" . $row["zip_code"] . "</td>
                <td>" . $row["city"] . "</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "0 results";
}
//show orders table
$sql1 = "SELECT * FROM orders ";
$result1 = $conn->query($sql1);

if ($result1-> num_rows > 0){
    echo "<h1>Order List</h1>";
    echo "<table border='1'>
        <tr>
            <th>ID</th>
            <th>Customer Name</th>
            <th>Phone Number</th>
            <th>Address</th>
        </tr>";
    while($row1 =$result1->fetch_assoc()) {
        echo "<tr>
                <td>" . $row1["id"] . "</td>
                    <td>" . $row1["customer_name"] . "</td>
                    <td>" . $row1["phonenumber"] . "</td>
                    <td>" . $row1["address"] . "</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "0 results";
    }

$conn->close();

?>