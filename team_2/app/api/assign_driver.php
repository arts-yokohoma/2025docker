<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);
$stmt = $db->prepare("UPDATE orders SET driver_name = :driver, status = 'cooking' WHERE id = :id");
$stmt->execute([':driver' => $data['driver_name'], ':id' => $data['id']]);
echo json_encode(["message" => "Driver assigned successfully"]);
?>