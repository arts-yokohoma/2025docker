<?php
session_start();
// အချိန်ဇုန် ညှိခြင်း
date_default_timezone_set('Asia/Tokyo');
include '../database/db_conn.php';

// --- ၁။ Settings (Traffic & Staff) ---
if (isset($_POST['toggle_traffic'])) {
    $current_status = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
    $new_status = ($current_status == '1') ? '0' : '1';
    file_put_contents('traffic_status.txt', $new_status);
}

// ဝန်ထမ်းအင်အား Update
if (isset($_POST['update_staff'])) {
    $_SESSION['kitchen_staff'] = $_POST['kitchen_staff'];
    $_SESSION['delivery_staff'] = $_POST['delivery_staff'];
}

$traffic_mode = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
$kitchen_staff = isset($_SESSION['kitchen_staff']) ? $_SESSION['kitchen_staff'] : 3;
$total_riders = isset($_SESSION['delivery_staff']) ? $_SESSION['delivery_staff'] : 2;

// --- ၂။ Rider Availability (Rider အားမအား တွက်ခြင်း) ---
// Delivering ဖြစ်နေသူများကိုသာ "မအား (Busy)" ဟု သတ်မှတ်မည်။
// Customer လက်ခံပြီးလို့ Completed ဖြစ်သွားရင် (Returning အနေအထား) "အားသည်" ဟု ယူဆမည်။
$sql_busy = "SELECT COUNT(*) as busy_count FROM orders WHERE status = 'Delivering'";
$busy_result = $conn->query($sql_busy);
$busy_data = $busy_result->fetch_assoc();
$busy_riders = $busy_data['busy_count'];
$free_riders = $total_riders - $busy_riders;
if ($free_riders < 0) $free_riders = 0;


// --- ၃။ Order Action Logic ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $current_time = date('Y-m-d H:i:s');
    $sql = "";

    if ($action == 'cook') {
        // စချက်ပြီ - start_time ထည့်မယ်
        $sql = "UPDATE orders SET status = 'Cooking', start_time = '$current_time' WHERE id = $id";
    
    } elseif ($action == 'deliver') {
        // ပို့ပြီ - departure_time ထည့်မယ် (start_time ကို မထိတော့ဘူး)
        $sql = "UPDATE orders SET status = 'Delivering', departure_time = '$current_time' WHERE id = $id";
    
    } elseif ($action == 'rider_back') {
        // ဆိုင်ပြန်ရောက်ပြီ - return_time ထည့်မယ် (ဒါဆို ဇယားက ပျောက်ပြီး History ရောက်မယ်)
        $sql = "UPDATE orders SET return_time = '$current_time' WHERE id = $id";
    
    } elseif ($action == 'reject') {
        // အော်ဒါပယ်ဖျက် - Rejected (Database ကမဖျက်ဘူး)
        $sql = "UPDATE orders SET status = 'Rejected' WHERE id = $id";
    
    } elseif ($action == 'cancel') {
        // အပြီးဖျက် - Delete
        $sql = "DELETE FROM orders WHERE id = $id";
    }

    if ($sql != "") {
        $conn->query($sql);
        header("Location: admin.php"); // Refresh to clear URL
        exit();
    }
}

