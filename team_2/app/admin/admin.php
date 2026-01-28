<?php
session_start();
date_default_timezone_set('Asia/Tokyo');
include '../database/db_conn.php';

// --- ၁။ AJAX Call for Notification (အသံမြည်ရန် စစ်ဆေးခြင်း) ---
// JavaScript ကနေ ဒီအပိုင်းကို ၅ စက္ကန့်တစ်ခါ လှမ်းခေါ်ပါမယ်
if (isset($_GET['check_new_orders'])) {
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
    $row = $result->fetch_assoc();
    echo $row['count'];
    exit(); // ဒီနေရာမှာ PHP ကို ရပ်လိုက်မယ် (Page အကုန် မ run အောင်)
}

// --- ၂။ Settings & Action Handling ---
// Traffic Toggle
if (isset($_POST['toggle_traffic'])) {
    $current = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
    file_put_contents('traffic_status.txt', ($current == '1' ? '0' : '1'));
    header("Location: admin.php"); exit();
}

// Action Handling (Deliver, Complete, Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $act = $_GET['action'];
    $now = date('Y-m-d H:i:s');

    if ($act == 'deliver') {
        $conn->query("UPDATE orders SET status='Delivering', departure_time='$now' WHERE id=$id");
    } elseif ($act == 'rider_back') {
        $conn->query("UPDATE orders SET status='Completed', return_time='$now' WHERE id=$id");
    } elseif ($act == 'reject') {
        $reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : 'Shop Busy';
        $stmt = $conn->prepare("UPDATE orders SET status='Rejected', reject_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $id);
        $stmt->execute();
    }
    header("Location: admin.php");
    exit();
}

// --- ၃။ Tab စနစ် Logic ---
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'active'; // default က active

$sql = "";
if ($tab == 'active') {
    // မပို့ရသေးတဲ့ Order တွေ (Pending, Cooking, Delivering)
    $sql = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering') 
            ORDER BY FIELD(status, 'Pending', 'Cooking', 'Delivering'), order_date DESC";
} elseif ($tab == 'completed') {
    // ပို့ပြီးတဲ့ Order တွေ
    $sql = "SELECT * FROM orders WHERE status = 'Completed' ORDER BY order_date DESC LIMIT 50";
} elseif ($tab == 'rejected') {
    // ငြင်းလိုက်တဲ့ Order တွေ
    $sql = "SELECT * FROM orders WHERE status = 'Rejected' ORDER BY order_date DESC LIMIT 50";
}

$result = $conn->query($sql);

// Count Pending Orders for Badge
$pending_res = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='Pending'");
$pending_count = $pending_res->fetch_assoc()['c'];

