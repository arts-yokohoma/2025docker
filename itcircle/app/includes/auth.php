<?php
session_start();


if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin/login.php");
    exit();
}


require_once 'config.php'; 
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, is_admin FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !$user['is_admin']) {
    session_destroy();
    header("Location: ../admin/login.php");
    exit();
}
?>