<?php
session_start();
date_default_timezone_set('Asia/Tokyo');
include '../database/db_conn.php';

// --- ၁။ AJAX Call for Notification ---
if (isset($_GET['check_new_orders'])) {
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
    $row = $result->fetch_assoc();
    echo $row['count'];
    exit();
}

// --- ၂။ Settings & Action Handling ---
if (isset($_POST['toggle_traffic'])) {
    $current = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
    file_put_contents('traffic_status.txt', ($current == '1' ? '0' : '1'));
    header("Location: admin.php"); exit();
}

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

// --- ၃။ Tab & Date Filter Logic ---
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
$filter_date = isset($_GET['date']) ? $_GET['date'] : ''; // Date Filter ယူမည်

$sql = "";
$date_sql = "";

// ရက်စွဲရွေးထားရင် SQL မှာ ထည့်ပေါင်းမည်
if (!empty($filter_date)) {
    $date_sql = " AND DATE(order_date) = '$filter_date' ";
}

if ($tab == 'active') {
    // Active tab မှာတော့ ရက်စွဲ filter မသုံးဘဲ အကုန်ပြတာ ပိုကောင်းပါတယ်
    $sql = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering') 
            ORDER BY FIELD(status, 'Pending', 'Cooking', 'Delivering'), order_date DESC";
} elseif ($tab == 'completed') {
    // Date ရွေးထားရင် Limit မထားတော့ပါ (အကုန်ပြမယ်)
    $limit = empty($filter_date) ? "LIMIT 50" : "";
    $sql = "SELECT * FROM orders WHERE status = 'Completed' $date_sql ORDER BY order_date DESC $limit";
} elseif ($tab == 'rejected') {
    $limit = empty($filter_date) ? "LIMIT 50" : "";
    $sql = "SELECT * FROM orders WHERE status = 'Rejected' $date_sql ORDER BY order_date DESC $limit";
}

$result = $conn->query($sql);

