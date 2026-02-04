<?php
include 'db/connect.php';

// PostgreSQL compatible date fetching
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('tomorrow'));
$next7 = date('Y-m-d', strtotime('+6 days'));

// Standard shift times for automatic approval
$shift_times = [
    'Morning' => ['start' => '10:00:00', 'end' => '16:00:00'],
    'Evening' => ['start' => '16:00:00', 'end' => '22:00:00']
];

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. UPDATE TARGETS & NOTICES (The "Forgot" Part)
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'update_targets') {
            $target_date = $_POST['target_date'] ?? $tomorrow;
            $stmt = $db->prepare("
                INSERT INTO shift_targets (target_date, admin_notice, urgency, m_cook_needed, m_driver_needed, m_manager_needed)
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (target_date) DO UPDATE 
                SET admin_notice = EXCLUDED.admin_notice, 
                    urgency = EXCLUDED.urgency,
                    m_cook_needed = EXCLUDED.m_cook_needed,
                    m_driver_needed = EXCLUDED.m_driver_needed,
                    m_manager_needed = EXCLUDED.m_manager_needed
            ");
            $stmt->execute([
                $target_date, 
                $_POST['admin_notice'], 
                $_POST['urgency'], 
                (int)$_POST['m_cook_needed'], 
                (int)$_POST['m_driver_needed'],
                (int)$_POST['m_manager_needed']
            ]);
            echo "<script>alert('✅ 目標と通知を更新しました'); window.location='shift.php';</script>";
            exit;
        }

        // 2. BULK DELETE SHIFTS
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'bulk_delete_shifts') {
            $ids = $_POST['shift_ids'] ?? [];
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("DELETE FROM staff_shift WHERE id IN ($placeholders)");
                $stmt->execute($ids);
            }
            header("Location: shift.php"); exit;
        }

        // 3. APPROVE / CANCEL SINGLE REQUEST (FIXED: NOW UPDATES SHIFT TABLE)
        if (isset($_POST['req_action'])) {
            $id = intval($_POST['request_id']);
            if ($_POST['req_action'] === "approve") {
                // Get the request details
                $q = $db->prepare("SELECT * FROM shift_requests WHERE id = ?");
                $q->execute([$id]);
                $req = $q->fetch(PDO::FETCH_ASSOC);

                if ($req) {
                    // Find internal staff ID based on User ID
                    $q2 = $db->prepare("SELECT id FROM staff WHERE user_id = ?");
                    $q2->execute([$req["staff_user_id"]]);
                    $staff = $q2->fetch(PDO::FETCH_ASSOC);

                    if ($staff) {
                        $times = $shift_times[$req["shift_type"]] ?? ['start'=>'00:00:00','end'=>'00:00:00'];
                        
                        // AUTOMATICALLY ADD TO STAFF_SHIFT TABLE
                        $insert = $db->prepare("
                            INSERT INTO staff_shift (staff_id, post, shift_date, shift_type, shift_start, shift_end, remarks, status) 
                            VALUES (?, ?, ?::date, ?, ?::time, ?::time, ?, 'Approved')
                        ");
                        $insert->execute([
                            $staff["id"], 
                            $req["post"], 
                            $req["shift_date"], 
                            $req["shift_type"], 
                            $times['start'], 
                            $times['end'], 
                            "Approved Request"
                        ]);
                        
                        // Update Request Status
                        $db->prepare("UPDATE shift_requests SET status='Approved' WHERE id=?")->execute([$id]);
                    }
                }
            } elseif ($_POST['req_action'] === "cancel") {
                $db->prepare("UPDATE shift_requests SET status='Canceled' WHERE id=?")->execute([$id]);
            }
            header("Location: shift.php"); exit;
        }

        // 4. MANUAL SHIFT REGISTRATION
        if (isset($_POST['action']) && $_POST['action'] === 'shift') {
            $user_id = $_POST['shift_user_id'];
            $find = $db->prepare("SELECT id FROM staff WHERE user_id = ?");
            $find->execute([$user_id]);
            $staff = $find->fetch(PDO::FETCH_ASSOC);

            if ($staff) {
                $startDT = new DateTime($_POST['shift_start']);
                $endDT = new DateTime($_POST['shift_end']);
                $stmt = $db->prepare("
                    INSERT INTO staff_shift (staff_id, post, shift_date, shift_type, shift_start, shift_end, remarks, status)
                    VALUES (?, ?, ?::date, 'Morning', ?::time, ?::time, ?, 'Approved')
                ");
                $stmt->execute([
                    $staff['id'], $_POST['post'], 
                    $startDT->format('Y-m-d'), $startDT->format('H:i:s'), 
                    $endDT->format('H:i:s'), $_POST['remarks']
                ]);
                header("Location: shift.php"); exit;
            }
        }

    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// --- DATA FETCHING ---
$shift_stmt = $db->prepare("
    SELECT sh.*, st.name, st.post 
    FROM staff_shift sh 
    JOIN staff st ON sh.staff_id = st.id 
    WHERE sh.shift_date >= ?::date AND sh.shift_date <= ?::date 
    ORDER BY sh.shift_date ASC, sh.shift_start ASC
");
$shift_stmt->execute([$today, $next7]);
$shifts = $shift_stmt->fetchAll(PDO::FETCH_ASSOC);

$requests = $db->query("SELECT r.*, s.name FROM shift_requests r LEFT JOIN staff s ON r.staff_user_id = s.user_id ORDER BY r.requested_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>シフト管理システム</title>
    <style>
        /* Design preserved as per your preference */
         *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins','Hiragino Sans',sans-serif;}
        body{display:flex;height:100vh;background:#f0f2f5;}
   /* サイドバー: ダッシュボードと統一 */
        .sidebar{
            width:240px; 
            background: linear-gradient(180deg, #ff4b2b, #ff416c);
            color:white; padding:25px; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar h2{margin-bottom:30px; font-size: 20px; text-align:center; letter-spacing: 1px;}
        .sidebar ul{list-style:none;}
        .sidebar ul li{margin:15px 0;}
        .sidebar ul li a{
            color:white; text-decoration:none; display:block; padding:12px; 
            border-radius:8px; transition:0.3s; background: rgba(255,255,255,0.1);
        }
        .sidebar ul li a:hover{background:rgba(255,255,255,0.2); transform: translateX(5px);}
        
        .main{flex:1; padding:40px; overflow-y:auto;}
        h1{color:#ff4b2b; margin-bottom:20px; font-size: 24px;}
        .card{background:white; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.05); margin-bottom:30px;}
        table{width:100%; border-collapse:collapse; margin-top:10px;}
        th, td{border:1px solid #eee; padding:12px; text-align:center;}
        th{background:#ff4b2b; color:white;}
        .mgr-box{background:#fff3f3; padding:15px; border-radius:5px; border:1px solid #ffcccc; margin-bottom:15px; display:flex; justify-content: space-between; align-items: center;}
        .btn-del{background:#f44336; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; font-weight: bold;}
        .manual-form label{display:block; margin-top:12px; font-weight:bold; color:#555;}
        .manual-form input, .manual-form select, .manual-form textarea{width:100%; padding:10px; margin-top:5px; border:1px solid #ccc; border-radius:5px;}
        .btn-save{background:#ff4b2b; color:white; border:none; padding:12px; border-radius:5px; cursor:pointer; width:100%; font-weight:bold; margin-top:20px; font-size:16px;}
        .target-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top:10px;}
        .badge{padding:4px 8px; border-radius:4px; font-size:12px; color:white;}
    </style>
</head>
<body>
<div class="sidebar">
        <h2>管理パネル</h2>
        <ul>
        <li><a href="menu_admin.php">🍔 メニュー変更</a></li>
        <li><a href="staff_management.php">👥 スタッフ管理</a></li>
        <li><a href="shift.php">📅 シフト管理</a></li>
        <li><a href="total_hr.php" style="background:rgba(255,255,255,0.3);">⏱ 総労働時間</a></li>
        </ul>
        <div style="text-align:center; margin-top:40px;">
        <a href="admin.php" style="background:#fff; color:#ff4b2b; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold; box-shadow: 0 4px 0 #ddd;">⬅ 戻る</a>
        </div>
    </div>

<div class="main">
    <h1>📅 週間シフトスケジュール</h1>
    <div class="card">
        <form id="bulkDeleteShiftForm" method="POST">
            <input type="hidden" name="action_type" value="bulk_delete_shifts">
            <div class="mgr-box">
                <div><strong>管理者認証:</strong> <input type="text" id="mgr_id_1" placeholder="ID" style="padding:5px; margin-left:10px;"></div>
                <button type="button" class="btn-del" onclick="confirmBulkDelete('bulkDeleteShiftForm', 'mgr_id_1', 'shift_ids[]')">削除</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this, 'shift_ids[]')"></th>
                        <th>日付</th><th>名前</th><th>役職</th><th>時間</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($shifts)): foreach($shifts as $sh): ?>
                    <tr>
                        <td><input type="checkbox" name="shift_ids[]" value="<?= $sh['id'] ?>"></td>
                        <td><?= htmlspecialchars($sh['shift_date']) ?></td>
                        <td><?= htmlspecialchars($sh['name']) ?></td>
                        <td><?= htmlspecialchars($sh['post']) ?></td>
                        <td>
                            <?= date('H:i', strtotime($sh['shift_start'])) ?> - <?= date('H:i', strtotime($sh['shift_end'])) ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5">現在予定されているシフトはありません。</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>

     <h1>🎯 特定日の目標 & 通知設定</h1>
    <div class="card" style="border-top: 5px solid #2196F3;">
        <form method="POST">
            <input type="hidden" name="action_type" value="update_targets">
            <div class="manual-form">
                <div style="display: flex; gap: 20px;">
                    <div style="flex: 1;">
                        <label>対象日付:</label>
                        <input type="date" name="target_date" value="<?= $tomorrow ?>" required>
                    </div>
                    <div style="flex: 1;">
                        <label>緊急度:</label>
                        <select name="urgency">
                            <option value="Normal">Normal (通常)</option>
                            <option value="Busy">Busy (混雑)</option>
                            <option value="Critical">Critical (緊急!)</option>
                        </select>
                    </div>
                </div>
                <label>通知メッセージ:</label>
                <input type="text" name="admin_notice" placeholder="例: 週末のイベントのため多めに募集します">
                <div class="target-grid">
                    <div><label>料理人必要数:</label><input type="number" name="m_cook_needed" value="3"></div>
                    <div><label>配達員必要数:</label><input type="number" name="m_driver_needed" value="2"></div>
                    <div><label>マネージャー必要数:</label><input type="number" name="m_manager_needed" value="1"></div>
                </div>
                <button type="submit" class="btn-save" style="background: #2196F3;">📢 目標を保存・更新する</button>
            </div>
        </form>
    </div>

    <h1>✅ シフト申請承認リスト</h1>
    <div class="card">
        <form id="bulkDeleteReqForm" method="POST">
            <input type="hidden" name="action_type" value="bulk_delete_requests">
            <div class="mgr-box">
                <div><strong>管理者認証:</strong> <input type="text" id="mgr_id_2" placeholder="ID" style="padding:5px; margin-left:10px;"></div>
                <button type="button" class="btn-del" onclick="confirmBulkDelete('bulkDeleteReqForm', 'mgr_id_2', 'request_ids[]')">一括削除</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="toggleAll(this, 'request_ids[]')"></th>
                        <th>名前</th><th>希望日</th><th>シフト</th><th>状態</th><th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($requests)): foreach ($requests as $req): ?>
                    <tr>
                        <td><input type="checkbox" name="request_ids[]" value="<?= $req['id'] ?>"></td>
                        <td><?= htmlspecialchars($req['name']) ?></td>
                        <td><?= htmlspecialchars($req['shift_date']) ?></td>
                        <td><?= htmlspecialchars($req['shift_type']) ?></td>
                        <td><?= htmlspecialchars($req['status']) ?></td>
                        <td>
                            <?php if($req['status'] === 'Pending'): ?>
                                <button type="submit" form="singleActionForm<?= $req['id'] ?>" name="req_action" value="approve" style="background:green; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">承認</button>
                                <button type="submit" form="singleActionForm<?= $req['id'] ?>" name="req_action" value="cancel" style="background:red; color:white; border:none; padding:4px 8px; border-radius:4px; cursor:pointer;">却下</button>
                            <?php else: ?>
                                <span style="color:gray;">完了</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="6">新しい申請はありません。</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        <?php foreach ($requests as $req): ?>
            <form id="singleActionForm<?= $req['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="request_id" value="<?= $req['id'] ?>"></form>
        <?php endforeach; ?>
    </div>

    <h1>🕒 手動シフト登録</h1>
    <div class="card manual-form">
        <form action="shift.php" method="POST">
            <input type="hidden" name="action" value="shift">
            <label>スタッフ ユーザーID:</label>
            <input type="text" name="shift_user_id" placeholder="スタッフIDを入力" required>
            <label>役職:</label>
            <select name="post" required>
                <option value="">--役職を選択--</option>
                <option value="マネージャー">マネージャー</option>
                <option value="料理人">料理人</option>
                <option value="配達員">配達員</option>
                <option value="カウンター">カウンター</option>
            </select>
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;"><label>開始日時:</label><input type="datetime-local" name="shift_start" required></div>
                <div style="flex: 1;"><label>終了日時:</label><input type="datetime-local" name="shift_end" required></div>
            </div>
            <label>備考 (オプション):</label>
            <textarea name="remarks" placeholder="備考を入力" rows="2"></textarea>
            <button type="submit" class="btn-save">➕ シフトを新規登録</button>
        </form>
    </div>
</div>

<script>
function toggleAll(source, name) {
    const checkboxes = document.getElementsByName(name);
    for(let i=0; i<checkboxes.length; i++) checkboxes[i].checked = source.checked;
}
function confirmBulkDelete(formId, mgrInputId, checkboxName) {
    const mgrId = document.getElementById(mgrInputId).value.trim();
    const checked = document.querySelectorAll(`input[name="${checkboxName}"]:checked`);
    if (!mgrId) { alert('管理者IDを入力してください'); return; }
    if (checked.length === 0) { alert('項目を選択してください'); return; }
    if (confirm(`選択した ${checked.length} 件を削除しますか？`)) document.getElementById(formId).submit();
}
</script>
</body>
</html>