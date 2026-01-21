<?php
include '../db_conn.php';
$result = $db->query("SELECT * FROM orders ORDER BY created_at DESC");
echo json_encode($orders);
?>