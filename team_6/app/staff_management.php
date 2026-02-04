<?php
include 'db/connect.php';
session_start();

// --- Logic: Handle Staff Registration & ID Generation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'staff') {
    $name = trim($_POST['name'] ?? '');
    $post = $_POST['post'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $mobile = trim($_POST['mobile'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name && $dob && $mobile && $email && $post && $password) {
        try {
            // 1. Generate unique user_id (First 2 letters of name + 4 random digits)
            $nameLetters = strtoupper(substr(preg_replace("/[^a-zA-Z]/", "", $name), 0, 2));
            if(empty($nameLetters)) $nameLetters = "ST"; 
            
            do {
                $randNum = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $generatedId = $nameLetters . $randNum;

                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM staff WHERE user_id = ?");
                $stmtCheck->execute([$generatedId]);
                $exists = $stmtCheck->fetchColumn();
            } while ($exists > 0);

            // 2. Hash Password
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);

            // 3. Insert into DB
            $stmt = $db->prepare("INSERT INTO staff (user_id, name, dob, mobile, email, post, password) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$generatedId, $name, $dob, $mobile, $email, $post, $hashedPass]);
            
            header("Location: staff_management.php?new_id=" . $generatedId);
            exit;
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// --- Logic: Get Staff List ---
$stmt = $db->query("SELECT id, user_id, name, post, mobile, email FROM staff ORDER BY id DESC");
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>スタッフ管理 - Staff Management</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins','Hiragino Sans',sans-serif;}
        body{display:flex;height:100vh;background:#f0f2f5;}
        
        .sidebar{
            width:240px; 
            background: linear-gradient(180deg, #ff4b2b, #ff416c);
            color:white; padding:25px; box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar h2{margin-bottom:30px; font-size: 20px; text-align:center;}
        .sidebar ul{list-style:none;}
        .sidebar ul li{margin:15px 0;}
        .sidebar ul li a{
            color:white; text-decoration:none; display:block; padding:12px; 
            border-radius:8px; transition:0.3s; background: rgba(255,255,255,0.1);
        }
        .sidebar ul li a:hover{background:rgba(255,255,255,0.2); transform: translateX(5px);}

        .main{flex:1; padding:40px; overflow-y:auto;}
        h1, h3{color:#ff4b2b; margin-bottom:20px;}
        
        .success-msg {
            background: #d4edda; color: #155724; padding: 15px; 
            border-radius: 8px; border-left: 5px solid #28a745; margin-bottom: 20px;
        }

        form.reg-form, .list-container{background:white; padding:20px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); margin-bottom:30px;}
        input, select{width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:5px;}
        
        table{width:100%; border-collapse:collapse; background:white;}
        th,td{border:1px solid #ddd; padding:12px; text-align:center;}
        th{background:#ff4b2b; color:white;}
        
        .btn-reg{background:#ff4b2b; color:white; border:none; padding:12px 20px; border-radius:5px; cursor:pointer; width:100%; font-weight:bold;}
        .edit-btn{background:#2196F3; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;}
        .delete-btn{background:#f44336; color:white; border:none; padding:10px 20px; border-radius:5px; cursor:pointer;}
    </style>
</head>
<body>
<div class="sidebar">
    <h2>管理パネル</h2>
    <ul>
      <li><a href="menu_admin.php">🍔 メニュー変更</a></li>
      <li><a href="staff_management.php">👥 スタッフ管理</a></li>
      <li><a href="shift.php">📅 シフト管理</a></li>
      <li><a href="total_hr.php">⏱ 総労働時間</a></li>
    </ul>
    <div style="text-align:center; margin-top:40px;">
      <a href="admin.php" style="background:#fff; color:#ff4b2b; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold;">⬅ 戻る</a>
    </div>
</div>

<div class="main">
    <h1>👥 スタッフ管理</h1>

    <?php if(isset($_GET['new_id'])): ?>
        <div class="success-msg">
            🎉 登録完了！ ユーザーID: <strong><?= htmlspecialchars($_GET['new_id']) ?></strong><br>
            <small>このIDと設定したパスワードをスタッフに伝えてください。</small>
        </div>
    <?php endif; ?>

    <form method="POST" class="reg-form">
        <h3>新規スタッフ登録</h3>
        <input type="hidden" name="action" value="staff">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <input type="text" name="name" placeholder="氏名 (例: Tanaka Taro)" required>
            <select name="post" required>
                <option value="">--役職を選択--</option>
                <option value="manager">マネージャー</option>
                <option value="cook">料理人</option>
                <option value="driver">配達員</option>
            </select>
            <input type="date" name="dob" required>
            <input type="tel" name="mobile" placeholder="携帯番号" required>
            <input type="email" name="email" placeholder="メールアドレス" required>
            <input type="password" name="password" placeholder="ログインパスワード" required>
        </div>
        <button type="submit" class="btn-reg">➕ スタッフを追加してIDを発行</button>
    </form>

    <div class="list-container">
        <h3>スタッフ一覧</h3>
        <div style="margin-bottom: 15px;">
            <label><strong>管理者ID確認:</strong></label>
            <input type="text" id="manager_id" placeholder="管理者IDを入力">
        </div>

        <form id="actionForm" method="POST" action="action_handler.php">
            <input type="hidden" name="staff_id" id="selectedStaffId">
            <input type="hidden" name="manager_id" id="hiddenManagerId">
            <input type="hidden" name="action_type" id="actionType">

            <table>
                <tr>
                    <th>選択</th>
                    <th>ユーザーID</th>
                    <th>名前</th>
                    <th>役職</th>
                    <th>携帯</th>
                </tr>
                <?php foreach($staff as $st): ?>
                <tr>
                    <td><input type="radio" name="staff_select" value="<?= $st['id'] ?>"></td>
                    <td style="font-weight:bold; color:#ff4b2b;"><?= htmlspecialchars($st['user_id']) ?></td>
                    <td><?= htmlspecialchars($st['name']) ?></td>
                    <td><?= htmlspecialchars($st['post']) ?></td>
                    <td><?= htmlspecialchars($st['mobile']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <div style="margin-top:20px; display:flex; gap:10px;">
                <button type="button" class="edit-btn" onclick="submitAction('edit_staff')">編集</button>
                <button type="button" class="delete-btn" onclick="submitAction('delete_staff')">削除</button>
            </div>
        </form>
    </div>
</div>

<script>
function submitAction(type) {
    const selected = document.querySelector('input[name="staff_select"]:checked');
    const manager = document.getElementById('manager_id').value.trim();
    if (!manager) { alert('管理者IDが必要です'); return; }
    if (!selected) { alert('スタッフを選択してください'); return; }

    document.getElementById('selectedStaffId').value = selected.value;
    document.getElementById('hiddenManagerId').value = manager;
    document.getElementById('actionType').value = type;
    document.getElementById('actionForm').submit();
}
</script>
</body>
</html>