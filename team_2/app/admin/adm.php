<?php
// ၁။ အချိန်ဇုန် ညှိခြင်း
date_default_timezone_set('Asia/Tokyo');

// ၂။ Database Connection ချိတ်ခြင်း
include '../database/db_conn.php';

// ၃။ Status Update လုပ်သည့် Logic
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $current_time = date('Y-m-d H:i:s');

    if ($action == 'cook') {
        // Cooking စလုပ်မှ start_time ကို လက်ရှိအချိန် ထည့်ပေးမယ်
        $sql = "UPDATE orders SET status = 'Cooking', start_time = '$current_time' WHERE id = $id";
    } elseif ($action == 'deliver') {
        $sql = "UPDATE orders SET status = 'Delivering' WHERE id = $id";
    } elseif ($action == 'complete') {
        $sql = "UPDATE orders SET status = 'Completed' WHERE id = $id";
    } elseif ($action == 'delete') {
        $sql = "DELETE FROM orders WHERE id = $id";
    }

    if (isset($sql) && $conn->query($sql) === TRUE) {
        header("Location: admin.php?msg=success");
        exit();
    }
}

// ၄။ အော်ဒါအားလုံးကို ဆွဲထုတ်ခြင်း
$query = "SELECT * FROM orders ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Pizza Orders</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #555; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .pending { background: #ffeaa7; color: #d35400; }
        .cooking { background: #fab1a0; color: #e17055; }
        .delivering { background: #74b9ff; color: #0984e3; }
        .completed { background: #55efc4; color: #00b894; }
        .btn { padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; margin-right: 5px; color: white; display: inline-block; }
        .btn-cook { background: #e67e22; }
        .btn-deliver { background: #3498db; }
        .btn-done { background: #2ecc71; }
        .btn-delete { background: #e74c3c; }
    </style>
</head>
<body>

<div class="container">
    <h1>🍕 Pizza Shop Admin Panel</h1>

    <?php if (isset($_GET['msg'])) echo "<p style='color:green;'>Action Successful!</p>"; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Pizza (Qty)</th>
                <th>Status</th>
                <th>Start Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($row['customer_name']); ?></strong><br>
                    <small><?php echo htmlspecialchars($row['phonenumber']); ?></small>
                </td>
                <td>Size <?php echo $row['pizza_type']; ?> (<?php echo $row['quantity']; ?>)</td>
                <td>
                    <span class="status-badge <?php echo strtolower($row['status']); ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </td>
                <td><?php echo $row['start_time'] ? date('H:i', strtotime($row['start_time'])) : '-'; ?></td>
                <td>
                    <?php if ($row['status'] == 'Pending'): ?>
                        <a href="?action=cook&id=<?php echo $row['id']; ?>" class="btn btn-cook">Start Cooking</a>
                    <?php elseif ($row['status'] == 'Cooking'): ?>
                        <a href="?action=deliver&id=<?php echo $row['id']; ?>" class="btn btn-deliver">Send to Deliver</a>
                    <?php elseif ($row['status'] == 'Delivering'): ?>
                        <a href="?action=complete&id=<?php echo $row['id']; ?>" class="btn btn-done">Mark Complete</a>
                    <?php endif; ?>
                    
                    <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-delete" onclick="return confirm('ဖျက်မှာ သေချာလား?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>