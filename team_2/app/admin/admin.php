<?php
// admin/admin_logic.php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
date_default_timezone_set('Asia/Tokyo');
require_once '../database/db_conn.php';

// --- DATABASE CHECK ---
$check_table = $conn->query("SHOW TABLES LIKE 'delivery_slots'");
if ($check_table->num_rows == 0) {
    $conn->query("CREATE TABLE `delivery_slots` (`slot_id` int(11) NOT NULL AUTO_INCREMENT, `status` varchar(20) DEFAULT 'Free', `next_available_time` datetime DEFAULT NULL, PRIMARY KEY (`slot_id`))");
    $conn->query("INSERT INTO `delivery_slots` (`status`) VALUES ('Free'), ('Free')");
    $conn->query("ALTER TABLE `orders` ADD COLUMN `assigned_slot_id` int(11) DEFAULT NULL");
}

// --- Update Settings ---
if (isset($_POST['update_settings'])) {
    $k = intval($_POST['kitchen_staff']);
    $d = intval($_POST['rider_staff']);
    file_put_contents('staff_config.txt', "$k,$d");
    
    // Sync Rider Slots
    $res = $conn->query("SELECT COUNT(*) as c FROM delivery_slots");
    $current_slots = $res->fetch_assoc()['c'];
    if ($d > $current_slots) {
        $needed = $d - $current_slots;
        for ($i = 0; $i < $needed; $i++) $conn->query("INSERT INTO delivery_slots (status) VALUES ('Free')");
    } elseif ($d < $current_slots) {
        $remove = $current_slots - $d;
        $conn->query("DELETE FROM delivery_slots ORDER BY slot_id DESC LIMIT $remove");
    }
    header("Location: admin.php"); exit();
}

$k_staff = 3; $r_staff = 2;
if (file_exists('staff_config.txt')) {
    $data = explode(',', file_get_contents('staff_config.txt'));
    $k_staff = isset($data[0]) ? intval($data[0]) : 3;
    $r_staff = isset($data[1]) ? intval($data[1]) : 2;
}
$max_capacity = ($k_staff * 4) + ($r_staff * 2); 

// --- Toggle Traffic ---
if (isset($_POST['toggle_traffic'])) {
    $current = file_exists('traffic_status.txt') ? file_get_contents('traffic_status.txt') : '0';
    file_put_contents('traffic_status.txt', ($current == '1' ? '0' : '1'));
    header("Location: admin.php"); exit();
}

// --- Action Handling ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $act = $_GET['action'];
    $now = date('Y-m-d H:i:s');

    if ($act == 'cook') {
        $conn->query("UPDATE orders SET status='Cooking', start_time='$now' WHERE id=$id");
        header("Location: admin.php"); exit();
    } elseif ($act == 'deliver') {
        // Simple Assign Logic
        $sql_free = "SELECT slot_id FROM delivery_slots WHERE slot_id NOT IN (SELECT assigned_slot_id FROM orders WHERE status='Delivering' AND assigned_slot_id IS NOT NULL) LIMIT 1";
        $free_slot_res = $conn->query($sql_free);
        if ($free_slot_res && $free_slot_res->num_rows > 0) {
            $slot_id = $free_slot_res->fetch_assoc()['slot_id'];
            $conn->query("UPDATE orders SET status='Delivering', departure_time='$now', assigned_slot_id=$slot_id WHERE id=$id");
            header("Location: admin.php"); exit();
        } else {
            echo "<script>alert('❌ Riders Busy!'); window.location.href='admin.php';</script>"; exit();
        }
    } elseif ($act == 'rider_back') {
        $conn->query("UPDATE orders SET status='Completed', return_time='$now' WHERE id=$id");
        header("Location: admin.php"); exit();
    } elseif ($act == 'reject') {
        $reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : 'Shop Busy';
        $stmt = $conn->prepare("UPDATE orders SET status='Rejected', reject_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $id);
        $stmt->execute();
        header("Location: admin.php"); exit();
    }
}

// --- Data Fetching ---
if (isset($_GET['check_new_orders'])) {
    $result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'");
    echo $result->fetch_assoc()['count']; exit();
}

