<?php
include '../../api/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_amount = intval($_POST['driver_amount']);
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'total_drivers'");
    $stmt->execute([$driver_amount]);
    echo "<p>Driver amount updated to " . htmlspecialchars($driver_amount) . ".</p>";
    echo "<a href='admin.php'>Back to Admin Panel</a>";
}?>