// --- ၄။ Order စာရင်း ဆွဲထုတ်ခြင်း ---
// Rejected မဟုတ်တာ၊ ဆိုင်ပြန်မရောက်သေးတာ(return_time NULL) တွေကို ပြမယ်
$sql_orders = "SELECT * FROM orders WHERE status != 'Rejected' AND return_time IS NULL ORDER BY FIELD(status, 'Pending', 'Cooking', 'Delivering', 'Completed'), order_date DESC";
$result = mysqli_query($conn, $sql_orders);
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Admin Control Panel</title>
    <meta http-equiv="refresh" content="10">
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f4; }
        .dashboard-grid { display: flex; gap: 20px; margin-bottom: 20px; }
        .card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1; }
        .traffic-on { background-color: #ffcccc; border: 2px solid red; color: red; font-weight: bold; }
        .traffic-off { background-color: #ccffcc; border: 2px solid green; color: green; }
        
        table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #333; color: white; }
        
        .btn { padding: 6px 10px; text-decoration: none; color: white; border-radius: 4px; font-size: 13px; margin-right: 5px; display: inline-block; }
        .btn-cook { background: orange; }
        .btn-go { background: #2980b9; }
        .btn-back { background: #27ae60; animation: blink 2s infinite; }
        .btn-reject { background: #c0392b; }
        .btn-cancel { background: #7f8c8d; }

        /* Row Highlighting for Returning Rider */
        .row-returning { background-color: #d5f5e3; border-left: 5px solid #2ecc71; }
        @keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }
    </style>
</head>
<body>

    <h1>🍕 Admin Dashboard</h1>
    <a href="order_history.php" class="btn" style="background:#555; padding: 10px;">📜 Order History ကြည့်ရန်</a>

    <div class="dashboard-grid" style="margin-top: 20px;">
        <div class="card <?php echo ($traffic_mode == '1') ? 'traffic-on' : 'traffic-off'; ?>">
            <h3>🚦 Traffic</h3>
            <p>Status: <?php echo ($traffic_mode == '1') ? 'Heavy Traffic' : 'Normal'; ?></p>
            <form method="POST">
                <button type="submit" name="toggle_traffic" style="cursor: pointer; padding: 5px;">Change Status</button>
            </form>
        </div>

        <div class="card">
            <h3>🛵 Rider Availability</h3>
            <div style="margin-bottom: 10px; padding: 8px; border-radius: 4px; background: <?php echo ($free_riders > 0) ? '#d5f5e3' : '#fadbd8'; ?>;">
                <strong style="color: <?php echo ($free_riders > 0) ? 'green' : 'red'; ?>;">
                    <?php echo ($free_riders > 0) ? "✔ $free_riders ယောက် အားသည်" : "❌ Rider မရှိပါ (All Busy)"; ?>
                </strong>
                <br><small>Total: <?php echo $total_riders; ?> | Busy: <?php echo $busy_riders; ?></small>
            </div>

            <form method="POST">
                <small>Kitchen:</small> <input type="number" name="kitchen_staff" value="<?php echo $kitchen_staff; ?>" style="width: 40px;">
                <small>Riders:</small> <input type="number" name="delivery_staff" value="<?php echo $total_riders; ?>" style="width: 40px;">
                <button type="submit" name="update_staff" style="cursor: pointer;">Set</button>
            </form>
        </div>
    </div>

    <table>
        <tr>
            <th>ID</th>
            <th>Info</th>
            <th>Order Detail</th>
            <th>Status / Timer</th>
            <th>Action</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)) { 
            // Customer received but rider not back yet
            $is_returning = ($row['status'] == 'Completed' && $row['return_time'] == NULL);
            $row_class = $is_returning ? 'row-returning' : '';
        ?>
            <tr class="<?php echo $row_class; ?>">
                <td>#<?php echo $row['id']; ?></td>
                <td>
                    <b><?php echo htmlspecialchars($row['customer_name']); ?></b><br>
                    <?php echo htmlspecialchars($row['phonenumber']); ?><br>
                    <small><?php echo htmlspecialchars($row['address']); ?></small>
                </td>
                <td>
                    <?php echo htmlspecialchars($row['pizza_type']); ?> (x<?php echo $row['quantity']; ?>)
                </td>
                
                <td>
                    <?php if ($is_returning): ?>
                        <span style="color: green; font-weight: bold;">🛵 Returning...</span><br>
                        <small>Customer received.</small>
                    <?php else: ?>
                        <span style="font-weight: bold;"><?php echo $row['status']; ?></span>
                        <?php if($row['status'] == 'Delivering') echo "<br><small>Sending...</small>"; ?>
                    <?php endif; ?>
                </td>

                <td>
                    <?php if ($row['status'] == 'Pending'): ?>
                        <a href="admin.php?action=cook&id=<?php echo $row['id']; ?>" class="btn btn-cook">👨‍🍳 Cook</a>
                        <a href="admin.php?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this order?');">❌ Reject</a>
                        <a href="admin.php?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-cancel" onclick="return confirm('Delete completely?');">🗑</a>
                    
                    <?php elseif ($row['status'] == 'Cooking'): ?>
                        <?php if ($free_riders > 0): ?>
                            <a href="admin.php?action=deliver&id=<?php echo $row['id']; ?>" class="btn btn-go">🛵 Depart</a>
                        <?php else: ?>
                            <a href="admin.php?action=deliver&id=<?php echo $row['id']; ?>" class="btn" style="background:grey;" onclick="return confirm('Rider မအားပါ။ ပို့မှာသေချာလား?');">⚠ No Rider</a>
                        <?php endif; ?>
                    
                    <?php elseif ($row['status'] == 'Delivering'): ?>
                        <span style="color: blue; font-size: 0.9em;">Wait for Customer...</span>
                    
                    <?php elseif ($is_returning): ?>
                        <a href="admin.php?action=rider_back&id=<?php echo $row['id']; ?>" class="btn btn-back">✅ Rider Arrived (Close)</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php } ?>
    </table>

</body>
</html>