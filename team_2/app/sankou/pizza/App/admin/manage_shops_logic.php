<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
include '../database/db_conn.php';

$edit_mode = false;
$edit_data = ['shop_name' => '', 'latitude' => '', 'longitude' => '', 'website_url' => '', 'id' => ''];

// (၁) အသစ်ထည့်ခြင်း (ADD)
if (isset($_POST['add_shop'])) {
    $name = $_POST['name'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $url = $_POST['url'];

    $stmt = $conn->prepare("INSERT INTO partner_shops (shop_name, latitude, longitude, website_url) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdds", $name, $lat, $lng, $url);
    $stmt->execute();
    header("Location: manage_shops.php"); exit();
}

// (၂) ပြင်ဆင်ခြင်း (UPDATE)
if (isset($_POST['update_shop'])) {
    $id = $_POST['shop_id'];
    $name = $_POST['name'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $url = $_POST['url'];

    $stmt = $conn->prepare("UPDATE partner_shops SET shop_name=?, latitude=?, longitude=?, website_url=? WHERE id=?");
    $stmt->bind_param("sddsi", $name, $lat, $lng, $url, $id);
    $stmt->execute();
    header("Location: manage_shops.php"); exit(); // Update ပြီးရင် မူလနေရာပြန်သွားမယ်
}

// (၃) ဖျက်ခြင်း (DELETE)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM partner_shops WHERE id=$id");
    header("Location: manage_shops.php"); exit();
}

// (၄) ပြင်ရန် ဒေတာလှမ်းယူခြင်း (FETCH FOR EDIT)
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $id = intval($_GET['edit']);
    $res = $conn->query("SELECT * FROM partner_shops WHERE id=$id");
    $edit_data = $res->fetch_assoc();
}
?>