$traffic_mode = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; padding: 20px; color: #333; }
        
        /* Top Cards */
        .top-bar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .card { background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px; }
        .heavy-traffic { border-left: 5px solid #c62828; background: #ffebee; }
        .normal-traffic { border-left: 5px solid #2e7d32; }

        /* Tabs Design */
        .tabs { display: flex; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
        .tab-link { 
            padding: 12px 25px; text-decoration: none; color: #555; 
            font-weight: bold; border-radius: 8px 8px 0 0; background: #e9ecef; margin-right: 5px;
            position: relative;
        }
        .tab-link.active { background: #007bff; color: white; }
        .tab-link:hover { background: #dbe0e5; }
        .tab-link.active:hover { background: #0056b3; }
        
        /* Notification Badge */
        .badge-count {
            background: #dc3545; color: white; border-radius: 50%; 
            padding: 2px 8px; font-size: 12px; position: absolute; top: -5px; right: -5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Table Design */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #343a40; color: white; text-transform: uppercase; font-size: 14px; }
        tr:hover { background: #f1f1f1; }

        /* Status Labels */
        .status-label { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; display: inline-block; }
        .st-pending { background: #ffc107; color: #856404; animation: pulse 2s infinite; }
        .st-cooking { background: #fd7e14; color: white; }
        .st-delivering { background: #17a2b8; color: white; }
        .st-completed { background: #28a745; color: white; }
        .st-rejected { background: #dc3545; color: white; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        /* Buttons */
        .btn { padding: 8px 12px; border: none; border-radius: 4px; color: white; cursor: pointer; text-decoration: none; font-size: 13px; margin-right: 5px; }
        .btn-go { background: #007bff; }
        .btn-back { background: #28a745; }
        .btn-reject { background: #dc3545; }

    </style>
</head>
<body>

    <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <div class="top-bar">
        <h2>🍕 Admin Dashboard</h2>
        
        <div class="card <?php echo ($traffic_mode == '1') ? 'heavy-traffic' : 'normal-traffic'; ?>">
            <div>
                <strong>Traffic Status:</strong> 
                <?php echo ($traffic_mode == '1') ? '<span style="color:red">Heavy ⛔</span>' : '<span style="color:green">Normal ✅</span>'; ?>
            </div>
            <form method="POST">
                <button type="submit" name="toggle_traffic" class="btn" style="background: #555;">Switch</button>
            </form>
        </div>
    </div>

    <div class="tabs">
        <a href="?tab=active" class="tab-link <?php echo $tab == 'active' ? 'active' : ''; ?>">
            🔥 Active Orders
            <?php if($pending_count > 0): ?>
                <span class="badge-count" id="pendingBadge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=completed" class="tab-link <?php echo $tab == 'completed' ? 'active' : ''; ?>">
            ✅ Completed History
        </a>
        <a href="?tab=rejected" class="tab-link <?php echo $tab == 'rejected' ? 'active' : ''; ?>">
            ❌ Rejected
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Time</th>
                <th>Customer</th>
                <th>Order Detail</th>
                <th>Status</th>
                <?php if($tab == 'active'): ?><th>Actions</th><?php endif; ?>
                <?php if($tab == 'rejected'): ?><th>Reason</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo date('h:i A', strtotime($row['order_date'])); ?></td>
                    <td>
                        <b><?php echo htmlspecialchars($row['customer_name'] ?? $row['name'] ?? '-'); ?></b><br>
                        <?php echo htmlspecialchars($row['phonenumber'] ?? $row['phone'] ?? '-'); ?><br>
                        <small style="color:#666;"><?php echo htmlspecialchars($row['address'] ?? '-'); ?></small>
                    </td>
                    <td>
                        <?php echo $row['pizza_type'] ?? $row['size']; ?> x <?php echo $row['quantity']; ?>
                    </td>
                    <td>
                        <?php 
                            $st = $row['status'];
                            $cls = 'st-' . strtolower($st);
                            echo "<span class='status-label $cls'>$st</span>";
                        ?>
                    </td>
                    
                    <?php if($tab == 'active'): ?>
                    <td>
                        <?php if($st == 'Pending' || $st == 'Cooking'): ?>
                            <a href="admin.php?action=deliver&id=<?php echo $row['id']; ?>" class="btn btn-go">🛵 Go</a>
                            <button onclick="rejectOrder(<?php echo $row['id']; ?>)" class="btn btn-reject">❌</button>
                        <?php elseif($st == 'Delivering'): ?>
                            <a href="admin.php?action=rider_back&id=<?php echo $row['id']; ?>" class="btn btn-back">🏁 Done</a>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>

                    <?php if($tab == 'rejected'): ?>
                        <td style="color: #c62828;"><?php echo htmlspecialchars($row['reject_reason']); ?></td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding: 30px; color: #999;">No orders found in this section.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // --- 1. Notification Logic ---
        let lastCount = <?php echo $pending_count; ?>;
        const sound = document.getElementById('notifSound');

        function checkNewOrders() {
            fetch('admin.php?check_new_orders=1')
                .then(response => response.text())
                .then(currentCount => {
                    currentCount = parseInt(currentCount);
                    
                    // အရင်အရေအတွက်ထက် များလာရင် (Order သစ်ဝင်လာရင်)
                    if (currentCount > lastCount) {
                        // 1. အသံဖွင့်မယ်
                        sound.play().catch(error => console.log('Audio blocked by browser, click page once to enable.'));
                        
                        // 2. Title bar မှာ စာတန်းပြောင်းမယ်
                        document.title = "(" + currentCount + ") New Order! 🍕";
                        
                        // 3. Page ကို Refresh လုပ်ပေးမယ် (Data အသစ်မြင်ရအောင်)
                        setTimeout(() => location.reload(), 2000);
                    }
                    lastCount = currentCount;
                });
        }

        // ၅ စက္ကန့်တစ်ခါ Server ကို လှမ်းစစ်မယ်
        setInterval(checkNewOrders, 5000);

        // --- 2. Reject Logic ---
        function rejectOrder(id) {
            Swal.fire({
                title: 'Reject Reason',
                input: 'text',
                inputPlaceholder: 'Out of stock / Shop closed',
                showCancelButton: true,
                confirmButtonText: 'Confirm',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `admin.php?action=reject&id=${id}&reason=${encodeURIComponent(result.value || 'Shop Busy')}`;
                }
            });
        }
    </script>
</body>
</html>