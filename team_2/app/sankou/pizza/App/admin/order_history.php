<?php
// ==========================================
// 1. LOGIC PART (Merged from order_history_logic.php)
// ==========================================
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
require_once '../database/db_conn.php';

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
    $title = "❌ Rejected Orders (ပယ်ဖျက်ထားသည်)";
    $color = "#c0392b";
} else {
    $sql = "SELECT * FROM orders WHERE status = 'Completed' $date_condition ORDER BY order_date DESC";
    $title = "✅ Completed Orders (ပြီးစီးသွားသည်)";
    $color = "#27ae60";
}

$result = $conn->query($sql);
$total_income = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f4f6f9; color: #333; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        .back-btn { background: #555; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
        .back-btn:hover { background: #333; }

        /* Tabs */
        .tabs { margin-bottom: 20px; }
        .tab-btn { padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px; color: white; font-weight: bold; opacity: 0.5; transition: 0.3s; border: none; }
        .tab-btn:hover { opacity: 0.8; transform: translateY(-2px); }
        .tab-btn.active { opacity: 1; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        
        /* Filter Form */
        .filter-box { background: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 10px; }
        .filter-box input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .filter-box button { padding: 8px 15px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .filter-box button:hover { background: #2980b9; }

        /* Table */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { border-bottom: 1px solid #eee; padding: 12px 15px; text-align: left; }
        th { background: <?php echo $color; ?>; color: white; text-transform: uppercase; font-size: 14px; }
        tr:hover { background-color: #f9f9f9; }
        
        .total-row { background: #e8f5e9; font-weight: bold; font-size: 1.1em; }
    </style>
</head>
<body>

    <div class="header">
        <h1 style="margin:0;">📜 Order History</h1>
        <a href="admin.php" class="back-btn">⬅ Back to Dashboard</a>
    </div>

    <div class="tabs">
        <a href="order_history.php?view=completed" class="tab-btn <?php echo ($view=='completed')?'active':''; ?>" style="background: #27ae60;">
            <i class="fas fa-check-circle"></i> Completed
        </a>
        <a href="order_history.php?view=rejected" class="tab-btn <?php echo ($view=='rejected')?'active':''; ?>" style="background: #c0392b;">
            <i class="fas fa-times-circle"></i> Rejected
        </a>
    </div>

    <h2 style="color:<?php echo $color; ?>; margin-top:0; border-left: 5px solid <?php echo $color; ?>; padding-left: 10px;">
        <?php echo $title; ?>
    </h2>

    <form method="POST" action="order_history.php?view=<?php echo $view; ?>" class="filter-box">
        <label style="font-weight:bold;">📅 Filter Date:</label>
        <input type="date" name="search_date" value="<?php echo $selected_date; ?>">
        <button type="submit" name="filter_date"><i class="fas fa-search"></i> Search</button>
        
        <?php if(!empty($selected_date)): ?>
            <a href="order_history.php?view=<?php echo $view; ?>" style="color: #c0392b; text-decoration: none; font-weight: bold; margin-left: 10px;">❌ Clear Filter</a>
        <?php endif; ?>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Date & Time</th>
            <th>Customer Info</th>
            <th>Items</th>
            <?php if($view == 'completed') echo "<th>Duration</th>"; ?>
            <th style="text-align: right;">Amount</th>
        </tr>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <?php 
                    // ဈေးနှုန်းတွက်ချက်ခြင်း
                    $unit_price = ($row['pizza_type'] == 'S') ? 1000 : (($row['pizza_type'] == 'M') ? 2000 : 3000);
                    $total = $unit_price * $row['quantity'];
                    
                    // Completed ဖြစ်မှသာ Total ပေါင်းမည်
                    if ($view == 'completed') {
                        $total_income += $total;
                    }

                    // ကြာချိန်တွက်ချက်ခြင်း
                    $duration = "-";
                    if ($view == 'completed' && !empty($row['start_time']) && !empty($row['return_time'])) {
                        $mins = round((strtotime($row['return_time']) - strtotime($row['start_time'])) / 60);
                        $duration = "$mins mins";
                    }
                ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td>
                        <?php echo date('M d, Y', strtotime($row['order_date'])); ?>
                        <br>
                        <small style="color:grey;"><?php echo date('h:i A', strtotime($row['order_date'])); ?></small>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong><br>
                        <small style="color:#555;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['phonenumber']); ?></small>
                    </td>
                    <td>
                        <span style="background: #eee; padding: 2px 8px; border-radius: 4px; font-weight:bold;">
                            <?php echo $row['pizza_type']; ?>
                        </span> 
                        x <?php echo $row['quantity']; ?>
                    </td>
                    <?php if($view == 'completed') echo "<td><i class='far fa-clock'></i> $duration</td>"; ?>
                    <td style="text-align: right;">
                        <?php if($view == 'rejected'): ?>
                            <s style='color:grey'><?php echo number_format($total); ?> Ks</s>
                            <br><small style="color:#c0392b;"><?php echo htmlspecialchars($row['reject_reason']); ?></small>
                        <?php else: ?>
                            <strong><?php echo number_format($total); ?> Ks</strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            
            <?php if($view == 'completed'): ?>
            <tr class="total-row">
                <td colspan="5" style="text-align:right;">Grand Total (Income):</td>
                <td style="text-align: right; color: #27ae60; font-size:1.2em;"><?php echo number_format($total_income); ?> Ks</td>
            </tr>
            <?php endif; ?>

        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center; padding: 40px; color: #777;">
                    <i class="fas fa-folder-open" style="font-size: 30px; margin-bottom: 10px; display:block;"></i>
                    No records found for this selection.
                </td>
            </tr>
        <?php endif; ?>
    </table>

</body>
</html>