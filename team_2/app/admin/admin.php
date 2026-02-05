<?php
session_start();

// ၁။ Login စစ်ဆေးခြင်း
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Tokyo');
include '../database/db_conn.php';

// --- ၂။ Settings Update Logic (အသစ်ထည့်ထားသောအပိုင်း) ---
if (isset($_POST['update_settings'])) {
    $k = intval($_POST['kitchen_staff']);
    $d = intval($_POST['rider_staff']);
    // ကော်မာခံပြီး staff_config.txt ထဲ သိမ်းမည်
    file_put_contents('staff_config.txt', "$k,$d");
    header("Location: admin.php"); exit();
}

// Staff Config ဖတ်ခြင်း (မရှိရင် Default 3,2 ထားမည်)
$k_staff = 3; $r_staff = 2;
if (file_exists('staff_config.txt')) {
    $data = explode(',', file_get_contents('staff_config.txt'));
    $k_staff = isset($data[0]) ? intval($data[0]) : 3;
    $r_staff = isset($data[1]) ? intval($data[1]) : 2;
}

// Capacity Calculation (Logic: ၁ ယောက်လျှင် အော်ဒါ ၂ ခုနှုန်း)
// ဒီဖော်မြူလာက order_form.php နဲ့ တူနေရပါမယ်
$max_capacity = ($k_staff + $r_staff) * 2; 

// --- ၃။ Notification & Data Query ---
if (isset($_GET['check_new_orders'])) {
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
    $row = $result->fetch_assoc();
    echo $row['count'];
    exit();
}

// Traffic Toggle
if (isset($_POST['toggle_traffic'])) {
    $current = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
    file_put_contents('traffic_status.txt', ($current == '1' ? '0' : '1'));
    header("Location: admin.php"); exit();
}

// Action Handling
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $act = $_GET['action'];
    $now = date('Y-m-d H:i:s');

    if ($act == 'cook') {
        $conn->query("UPDATE orders SET status='Cooking', start_time='$now' WHERE id=$id");
    } elseif ($act == 'deliver') {
        $conn->query("UPDATE orders SET status='Delivering', departure_time='$now' WHERE id=$id");
    } elseif ($act == 'rider_back') {
        $conn->query("UPDATE orders SET status='Completed', return_time='$now' WHERE id=$id");
    } elseif ($act == 'reject') {
        $reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : 'Shop Busy';
        $stmt = $conn->prepare("UPDATE orders SET status='Rejected', reject_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $id);
        $stmt->execute();
    }
    header("Location: admin.php"); exit();
}

// Active Orders Count (Total Active)
$active_res = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering')");
$current_active = $active_res->fetch_assoc()['c'];

// Percentage for Progress Bar
$capacity_percent = ($max_capacity > 0) ? ($current_active / $max_capacity) * 100 : 100;
if($capacity_percent > 100) $capacity_percent = 100;

// Tab & List Logic
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$date_sql = !empty($filter_date) ? " AND DATE(order_date) = '$filter_date' " : "";

if ($tab == 'active') {
    $sql = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering') 
            ORDER BY FIELD(status, 'Pending', 'Cooking', 'Delivering'), order_date ASC";
} elseif ($tab == 'completed') {
    $limit = empty($filter_date) ? "LIMIT 50" : "";
    $sql = "SELECT * FROM orders WHERE status = 'Completed' $date_sql ORDER BY order_date DESC $limit";
} elseif ($tab == 'rejected') {
    $limit = empty($filter_date) ? "LIMIT 50" : "";
    $sql = "SELECT * FROM orders WHERE status = 'Rejected' $date_sql ORDER BY order_date DESC $limit";
}
$result = $conn->query($sql);

