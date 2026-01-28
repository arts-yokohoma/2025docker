<?php
session_start();
include '../database/db_conn.php';

// View Mode (Completed or Rejected)
$view = isset($_GET['view']) ? $_GET['view'] : 'completed';

// Date Filter
$date_condition = "";
$selected_date = "";
if (isset($_POST['filter_date']) && !empty($_POST['search_date'])) {
    $selected_date = $_POST['search_date'];
    $date_condition = "AND DATE(order_date) = '$selected_date'";
}

// Query
if ($view == 'rejected') {
    $sql = "SELECT * FROM orders WHERE status = 'Rejected' $date_condition ORDER BY order_date DESC";
    $title = "❌ ပယ်ဖျက်လိုက်သော အော်ဒါများ (Rejected List)";
    $color = "#c0392b";
} else {
    $sql = "SELECT * FROM orders WHERE status = 'Completed' $date_condition ORDER BY order_date DESC";
    $title = "✅ ပြီးစီးသွားသော အော်ဒါများ (Completed List)";
    $color = "#27ae60";
}

$result = $conn->query($sql);
$total_income = 0;
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Order History</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f9f9f9; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .tab-btn { padding: 10px 20px; text-decoration: none; border-radius: 5px 5px 0 0; display: inline-block; margin-right: 5px; color: white; }
        .active { opacity: 1; font-weight: bold; }
        .inactive { opacity: 0.5; background: grey !important; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: <?php echo $color; ?>; color: white; }
    </style>
</head>
<body>

    <div class="header">
        <h1>📜 Order History</h1>
        <a href="admin.php" style="background:#555; color:white; padding:10px; text-decoration:none; border-radius:5px;">⬅ Back to Admin</a>
    </div>

    <div>
        <a href="order_history.php?view=completed" class="tab-btn <?php echo ($view=='completed')?'active':'inactive'; ?>" style="background: #27ae60;">Completed</a>
        <a href="order_history.php?view=rejected" class="tab-btn <?php echo ($view=='rejected')?'active':'inactive'; ?>" style="background: #c0392b;">Rejected</a>
    </div>

    <h2 style="color:<?php echo $color; ?>;"><?php echo $title; ?></h2>

    <form method="POST" action="order_history.php?view=<?php echo $view; ?>" style="margin-bottom: 20px; background: white; padding: 15px;">
        Date: <input type="date" name="search_date" value="<?php echo $selected_date; ?>">
        <button type="submit" name="filter_date">Search</button>
        <a href="order_history.php?view=<?php echo $view; ?>">Show All</a>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Customer</th>
            <th>Item</th>
            <?php if($view == 'completed') echo "<th>Duration</th>"; ?>
            <th>Amount</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <?php 
                    $unit_price = ($row['pizza_type'] == 'S') ? 1000 : (($row['pizza_type'] == 'M') ? 2000 : 3000);
                    $total = $unit_price * $row['quantity'];
                    if ($view == 'completed') $total_income += $total;

                    $duration = "-";
                    if ($view == 'completed' && !empty($row['start_time']) && !empty($row['return_time'])) {
                        $mins = round((strtotime($row['return_time']) - strtotime($row['start_time'])) / 60);
                        $duration = "$mins mins";
                    }
                ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo date('Y-m-d h:i A', strtotime($row['order_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo $row['pizza_type']; ?> (x<?php echo $row['quantity']; ?>)</td>
                    <?php if($view == 'completed') echo "<td>$duration</td>"; ?>
                    <td>
                        <?php echo ($view == 'rejected') ? "<s style='color:grey'>$total Ks</s>" : "$total Ks"; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            
            <?php if($view == 'completed'): ?>
            <tr style="background: #dff0d8; font-weight:bold;">
                <td colspan="<?php echo ($view == 'completed') ? '5' : '4'; ?>" style="text-align:right;">Grand Total:</td>
                <td style="color:green;"><?php echo number_format($total_income); ?> Ks</td>
            </tr>
            <?php endif; ?>

        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No records found.</td></tr>
        <?php endif; ?>
    </table>

</body>
</html>