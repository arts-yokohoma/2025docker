<?php
require_once __DIR__ . '/auth.php';
// Shifts: admin, manager (RW), kitchen, delivery (read-only)
requireRoles(['admin', 'manager', 'kitchen', 'delivery']);

$userRole = $_SESSION['admin_role'] ?? 'user';
$canEdit = in_array($userRole, ['admin', 'manager']); // Only admin/manager can edit

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>シフトページ</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/shift.css">
</head>

<body>
<div class="box">
  <div class="header-section">
    <h1>シフトページ</h1>
    <form method="post" style="display: inline;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="logout-btn">ログアウト</button>
    </form>
  </div>

  <!-- ===== FORM ===== -->
  <div class="form">
    <input id="name" placeholder="名前を入力">
    <select id="role">
      <option>キッチン</option>
      <option>ドライバー</option>
    </select>
    <select id="time">
      <option>9:00-15:00</option>
      <option>15:00-23:00</option>
    </select>
    <select id="day">
      <option value="0">月</option>
  <option value="1">火</option>
  <option value="2">水</option>
  <option value="3">木</option>
  <option value="4">金</option>
  <option value="5">土</option>
  <option value="6">日</option>
</select>
    <button onclick="addShift()">シフトを提出</button>
  </div>

  <!-- ===== TABLE ===== -->
   <h2>週間のスケージュール</h2>
  <table>
<tr>
  <th>時間</th><th>役割</th>
  <th>月</th><th>火</th><th>水</th><th>木</th><th>金</th><th>土</th><th>日</th>
</tr>

<tr>
  <td class="time" rowspan="2">9:00-15:00</td>
  <th>キッチン</th>
  <td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td>
</tr>
<tr>
  <th>ドライバー</th>
  <td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td>
</tr>

<tr>
  <td class="time" rowspan="2">15:00-23:00</td>
  <th>キッチン</th>
  <td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td>
</tr>
<tr>
  <th>ドライバー</th>
  <td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td><td class="cell"></td>
</tr>
</table>

</div>

<script>
/* ===== row mapping ===== */
const rowMap = {
  "9:00-15:00キッチン": 1,
  "9:00-15:00ドライバー": 2,
  "15:00-23:00キッチン": 3,
  "15:00-23:00ドライバー": 4
};

/* ===== fill 空 ===== */
document.querySelectorAll(".cell").forEach(cell => {
  cell.innerHTML = '<span class="empty">空</span>';
});

/* ===== add shift ===== */
function addShift(){
  const name = document.getElementById("name").value.trim();
  const role = document.getElementById("role").value;
  const time = document.getElementById("time").value;
  const dayIndex = Number(document.getElementById("day").value); // 0~6

  if(!name){
    alert("名前を入力してください");
    return;
  }

  const table = document.querySelector(".box table");
  const rowIndex = rowMap[time + role];
  const row = table.rows[rowIndex];

  const cells = row.querySelectorAll(".cell");
  const cell = cells[dayIndex];

  if(!cell){
    alert("セル取得エラー");
    return;
  }

  if(!cell.querySelector(".empty")){
    alert("そのシフトは既に埋まっています");
    return;
  }

  cell.textContent = name;
  cell.onclick = () => removeShift(cell);
  document.getElementById("name").value = "";
}
</script>


</body>
</html>