// Count Pending
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
        
        /* Overlay for Audio Policy */
        #audioOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); z-index: 9999;
            display: flex; justify-content: center; align-items: center;
        }
        .overlay-content {
            background: white; padding: 30px; border-radius: 10px; text-align: center;
        }
        .btn-start {
            background: #28a745; color: white; padding: 10px 20px; border: none; 
            border-radius: 5px; font-size: 16px; cursor: pointer; margin-top: 15px;
        }

        .top-bar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .card { background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px; }
        .heavy-traffic { border-left: 5px solid #c62828; background: #ffebee; }
        .normal-traffic { border-left: 5px solid #2e7d32; }

        .tabs { display: flex; border-bottom: 2px solid #ddd; margin-bottom: 20px; align-items: center; }
        .tab-link { 
            padding: 12px 25px; text-decoration: none; color: #555; 
            font-weight: bold; border-radius: 8px 8px 0 0; background: #e9ecef; margin-right: 5px;
            position: relative;
        }
        .tab-link.active { background: #007bff; color: white; }
        .badge-count {
            background: #dc3545; color: white; border-radius: 50%; 
            padding: 2px 8px; font-size: 12px; position: absolute; top: -5px; right: -5px;
        }

        /* Filter Form */
        .filter-form { margin-left: auto; display: flex; gap: 10px; align-items: center; }
        .date-input { padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
        .btn-filter { background: #6c757d; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #343a40; color: white; text-transform: uppercase; font-size: 14px; }
        tr:hover { background: #f1f1f1; }

        .status-label { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; display: inline-block; }
        .st-pending { background: #ffc107; color: #856404; animation: pulse 2s infinite; }
        .st-cooking { background: #fd7e14; color: white; }
        .st-delivering { background: #17a2b8; color: white; }
        .st-completed { background: #28a745; color: white; }
        .st-rejected { background: #dc3545; color: white; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

        .btn { padding: 8px 12px; border: none; border-radius: 4px; color: white; cursor: pointer; text-decoration: none; font-size: 13px; margin-right: 5px; }
        .btn-go { background: #007bff; }
        .btn-back { background: #28a745; }
        .btn-reject { background: #dc3545; }
    </style>
</head>
<body>

    <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>

    <div id="audioOverlay">
        <div class="overlay-content">
            <h2>🍕 Admin Panel</h2>
            <p>အော်ဒါသစ်ဝင်လာပါက အသံမြည်ရန်အတွက်<br>အောက်ပါခလုတ်ကို နှိပ်ပြီး စတင်ပါ။</p>
            <button class="btn-start" onclick="enableAudio()">🔊 Start Dashboard</button>
        </div>
    </div>

    <div class="top-bar">
        <h2>Admin Dashboard</h2>
        <div class="card <?php echo ($traffic_mode == '1') ? 'heavy-traffic' : 'normal-traffic'; ?>">
            <div>
                <strong>Traffic:</strong> 
                <?php echo ($traffic_mode == '1') ? '<span style="color:red">Heavy ⛔</span>' : '<span style="color:green">Normal ✅</span>'; ?>
            </div>
            <form method="POST">
                <button type="submit" name="toggle_traffic" class="btn" style="background: #555;">Switch</button>
            </form>
        </div>
    </div>

    <div class="tabs">
        <a href="?tab=active" class="tab-link <?php echo $tab == 'active' ? 'active' : ''; ?>">
            🔥 Active
            <?php if($pending_count > 0): ?>
                <span class="badge-count" id="pendingBadge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=completed" class="tab-link <?php echo $tab == 'completed' ? 'active' : ''; ?>">
            ✅ History
        </a>
        <a href="?tab=rejected" class="tab-link <?php echo $tab == 'rejected' ? 'active' : ''; ?>">
            ❌ Rejected
        </a>

        <?php if($tab != 'active'): ?>
        <form method="GET" class="filter-form">
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <input type="date" name="date" class="date-input" value="<?php echo $filter_date; ?>">
            <button type="submit" class="btn-filter">🔎 Filter</button>
            <?php if($filter_date): ?>
                <a href="admin.php?tab=<?php echo $tab; ?>" style="color:red; text-decoration:none;">✖ Clear</a>
            <?php endif; ?>
        </form>
        <?php endif; ?>
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
                    <td>
                        <?php echo date('Y-m-d', strtotime($row['order_date'])); ?><br>
                        <?php echo date('h:i A', strtotime($row['order_date'])); ?>
                    </td>
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
                <tr><td colspan="6" style="text-align:center; padding: 30px; color: #999;">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // --- 1. Audio Policy Logic ---
        function enableAudio() {
            const sound = document.getElementById('notifSound');
            // အသံတိုးတိုးလေး ဖွင့်ပြီး ချက်ချင်းပြန်ပိတ် (Browser ကို Unlock လုပ်ရန်)
            sound.volume = 0.1;
            sound.play().then(() => {
                sound.pause();
                sound.currentTime = 0;
                sound.volume = 1.0; // အသံပြန်ကျယ်
                document.getElementById('audioOverlay').style.display = 'none'; // Overlay ဖျောက်
            }).catch((e) => {
                console.log("Audio still blocked");
            });
        }

        // --- 2. Notification Logic ---
        let lastCount = <?php echo $pending_count; ?>;
        
        function checkNewOrders() {
            fetch('admin.php?check_new_orders=1')
                .then(response => response.text())
                .then(currentCount => {
                    currentCount = parseInt(currentCount);
                    if (currentCount > lastCount) {
                        document.getElementById('notifSound').play().catch(e => console.log("Sound blocked"));
                        document.title = "(" + currentCount + ") New Order! 🍕";
                        setTimeout(() => location.reload(), 2000);
                    }
                    lastCount = currentCount;
                });
        }
        setInterval(checkNewOrders, 5000);

        // --- 3. Reject Logic ---
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