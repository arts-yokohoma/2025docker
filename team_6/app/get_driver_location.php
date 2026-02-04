<?php
include 'db/connect.php';
header('Content-Type: application/json');

// Get the order ID from the URL
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : 0;

// SQL query to get the driver's current position
$stmt = $db->prepare("SELECT s.current_lat, s.current_lng 
                    FROM orders o 
                    JOIN staff s ON o.delivery_person_id = s.id 
                    WHERE o.id = ?");
$stmt->execute([$order_id]);
$location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    // Return error if no driver is assigned to this order yet
    echo json_encode(['error' => 'No driver assigned or location unavailable']);
} else {
    // Return the latitude and longitude to the map
    echo json_encode($location);
}
?>