// Kitchen Load
$active_res = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('Pending', 'Cooking')");
$kitchen_active = $active_res->fetch_assoc()['c'];
$capacity_percent = ($max_capacity > 0) ? ($kitchen_active / $max_capacity) * 100 : 100;
if($capacity_percent > 100) $capacity_percent = 100;

// --- RIDER BUSY LOGIC (Edited) ---
// Total Rider Slots
$res_total = $conn->query("SELECT COUNT(*) as c FROM delivery_slots");
$total_riders_db = ($res_total) ? $res_total->fetch_assoc()['c'] : 0;

// Busy Rider Count = Pending + Cooking + Delivering
// (အော်ဒါဝင်တာနဲ့ Rider တစ်ယောက်စာ နေရာယူထားမယ်လို့ တွက်လိုက်သည်)
$res_busy = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering')");
$busy_riders_db = ($res_busy) ? $res_busy->fetch_assoc()['c'] : 0;

// Free Riders
$free_riders = $total_riders_db - $busy_riders_db;
if ($free_riders < 0) $free_riders = 0; // Rider ထက် အော်ဒါများနေရင် 0 ပဲပြမယ်

// Order List
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
if ($tab == 'active') {
    $sql = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering') ORDER BY FIELD(status, 'Pending', 'Cooking', 'Delivering'), order_date ASC";
} else {
    $status = ($tab == 'rejected') ? 'Rejected' : 'Completed';
    $sql = "SELECT * FROM orders WHERE status = '$status' ORDER BY order_date DESC LIMIT 50";
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
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #555; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .progress-container { background: #e9ecef; border-radius: 20px; height: 25px; width: 100%; overflow: hidden; margin-top: 5px; }
        .progress-bar { height: 100%; text-align: center; line-height: 25px; color: white; font-weight: bold; font-size: 14px; transition: width 0.5s; }
        .settings-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        .settings-row input { width: 60px; padding: 5px; text-align: center; border: 1px solid #ddd; border-radius: 5px; }
        .btn-save { background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; width: 100%; }
        .traffic-box { display: flex; justify-content: space-between; align-items: center; background: <?= ($traffic_mode=='1') ? '#ffebee' : '#e8f5e9'; ?>; padding: 15px; border-radius: 8px; border: 1px solid <?= ($traffic_mode=='1') ? '#ffcdd2' : '#c8e6c9'; ?>; }
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
        #audioOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 999; display: flex; justify-content: center; align-items: center; }
        .btn-start { background: #28a745; color: white; padding: 15px 30px; border: none; border-radius: 50px; font-size: 18px; cursor: pointer; }
    </style>
</head>
<body>

    <audio id="notifSound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
    <div id="audioOverlay"><button class="btn-start" onclick="enableAudio()">🔊 Start Dashboard</button></div>

    <h2>Admin Dashboard</h2>
    <a href="manage_shops.php" class="btn" style="background: #6f42c1; margin-bottom:15px; display:inline-block;">📍 Manage Partner Shops</a>
    
    <div class="dashboard-grid">
        <div class="card">
            <h3>📊 Status Overview</h3>
            
            <div style="margin-bottom: 15px;">
                <div style="display:flex; justify-content:space-between;">
                    <span>👨‍🍳 Kitchen Load (Items):</span>
                    <strong><?= $current_kitchen_load ?> / <?= $max_kitchen_capacity ?></strong>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= $capacity_percent ?>%; background: <?= ($capacity_percent >= 80) ? '#dc3545' : (($capacity_percent >= 50) ? '#ffc107' : '#28a745'); ?>;">
                        <?= round($capacity_percent) ?>%
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <div style="display:flex; justify-content:space-between;">
                    <span>🛵 Free Riders:</span>
                    <strong style="color: <?= $free_riders > 0 ? 'green' : 'red' ?>; font-size:1.1em;"><?= $free_riders ?> / <?= $total_riders_db ?></strong>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?= ($total_riders_db > 0 ? ($busy_riders_db/$total_riders_db)*100 : 0) ?>%; background: #17a2b8;">
                        <?= $busy_riders_db ?> Busy
                    </div>
                </div>
            </div>

            <form method="POST" class="traffic-box" style="margin-top:20px;">
                <div>
                    <strong>Traffic Mode:</strong><br>
                    <?= ($traffic_mode == '1') ? '<span style="color:red">⛔ Busy (Wait +15m)</span>' : '<span style="color:green">✅ Normal</span>'; ?>
                </div>
                <button type="submit" name="toggle_traffic" class="btn" style="background: #555;">Switch</button>
            </form>
        </div>

        <div class="card">
            <h3>⚙️ Staff Configuration</h3>
            <form method="POST">
                <div class="settings-row">
                    <label>👨‍🍳 Kitchen Staff:</label>
                    <input type="number" name="kitchen_staff" value="<?= $k_staff ?>" min="1" required>
                </div>
                <div class="settings-row">
                    <label>🛵 Riders (Slots):</label>
                    <input type="number" name="rider_staff" value="<?= $total_riders_db ?>" min="1" required>
                </div>
                <button type="submit" name="update_settings" class="btn-save">Update & Sync DB</button>
            </form>
            <p style="font-size:12px; color:#666; margin-top:10px;">
                * Smart Routing Active: Riders can take multiple orders if within 2km.
            </p>
        </div>
    </div>

    <div class="tabs">
        <a href="?tab=active" class="tab-link <?= $tab == 'active' ? 'active' : '' ?>">🔥 Active</a>
        <a href="?tab=completed" class="tab-link <?= $tab == 'completed' ? 'active' : '' ?>">✅ History</a>
        <a href="?tab=rejected" class="tab-link <?= $tab == 'rejected' ? 'active' : '' ?>">❌ Rejected</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Time</th>
                <th>Customer</th> <th>Status</th>
                <?php if($tab == 'active'): ?><th>Action</th><?php endif; ?>
                <?php if($tab == 'rejected'): ?><th>Reason</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td><?= date('H:i', strtotime($row['order_date'])) ?></td>
                    <td>
                        <b><?= htmlspecialchars($row['customer_name']) ?></b>
                        <br>
                        📞 <span style="color:#007bff"><?= htmlspecialchars($row['phonenumber']) ?></span>
                        <br>
                        <small><?= $row['pizza_type'] ?> x <?= $row['quantity'] ?></small>
                        <?php if(!empty($row['latitude'])): ?>
                            <br><span style="font-size:10px; color:green;">📍 Location Found</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="padding:4px 8px; border-radius:10px; font-size:12px; color:white; background:
                            <?= match($row['status']) {
                                'Pending' => '#ffc107',
                                'Cooking' => '#fd7e14',
                                'Delivering' => '#17a2b8',
                                'Completed' => '#28a745',
                                'Rejected' => '#dc3545',
                                default => 'grey'
                            }; ?>">
                            <?= $row['status'] ?>
                        </span>
                        <?php if($row['assigned_slot_id']): ?>
                            <br><small style="color:#666;">Rider #<?= $row['assigned_slot_id'] ?></small>
                        <?php endif; ?>
                    </td>
                    <?php if($tab == 'active'): ?>
                    <td>
                        <?php if($row['status'] == 'Pending'): ?>
                            <a href="admin.php?action=cook&id=<?= $row['id'] ?>" class="btn btn-cook">Cook</a>
                            <button onclick="rejectOrder(<?= $row['id'] ?>)" class="btn btn-reject">❌</button>
                        <?php elseif($row['status'] == 'Cooking'): ?>
                            <a href="admin.php?action=deliver&id=<?= $row['id'] ?>" class="btn btn-deliver">Send</a>
                        <?php elseif($row['status'] == 'Delivering'): ?>
                            <span style="color:grey; font-size:11px; display:block; margin-bottom:5px;">Waiting Customer</span>
                            <a href="admin.php?action=rider_back&id=<?= $row['id'] ?>" class="btn btn-done" onclick="return confirm('Force Complete? Rider will be freed.')">Force Done</a>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    
                    <?php if($tab == 'rejected'): ?>
                        <td style="color:red"><?= htmlspecialchars($row['reject_reason']) ?></td>
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

        let lastCount = -1;
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
        setInterval(checkNewOrders, 3000);

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