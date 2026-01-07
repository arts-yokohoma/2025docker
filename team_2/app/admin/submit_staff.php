<?php
include '../../api/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_amount = intval($_POST['staff_amount']);
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'total_kitchen_staff'");
    $stmt->execute([$staff_amount]);
    echo "<p>Kitchen staff amount updated to " . htmlspecialchars($staff_amount) . ".</p>";
    echo "<a href='admin.php'>Back to Admin Panel</a>";
}