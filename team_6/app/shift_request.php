<?php
include 'db/connect.php';
session_start();

// --- 1. MONTH & DATE HANDLING ---
$monthOffset = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$currentMonth = date('Y-m-01', strtotime("$monthOffset month"));
$monthName = date('F Y', strtotime($currentMonth));
$lastDay = date('Y-m-t', strtotime($currentMonth));

// --- 2. FETCH TARGETS & NOTICES ---
$stmt = $db->prepare("SELECT * FROM shift_targets WHERE target_date BETWEEN ? AND ?");
$stmt->execute([$currentMonth, $lastDay]);
$monthTargets = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

// --- 3. FETCH CURRENT APPROVED COUNTS ---
// Note: $row['post'] will now be 'cook', 'driver', or 'manager'
$countQuery = $db->query("
    SELECT shift_date, shift_type, post, COUNT(*) as total 
    FROM shift_requests 
    WHERE status = 'Approved' AND shift_date BETWEEN '$currentMonth' AND '$lastDay'
    GROUP BY shift_date, shift_type, post
");
$roleCountMap = [];
while ($row = $countQuery->fetch(PDO::FETCH_ASSOC)) {
    $roleCountMap[$row['shift_date']][$row['shift_type']][$row['post']] = $row['total'];
}

// --- LOGIN LOGIC ---
if (isset($_POST['login'])) {
    $_SESSION['user_id'] = $_POST['staff_user_id'];
}
if (isset($_POST['logout'])) {
    unset($_SESSION['user_id']);
    header("Location: shift_request.php?month=$monthOffset"); exit;
}

$activeUserID = $_SESSION['user_id'] ?? '';
$myRequests = [];
$userData = null;

if (!empty($activeUserID)) {
    $stf = $db->prepare("SELECT id, name, post FROM staff WHERE user_id = ?");
    $stf->execute([$activeUserID]);
    $userData = $stf->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $myStmt = $db->prepare("SELECT shift_date, shift_type, status FROM shift_requests WHERE staff_user_id = ? AND shift_date BETWEEN ? AND ?");
        $myStmt->execute([$activeUserID, $currentMonth, $lastDay]);
        while ($r = $myStmt->fetch(PDO::FETCH_ASSOC)) {
            $myRequests[$r['shift_date']][$r['shift_type']] = $r['status'];
        }
    }
}

// --- 4. HANDLE SHIFT REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_shift']) && $userData) {
    $s_date = $_POST['shift_date'];
    $s_type = $_POST['shift_type'];
    
    // userData['post'] is now English ('cook', 'driver', 'manager')
    $roleKey = $userData['post']; 
    $prefix  = ($s_type === 'Morning') ? 'm_' : 'e_';
    $colName = $prefix . $roleKey . '_needed';
    
    $targetLimit = $monthTargets[$s_date][$colName] ?? (($roleKey == 'cook') ? 3 : (($roleKey == 'driver') ? 2 : 1));
    $currentCount = $roleCountMap[$s_date][$s_type][$roleKey] ?? 0;

    if ($currentCount < $targetLimit) {
        try {
            $db->beginTransaction();
            $insReq = $db->prepare("INSERT INTO shift_requests (staff_user_id, name, post, shift_date, shift_type, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
            $insReq->execute([$activeUserID, $userData['name'], $roleKey, $s_date, $s_type]);
            $db->commit();
            header("Location: shift_request.php?month=$monthOffset"); exit;
        } catch (Exception $e) { $db->rollBack(); }
    }
}

