<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);
$stmt = $db->prepare("UPDATE orders SET status = 'delivering' WHERE id = :id");
$stmt->execute([':id' => $data['id']]);
echo json_encode(["message" => "Delivery started"]);
?>