<?php
session_start();

require __DIR__ . '/../db/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

$stmt = $pdo->prepare(
    "SELECT id, username, password, role FROM users WHERE username = ?"
);
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'] ?? 'staff'; // Default to 'staff' if role not set

    header("Location: dashboard.php");
    exit;
}

header("Location: login.php?error=invalid");
exit;
