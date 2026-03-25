<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) { header("Location: login.php"); exit(); }
require_once '../database/db_conn.php'; 

// ==========================================
// 🛠️ AUTO-FIX: STUCK RIDERS
// ==========================================
// Pending/Cooking/Delivering မရှိဘဲ Busy ဖြစ်နေတဲ့ Rider တွေကို ပြန်လွှတ်မယ်
$conn->query("
    UPDATE delivery_slots 
    SET status = 'Free', next_available_time = NULL 
    WHERE status = 'Busy' 
    AND slot_id NOT IN (
        SELECT assigned_slot_id FROM orders 
        WHERE status IN ('Pending', 'Cooking', 'Delivering') 
        AND assigned_slot_id IS NOT NULL
    )
");

// 1. API
if (isset($_GET['check_latest_order'])) {
    $res = $conn->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
    echo json_encode(['latest_id' => ($res->num_rows > 0 ? $res->fetch_assoc()['id'] : 0)]); exit(); 
}

// 2. ACTIONS
if (isset($_GET['action'])) {
    $act = $_GET['action'];
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $now = date('Y-m-d H:i:s');

    // 🔥 NEW: RESET ALL RIDERS BUTTON
    if ($act == 'reset_riders') {
        $conn->query("UPDATE delivery_slots SET status='Free', next_available_time=NULL");
        // အကယ်၍ အော်ဒါတွေရှိနေရင် Rider ပြန်ဖြုတ်မယ် (Clean Slate)
        $conn->query("UPDATE orders SET assigned_slot_id=NULL WHERE status IN ('Pending', 'Cooking')");
        header("Location: kitchen.php");
        exit();
    }

    if ($id > 0) {
        if ($act == 'start_cook') {
            $conn->query("UPDATE orders SET status='Cooking', start_time='$now' WHERE id=$id");
            header("Location: kitchen.php?tab=kitchen");

        } elseif ($act == 'call_rider') {
            $check = $conn->query("SELECT assigned_slot_id FROM orders WHERE id=$id");
            $reserved_slot = $check->fetch_assoc()['assigned_slot_id'] ?? 0;

            if ($reserved_slot > 0) {
                // Use Reserved
                $return_time = date('Y-m-d H:i:s', strtotime("+30 minutes"));
                $conn->query("UPDATE orders SET status='Delivering', departure_time='$now' WHERE id=$id");
                $conn->query("UPDATE delivery_slots SET status='Busy', next_available_time='$return_time' WHERE slot_id=$reserved_slot");
            } else {
                // Find New
                $slot_id = $conn->query("SELECT slot_id FROM delivery_slots WHERE status='Free' LIMIT 1")->fetch_assoc()['slot_id'] ?? 0;
                if ($slot_id) { 
                    $return_time = date('Y-m-d H:i:s', strtotime("+30 minutes"));
                    $conn->query("UPDATE orders SET status='Delivering', departure_time='$now', assigned_slot_id=$slot_id WHERE id=$id");
                    $conn->query("UPDATE delivery_slots SET status='Busy', next_available_time='$return_time' WHERE slot_id=$slot_id");
                } else {
                    $conn->query("UPDATE orders SET status='Delivering', departure_time='$now' WHERE id=$id");
                }
            }
            header("Location: kitchen.php?tab=kitchen");

        } elseif ($act == 'reject') {
            $q = $conn->query("SELECT assigned_slot_id FROM orders WHERE id=$id");
            $sid = $q->fetch_assoc()['assigned_slot_id'] ?? 0;
            if($sid) $conn->query("UPDATE delivery_slots SET status='Free' WHERE slot_id=$sid");
            
            $reason = urldecode($_GET['reason'] ?? 'Kitchen Cancel');
            $conn->query("UPDATE orders SET status='Rejected', reject_reason='$reason' WHERE id=$id");
            header("Location: kitchen.php?tab=kitchen");

        } elseif ($act == 'force_done') {
            $q = $conn->query("SELECT assigned_slot_id FROM orders WHERE id=$id");
            $sid = $q->fetch_assoc()['assigned_slot_id'] ?? 0;
            
            $conn->query("UPDATE orders SET status='Completed', return_time='$now' WHERE id=$id");
            if ($sid) $conn->query("UPDATE delivery_slots SET status='Free' WHERE slot_id=$sid");
            
            header("Location: kitchen.php?tab=completed"); 
        }
    }
}

// 3. FETCH DATA
$tab = $_GET['tab'] ?? 'kitchen';
if ($tab == 'delivering') $sql = "SELECT * FROM orders WHERE status = 'Delivering' ORDER BY id DESC";
elseif ($tab == 'completed') $sql = "SELECT * FROM orders WHERE status = 'Completed' ORDER BY return_time DESC LIMIT 20";
elseif ($tab == 'rejected') $sql = "SELECT * FROM orders WHERE status = 'Rejected' ORDER BY id DESC LIMIT 20";
else $sql = "SELECT * FROM orders WHERE status IN ('Pending', 'Cooking') ORDER BY FIELD(status, 'Pending', 'Cooking'), id DESC";
$result = $conn->query($sql);

$k_staff = intval($conn->query("SELECT setting_value FROM system_config WHERE setting_key='kitchen_staff'")->fetch_assoc()['setting_value']??3);
$max_cap = $k_staff * 4;
$curr_load = intval($conn->query("SELECT SUM(quantity) as total FROM orders WHERE status IN ('Pending','Cooking')")->fetch_assoc()['total']??0);
$cap_pct = ($max_cap > 0) ? ($curr_load / $max_cap) * 100 : 100;

$res_tot = $conn->query("SELECT COUNT(*) as c FROM delivery_slots");
$tot_rid = $res_tot->fetch_assoc()['c'];
$res_free = $conn->query("SELECT COUNT(*) as c FROM delivery_slots WHERE status='Free'");
$free_rid = intval($res_free->fetch_assoc()['c']); 
$busy_rid = $tot_rid - $free_rid;
$deli_pct = ($tot_rid > 0) ? ($busy_rid / $tot_rid) * 100 : 0;

$traffic = $conn->query("SELECT setting_value FROM system_config WHERE setting_key='traffic_mode'")->fetch_assoc()['setting_value']??'0';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8"> <title><?= $lang['kitchen_title'] ?></title>
    <?php if($tab == 'kitchen' || $tab == 'delivering'): ?><meta http-equiv="refresh" content="30"><?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: #222; color: white; font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #333; padding: 15px; border-radius: 8px; border: 1px solid #444; text-align: center; }
        .stat-num { font-size: 1.8em; font-weight: bold; margin: 5px 0; }
        .progress-bar { height: 100%; transition: width 0.5s; }
        .tab-container { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .tab-btn { background: #333; color: #aaa; padding: 10px 20px; text-decoration: none; border-radius: 5px; flex: 1; text-align: center; font-weight: bold; transition: 0.3s; }
        .tab-btn:hover { background: #444; color: white; }
        .tab-btn.active { color: white; border-bottom: 3px solid white; background: #444; }
        .tab-btn.kitchen.active { border-color: #f1c40f; } .tab-btn.delivering.active { border-color: #3498db; }
        .tab-btn.completed.active { border-color: #27ae60; } .tab-btn.rejected.active { border-color: #c0392b; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; }
        .card { background: #333; padding: 15px; border-radius: 8px; border-left: 8px solid grey; }
        .card.pending { border-left-color: #f1c40f; animation: pulse 2s infinite; }
        .card.cooking { border-left-color: #e67e22; } .card.delivering { border-left-color: #3498db; }
        .card.completed { border-left-color: #27ae60; opacity: 0.8; } .card.rejected { border-left-color: #c0392b; opacity: 0.7; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(241, 196, 15, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(241, 196, 15, 0); } 100% { box-shadow: 0 0 0 0 rgba(241, 196, 15, 0); } }
        .btn { width: 100%; padding: 10px; margin-top: 5px; cursor: pointer; border: none; color: white; font-weight: bold; border-radius: 4px; }
        .btn-cook { background: #27ae60; } 
        .btn-rider { background: #e67e22; } 
        .btn-info { background: #3498db; cursor: default; } 
        .action-row { display: flex; gap: 5px; margin-top: 10px; }
        .btn-small { flex: 1; padding: 8px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; color: white; font-weight: bold; }
        .btn-reject { background: #c0392b; } .btn-done { background: #2ecc71; } .btn-print { background: #7f8c8d; }
    </style>
</head>
<body>
    <audio id="bell" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"></audio>
    <iframe id="printFrame" style="width:0; height:0; border:0; position:absolute;"></iframe>

    <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
        <h1 style="margin:0;">👨‍🍳 <?= $lang['kitchen_title'] ?> <small style="font-size:14px; color:#aaa;"><?= $lang['riders_free'] ?>: <?= $free_rid ?></small></h1> 
        <div style="text-align:right;">
            <button onclick="resetRiders()" style="background:#555; border:none; color:#ccc; padding:5px 10px; cursor:pointer; font-size:12px; border-radius:4px;">🔄 Rider Reset</button>
            <a href="admin.php" style="color:#aaa; margin-left:10px;">Exit</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div style="font-size:12px; color:#aaa;">調理負荷 (Kitchen Load)</div>
            <div class="stat-num"><?= $curr_load ?> <small style="font-size:0.5em; color:#888;">/ <?= $max_cap ?></small></div>
            <div style="background:#444; height:10px; border-radius:10px; overflow:hidden;"><div class="progress-bar" style="width:<?= $cap_pct ?>%; background:<?= ($cap_pct>=80)?'#e74c3c':'#f1c40f' ?>;"></div></div>
        </div>
        <div class="stat-card">
            <div style="font-size:12px; color:#aaa;">配達負荷 (Deli Load)</div>
            <div class="stat-num"><?= $busy_rid ?> <small style="font-size:0.5em; color:#888;">/ <?= $tot_rid ?></small></div>
            <div style="background:#444; height:10px; border-radius:10px; overflow:hidden;"><div class="progress-bar" style="width:<?= $deli_pct ?>%; background:#3498db;"></div></div>
        </div>
        <div class="stat-card" style="border-color: <?= ($traffic=='1')?'#c0392b':'#27ae60' ?>;">
            <div style="font-size:12px; color:#aaa;">交通状況 (Traffic)</div>
            <div class="stat-num"><?= ($traffic=='1')?'<span style="color:#e74c3c">混雑 (BUSY)</span>':'<span style="color:#2ecc71">通常 (NORMAL)</span>' ?></div>
        </div>
    </div>

    <div class="tab-container">
        <a href="?tab=kitchen" class="tab-btn kitchen <?= ($tab=='kitchen')?'active':'' ?>">👨‍🍳 調理中</a>
        <a href="?tab=delivering" class="tab-btn delivering <?= ($tab=='delivering')?'active':'' ?>">🚀 配達中</a>
        <a href="?tab=completed" class="tab-btn completed <?= ($tab=='completed')?'active':'' ?>">✅ 完了</a>
        <a href="?tab=rejected" class="tab-btn rejected <?= ($tab=='rejected')?'active':'' ?>">❌ 却下</a>
    </div>

    <div class="grid">
        <?php $max_id=0; if ($result && $result->num_rows > 0): while($row = $result->fetch_assoc()): 
            if ($row['id'] > $max_id) $max_id = $row['id']; 
            
            $reserved = $row['assigned_slot_id'] ?? 0;
            ?>
            <div class="card <?= strtolower($row['status']) ?>">
                <h3>#<?= $row['id'] ?> <small style="color:#ccc; font-size:14px;"><?= date('H:i', strtotime($row['order_date'])) ?></small></h3>
                <div style="font-size: 1.4em; font-weight: bold; margin: 10px 0;"><?= $row['pizza_type'] ?> x <?= $row['quantity'] ?></div>
                <div style="color:#ddd;">Status: <?= $row['status'] ?></div>
                
                <?php if($row['status']=='Pending'): ?>
                    <a href="kitchen.php?action=start_cook&id=<?= $row['id'] ?>"><button class="btn btn-cook">🔥 <?= $lang['cook_btn'] ?></button></a>
                <?php elseif($row['status']=='Cooking'): ?>
                    <button class="btn btn-rider" onclick="checkRider(<?= $row['id'] ?>, <?= $reserved ?>)">🛵 <?= $lang['call_btn'] ?></button>
                <?php elseif($row['status']=='Delivering'): ?>
                    <button class="btn btn-info">🚀 On The Way</button>
                <?php endif; ?>

                <div class="action-row">
                    <button onclick="document.getElementById('printFrame').src='print_ticket.php?id=<?= $row['id'] ?>'" class="btn-small btn-print">🖨️ <?= $lang['print_btn'] ?></button>
                    <?php if($row['status']=='Pending'): ?><button onclick="rejectOrder(<?= $row['id'] ?>)" class="btn-small btn-reject">❌ 拒否</button><?php endif; ?>
                    <?php if($row['status']=='Delivering'): ?>
                        <button onclick="forceDone(<?= $row['id'] ?>)" class="btn-small btn-done">✅ <?= $lang['done_btn'] ?></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; else: ?><p style="color:#666; text-align:center; grid-column: 1/-1;">注文はありません (No orders)</p><?php endif; ?>
    </div>

    <script>
        function resetRiders() {
            Swal.fire({
                title: 'Rider Reset?',
                text: "Force all riders to become 'Free'? (Only do this if system is stuck)",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, Reset All'
            }).then((r) => {
                if (r.isConfirmed) window.location.href = '?action=reset_riders';
            });
        }

        function rejectOrder(id) { Swal.fire({ title: '拒否しますか？', input: 'text', showCancelButton: true, confirmButtonText: 'はい', cancelButtonText: 'いいえ' }).then((r) => { if (r.isConfirmed) window.location.href = `kitchen.php?action=reject&id=${id}&reason=${encodeURIComponent(r.value)}`; }); }
        
        function forceDone(id) { 
            Swal.fire({ 
                title: '配達完了確認', 
                text: "配達員が戻ってきましたか？ (すぐに待機中になります)", 
                icon: 'question', 
                showCancelButton: true, 
                confirmButtonColor: '#2ecc71', 
                confirmButtonText: 'はい、完了',
                cancelButtonText: 'いいえ' 
            }).then((r) => { 
                if (r.isConfirmed) window.location.href = `kitchen.php?action=force_done&id=${id}`; 
            }); 
        }

        function checkRider(id, reservedId) {
            if (reservedId > 0) {
                window.location.href = `kitchen.php?action=call_rider&id=${id}`;
            } else {
                let freeRiders = <?= $free_rid ?>; 
                if (freeRiders > 0) {
                    window.location.href = `kitchen.php?action=call_rider&id=${id}`;
                } else {
                    Swal.fire({
                        title: '配達員がいません！',
                        text: "すべての配達員が配送中です。強制的に出発させますか？",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e67e22',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'はい、強制出発',
                        cancelButtonText: '待つ'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `kitchen.php?action=call_rider&id=${id}`;
                        }
                    });
                }
            }
        }

        <?php if($tab == 'kitchen'): ?>
        let currMax=<?= $max_id ?>; 
        setInterval(() => { 
            fetch('kitchen.php?check_latest_order=1').then(r=>r.json()).then(d=>{ 
                let sid=parseInt(d.latest_id); let lpid=localStorage.getItem('lpid')||0; 
                if(sid > currMax && sid > lpid) { 
                    console.log("New Order! Printing #" + sid);
                    document.getElementById('bell').play().catch(()=>{});
                    document.getElementById('printFrame').src = 'print_ticket.php?id='+sid;
                    localStorage.setItem('lpid', sid);
                    currMax=sid; 
                    setTimeout(()=>location.reload(), 3000); 
                } 
            }); 
        }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>