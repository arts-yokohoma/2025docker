

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