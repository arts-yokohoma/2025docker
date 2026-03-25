<?php
// admin/login_logic.php
session_start();

$error = '';

if (isset($_POST['login'])) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    // Admin Username & Password (လိုအပ်ရင် Database နဲ့ ချိတ်နိုင်ပါတယ်)
    if ($user === 'admin' && $pass === '1234') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    } else {
        $error = "Username or Password Incorrect";
    }
}
?>