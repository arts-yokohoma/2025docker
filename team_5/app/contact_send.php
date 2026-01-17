<?php
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '') {
    $errors[] = 'name';
}
if ($phone === '') {
    $errors[] = 'phone';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'email';
}
if ($message === '') {
    $errors[] = 'message';
}

if (!empty($errors)) {
    // Provide a simple error code; frontend can show messages based on this
    header('Location: contact.php?error=1');
    exit;
}

try {
    $sql = "INSERT INTO contacts (name, phone, email, message, created_at) VALUES (:name, :phone, :email, :message, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':message' => $message,
    ]);

    header('Location: contact.php?success=1');
    exit;
} catch (PDOException $e) {
    // PostgreSQL unique_violation SQLSTATE is 23505
    if ($e->getCode() === '23505') {
        header('Location: contact.php?error=duplicate');
        exit;
    }
    // In production, log $e->getMessage() to a secure error log.
    header('Location: contact.php?error=1');
    exit;
}
