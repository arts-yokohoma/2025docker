<?php
/**
 * User creation / edit form page
 * - Protected: Only admin and manager can access
 * - Fields: login (username), mail, phone, name, surname (name+surname on one line), password, role
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/db.php';

// Only admin and manager can access
requireRoles(['admin', 'manager']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
  session_destroy();
  header('Location: login.php');
  exit;
}

$message = '';
$error = '';
$success = false;
// Edit ID: from GET when opening edit form, or from POST when re-displaying after submit
$editId = (int)($_GET['edit'] ?? $_POST['edit_id'] ?? 0);

// Ensure users table has name, surname, phone (for existing DBs created before schema update)
$ensureColumns = function ($mysqli) {
  $table = 'users';
  $cols = ['name', 'surname', 'phone'];
  foreach ($cols as $col) {
    $stmt = $mysqli->prepare("
      SELECT 1 FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    if ($stmt) {
      $stmt->bind_param('ss', $table, $col);
      $stmt->execute();
      if (!$stmt->get_result()->fetch_assoc()) {
        $def = ($col === 'phone') ? "VARCHAR(50) NOT NULL DEFAULT ''" : "VARCHAR(100) NOT NULL DEFAULT ''";
        @$mysqli->query("ALTER TABLE users ADD COLUMN `" . $mysqli->real_escape_string($col) . "` " . $def);
      }
      $stmt->close();
    }
  }
};
$ensureColumns($mysqli);

// Role key to Japanese label mapping
$role_labels = [
  'admin' => '管理者',
  'manager' => 'マネージャー',
  'kitchen' => 'キッチン',
  'driver' => '配達',
];

// Load user for edit mode so the form shows existing data when you click "Edit"
$editUser = null;
$editUserRole = null;
if ($editId > 0) {
  // Try full columns first (name, surname, phone) - also get role name
  $st = $mysqli->prepare("SELECT u.id, u.username, u.email, COALESCE(u.name,'') AS name, COALESCE(u.surname,'') AS surname, COALESCE(u.phone,'') AS phone, u.role_id, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
  if ($st) {
    $st->bind_param('i', $editId);
    if ($st->execute()) {
      $res = $st->get_result();
      if ($res) {
        $editUser = $res->fetch_assoc();
        $editUserRole = $editUser['role_name'] ?? null;
      }
    }
    $st->close();
  }
  // Fallback: if columns might not exist yet, load without them
  if (!$editUser) {
    $st2 = $mysqli->prepare("SELECT u.id, u.username, u.email, u.role_id, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
    if ($st2) {
      $st2->bind_param('i', $editId);
      if ($st2->execute()) {
        $res2 = $st2->get_result();
        if ($res2 && ($row = $res2->fetch_assoc())) {
          $row['name'] = '';
          $row['surname'] = '';
          $row['phone'] = '';
          $editUser = $row;
          $editUserRole = $row['role_name'] ?? null;
        }
      }
      $st2->close();
    }
  }
}

// Load available roles - exclude admin UNLESS we're editing an admin user
$roles = [];
$roleFilter = ($editUserRole === 'admin') ? "" : "WHERE name != 'admin'";
$roleResult = $mysqli->query("SELECT id, name FROM roles $roleFilter ORDER BY id");
if ($roleResult) {
  while ($role = $roleResult->fetch_assoc()) {
    $roles[] = $role;
  }
  $roleResult->free();
} else {
  $error = 'ロールを読み込めません。roles テーブルがあるか確認し、<a href="../data/setup_roles.php">setup_roles.php</a> を実行してください。(' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8') . ')';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $surname = trim($_POST['surname'] ?? '');
  $password = trim($_POST['password'] ?? '');
  $password_confirm = trim($_POST['password_confirm'] ?? '');
  $role_id = (int)($_POST['role_id'] ?? 0);
  $isEdit = ($editId > 0);

  $requirePassword = !$isEdit;
  if (empty($username) || empty($email) || $role_id === 0) {
    $error = $role_id === 0 && empty($roles) ? 'ロールがありません。<a href="../data/setup_roles.php">setup_roles.php</a> を実行してください。' : 'ログイン・メール・役割は必須です。';
  } elseif ($requirePassword && (empty($password) || strlen($password) < 6)) {
    $error = 'パスワードは6文字以上である必要があります';
  } elseif ($requirePassword && $password !== $password_confirm) {
    $error = 'パスワードが一致しません';
  } elseif (!$requirePassword && $password !== '' && (strlen($password) < 6 || $password !== $password_confirm)) {
    $error = 'パスワードを変更する場合は6文字以上で、確認と一致させてください。';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = '有効なメールアドレスを入力してください';
  } elseif (strlen($username) < 3) {
    $error = 'ユーザー名は3文字以上である必要があります';
  } else {
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
        // Security: Prevent changing admin users' roles or downgrading them
        if ($isEdit && $editUserRole === 'admin') {
          // Get the new role name to verify it's still admin
          $newRoleCheck = $mysqli->prepare("SELECT name FROM roles WHERE id = ?");
          if ($newRoleCheck) {
            $newRoleCheck->bind_param("i", $role_id);
            $newRoleCheck->execute();
            $newRoleResult = $newRoleCheck->get_result();
            $newRoleData = $newRoleResult->fetch_assoc();
            $newRoleCheck->close();
            
            if ($newRoleData && $newRoleData['name'] !== 'admin') {
              $error = '管理者ユーザーのロールは変更できません。セキュリティ保護のため、管理者は管理者のままである必要があります。';
            }
          }
        }
        
        // Continue only if no error occurred
        if (empty($error)) {
        // Duplicate check (exclude current user in edit mode)
        $excludeId = $isEdit ? $editId : 0;
        $dupUser = $mysqli->prepare("SELECT 1 FROM users WHERE username = ? AND id != ? LIMIT 1");
        $dupUser->bind_param("si", $username, $excludeId);
        $dupUser->execute();
        $hasDupUser = $dupUser->get_result()->fetch_assoc();
        $dupUser->close();

        $dupEmail = $mysqli->prepare("SELECT 1 FROM users WHERE email = ? AND id != ? LIMIT 1");
        $dupEmail->bind_param("si", $email, $excludeId);
        $dupEmail->execute();
        $hasDupEmail = $dupEmail->get_result()->fetch_assoc();
        $dupEmail->close();

        if ($hasDupUser) {
          $error = 'このユーザー名は既に使用されています。別のユーザー名を入力してください。';
        } elseif ($hasDupEmail) {
          $error = 'このメールアドレスは既に使用されています。別のメールアドレスを入力してください。';
        } else {
          if ($isEdit) {
            if ($password !== '') {
              $password_hash = password_hash($password, PASSWORD_DEFAULT);
              $stmt = $mysqli->prepare("UPDATE users SET username=?, email=?, name=?, surname=?, phone=?, password=?, role_id=?, updated_at=NOW() WHERE id=?");
              $stmt->bind_param("ssssssii", $username, $email, $name, $surname, $phone, $password_hash, $role_id, $editId);
            } else {
              $stmt = $mysqli->prepare("UPDATE users SET username=?, email=?, name=?, surname=?, phone=?, role_id=?, updated_at=NOW() WHERE id=?");
              $stmt->bind_param("sssssii", $username, $email, $name, $surname, $phone, $role_id, $editId);
            }
            if ($stmt && $stmt->execute()) {
              $_SESSION['flash_success'] = 'ユーザー「' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '」を更新しました。';
              header('Location: user.php');
              exit;
            }
            if ($stmt) $stmt->close();
            $error = '更新に失敗しました: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
          } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (username, email, name, surname, phone, password, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            if (!$stmt) {
              $error = 'データベースエラー: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
            } else {
              $stmt->bind_param("ssssssi", $username, $email, $name, $surname, $phone, $password_hash, $role_id);
              if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'ユーザー「' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '」を作成しました。';
                header('Location: user.php');
                exit;
              } else {
                if ($mysqli->errno === 1146) {
                  $error = 'users テーブルが存在しません。<a href="../data/install_schema.php">install_schema.php</a> を実行してください。';
                } else {
                  $error = 'エラー: ' . htmlspecialchars($mysqli->error, ENT_QUOTES, 'UTF-8');
                }
              }
              $stmt->close();
            }
          }
        }
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
<title><?= $editId ? 'ユーザー編集' : 'ユーザー作成' ?></title>
<link rel="stylesheet" href="css/add_user.css">
</head>
<body>

<header class="admin-page-header">
  <img src="../assets/image/logo.png" alt="Pizza Mach" class="admin-page-logo">
  <span class="admin-page-title"><?= $editId ? 'ユーザー編集' : 'ユーザー作成' ?></span>
  <a href="user.php" class="admin-page-back">戻る</a>
  <form method="post" style="margin: 0;">
    <input type="hidden" name="action" value="logout">
    <button type="submit" class="admin-page-logout">ログアウト</button>
  </form>
</header>

<div class="wrap">

  <a href="user.php" class="back-link">← ユーザー一覧に戻る</a>

  <h1><?= $editId ? 'ユーザー編集' : 'ユーザー作成' ?></h1>
  <div class="sub"><?= $editId ? 'ユーザー情報を変更します。' : '新しいシステムユーザーを登録し、適切な権限を割り当てます。' ?></div>

  <?php if ($editId > 0 && !$editUser): ?>
    <div class="error-box" role="alert">
      <span class="error-box-icon" aria-hidden="true">⚠</span>
      <div class="error-box-text">指定されたユーザーが見つかりません。</div>
    </div>
    <p><a href="user.php" class="back-link">← ユーザー一覧に戻る</a></p>
  <?php elseif ($editId === 0 || $editUser): ?>
  <?php if ($error): ?>
    <div class="error-box" role="alert">
      <span class="error-box-icon" aria-hidden="true">⚠</span>
      <div class="error-box-text"><?= (strpos($error, '<a ') !== false ? nl2br(strip_tags($error, '<a>')) : nl2br(htmlspecialchars($error, ENT_QUOTES, 'UTF-8'))) ?></div>
      <?php if (strpos($error, 'setup_roles') !== false || strpos($error, 'install_schema') !== false): ?>
        <p class="error-box-hint">上記リンクをクリックしてセットアップを実行してください。</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php
    // Prefill: on edit (GET) use database values; on re-display after POST use submitted values
    $post = $_POST;
    $username = ($editUser && empty($post)) ? $editUser['username'] : ($post['username'] ?? $editUser['username'] ?? '');
    $email = ($editUser && empty($post)) ? $editUser['email'] : ($post['email'] ?? $editUser['email'] ?? '');
    $phone = ($editUser && empty($post)) ? ($editUser['phone'] ?? '') : ($post['phone'] ?? $editUser['phone'] ?? '');
    $name = ($editUser && empty($post)) ? ($editUser['name'] ?? '') : ($post['name'] ?? $editUser['name'] ?? '');
    $surname = ($editUser && empty($post)) ? ($editUser['surname'] ?? '') : ($post['surname'] ?? $editUser['surname'] ?? '');
    $sel_role = ($editUser && empty($post)) ? $editUser['role_id'] : ($post['role_id'] ?? $editUser['role_id'] ?? '');
  ?>
  <form method="POST" class="user-form">
    <?php if ($editId > 0): ?><input type="hidden" name="edit_id" value="<?= (int)$editId ?>"><?php endif; ?>
    <div class="row">
      <div class="col">
        <label>ログイン (ユーザー名)</label>
        <input type="text" name="username" placeholder="例: tanaka01" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col">
        <label>メール</label>
        <input type="email" name="email" placeholder="例: tanaka@example.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label>電話番号</label>
        <input type="text" name="phone" placeholder="例: 090-1234-5678" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="col col-name-surname">
        <label>名前・姓 (1行)</label>
        <div class="name-surname-row">
          <input type="text" name="name" placeholder="名前" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">
          <input type="text" name="surname" placeholder="姓" value="<?= htmlspecialchars($surname, ENT_QUOTES, 'UTF-8') ?>">
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col col-pw">
        <label>パスワード<?= $editId ? ' <span class="optional">（変更時のみ入力）</span>' : '' ?></label>
        <div class="pw-wrap">
          <input type="password" name="password" id="pw" placeholder="<?= $editId ? '空欄で現状維持' : '6文字以上' ?>">
          <span class="pw-eye" onclick="document.getElementById('pw').type=document.getElementById('pw').type=='password'?'text':'password'">👁</span>
        </div>
      </div>
      <div class="col col-pw">
        <label>パスワード確認</label>
        <div class="pw-wrap">
          <input type="password" name="password_confirm" id="pw-confirm" placeholder="確認用">
          <span class="pw-eye" onclick="document.getElementById('pw-confirm').type=document.getElementById('pw-confirm').type=='password'?'text':'password'">👁</span>
        </div>
      </div>
    </div>

    <div style="margin-top:20px">
      <label>役割 (Role)</label>
      <?php if (empty($roles) && !$error): ?>
        <p class="error-message">ロールがありません。<a href="../data/setup_roles.php">setup_roles.php</a> を実行してからユーザーを作成してください。</p>
      <?php endif; ?>
      <div class="roles">
        <?php foreach ($roles as $role): ?>
          <div class="role">
            <input type="radio" name="role_id" value="<?= htmlspecialchars($role['id'], ENT_QUOTES, 'UTF-8') ?>"
              <?= (int)$sel_role === (int)$role['id'] ? 'checked' : '' ?>>
            <label>
              <?= isset($role_labels[$role['name']]) ? $role_labels[$role['name']] : htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="btns">
      <a href="user.php" class="btn btn-cancel">キャンセル</a>
      <button type="submit" class="btn btn-primary"><?= $editId ? '更新' : '追加' ?></button>
    </div>
  </form>
  <?php endif; ?>
</div>

</body>
</html>