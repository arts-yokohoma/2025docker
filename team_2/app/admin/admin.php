<?php
session_start();
include '../database/db_conn.php';

// --- ၁။ Settings (Traffic & Staff) သိမ်းဆည်းခြင်း ---
// Traffic Mode ကို Database မလိုဘဲ ဖိုင်အသေးလေးနဲ့ မှတ်ထားမယ် (index.php က လှမ်းဖတ်ဖို့)
if (isset($_POST['toggle_traffic'])) {
    $current_status = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
    $new_status = ($current_status == '1') ? '0' : '1';
    file_put_contents('traffic_status.txt', $new_status);
}

// ဝန်ထမ်းအင်အား (Session ထဲမှာပဲ ယာယီသိမ်းမယ်)
if (isset($_POST['update_staff'])) {
    $_SESSION['kitchen_staff'] = $_POST['kitchen_staff'];
    $_SESSION['delivery_staff'] = $_POST['delivery_staff'];
}

// လက်ရှိ Settings တွေကို ပြန်ဆွဲထုတ်မယ်
$traffic_mode = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
$kitchen_staff = isset($_SESSION['kitchen_staff']) ? $_SESSION['kitchen_staff'] : 3; // Default 3 ယောက်
$delivery_staff = isset($_SESSION['delivery_staff']) ? $_SESSION['delivery_staff'] : 2; // Default 2 ယောက်


// --- ၂။ Order Status ပြောင်းလဲခြင်း Logic ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $sql = "";

    if ($action == 'cook') {
        $sql = "UPDATE orders SET status = 'Cooking' WHERE id = $id";
    } elseif ($action == 'deliver') {
        // ဆိုင်ကထွက်ပြီ (departure_time မှတ်မယ်)
        $sql = "UPDATE orders SET status = 'Delivering', departure_time = NOW() WHERE id = $id";
    } elseif ($action == 'complete') {
        // ပြန်ရောက်ပြီ (return_time မှတ်မယ်)
        $sql = "UPDATE orders SET status = 'Completed', return_time = NOW() WHERE id = $id";
    } elseif ($action == 'cancel') {
        $sql = "DELETE FROM orders WHERE id = $id";
    }

    if ($sql != "") {
        mysqli_query($conn, $sql);
    }
    // admin.php ထဲက Status ပြောင်းတဲ့နေရာ (ဥပမာ action=cook ဖြစ်တဲ့နေရာ)
if (isset($_GET['action']) && $_GET['action'] == 'cook') {
    $id = $_GET['id'];
    $current_time = date('Y-m-d H:i:s'); // လက်ရှိအချိန်ကို ယူမယ်
    
    // Status ပြောင်းရုံတင်မကဘဲ start_time ကိုပါ ထည့်ပေးလိုက်မယ်
    $sql = "UPDATE orders SET status = 'Cooking', start_time = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $current_time, $id);
    $stmt->execute();
    
    header("Location: admin.php");
    exit();
}
}

