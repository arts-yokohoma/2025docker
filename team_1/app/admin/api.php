<?php
require_once __DIR__ . '/auth.php';
requireRoles(['admin', 'manager', 'driver', 'kitchen']); // Require specific roles

header('Content-Type: application/json');

$dataFile = __DIR__ . '/data/orders.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!file_exists($dataFile)) {
        http_response_code(404);
        echo json_encode(['error' => 'Orders file not found']);
        exit;
    }
    
    $orders = json_decode(file_get_contents($dataFile), true);
    
    // Update order status
    if (isset($input['action']) && $input['action'] === 'update_status') {
        $orderId = $input['id'] ?? null;
        $newStatus = $input['status'] ?? null;
        
        if (!$orderId || !$newStatus) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id or status']);
            exit;
        }
        
        $found = false;
        foreach ($orders as &$order) {
            if ($order['id'] == $orderId) {
                $order['status'] = $newStatus;
                $order['date'] = date('Y-m-d H:i');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        
        file_put_contents($dataFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => 'Order updated']);
    }
    // Delete order
    elseif (isset($input['action']) && $input['action'] === 'delete') {
        $orderId = $input['id'] ?? null;
        
        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id']);
            exit;
        }
        
        $orders = array_filter($orders, fn($order) => $order['id'] != $orderId);
        $orders = array_values($orders);
        
        file_put_contents($dataFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true, 'message' => 'Order deleted']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