$dates = [];
for ($i = 0; $i < date('t', strtotime($currentMonth)); $i++) {
    $d = date('Y-m-d', strtotime("+$i days", strtotime($currentMonth)));
    $dates[$d] = date('D', strtotime($d));
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Shift System</title>
    <style>
        :root{--primary:#4361ee;--success:#2ec4b6;--danger:#e71d36;--warning:#ff9f1c;--dark:#011627;--light:#f8f9fa;}
        body{font-family:'Helvetica Neue', Arial, sans-serif; background:#f0f2f5; margin:0; padding:20px; color:var(--dark);}
        .container{max-width:1100px; margin:auto;}
        .hero-box { padding:20px; border-radius:15px; margin-bottom:20px; border-left:8px solid; background:white; box-shadow:0 4px 12px rgba(0,0,0,0.08); position:relative; overflow:hidden;}
        .hero-box.Normal { border-color: var(--primary); background: #f0f7ff; }
        .hero-box.Busy { border-color: var(--warning); background: #fffaf0; }
        .hero-box.Critical { border-color: var(--danger); background: #fff5f5; }
        .hero-box small { text-transform:uppercase; font-weight:bold; letter-spacing:1px; color:#666; }
        .user-auth-card { background:var(--dark); color:white; padding:20px; border-radius:15px; display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; box-shadow:0 10px 20px rgba(0,0,0,0.2); }
        .auth-field form { display:flex; gap:10px; }
        .auth-input { padding:12px; border:none; border-radius:8px; width:200px; font-size:1rem; }
        .login-btn { background:var(--primary); color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold; }
        .logout-btn { background:transparent; border:1px solid #ff4d4d; color:#ff4d4d; padding:5px 10px; border-radius:5px; cursor:pointer; font-size:0.8rem; }
        .user-display { text-align:right; }
        table{width:100%; border-collapse:separate; border-spacing:0; background:white; border-radius:15px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.05);}
        th{background:var(--light); padding:18px; color:var(--dark); font-weight:700; border-bottom:2px solid #eee;}
        td{padding:15px; text-align:center; border-bottom:1px solid #f0f0f0; vertical-align:top; transition: 0.3s; }
        .date-cell.has-notice { background-color: #e3f2fd; }
        .notice-indicator { font-size: 0.7rem; color: var(--primary); font-weight: bold; background: #d0e7ff; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 5px; }
        .badge { padding:10px; border-radius:8px; font-weight:bold; font-size:0.9rem; display:block; margin-bottom:10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);}
        .badge-approved { background:var(--success); color:white; }
        .badge-pending { background:var(--warning); color:white; }
        .badge-canceled { background:var(--danger); color:white; }
        .badge-full { background:#ddd; color:#777; }
        .req-btn { width:100%; background:var(--primary); color:white; border:none; padding:12px; border-radius:8px; cursor:pointer; font-weight:bold; transition:0.2s; }
        .req-btn:hover { background:#3046c8; transform:scale(1.02); }
        .cap-info { background: #f8f9fa; border-radius:10px; padding:10px; margin-top:10px; display:grid; grid-template-columns: 1fr; gap:4px; border:1px solid #eee;}
        .cap-row { display:flex; justify-content:space-between; font-size:0.85rem; font-weight:600; color:#444;}
        .num-red { color:var(--danger); }
        .num-ok { color:var(--success); }
    </style>
</head>
<body>
<div class="container">

    <?php 
    $tmr = date('Y-m-d', strtotime('tomorrow')); 
    $tmrData = $monthTargets[$tmr] ?? null; 
    $urgency = $tmrData['urgency'] ?? 'Normal';
    ?>
    <div class="hero-box <?= $urgency ?>">
        <small>📢 管理者の指示 (<?= $tmr ?>)</small>
        <h2 style="margin:10px 0;"><?= htmlspecialchars($tmrData['admin_notice'] ?? '本日の連絡事項はありません。') ?></h2>
    </div>

    <div class="user-auth-card">
        <div class="auth-field">
            <?php if(!$userData): ?>
                <form method="POST">
                    <input type="text" name="staff_user_id" class="auth-input" placeholder="スタッフIDを入力" required>
                    <button name="login" class="login-btn">ログイン</button>
                </form>
            <?php else: ?>
                <div class="user-display">
                    <span style="font-size:1.2rem; font-weight:bold;">👤 <?= htmlspecialchars($userData['name']) ?></span>
                    <span style="margin-left:15px; color:#aaa;">役職: <?= htmlspecialchars($userData['post']) ?></span>
                    <form method="POST" style="display:inline; margin-left:15px;">
                        <button name="logout" class="logout-btn">ログアウト</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <div style="font-size:0.9rem; opacity:0.7; text-align:right;">
            SHIFT REQUEST SYSTEM<br>v2.0
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; margin-bottom:20px; align-items:center;">
        <a href="?month=<?= $monthOffset-1 ?>" style="color:var(--primary); font-weight:bold; text-decoration:none;">◀ 前の月</a>
        <h1 style="margin:0; font-size:1.8rem;"><?= $monthName ?></h1>
        <a href="?month=<?= $monthOffset+1 ?>" style="color:var(--primary); font-weight:bold; text-decoration:none;">次の月 ▶</a>
    </div>

    <table>
        <thead>
            <tr>
                <th width="14%">日付</th>
                <th>AM モーニング (10-16)</th>
                <th>PM イブニング (16-22)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($dates as $date => $day): 
                $dayTarget = $monthTargets[$date] ?? null;
                $isSpecial = !empty($dayTarget['admin_notice']);
            ?>
            <tr>
                <td class="date-cell <?= $isSpecial ? 'has-notice' : '' ?>">
                    <strong style="font-size:1.2rem;"><?= date('d', strtotime($date)) ?></strong><br>
                    <span><?= $day ?></span>
                    <?php if($isSpecial): ?>
                        <br><span class="notice-indicator">注記あり</span>
                    <?php endif; ?>
                </td>
                
                <?php foreach(['Morning', 'Evening'] as $type): 
                    $status = $myRequests[$date][$type] ?? null;
                    $prefix = ($type == 'Morning' ? 'm_' : 'e_');

                    // MAPPING Logic for Display
                    $roles = [
                        'cook' => ['jp' => '料理人', 'icon' => '🍳'],
                        'driver' => ['jp' => '配達員', 'icon' => '🚗'],
                        'manager' => ['jp' => 'マネージャー', 'icon' => '👤']
                    ];

                    $myRole = $userData['post'] ?? ''; // e.g. 'cook'
                    $isFull = false;
                    if (!empty($myRole)) {
                        $c = $roleCountMap[$date][$type][$myRole] ?? 0;
                        $t = $dayTarget[$prefix . $myRole . '_needed'] ?? ($myRole == 'cook' ? 3 : ($myRole == 'driver' ? 2 : 1));
                        $isFull = ($c >= $t);
                    }
                ?>
                <td>
                    <?php if(!$userData): ?>
                        <span style="color:#ccc; font-size:0.8rem;">ログイン後に申請可能</span>
                    <?php elseif($status === 'Approved'): ?>
                        <span class="badge badge-approved">✅ 確定</span>
                    <?php elseif($status === 'Pending'): ?>
                        <span class="badge badge-pending">⏳ 申請中</span>
                    <?php elseif($status === 'Canceled'): ?>
                        <span class="badge badge-canceled">❌ 却下</span>
                    <?php elseif($isFull): ?>
                        <span class="badge badge-full">🈵 満員</span>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="shift_date" value="<?= $date ?>">
                            <input type="hidden" name="shift_type" value="<?= $type ?>">
                            <button name="request_shift" class="req-btn">申請する</button>
                        </form>
                    <?php endif; ?>

                    <div class="cap-info">
                        <?php foreach($roles as $engKey => $info): 
                            $c = $roleCountMap[$date][$type][$engKey] ?? 0;
                            $t = $dayTarget[$prefix . $engKey . '_needed'] ?? ($engKey == 'cook' ? 3 : ($engKey == 'driver' ? 2 : 1));
                            $isRoleFull = ($c >= $t);
                        ?>
                        <div class="cap-row">
                            <span><?= $info['icon'] ?> <?= $info['jp'] ?></span>
                            <span class="<?= $isRoleFull ? 'num-red' : 'num-ok' ?>"><?= $c ?> / <?= $t ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>