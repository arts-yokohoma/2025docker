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

// NOTE: User creation functionality is disabled on this page
// Use user.php for actual user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = 'このページではユーザー作成は無効です。user.php から作成してください。';
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

<div class="wrap">

  <!-- Back link -->
  <a href="user.php" class="back-link">← 戻る</a>

  <h1>ユーザー作成</h1>
  <div class="sub">新しいシステムユーザーを登録し、適切な権限を割り当てます。</div>

  <?php if ($error): ?>
    <div class="error-message"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <?php if ($message): ?>
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
            <label><?php echo htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8'); ?></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="btns">
      <a href="user.php" class="btn btn-cancel">キャンセル</a>
      <button type="submit" class="btn btn-primary" disabled title="このページでは作成不可。user.php を使用してください。">追加 (無効)</button>
    </div>
  </form>
</div>

</body>
</html>