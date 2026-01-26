<?php
// /c:/Users/Kusal Isiwara/Documents/GitHub/2025docker/team_5/app/admin_login_process.php
session_start();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_login.php');
    exit;
}

// Basic input validation
$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    header('Location: admin_login.php?error=empty');
    exit;
}

// OPTIONAL: CSRF check if login form sets a token
if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: admin_login.php?error=csrf');
        exit;
    }
}

// Use the project's DB config (Postgres) and the existing `admin` table
try {
    require_once __DIR__ . '/db_config.php';

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admin WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $admin = $stmt->fetch();

    if ($admin) {
        if (password_verify($password, $admin['password_hash']) || hash_equals($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            unset($_SESSION['csrf_token']);
            header('Location: admin_panel.php');
            exit;
        }
    }

    // Invalid credentials
    header('Location: admin_login.php?error=invalid');
    exit;
} catch (Exception $e) {
    // In production log $e->getMessage()
    header('Location: admin_login.php?error=server');
    exit;
}
