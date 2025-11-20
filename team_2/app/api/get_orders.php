<?php
include 'db.php';
$result = $db->query("SELECT * FROM orders ORDER BY created_at DESC");
$orders = $result->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($orders);
?>