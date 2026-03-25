<?php 
require_once 'admin_logic.php'; 

// Sales Calculation
$today = date('Y-m-d');
$sales_sql = "SELECT SUM(
                CASE 
                    WHEN pizza_type='S' THEN 1000 
                    WHEN pizza_type='M' THEN 2000 
                    WHEN pizza_type='L' THEN 3000 
                    ELSE 0 
                END * quantity
              ) as total FROM orders WHERE status='Completed' AND DATE(return_time) = '$today'";
$sales_res = $conn->query($sales_sql);
$daily_sales = $sales_res->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>Store Management (Admin)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; padding: 20px; color: #333; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .stat-num { font-size: 2em; font-weight: bold; color: #2c3e50; margin: 5px 0; }
        .stat-label { color: #7f8c8d; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }

        /* Control Panel Grid */
        .control-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        
        /* Traffic Box */
        .traffic-box { background: white; padding: 20px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-left: 5px solid #6c757d; }
        
        /* Link Buttons */
        .menu-btn { display: inline-flex; align-items: center; padding: 10px 20px; background: white; border-radius: 8px; text-decoration: none; color: #333; font-weight: bold; margin-right: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: 0.2s; }
        .menu-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .menu-icon { margin-right: 10px; font-size: 1.2em; }

        /* Table Styles */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #343a40; color: white; text-transform: uppercase; font-size: 0.85em; }
        
        /* Action Buttons */
        .btn-action { padding: 5px 10px; border-radius: 4px; color: white; text-decoration: none; font-size: 12px; border: none; cursor: pointer; display: inline-block; }
        .btn-reject { background: #dc3545; }
        .btn-force { background: #28a745; }
        
        /* Staff Form */
        .staff-input { width: 50px; padding: 5px; text-align: center; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <div>
            <h1 style="margin:0;">🏪 Manager Dashboard</h1>
            <span style="color:#666; font-size:14px;">Welcome Admin | <?= date('Y-m-d') ?></span>
        </div>
        <div>
            <a href="kitchen.php" target="_blank" class="menu-btn" style="border-left: 5px solid #e67e22;">
                <span class="menu-icon">🍳</span> Open Kitchen Monitor
            </a>
            <a href="login.php" style="color: red; text-decoration: none; margin-left: 10px;">Logout</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="border-bottom: 4px solid #27ae60;">
            <div class="stat-label">Today's Sales (Uriage)</div>
            <div class="stat-num">¥<?= number_format($daily_sales) ?></div>
        </div>
        
        <div class="stat-card" style="border-bottom: 4px solid #e67e22;">
            <div class="stat-label">Kitchen Load</div>
            <div class="stat-num">
                <?= $current_kitchen_load ?> <small style="font-size:0.4em; color:#999;">/ <?= $max_kitchen_capacity ?> Items</small>
            </div>
            <div style="background:#eee; height:5px; border-radius:5px; margin-top:5px;">
                <div style="width:<?= $capacity_percent ?>%; height:100%; background:<?= ($capacity_percent>80)?'red':'orange' ?>; border-radius:5px;"></div>
            </div>
        </div>
        
        <div class="stat-card" style="border-bottom: 4px solid #2980b9;">
            <div class="stat-label">Active Riders (Deli Load)</div>
            <div class="stat-num">
                <?= $busy_riders_db ?> <small style="font-size:0.4em; color:#999;">/ <?= $total_riders_db ?></small>
            </div>
            <div style="background:#eee; height:5px; border-radius:5px; margin-top:5px;">
                <div style="width:<?= $deli_percent ?>%; height:100%; background:#2980b9; border-radius:5px;"></div>
            </div>
        </div>
    </div>

    <div class="control-grid">
        <div class="traffic-box">
            <div>
                <strong>🚦 Traffic / Weather Mode</strong><br>
                <small style="color:#666;">Turn ON if rain or heavy traffic (+15 mins delay).</small>
            </div>
            <form method="POST" action="admin_logic.php">
                <?php if($traffic_mode == '1'): ?>
                    <button type="submit" name="toggle_traffic" class="btn-action" style="background: #dc3545; font-size:14px; padding:10px 20px;">⛔ BUSY MODE (ON)</button>
                <?php else: ?>
                    <button type="submit" name="toggle_traffic" class="btn-action" style="background: #28a745; font-size:14px; padding:10px 20px;">✅ NORMAL MODE</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="traffic-box" style="border-left-color: #34495e; display:block;">
            <form method="POST" action="admin_logic.php" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>⚙️ Staffing</strong><br>
                    <small>Chefs: <input type="number" name="kitchen_staff" value="<?= $k_staff ?>" class="staff-input"> </small>
                    <small>Riders: <input type="number" name="rider_staff" value="<?= $total_riders_db ?>" class="staff-input"> </small>
                </div>
                <button type="submit" name="update_settings" class="btn-action" style="background:#34495e;">Save</button>
            </form>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <a href="manage_shops.php" class="menu-btn">📍 Manage Shops</a>
        <a href="order_history.php" class="menu-btn">📜 Order History</a>
        <a href="?tab=active" class="menu-btn <?= ($tab=='active'?'':'style="opacity:0.6"') ?>">🔥 Active Orders</a>
    </div>

    <h3>📋 Active Orders Oversight</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Time</th>
                <th>Customer</th>
                <th>Order Items</th>
                <th>Status</th>
                <th>Admin Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Active Orders (Pending, Cooking, Delivering)
            $sql_active = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking', 'Delivering') ORDER BY id ASC";
            $res_active = $conn->query($sql_active);
            
            if ($res_active && $res_active->num_rows > 0): 
                while($row = $res_active->fetch_assoc()): 
            ?>
            <tr>
                <td>#<?= $row['id'] ?></td>
                <td><?= date('H:i', strtotime($row['order_date'])) ?></td>
                <td>
                    <b><?= htmlspecialchars($row['customer_name']) ?></b><br>
                    <small>📞 <?= htmlspecialchars($row['phonenumber']) ?></small>
                </td>
                <td><?= $row['pizza_type'] ?> x <?= $row['quantity'] ?></td>
                <td>
                    <span style="padding:4px 8px; border-radius:10px; font-size:11px; color:white; background:
                        <?= match($row['status']) {
                            'Pending' => '#f1c40f',
                            'Cooking' => '#e67e22',
                            'Delivering' => '#3498db',
                            default => 'grey'
                        }; ?>">
                        <?= $row['status'] ?>
                    </span>
                    <?php if($row['assigned_slot_id']): ?>
                        <br><small style="font-size:10px; color:#666;">Rider: #<?= $row['assigned_slot_id'] ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($row['status'] == 'Pending'): ?>
                        <button onclick="rejectOrder(<?= $row['id'] ?>)" class="btn-action btn-reject">❌ Reject</button>
                    <?php elseif($row['status'] == 'Delivering'): ?>
                        <a href="admin_logic.php?action=rider_back&id=<?= $row['id'] ?>" class="btn-action btn-force" onclick="return confirm('Force Complete?')">Force Done</a>
                    <?php else: ?>
                        <span style="color:#ccc;">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">No Active Orders</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function rejectOrder(id) {
            Swal.fire({
                title: 'Reject Order?',
                input: 'text',
                inputPlaceholder: 'Reason (e.g. Out of stock)',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `admin_logic.php?action=reject&id=${id}&reason=${encodeURIComponent(result.value || 'Shop Busy')}`;
                }
            });
        }
    </script>

</body>
</html>