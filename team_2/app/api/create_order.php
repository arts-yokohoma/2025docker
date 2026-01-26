<?php
include 'db.php';
$data = json_decode(file_get_contents("php://input"), true);
$stmt = $db->prepare("INSERT INTO orders (name, phone, address, size, time)
                      VALUES (:name, :phone, :address, :size, :time)");
$stmt->execute([
    ':name' => $data['name'],
    ':phone' => $data['phone'],
    ':address' => $data['address'],
    ':size' => $data['size'],
    ':time' => $data['time']
]);
echo json_encode(["message" => "Order created successfully"]);
?>