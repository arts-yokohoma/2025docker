<?php
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

require_once __DIR__ . '/db_config.php';

$errors = [];
$successMessage = '';

if (empty($_SESSION['admin_create_csrf'])) {
    $_SESSION['admin_create_csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($csrf === '' || !hash_equals((string)$_SESSION['admin_create_csrf'], $csrf)) {
        $errors[] = '送信の検証に失敗しました。もう一度お試しください。';
    }

    $newUsername = trim((string)($_POST['new_username'] ?? ''));
    $newPassword = (string)($_POST['new_password'] ?? '');
    $newPasswordConfirm = (string)($_POST['new_password_confirm'] ?? '');
    $currentAdminPassword = (string)($_POST['current_admin_password'] ?? '');

    if ($newUsername === '') {
        $errors[] = '新しいユーザー名を入力してください。';
    }
    if ($newPassword === '' || $newPasswordConfirm === '') {
        $errors[] = '新しいパスワードを入力してください。';
    } elseif ($newPassword !== $newPasswordConfirm) {
        $errors[] = '新しいパスワードが一致しません。';
    }
    if ($currentAdminPassword === '') {
        $errors[] = '現在の管理者パスワードを入力してください。';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $_SESSION['admin_id']]);
            $currentAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentAdmin) {
                $errors[] = '現在の管理者情報を取得できませんでした。もう一度ログインしてください。';
            } else {
                $storedHash = (string)($currentAdmin['password_hash'] ?? '');
                $ok = false;
                if ($storedHash !== '') {
                    $ok = password_verify($currentAdminPassword, $storedHash) || hash_equals($currentAdminPassword, $storedHash);
                }

                if (!$ok) {
                    $errors[] = '現在の管理者パスワードが正しくありません。';
                }
            }

            if (empty($errors)) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);

                $insert = $pdo->prepare(
                    "INSERT INTO admin (username, password_hash, role, created_at) VALUES (:u, :h, 'admin', NOW())"
                );

                try {
                    $insert->execute([':u' => $newUsername, ':h' => $hash]);
                    $_SESSION['flash_success'] = '新しい管理者ユーザーを作成しました。';
                    // rotate token
                    $_SESSION['admin_create_csrf'] = bin2hex(random_bytes(16));
                    header('Location: admin_panel.php');
                    exit;
                } catch (PDOException $e) {
                    $code = $e->getCode();
                    if (is_string($code) && $code === '23505') {
                        $errors[] = 'そのユーザー名は既に使われています。';
                    } else {
                        throw $e;
                    }
                }
            }
        } catch (Exception $e) {
            $errors[] = '作成に失敗しました。（サーバーエラー）';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>管理者ユーザー作成</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="img/nav_bar_logo.png" height="60" class="me-2" alt="Team 5 logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-controls="navMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link btn btn-filled-custom rounded-pill px-4 m-2" href="admin_panel.php">管理メニュー</a>
                    </li>
                    <li class="nav-item d-flex align-items-center me-2">
                        <span class="nav-link fw-bold">
                            <?php echo htmlspecialchars((string)($_SESSION['admin_username'] ?? '')); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="container_def">
            <h3 class="text-center fw-bold mb-4">管理者ユーザー作成</h3>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success fs-4" role="alert"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fs-5" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <div><?php echo htmlspecialchars((string)$err); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['admin_create_csrf']); ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold fs-4">新しいユーザー名</label>
                    <input type="text" name="new_username" class="form-control fs-4" value="<?php echo htmlspecialchars((string)($_POST['new_username'] ?? '')); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold fs-4">新しいパスワード</label>
                    <input type="password" name="new_password" class="form-control fs-4" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold fs-4">新しいパスワード（確認）</label>
                    <input type="password" name="new_password_confirm" class="form-control fs-4" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold fs-4">現在の管理者パスワード（確認用）</label>
                    <input type="password" name="current_admin_password" class="form-control fs-4" required>
                </div>

                <div class="d-flex justify-content-center gap-3">
                    <a href="admin_panel.php" class="btn btn-outline-secondary fs-4">戻る</a>
                    <button type="submit" class="btn btn-success fs-4 fw-bold">作成</button>
                </div>
            </form>
        </div>
    </div>
    <footer class="site-footer mt-5">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <a class="navbar-brand d-flex align-items-center" href="index.php">
                        <img src="img/nav_bar_logo.png" height="40" class="me-2" alt="Team 5 logo">
                    </a>
                    <small class="d-block">&copy; <span id="year"></span> CYBER EDGE. All rights reserved.</small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0 footer-links">
                        <li class="list-inline-item"><a href="/index.php">ホーム</a></li>
                        <li class="list-inline-item"><a href="contact.php">お問い合わせ</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('year').textContent = new Date().getFullYear();
    </script>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>