// --- ၃။ Order စာရင်းများကို ဆွဲထုတ်ခြင်း ---
// ပြီးသွားတဲ့ Order တွေကို အောက်ဆုံးမှာထားမယ်
$sql_orders = "SELECT * FROM orders ORDER BY FIELD(status, 'Pending', 'Cooking', 'Delivering', 'Completed'), order_date DESC";
$result = mysqli_query($conn, $sql_orders);
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Pizza Mach Admin Panel</title>
    <meta http-equiv="refresh" content="10">
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f4; }
        .dashboard-grid { display: flex; gap: 20px; margin-bottom: 20px; }
        .card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1; }
        .traffic-on { background-color: #ffcccc; border: 2px solid red; color: red; font-weight: bold; }
        .traffic-off { background-color: #ccffcc; border: 2px solid green; color: green; }
        
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #333; color: white; }
        
        .btn { padding: 5px 10px; text-decoration: none; color: white; border-radius: 4px; font-size: 12px; margin-right: 5px; }
        .btn-cook { background: orange; }
        .btn-go { background: blue; }
        .btn-done { background: green; }
        .btn-cancel { background: red; }
        
        /* Status Colors */
        .status-Pending { color: red; font-weight: bold; }
        .status-Cooking { color: orange; font-weight: bold; }
        .status-Delivering { color: blue; font-weight: bold; }
        .status-Completed { color: green; }
    </style>
</head>
<body>

    <h1>🍕 Admin Control Panel</h1>

    <div class="dashboard-grid">
        <div class="card <?php echo ($traffic_mode == '1') ? 'traffic-on' : 'traffic-off'; ?>">
            <h3>Traffic Condition</h3>
            <p>လက်ရှိအခြေအနေ: <?php echo ($traffic_mode == '1') ? 'လမ်းပိတ်နေသည် (Heavy Traffic)' : 'ပုံမှန် (Normal)'; ?></p>
            <form method="POST">
                <button type="submit" name="toggle_traffic" style="padding: 10px;">
                    <?php echo ($traffic_mode == '1') ? 'လမ်းရှင်းပြီ (Set Normal)' : 'လမ်းပိတ်နေသည် (Set Heavy Traffic)'; ?>
                </button>
            </form>
        </div>

        <div class="card">
            <h3>ဝန်ထမ်း အင်အားစာရင်း</h3>
            <form method="POST">
                <label>Kitchen Staff:</label>
                <input type="number" name="kitchen_staff" value="<?php echo $kitchen_staff; ?>" style="width: 50px;"> ဦး<br><br>
                
                <label>Drivers:</label>
                <input type="number" name="delivery_staff" value="<?php echo $delivery_staff; ?>" style="width: 50px;"> ဦး<br><br>
                
                <button type="submit" name="update_staff">Update Staff</button>
            </form>
        </div>
    </div>

    <hr>

    <h2>မှာယူထားသော စာရင်းများ (Orders)</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Customer / Phone</th>
            <th>Address</th>
            <th>Pizza / Qty</th>
            <th>Status</th>
            <th>Delivery Time</th> <th>Action (အဆင့်ပြောင်းရန်)</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)) { 
            // Delivery ကြာချိန်တွက်ခြင်း (ပြန်ရောက်မှ တွက်မယ်)
            $duration_msg = "-";
            if ($row['status'] == 'Completed' && $row['departure_time'] && $row['return_time']) {
                $start = strtotime($row['departure_time']);
                $end = strtotime($row['return_time']);
                $mins = round(abs($end - $start) / 60);
                $duration_msg = "$mins မိနစ် ကြာခဲ့သည်";
            } elseif ($row['status'] == 'Delivering') {
                $duration_msg = "သွားပို့နေဆဲ...";
            }
        ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td>
                    <?php echo htmlspecialchars($row['customer_name']); ?><br>
                    <small><?php echo htmlspecialchars($row['phonenumber']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($row['address']); ?></td>
                <td>
                    <?php echo htmlspecialchars($row['pizza_type']); ?> <br> 
                    (<?php echo $row['quantity']; ?> ခု)
                </td>
                
                <td class="status-<?php echo $row['status']; ?>">
                    <?php echo $row['status']; ?>
                </td>

                <td><?php echo $duration_msg; ?></td>

                <td>
                    <?php if ($row['status'] == 'Pending'): ?>
                        <a href="admin.php?action=cook&id=<?php echo $row['id']; ?>" class="btn btn-cook">ချက်မယ် (Cook)</a>
                        <a href="admin.php?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-cancel" onclick="return confirm('ဖျက်မှာသေချာလား?');">X</a>
                    
                    <?php elseif ($row['status'] == 'Cooking'): ?>
                        <a href="admin.php?action=deliver&id=<?php echo $row['id']; ?>" class="btn btn-go">ပို့ဆောင်မည် (Depart)</a>
                    
                    <?php elseif ($row['status'] == 'Delivering'): ?>
                        <a href="admin.php?action=complete&id=<?php echo $row['id']; ?>" class="btn btn-done">ပြန်ရောက်ပြီ (Done)</a>
                    
                    <?php else: ?>
                        <span style="color: grey;">✔ ပြီးစီး</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php } ?>
    </table>

</body>
</html>