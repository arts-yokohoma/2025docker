<?php
/**
 * User creation form page
 * - Protected: Only admin and manager can access
 * - User creation functionality is DISABLED (use user.php instead)
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

// Only admin and manager can access
requireRoles(['admin', 'manager']);

$message = '';
$error = '';
$success = false;

// Role key to Japanese label mapping
$role_labels = [
  'admin' => '管理者',
  'manager' => 'マネージャー',
  'kitchen' => 'キッチン',
  'delivery' => '配達',
];

// Load available roles
$roles = [];
$roleResult = $mysqli->query("SELECT id, name FROM roles ORDER BY id");
if ($roleResult) {
  while ($role = $roleResult->fetch_assoc()) {
    $roles[] = $role;
  }
  $roleResult->free();
} else {
  $error = 'ロールを読み込めません: ' . $mysqli->error;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $password_confirm = trim($_POST['password_confirm'] ?? '');
  $role_id = (int)($_POST['role_id'] ?? 0);

  if (empty($username) || empty($email) || empty($password) || $role_id === 0) {
    $error = 'すべてのフィールドを入力してください';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = '有効なメールアドレスを入力してください';
  } elseif (strlen($password) < 6) {
    $error = 'パスワードは6文字以上である必要があります';
  } elseif ($password !== $password_confirm) {
    $error = 'パスワードが一致しません';
  } elseif (strlen($username) < 3) {
    $error = 'ユーザー名は3文字以上である必要があります';
  } else {
    // Verify role exists
    $roleCheck = $mysqli->prepare("SELECT id FROM roles WHERE id = ?");
    if (!$roleCheck) {
      $error = 'データベースエラー: ' . $mysqli->error;
    } else {
      $roleCheck->bind_param("i", $role_id);
      $roleCheck->execute();
      $roleCheckResult = $roleCheck->get_result();
      if (!$roleCheckResult->fetch_assoc()) {
        $error = 'ロールが見つかりません';
      } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        if (!$stmt) {
          $error = 'データベースエラー: ' . $mysqli->error;
        } else {
          $stmt->bind_param("sssi", $username, $email, $password_hash, $role_id);
          if ($stmt->execute()) {
            $success = true;
            $message = 'ユーザー「' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '」を作成しました';
            $_POST = [];
          } else {
            if ($mysqli->errno === 1062) {
              $error = 'このユーザー名またはメールアドレスは既に使用されています';
            } else {
              $error = 'エラー: ' . $mysqli->error;
            }
          }
          $stmt->close();
        }
      }
      $roleCheck->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ユーザー作成</title>
<link rel="stylesheet" href="css/add_user.css">
</head>
<body>

<header style="display: flex; justify-content: space-between; align-items: center; padding: 12px 20px; background: #fff; border-bottom: 1px solid #e1e8ed;">
  <div style="display: flex; align-items: center; gap: 12px;">
    <img src="../assets/image/logo.png" alt="Pizza Mach" style="height: 40px; width: auto;">
    <span style="font-size: 1.25rem; font-weight: 600;">Pizza Mach</span>
  </div>
  <a href="user.php" class="back-link">← 戻る</a>
</header>

<div class="wrap">

  <!-- Back link (in content) -->
  <a href="user.php" class="back-link">← 戻る</a>

  <h1>ユーザー作成</h1>
  <div class="sub">新しいシステムユーザーを登録し、適切な権限を割り当てます。</div>

  <?php if ($error): ?>
    <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="success-message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <form method="POST" class="user-form">
    <div class="row">
      <div class="col">
        <label>ユーザー名</label>
        <input type="text" name="username" placeholder="例:tanaka01" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div class="col">
        <label>メールアドレス</label>
        <input type="email" name="email" placeholder="例:tanaka@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      </div>
    </div>

    <div class="row">
      <div class="col col-pw">
        <label>パスワード</label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pw" placeholder="6文字以上">
          <span class="pw-eye" onclick="document.getElementById('pw').type=document.getElementById('pw').type=='password'?'text':'password'">👁</span>
        </div>
      </div>

      <div class="col col-pw">
        <label>パスワード確認</label>
        <div class="pw-wrap">
          <input type="password" name="password_confirm" id="pw-confirm" placeholder="確認用パスワード">
          <span class="pw-eye" onclick="document.getElementById('pw-confirm').type=document.getElementById('pw-confirm').type=='password'?'text':'password'">👁</span>
        </div>
      </div>
    </div>

    <div style="margin-top:20px">
      <label>役割 (Role)</label>
      <div class="roles">
        <?php foreach ($roles as $role): ?>
          <div class="role">
            <input type="radio" name="role_id" value="<?php echo htmlspecialchars($role['id'], ENT_QUOTES, 'UTF-8'); ?>" 
              <?php echo isset($_POST['role_id']) && (int)$_POST['role_id'] === (int)$role['id'] ? 'checked' : ''; ?>>
            <label>
              <?php
                $key = $role['name'];
                echo isset($role_labels[$key]) ? $role_labels[$key] : htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
              ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="btns">
      <a href="user.php" class="btn btn-cancel">キャンセル</a>
      <button type="submit" class="btn btn-primary">追加</button>
    </div>
  </form>
</div>

</body>
</html>