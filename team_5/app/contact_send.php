<?php
require_once __DIR__ . '/db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$inquiryMethod = trim($_POST['inquiry_method'] ?? '');
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

if ($inquiryMethod !== 'email' && $inquiryMethod !== 'phone') {
    $inquiryMethod = 'email';
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
    // Table in repo is named `customer` (see create_contacts.sql)
    $sql = "INSERT INTO customer (phone, name, email, inquiry_method, inquiry_status, message) VALUES (:phone, :name, :email, :inquiry_method, :inquiry_status, :message)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':phone' => $phone,
        ':name' => $name,
        ':email' => $email,
        ':inquiry_method' => $inquiryMethod,
        ':inquiry_status' => '未対応',
        ':message' => $message,
    ]);

    header('Location: contact_success.php');
    exit;
} catch (PDOException $e) {
    // PostgreSQL unique_violation SQLSTATE is 23505
    if ($e->getCode() === '23505') {
        header('Location: contact.php?error=duplicate');
        exit;
    }
    header('Location: contact.php?error=1');
    exit;
}
