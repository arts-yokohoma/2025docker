<?php
// ၁။ Database ချိတ်ဆက်ခြင်း
$servername = "team_2_mysql"; // Docker Service Name
$username = "root";           // Root နဲ့ဝင်ရင် အကုန်လုပ်လို့ရတယ်
$password = "rootpass";
$dbname = "team_2_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete ခလုတ်နှိပ်ခဲ့ရင် အလုပ်လုပ်မည့်အပိုင်း
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM orders WHERE id=$id");
    echo "<p style='color:red;'>Order ID $id has been deleted!</p>";
}

// ၂။ Data တွေကို ဆွဲထုတ်ခြင်း
$sql = "SELECT * FROM orders ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Database View</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f0f2f5; }
        .dashboard { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #ddd; }
        .btn-delete { background-color: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .refresh { display: inline-block; margin-bottom: 10px; text-decoration: none; background: #28a745; color: white; padding: 8px 15px; border-radius: 4px; }
    </style>
</head>
<body>

<div class="dashboard">
    <h2>🛠️ Database Monitor (Orders Table)</h2>
    <a href="admin.php" class="refresh">🔄 Refresh Data</a>
    <a href="index.php" class="refresh" style="background:#6c757d;">⬅️ Back to Shop</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Pizza Type</th>
                <th>Qty</th>
                <th>Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row["id"] . "</td>";
                    echo "<td><b>" . $row["customer_name"] . "</b></td>";
                    echo "<td>" . $row["pizza_type"] . "</td>";
                    echo "<td>" . $row["quantity"] . "</td>";
                    echo "<td>" . $row["order_date"] . "</td>";
                    echo "<td>
                            <form method='POST' style='margin:0;'>
                                <input type='hidden' name='delete_id' value='" . $row["id"] . "'>
                                <button type='submit' class='btn-delete' onclick='return confirm(\"Are you sure?\");'>Delete</button>
                            </form>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>No Data Found in Database</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <p>Total Records: <strong><?php echo $result->num_rows; ?></strong></p>
</div>

</body>
</html>