$traffic_mode = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; padding: 20px; color: #333; }
        
        /* Layout Grid */
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        
        /* Cards */
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #555; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }

        /* Capacity Bar */
        .progress-container { background: #e9ecef; border-radius: 20px; height: 25px; width: 100%; overflow: hidden; margin-top: 10px; }
        .progress-bar { 
            height: 100%; text-align: center; line-height: 25px; color: white; font-weight: bold; font-size: 14px; transition: width 0.5s;
            background: <?php echo ($capacity_percent >= 80) ? '#dc3545' : (($capacity_percent >= 50) ? '#ffc107' : '#28a745'); ?>;
        }

        /* Settings Form */
        .settings-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .settings-row label { flex: 1; font-weight: 500; }
        .settings-row input { width: 60px; padding: 5px; text-align: center; border: 1px solid #ddd; border-radius: 5px; }
        .btn-save { background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; width: 100%; }

        /* Traffic Toggle */
        .traffic-box { display: flex; justify-content: space-between; align-items: center; background: <?php echo ($traffic_mode=='1') ? '#ffebee' : '#e8f5e9'; ?>; padding: 15px; border-radius: 8px; border: 1px solid <?php echo ($traffic_mode=='1') ? '#ffcdd2' : '#c8e6c9'; ?>; }

        /* Table & Tabs (Existing Styles) */
        .tabs { display: flex; margin-bottom: 20px; border-bottom: 2px solid #ddd; }
        .tab-link { padding: 10px 20px; text-decoration: none; color: #555; font-weight: bold; background: #e9ecef; margin-right: 5px; border-radius: 5px 5px 0 0; }
        .tab-link.active { background: #007bff; color: white; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #343a40; color: white; }
        
        .btn { padding: 5px 10px; border-radius: 4px; color: white; text-decoration: none; font-size: 13px; margin-right: 5px; border:none; cursor: pointer;}
        .btn-cook { background: #fd7e14; }
        .btn-deliver { background: #17a2b8; }
        .btn-done { background: #28a745; }
        .btn-reject { background: #dc3545; }

        /* Audio Overlay */
        #audioOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 999; display: flex; justify-content: center; align-items: center; }
        .btn-start { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 50px; font-size: 18px; cursor: pointer; }
    </style>
</head>
<body>

    <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <div id="audioOverlay">
        <button class="btn-start" onclick="enableAudio()">🔊 Start Dashboard</button>
    </div>

    <h2>Admin Dashboard</h2>
        <a href="manage_shops.php" class="btn" style="background: #6f42c1;">📍 Manage Partner Shops</a>
    <div class="dashboard-grid">
        <div class="card">
            <h3>📊 Shop Capacity Status</h3>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span>Active Orders: <strong><?php echo $current_active; ?></strong></span>
                <span>Max Capacity: <strong><?php echo $max_capacity; ?></strong></span>
            </div>
            <div class="progress-container">
                <div class="progress-bar" style="width: <?php echo $capacity_percent; ?>%;">
                    <?php echo round($capacity_percent); ?>% Full
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <form method="POST" class="traffic-box">
                    <div>
                        <strong>Manual Override:</strong><br>
                        <?php echo ($traffic_mode == '1') ? '<span style="color:red">⛔ Busy Mode ON</span>' : '<span style="color:green">✅ Normal Mode</span>'; ?>
                    </div>
                    <button type="submit" name="toggle_traffic" class="btn" style="background: #555;">Switch Mode</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3>⚙️ Staff Configuration</h3>
            <form method="POST">
                <div class="settings-row">
                    <label>👨‍🍳 Kitchen Staff:</label>
                    <input type="number" name="kitchen_staff" value="<?php echo $k_staff; ?>" min="0" required>
                </div>
                <div class="settings-row">
                    <label>🛵 Riders:</label>
                    <input type="number" name="rider_staff" value="<?php echo $r_staff; ?>" min="0" required>
                </div>
                <button type="submit" name="update_settings" class="btn-save">Save Settings</button>
            </form>
            <small style="color:#777; display:block; margin-top:10px;">
                *Formula: (Staff + Riders) x 2 = Max Capacity
            </small>
        </div>
    </div>

    <div class="tabs">
        <a href="?tab=active" class="tab-link <?php echo $tab == 'active' ? 'active' : ''; ?>">🔥 Active</a>
        <a href="?tab=completed" class="tab-link <?php echo $tab == 'completed' ? 'active' : ''; ?>">✅ History</a>
        <a href="?tab=rejected" class="tab-link <?php echo $tab == 'rejected' ? 'active' : ''; ?>">❌ Rejected</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Time</th>
                <th>Details</th>
                <th>Status</th>
                <?php if($tab == 'active'): ?><th>Action</th><?php endif; ?>
                <?php if($tab == 'rejected'): ?><th>Reason</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo date('H:i', strtotime($row['order_date'])); ?></td>
                    <td>
                        <b><?php echo htmlspecialchars($row['customer_name']); ?></b><br>
                        <?php echo $row['pizza_type']; ?> x <?php echo $row['quantity']; ?>
                    </td>
                    <td>
                        <span style="padding:4px 8px; border-radius:10px; font-size:12px; color:white; background:
                            <?php 
                                echo match($row['status']) {
                                    'Pending' => '#ffc107',
                                    'Cooking' => '#fd7e14',
                                    'Delivering' => '#17a2b8',
                                    'Completed' => '#28a745',
                                    'Rejected' => '#dc3545',
                                    default => 'grey'
                                }; 
                            ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <?php if($tab == 'active'): ?>
                    <td>
                        <?php if($row['status'] == 'Pending'): ?>
                            <a href="admin.php?action=cook&id=<?php echo $row['id']; ?>" class="btn btn-cook">Cook</a>
                            <button onclick="rejectOrder(<?php echo $row['id']; ?>)" class="btn btn-reject">❌</button>
                        <?php elseif($row['status'] == 'Cooking'): ?>
                            <a href="admin.php?action=deliver&id=<?php echo $row['id']; ?>" class="btn btn-deliver">Send</a>
                        <?php elseif($row['status'] == 'Delivering'): ?>
                            <a href="admin.php?action=rider_back&id=<?php echo $row['id']; ?>" class="btn btn-done">Finish</a>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    
                    <?php if($tab == 'rejected'): ?>
                        <td style="color:red"><?php echo $row['reject_reason']; ?></td>
                    <?php endif; ?>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center; padding:20px;">No Orders</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Audio Logic
        document.addEventListener("DOMContentLoaded", function() {
            if (sessionStorage.getItem("audio_enabled") === "true") {
                document.getElementById('audioOverlay').style.display = 'none';
            }
        });
        function enableAudio() {
            const sound = document.getElementById('notifSound');
            sound.play().then(() => {
                sound.pause(); sound.currentTime = 0;
                sessionStorage.setItem("audio_enabled", "true");
                document.getElementById('audioOverlay').style.display = 'none';
            }).catch(e => console.log("Audio Blocked"));
        }

        // Notification Logic
        let lastCount = -1; // Force check on load
        function checkNewOrders() {
            fetch('admin.php?check_new_orders=1&_=' + new Date().getTime())
                .then(r => r.text())
                .then(c => {
                    c = parseInt(c);
                    if (lastCount !== -1 && c > lastCount) {
                        document.getElementById('notifSound').play().catch(()=>{});
                        setTimeout(() => location.reload(), 2000);
                    }
                    lastCount = c;
                });
        }
        setInterval(checkNewOrders, 5000);

        function rejectOrder(id) {
            Swal.fire({
                title: 'Reject Reason',
                input: 'text',
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